<?php
define("DB_SERVER", "localhost");
define("DB_NAME", "ellenfoodhouse");
define("DB_USER", "root");
define("DB_PASS", "password");

$connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (mysqli_connect_errno()) {
    die("Database connection failed: " .
        mysqli_connect_error() .
        " (" . mysqli_connect_errno() . ")"
    );
}

//display
//fetch all records from a table
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


function save($tableOrSql, $data = null, $fileField = null, $uploadDir = null, $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png']) {
    global $connection;
    
    if ($data === null) {
        // Old way: raw SQL query
        $result = mysqli_query($connection, $tableOrSql);
        
        if ($result) {
            $insertId = mysqli_insert_id($connection);
            
            // Handle file upload if specified
            if ($fileField && $uploadDir && isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] == 0) {
                // Validate file type
                $fileType = $_FILES[$fileField]['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    return false; // Invalid file type
                }
                
                // Validate file size (2MB max)
                $maxFileSize = 2 * 1024 * 1024;
                if ($_FILES[$fileField]['size'] > $maxFileSize) {
                    return false; // File too large
                }
                
                $extension = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
                
                // Generate filename based on context (check if it's for banners)
                if (strpos($tableOrSql, 'banners') !== false) {
                    // For banners, use unique timestamp-based filename
                    $newname = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                } else {
                    // For other tables, use record ID
                    $newname = "$insertId.$extension";
                }
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newname)) {
                    // Update the record with image path if it's a table-based insert
                    if (strpos($tableOrSql, 'INSERT INTO') !== false) {
                        // Extract table name from INSERT query
                        preg_match('/INSERT INTO (\w+)/', $tableOrSql, $matches);
                        if ($matches[1]) {
                            $tableName = $matches[1];
                            
                            // Determine correct ID column and image field
                            $idColumn = 'id'; // default
                            $imageField = 'image_path'; // default
                            if ($tableName === 'menu') {
                                $idColumn = 'menu_id';
                            } elseif ($tableName === 'banners') {
                                $idColumn = 'banner_id';
                                $imageField = 'filename';
                            }
                            
                            mysqli_query($connection, "UPDATE $tableName SET $imageField = '$newname' WHERE $idColumn = $insertId");
                        }
                    }
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
                // Validate file type
                $fileType = $_FILES[$fileField]['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    mysqli_stmt_close($stmt);
                    return false; // Invalid file type
                }
                
                // Validate file size (2MB max)
                $maxFileSize = 2 * 1024 * 1024;
                if ($_FILES[$fileField]['size'] > $maxFileSize) {
                    mysqli_stmt_close($stmt);
                    return false; // File too large
                }
                
                $extension = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
                
                // Generate filename based on table type
                if ($table === 'banners') {
                    // For banners, use unique timestamp-based filename
                    $newname = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                } else {
                    // For other tables, use record ID
                    $newname = "$insertId.$extension";
                }
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newname)) {
                    // Update the record with image path - determine correct ID column and image field
                    $idColumn = 'id'; // default
                    $imageField = 'image_path'; // default
                    if ($table === 'menu') {
                        $idColumn = 'menu_id';
                    } elseif ($table === 'banners') {
                        $idColumn = 'banner_id';
                        $imageField = 'filename';
                    }
                    
                    $updateSql = "UPDATE {$table} SET $imageField = ? WHERE {$idColumn} = ?";
                    $updateStmt = mysqli_prepare($connection, $updateSql);
                    mysqli_stmt_bind_param($updateStmt, 'si', $newname, $insertId);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
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
function update($tableOrSql, $data = null, $conditions = '', $fileField = null, $uploadDir = null, $recordId = null, $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png']) {
    global $connection;
    
    if ($data === null) {
        // Old way: raw SQL query
        return mysqli_query($connection, $tableOrSql);
    } else {
        // New way: table name with data array
        $table = $tableOrSql;
        
        // Handle file upload if specified
        if ($fileField && $uploadDir && $recordId && isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] == 0) {
            // Get old record to potentially delete old image
            $oldRecord = fetch($table, $conditions);
            $oldData = !empty($oldRecord) ? $oldRecord[0] : null;
            
            // Validate file type
            $fileType = $_FILES[$fileField]['type'];
            if (!in_array($fileType, $allowedTypes)) {
                return false; // Invalid file type
            }
            
            // Validate file size (2MB max)
            $maxFileSize = 2 * 1024 * 1024;
            if ($_FILES[$fileField]['size'] > $maxFileSize) {
                return false; // File too large
            }
            
            $extension = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
            $newFilename = $recordId . '.' . $extension;
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Delete old image if it has different filename
            $imageField = ($table === 'banners') ? 'filename' : 'image_path';
            if ($oldData && isset($oldData[$imageField]) && $oldData[$imageField] && $oldData[$imageField] !== $newFilename) {
                $oldImagePath = $uploadDir . $oldData[$imageField];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newFilename)) {
                // Add image path to update data
                $data[$imageField] = $newFilename;
            }
        }
        
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
