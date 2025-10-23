<?php
/**
 * Database Model - Pure Data Layer (MVC Compliant)
 * 
 * This file contains ONLY database operations and data retrieval.
 * NO HTML rendering or business logic should be in this file.
 * 
 * All database credentials are externalized to config.php
 */

// Load external configuration
require_once __DIR__ . '/config.php';

// Database connection using external configuration
$dbConfig = DB_CONFIG;
$connection = mysqli_connect(
    $dbConfig['server'], 
    $dbConfig['username'], 
    $dbConfig['password'], 
    $dbConfig['database']
);

if (mysqli_connect_errno()) {
    die("Database connection failed: " .
        mysqli_connect_error() .
        "(" . mysqli_connect_errno() . ")"
    );
}

// Set charset
mysqli_set_charset($connection, $dbConfig['charset']);

//display
//fetch all records from a table - pure data retrieval
function fetch($table, $conditions = '', $orderBy = '', $limit = '') {
    global $connection;
    
    $sql = "SELECT * FROM $table";
    
    if (!empty($conditions)) {
        $sql .= " WHERE $conditions";
    }
    
    if (!empty($orderBy)) {
        $sql .= " ORDER BY $orderBy";
    }
    
    if (!empty($limit)) {
        $sql .= " LIMIT $limit";
    }
    
    $result = mysqli_query($connection, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

/**
 * Centralized parameterized SELECT method for DRY compliance
 * Prevents SQL injection and provides consistent querying interface
 * 
 * @param string $table Table name
 * @param array $columns Columns to select (default: all)
 * @param array $conditions WHERE conditions as key-value pairs
 * @param string $orderBy ORDER BY clause
 * @param int $limit LIMIT clause
 * @param int $offset OFFSET clause
 * @param array $joins JOIN clauses
 * @return array Result set
 */
function selectData($table, $columns = ['*'], $conditions = [], $orderBy = '', $limit = 0, $offset = 0, $joins = []) {
    global $connection;
    
    $sql = "SELECT " . implode(', ', $columns) . " FROM {$table}";
    
    // Add JOINs
    foreach ($joins as $join) {
        $sql .= " " . $join;
    }
    
    $params = [];
    $types = '';
    
    // Add WHERE conditions
    if (!empty($conditions)) {
        $whereClauses = [];
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                // Handle IN conditions
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $whereClauses[] = "{$column} IN ({$placeholders})";
                foreach ($value as $val) {
                    $params[] = $val;
                    $types .= getParamType($val);
                }
            } elseif (strpos($column, ' ') !== false) {
                // Handle complex conditions like "column LIKE" or "column >="
                $whereClauses[] = $column;
                $params[] = $value;
                $types .= getParamType($value);
            } else {
                // Simple equality condition
                $whereClauses[] = "{$column} = ?";
                $params[] = $value;
                $types .= getParamType($value);
            }
        }
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    
    // Add ORDER BY
    if (!empty($orderBy)) {
        $sql .= " ORDER BY {$orderBy}";
    }
    
    // Add LIMIT and OFFSET
    if ($limit > 0) {
        $sql .= " LIMIT {$limit}";
        if ($offset > 0) {
            $sql .= " OFFSET {$offset}";
        }
    }
    
    return executeQuery($sql, $params, $types);
}

/**
 * Centralized paginated SELECT method for optimized data fetching
 * 
 * @param string $table Table name
 * @param int $page Page number (1-based)
 * @param int $limit Records per page
 * @param array $columns Columns to select
 * @param array $conditions WHERE conditions
 * @param string $orderBy ORDER BY clause
 * @param array $joins JOIN clauses
 * @return array ['data' => results, 'pagination' => info]
 */
function selectWithPagination($table, $page = 1, $limit = null, $columns = ['*'], $conditions = [], $orderBy = '', $joins = []) {
    global $connection;
    
    if ($limit === null) {
        $limit = PAGINATION_CONFIG['default_limit'];
    }
    
    // Ensure limit doesn't exceed maximum
    $limit = min($limit, PAGINATION_CONFIG['max_limit']);
    $offset = ($page - 1) * $limit;
    
    // Get total count for pagination info
    $countSql = "SELECT COUNT(*) as total FROM {$table}";
    
    // Add JOINs for count
    foreach ($joins as $join) {
        $countSql .= " " . $join;
    }
    
    $params = [];
    $types = '';
    
    // Add WHERE conditions for count
    if (!empty($conditions)) {
        $whereClauses = [];
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $whereClauses[] = "{$column} IN ({$placeholders})";
                foreach ($value as $val) {
                    $params[] = $val;
                    $types .= getParamType($val);
                }
            } elseif (strpos($column, ' ') !== false) {
                $whereClauses[] = $column;
                $params[] = $value;
                $types .= getParamType($value);
            } else {
                $whereClauses[] = "{$column} = ?";
                $params[] = $value;
                $types .= getParamType($value);
            }
        }
        $countSql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    
    // Execute count query
    $countResult = executeQuery($countSql, $params, $types);
    $totalRecords = $countResult[0]['total'] ?? 0;
    
    // Get actual data
    $data = selectData($table, $columns, $conditions, $orderBy, $limit, $offset, $joins);
    
    // Calculate pagination info
    $totalPages = ceil($totalRecords / $limit);
    
    return [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'records_per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1
        ]
    ];
}

/**
 * Execute parameterized query safely
 */
function executeQuery($sql, $params = [], $types = '') {
    global $connection;
    
    $stmt = mysqli_prepare($connection, $sql);
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . mysqli_error($connection));
    }
    
    // Bind parameters if any
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Query execution failed: " . $error);
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
    return $data;
}

/**
 * Get parameter type for prepared statement
 */
function getParamType($value) {
    if (is_int($value)) {
        return 'i';
    } elseif (is_float($value)) {
        return 'd';
    } else {
        return 's';
    }
}

/**
 * Begin database transaction for atomic operations
 */
function beginTransaction() {
    global $connection;
    return mysqli_autocommit($connection, false);
}

/**
 * Commit database transaction
 */
function commitTransaction() {
    global $connection;
    $result = mysqli_commit($connection);
    mysqli_autocommit($connection, true);
    return $result;
}

/**
 * Rollback database transaction
 */
function rollbackTransaction() {
    global $connection;
    $result = mysqli_rollback($connection);
    mysqli_autocommit($connection, true);
    return $result;
}

// REFACTORED: display_all now returns ONLY raw data (no HTML)
// This function is now MVC compliant - pure data retrieval
function display_all_data($sql, $column_mappings = [], $format = 'simple') {
    global $connection;
    $result = mysqli_query($connection, $sql);
    $data = [];

    if (!$result) {
        return ['success' => false, 'data' => [], 'error' => mysqli_error($connection)];
    }

    $rowCount = mysqli_num_rows($result);

    if ($rowCount > 0) {
        while($row = mysqli_fetch_array($result)) {
            $data[] = $row;
        }
    }
    
    return [
        'success' => true,
        'data' => $data,
        'count' => $rowCount,
        'format' => $format,
        'column_mappings' => $column_mappings
    ];
}


function save($tableOrSql, $data = null, $fileField = null, $uploadDir = null, $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png']) {
    global $connection;
    
    // Auto-determine upload directory based on table name if not provided
    if ($fileField && $uploadDir === null) {
        $tableName = '';
        if ($data === null) {
            // Extract table name from SQL query
            preg_match('/INSERT INTO (\w+)/', $tableOrSql, $matches);
            $tableName = $matches[1] ?? '';
        } else {
            // Table name is the first parameter
            $tableName = $tableOrSql;
        }
        
        // Automatic directory generation  table name mapping
        $directoryName = $tableName;
        
        // Auto-generate upload directory path
        $uploadDir = "../../uploads/{$directoryName}/";
        
        // Ensure the directory exists - create if it doesn't
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("Failed to create upload directory: $uploadDir");
                return false;
            }
        }
    }
    
    if ($data === null) {
        // Old way: raw SQL query
        $result = mysqli_query($connection, $tableOrSql);
        
        if ($result) {
            $insertId = mysqli_insert_id($connection);
            
            // Handle file upload if specified
            if ($fileField && $uploadDir && isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] == 0) {
                // Validate file type first
                $fileType = $_FILES[$fileField]['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    error_log("Invalid file type uploaded: $fileType");
                    return false;
                }
                
                $extension = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
                $newname = "$insertId.$extension";
                
                // Directory should already exist from earlier check, but double-check
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        error_log("Failed to create upload directory: $uploadDir");
                        return false;
                    }
                }
                
                // Move uploaded file
                if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newname)) {
                    // Update the record with image path if it's a table-based insert
                    if (strpos($tableOrSql, 'INSERT INTO') !== false) {
                        // Extract table name from INSERT query
                        preg_match('/INSERT INTO (\w+)/', $tableOrSql, $matches);
                        if ($matches[1]) {
                            $tableName = $matches[1];
                            
                            // Auto-detect primary key column name
                            $pkQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                       WHERE TABLE_SCHEMA = DATABASE() 
                                       AND TABLE_NAME = '$tableName' 
                                       AND COLUMN_KEY = 'PRI'";
                            $pkResult = mysqli_query($connection, $pkQuery);
                            $idColumn = 'id'; // fallback default
                            if ($pkResult && $pkRow = mysqli_fetch_assoc($pkResult)) {
                                $idColumn = $pkRow['COLUMN_NAME'];
                            }
                            
                            // Auto-detect image column name (filename, image_path, image, etc.)
                            $imageQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                          WHERE TABLE_SCHEMA = DATABASE() 
                                          AND TABLE_NAME = '$tableName' 
                                          AND (COLUMN_NAME LIKE '%image%' OR COLUMN_NAME LIKE '%filename%' OR COLUMN_NAME LIKE '%file%')
                                          AND DATA_TYPE = 'varchar'";
                            $imageResult = mysqli_query($connection, $imageQuery);
                            $imageColumn = 'image_path'; // fallback default
                            if ($imageResult && $imageRow = mysqli_fetch_assoc($imageResult)) {
                                $imageColumn = $imageRow['COLUMN_NAME'];
                            }
                            
                            mysqli_query($connection, "UPDATE $tableName SET $imageColumn = '$newname' WHERE $idColumn = $insertId");
                        }
                    }
                } else {
                    error_log("Failed to move uploaded file to: " . $uploadDir . $newname);
                }
            }
            
            return $insertId;
        } else {
            return false;
        }
    } else {
        // New way: table name with data array
        $table = $tableOrSql;
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnsList = implode(', ', $columns);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        
        $sql = "INSERT INTO {$table} ({$columnsList}) VALUES ({$placeholders})";
        $stmt = mysqli_prepare($connection, $sql);
        
        if (!$stmt) {
            return false;
        }
        
        // Create types string for bind_param
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            $insertId = mysqli_insert_id($connection);
            
            // Handle file upload if specified
            if ($fileField && $uploadDir && isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] == 0) {
                // Validate file type first
                $fileType = $_FILES[$fileField]['type'];
                if (in_array($fileType, $allowedTypes)) {
                    $extension = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
                    $newname = "$insertId.$extension";
                    
                    // Directory should already exist from earlier check, but double-check
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            error_log("Failed to create upload directory: $uploadDir");
                            mysqli_stmt_close($stmt);
                            return false;
                        }
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newname)) {
                        // Auto-detect primary key column name
                        $pkQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                   WHERE TABLE_SCHEMA = DATABASE() 
                                   AND TABLE_NAME = '$table' 
                                   AND COLUMN_KEY = 'PRI'";
                        $pkResult = mysqli_query($connection, $pkQuery);
                        $idColumn = 'id'; // fallback default
                        if ($pkResult && $pkRow = mysqli_fetch_assoc($pkResult)) {
                            $idColumn = $pkRow['COLUMN_NAME'];
                        }
                        
                        // Auto-detect image column name (filename, image_path, image, etc.)
                        $imageQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                      WHERE TABLE_SCHEMA = DATABASE() 
                                      AND TABLE_NAME = '$table' 
                                      AND (COLUMN_NAME LIKE '%image%' OR COLUMN_NAME LIKE '%filename%' OR COLUMN_NAME LIKE '%file%')
                                      AND DATA_TYPE = 'varchar'";
                        $imageResult = mysqli_query($connection, $imageQuery);
                        $imageColumn = 'image_path'; // fallback default
                        if ($imageResult && $imageRow = mysqli_fetch_assoc($imageResult)) {
                            $imageColumn = $imageRow['COLUMN_NAME'];
                        }
                        
                        $updateSql = "UPDATE {$table} SET {$imageColumn} = ? WHERE {$idColumn} = ?";
                        $updateStmt = mysqli_prepare($connection, $updateSql);
                        mysqli_stmt_bind_param($updateStmt, 'si', $newname, $insertId);
                        mysqli_stmt_execute($updateStmt);
                        mysqli_stmt_close($updateStmt);
                    } else {
                        error_log("Failed to move uploaded file to: " . $uploadDir . $newname);
                    }
                } else {
                    error_log("Invalid file type for upload: $fileType");
                }
            }
            
            mysqli_stmt_close($stmt);
            return $insertId;
        } else {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}

// Generic update function
// Can accept either raw SQL or table name with data array and conditions
function update($tableOrSql, $data = null, $conditions = '') {
    global $connection;
    
    if ($data === null) {
        // Old way: raw SQL query
        return mysqli_query($connection, $tableOrSql);
    } else {
        // New way: table name with data array
        $table = $tableOrSql;
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE {$conditions}";
        }
        
        $stmt = mysqli_prepare($connection, $sql);
        
        if (!$stmt) {
            return false;
        }
        
        // Create types string for bind_param
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }
}

// Generic delete function
function delete($table, $idValue, $idColumn = 'id') {
    global $connection;
    $sql = "DELETE FROM {$table} WHERE {$idColumn} = ?";
    $stmt = mysqli_prepare($connection, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $idValue);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Redirect function
function redirect_to($new_location) {
    header("Location: ".$new_location);
    exit();
}

// Redirect with message function (for better UX)
function redirect_with_message($location, $message, $type) {
    $encodedMessage = urlencode($message);
    $redirectUrl = $location . "?message=" . $encodedMessage . "&type=" . $type;
    header("Location: " . $redirectUrl);
    exit();
}


function closeConnection() {
    global $connection;
    mysqli_close($connection);
}
?>