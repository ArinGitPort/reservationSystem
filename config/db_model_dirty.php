<?php
// Ensure connection is only established once
if (!isset($GLOBALS['connection'])) {
    define("DB_SERVER", "localhost");
    define("DB_NAME", "ellenfoodhouse");
    define("DB_USER", "root");
    define("DB_PASS", "password");

    $connection = @mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    
    if (!$connection || mysqli_connect_errno()) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . mysqli_connect_error(),
            'errno' => mysqli_connect_errno()
        ]));
    }
    
    // Set charset
    mysqli_set_charset($connection, 'utf8mb4');
    
    // Store in global scope
    $GLOBALS['connection'] = $connection;
} else {
    $connection = $GLOBALS['connection'];
}

/**
 * DbModel Class - Object-oriented wrapper for database operations
 */
class DbModel {
    private $connection;
    
    public function __construct() {
        $this->connection = $GLOBALS['connection'];
    }
    
    // All methods use $this->connection instead of global $connection
    
    public function fetch($table, $conditions = '', $orderBy = '', $limit = '') {
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
        
        $result = mysqli_query($this->connection, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function display_all($sql, $column_mappings, $url, $format = 'simple') {
        return display_all($sql, $column_mappings, $url, $format);
    }
    
    public function save($tableOrSql, $data = null, $fileField = null, $uploadDir = null, $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png']) {
        return save($tableOrSql, $data, $fileField, $uploadDir, $allowedTypes);
    }
    
    public function update($tableOrSql, $data = null, $conditions = '') {
        return update($tableOrSql, $data, $conditions);
    }
    
    public function delete($table, $idValue, $idColumn = 'id') {
        return delete($table, $idValue, $idColumn);
    }
    
    // Order Management Methods
    public function createOrder($orderData) {
        return createOrder($orderData);
    }
    
    public function addOrderItems($orderId, $items) {
        return addOrderItems($orderId, $items);
    }
    
    public function getOrderDetails($orderId) {
        return getOrderDetails($orderId);
    }
    
    public function updateOrderStatus($orderId, $status) {
        return updateOrderStatus($orderId, $status);
    }
    
    public function getAllOrders($limit = null, $status = null, $orderBy = 'order_date DESC') {
        return getAllOrders($limit, $status, $orderBy);
    }
    
    public function getOrderById($orderId) {
        return getOrderById($orderId);
    }
    
    public function getOrderItemsById($orderId) {
        return getOrderItemsById($orderId);
    }
    
    public function getOrderStatusCounts() {
        return getOrderStatusCounts();
    }
    
    public function getTodaysOrders() {
        return getTodaysOrders();
    }
    
    public function searchOrders($searchTerm, $status = null) {
        return searchOrders($searchTerm, $status);
    }
    
    public function getTodaysRevenue() {
        return getTodaysRevenue();
    }
    
    public function processOrder($orderData) {
        return processOrder($orderData);
    }
    
    public function getCustomerOrders($customerId, $limit = 10) {
        return getCustomerOrders($customerId, $limit);
    }
    
    public function calculateOrderTotal($items) {
        return calculateOrderTotal($items);
    }
    
    public function findOrCreateCustomer($name, $email, $phone) {
        return findOrCreateCustomer($name, $email, $phone);
    }
    
    public function getMenuItems($bestSellerOnly = false, $limit = null) {
        return getMenuItems($bestSellerOnly, $limit);
    }
    
    public function getRecentOrders($limit = 5) {
        return getRecentOrders($limit);
    }
}

// Keep procedural functions for backward compatibility

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

// Legacy display_all function - outputs HTML directly
function display_all($sql, $column_mappings, $url, $format = 'simple') {
    global $connection;
    $output_list = "";
    $result = mysqli_query($connection, $sql);

    $rowCount = mysqli_num_rows($result);

    if ($rowCount > 0) {
        $index = 1;
        while($row = mysqli_fetch_array($result)){ 
            if ($format === 'table') {
                // Bootstrap table row format
                $output_list .= "<tr>";
                $output_list .= "<td>" . $index . "</td>";
                
                // Special handling for customer account management
                if ($url == 'account_management.php') {
                    // Profile column with image
                    $output_list .= "<td>";
                    if (isset($row['image_path']) && $row['image_path']) {
                        $output_list .= "<img src='../../uploads/profiles/" . htmlspecialchars($row['image_path']) . "' 
                                 alt='" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "' 
                                 style='width: 50px; height: 50px; object-fit: cover; border-radius: 50%;'>";
                    } else {
                        $output_list .= "<div style='width: 50px; height: 50px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center;'>
                                <i class='fas fa-user text-muted'></i>
                              </div>";
                    }
                    $output_list .= "</td>";
                    
                    // Name column
                    $output_list .= "<td><div class='fw-bold'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</div></td>";
                    
                    // Email column
                    $output_list .= "<td>" . htmlspecialchars($row['email']) . "</td>";
                    
                    // Phone column
                    $output_list .= "<td>" . htmlspecialchars($row['phone'] ?: 'N/A') . "</td>";
                    
                    // Date column
                    $output_list .= "<td>";
                    $output_list .= "<div>" . date('d M Y', strtotime($row['created_at'])) . "</div>";
                    $output_list .= "<small class='text-muted'>" . date('h:i A', strtotime($row['created_at'])) . "</small>";
                    $output_list .= "</td>";
                    
                    // Action buttons column
                    $id = $row['id'];
                    $output_list .= "<td>";
                    $output_list .= "<div class='action-buttons'>";
                    $output_list .= "<button class='btn btn-sm btn-outline-dark' 
                                onclick=\"editCustomer($id, '" . htmlspecialchars($row['first_name']) . "', '" . htmlspecialchars($row['last_name']) . "', '" . htmlspecialchars($row['email']) . "', '" . htmlspecialchars($row['phone']) . "')\"
                                data-bs-toggle='modal' data-bs-target='#editCustomerModal'>
                            <i class='fas fa-edit'></i>
                          </button>";
                    $output_list .= "<button class='btn btn-sm btn-outline-danger' 
                                onclick=\"confirmDelete('customer', $id)\"
                                data-bs-toggle='modal' data-bs-target='#confirmDeleteModal'>
                            <i class='fas fa-trash'></i>
                          </button>";
                    $output_list .= "</div>";
                    $output_list .= "</td>";
                    
                } elseif ($url == 'menu_management.php') {
                    // Menu image column
                    $output_list .= "<td>";
                    if (isset($row['image_path']) && $row['image_path']) {
                        $output_list .= "<img src='../../uploads/menu/" . htmlspecialchars($row['image_path']) . "' 
                                 alt='" . htmlspecialchars($row['name']) . "' 
                                 style='width: 60px; height: 60px; object-fit: cover; border-radius: 8px;'>";
                    } else {
                        $output_list .= "<div style='width: 60px; height: 60px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;'>
                                <i class='fas fa-image text-muted'></i>
                              </div>";
                    }
                    $output_list .= "</td>";
                    
                    // Menu name column
                    $output_list .= "<td><div class='fw-bold'>" . htmlspecialchars($row['name']) . "</div></td>";
                    
                    // Price column
                    $output_list .= "<td><span class='fw-bold text-primary'>₱" . number_format($row['price'], 2) . "</span></td>";
                    
                    // Best seller status column
                    $output_list .= "<td>";
                    if ($row['is_best_seller']) {
                        $output_list .= "<span class='badge bg-warning text-dark'><i class='fas fa-star me-1'></i>Best Seller</span>";
                    } else {
                        $output_list .= "<span class='badge bg-light text-dark'>Regular</span>";
                    }
                    $output_list .= "</td>";
                    
                    // Action buttons column
                    $menuId = $row['menu_id'];
                    $itemJson = htmlspecialchars(json_encode($row));
                    $output_list .= "<td>";
                    $output_list .= "<div class='action-buttons'>";
                    $output_list .= "<button class='btn btn-sm btn-outline-primary' 
                                onclick=\"editMenuItem($itemJson)\"
                                data-bs-toggle='modal' data-bs-target='#editMenuModal'>
                            <i class='fas fa-edit'></i>
                          </button>";
                    $output_list .= "<button class='btn btn-sm btn-outline-danger' 
                                onclick=\"confirmDelete('$menuId', '" . htmlspecialchars($row['image_path']) . "')\"
                                data-bs-toggle='modal' data-bs-target='#confirmDeleteModal'>
                            <i class='fas fa-trash'></i>
                          </button>";
                    $output_list .= "</div>";
                    $output_list .= "</td>";
                    
                } elseif ($url == 'reservation_management.php') {
                    // Customer name column
                    $output_list .= "<td>";
                    $output_list .= "<div class='fw-bold'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</div>";
                    $output_list .= "<small class='text-muted'>" . htmlspecialchars($row['email']) . "</small>";
                    $output_list .= "</td>";
                    
                    // Date column
                    $output_list .= "<td>" . date('d M Y', strtotime($row['reservation_date'])) . "</td>";
                    
                    // Time column
                    $output_list .= "<td>" . date('g:i A', strtotime($row['reservation_time'])) . "</td>";
                    
                    // Party size column
                    $output_list .= "<td>" . $row['party_size'] . "</td>";
                    
                    // Table column
                    $output_list .= "<td>" . ($row['table_number'] ?: 'TBD') . "</td>";
                    
                    // Status column
                    $output_list .= "<td>";
                    $output_list .= "<span class='status-badge status-" . $row['status'] . "'>";
                    $output_list .= ucfirst($row['status']);
                    $output_list .= "</span>";
                    $output_list .= "</td>";
                    
                    // Action buttons column
                    $reservationId = $row['id'];
                    $output_list .= "<td>";
                    $output_list .= "<div class='action-buttons'>";
                    $output_list .= "<div class='dropdown'>";
                    $output_list .= "<button class='btn btn-sm btn-outline-dark dropdown-toggle' type='button' data-bs-toggle='dropdown'>";
                    $output_list .= "<i class='fas fa-cog'></i>";
                    $output_list .= "</button>";
                    $output_list .= "<ul class='dropdown-menu'>";
                    $output_list .= "<li><a class='dropdown-item' href='#' onclick=\"updateStatus($reservationId, 'pending')\">Set Pending</a></li>";
                    $output_list .= "<li><a class='dropdown-item' href='#' onclick=\"updateStatus($reservationId, 'confirmed')\">Set Confirmed</a></li>";
                    $output_list .= "<li><a class='dropdown-item' href='#' onclick=\"updateStatus($reservationId, 'cancelled')\">Set Cancelled</a></li>";
                    $output_list .= "</ul>";
                    $output_list .= "</div>";
                    $output_list .= "<button class='btn btn-sm btn-outline-danger' 
                                onclick=\"confirmDelete('reservation', $reservationId)\"
                                data-bs-toggle='modal' data-bs-target='#confirmDeleteModal'>
                            <i class='fas fa-trash'></i>
                          </button>";
                    $output_list .= "</div>";
                    $output_list .= "</td>";
                    
                } elseif ($url == 'event_display_management.php') {
                    // Banner preview column
                    $output_list .= "<td>";
                    $output_list .= "<img src='../../uploads/banners/" . htmlspecialchars($row['filename']) . "' 
                             alt='" . htmlspecialchars($row['title']) . "' 
                             style='width: 80px; height: 50px; object-fit: cover; border-radius: 4px;'>";
                    $output_list .= "</td>";
                    
                    // Event details column
                    $output_list .= "<td>";
                    $output_list .= "<div class='fw-bold mb-1'>" . htmlspecialchars($row['title']) . "</div>";
                    if (isset($row['description']) && $row['description']) {
                        $description = htmlspecialchars(substr($row['description'], 0, 80));
                        $description .= strlen($row['description']) > 80 ? '...' : '';
                        $output_list .= "<small class='text-muted'>$description</small>";
                    } else {
                        $output_list .= "<small class='text-muted'>No description</small>";
                    }
                    $output_list .= "</td>";
                    
                    // Event date column
                    $output_list .= "<td>";
                    if (isset($row['event_start_date']) && $row['event_start_date']) {
                        $output_list .= "<div class='fw-bold text-primary'>" . date('M d, Y', strtotime($row['event_start_date'])) . "</div>";
                        $output_list .= "<small class='text-muted'>to</small>";
                        $output_list .= "<div class='fw-bold text-primary'>" . date('M d, Y', strtotime($row['event_end_date'])) . "</div>";
                    } elseif (isset($row['event_date']) && $row['event_date']) {
                        $output_list .= "<div class='fw-bold text-primary'>" . date('M d, Y', strtotime($row['event_date'])) . "</div>";
                    } else {
                        $output_list .= "<span class='text-muted'>Not set</span>";
                    }
                    $output_list .= "</td>";
                    
                    // Status column
                    $output_list .= "<td>";
                    $output_list .= "<div class='d-flex flex-column gap-1'>";
                    $output_list .= "<span class='badge bg-" . ($row['active'] ? 'success' : 'secondary') . "'>";
                    $output_list .= ($row['active'] ? 'Active' : 'Inactive');
                    $output_list .= "</span>";
                    $output_list .= "</div>";
                    $output_list .= "</td>";
                    
                    // Uploaded date column
                    $output_list .= "<td>" . date('M d, Y', strtotime($row['date_uploaded'])) . "</td>";
                    
                    // Action buttons column
                    $bannerId = $row['banner_id'];
                    $output_list .= "<td>";
                    $output_list .= "<div class='action-buttons'>";
                    
                    // Create JSON data for edit functionality using data attribute
                    $bannerJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    $output_list .= "<button class='btn btn-sm btn-outline-primary edit-banner-btn' 
                                data-banner='$bannerJson'
                                data-bs-toggle='modal' data-bs-target='#editBannerModal' title='Edit Banner'>
                            <i class='fas fa-edit'></i>
                          </button>";
                    $output_list .= "<form method='POST' style='display: inline;'>";
                    $output_list .= "<input type='hidden' name='action' value='toggle_banner'>";
                    $output_list .= "<input type='hidden' name='banner_id' value='$bannerId'>";
                    $output_list .= "<input type='hidden' name='current_status' value='" . $row['active'] . "'>";
                    $output_list .= "<button type='submit' class='btn btn-sm btn-outline-secondary' title='Toggle Status'>";
                    $output_list .= "<i class='fas fa-" . ($row['active'] ? 'eye-slash' : 'eye') . "'></i>";
                    $output_list .= "</button>";
                    $output_list .= "</form>";
                    $output_list .= "<button class='btn btn-sm btn-outline-danger' 
                                onclick=\"confirmDelete('$bannerId', '" . htmlspecialchars($row['filename']) . "')\"
                                data-bs-toggle='modal' data-bs-target='#confirmDeleteModal' title='Delete Banner'>
                            <i class='fas fa-trash'></i>
                          </button>";
                    $output_list .= "</div>";
                    $output_list .= "</td>";
                    
                } else {
                    // Generic table format for other pages
                    foreach ($column_mappings as $column_name => $label) {
                        $value = $row[$column_name];
                        if (strpos($column_name, 'date') !== false) {
                            $value = date('M d, Y', strtotime($value));
                        }
                        $output_list .= "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    
                    $id = $row['id'];
                    $output_list .= "<td>";
                    $output_list .= "<button class='btn btn-sm btn-outline-primary me-1' onclick='editRecord($id)'><i class='fas fa-edit'></i></button>";
                    $output_list .= "<button class='btn btn-sm btn-outline-danger' onclick='deleteRecord($id)'><i class='fas fa-trash'></i></button>";
                    $output_list .= "</td>";
                }
                
                $output_list .= "</tr>";
                $index++;
            } else {
                // Simple format (original)
                foreach ($column_mappings as $column_name => $label) {
                    $value = $row[$column_name];
                    if (strpos($column_name, 'date') !== false) {
                        $value = strftime("%b %d, %Y", strtotime($value));
                    }
                    $output_list .= "<strong>$label </strong> $value &nbsp; ";
                }
                
                $id = $row['id'];
                $output_list .= "<a href='edit.php?editid=$id'>edit</a> &bull; <a href='$url?deleteid=$id'>delete</a><br />";
            }
        }
    } else {
        if ($format === 'table') {
            $columnCount = 7; // Default
            if ($url == 'account_management.php') {
                $columnCount = 7; // #, Profile, Name, Email, Phone, Added, Manage
            } elseif ($url == 'menu_management.php') {
                $columnCount = 6; // #, Image, Name, Price, Best Seller, Manage
            } elseif ($url == 'reservation_management.php') {
                $columnCount = 8; // #, Customer, Date, Time, Party Size, Table, Status, Manage
            } elseif ($url == 'event_display_management.php') {
                $columnCount = 7; // #, Preview, Event Details, Event Date, Status, Uploaded, Manage
            } else {
                $columnCount = count($column_mappings) + 2; // +2 for # and Actions columns
            }
            $output_list = "<tr><td colspan='$columnCount' class='text-center text-muted'>No records found.</td></tr>";
        } else {
            $output_list = "No records found.";
        }
    }
    echo $output_list;
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


// Order Management Functions

// Create a new order and return order ID
function createOrder($orderData) {
    global $connection;
    
    $sql = "INSERT INTO orders (customer_id, customer_name, customer_phone, customer_email, 
                              order_type, order_status, delivery_address, special_instructions, total_amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "isssssssd", 
        $orderData['customer_id'],
        $orderData['customer_name'],
        $orderData['customer_phone'], 
        $orderData['customer_email'],
        $orderData['order_type'],
        $orderData['order_status'],
        $orderData['delivery_address'],
        $orderData['special_instructions'],
        $orderData['total_amount']
    );
    
    $result = mysqli_stmt_execute($stmt);
    $orderId = mysqli_insert_id($connection);
    mysqli_stmt_close($stmt);
    
    return $result ? $orderId : false;
}

// Add items to an order
function addOrderItems($orderId, $items) {
    global $connection;
    
    $sql = "INSERT INTO order_items (order_id, menu_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    $allSuccess = true;
    foreach ($items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        mysqli_stmt_bind_param($stmt, "iiidd", 
            $orderId, 
            $item['menu_id'], 
            $item['quantity'], 
            $item['price'], 
            $subtotal
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            $allSuccess = false;
            break;
        }
    }
    
    mysqli_stmt_close($stmt);
    return $allSuccess;
}

// Get order details with items
function getOrderDetails($orderId) {
    global $connection;
    
    // Get order info
    $orderSql = "SELECT o.*, c.first_name, c.last_name FROM orders o 
                 LEFT JOIN customers c ON o.customer_id = c.id 
                 WHERE o.order_id = ?";
    $stmt = mysqli_prepare($connection, $orderSql);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $orderResult = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($orderResult);
    mysqli_stmt_close($stmt);
    
    if (!$order) {
        return false;
    }
    
    // Get order items
    $itemsSql = "SELECT oi.*, m.name, m.image_path FROM order_items oi 
                 JOIN menu m ON oi.menu_id = m.menu_id 
                 WHERE oi.order_id = ?";
    $stmt = mysqli_prepare($connection, $itemsSql);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $itemsResult = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($item = mysqli_fetch_assoc($itemsResult)) {
        $items[] = $item;
    }
    mysqli_stmt_close($stmt);
    
    $order['items'] = $items;
    return $order;
}

// Update order status
function updateOrderStatus($orderId, $status) {
    return update('orders', ['order_status' => $status], "order_id = $orderId");
}

// Get customer orders
function getCustomerOrders($customerId, $limit = 10) {
    return fetch('orders', "customer_id = $customerId", "order_date DESC", $limit);
}

// Calculate order total from items
function calculateOrderTotal($items) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Find or create customer by email
function findOrCreateCustomer($name, $email, $phone) {
    global $connection;
    
    // Try to find existing customer by email
    $existingCustomer = fetch('customers', "email = '$email'");
    
    if (!empty($existingCustomer)) {
        return $existingCustomer[0]['id'];
    }
    
    // Create new customer
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    $customerData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone
    ];
    
    return save('customers', $customerData);
}

// Get menu items with optional filtering
function getMenuItems($bestSellerOnly = false, $limit = null) {
    $conditions = $bestSellerOnly ? 'is_best_seller = 1' : '';
    $orderBy = 'is_best_seller DESC, name ASC';
    return fetch('menu', $conditions, $orderBy, $limit);
}

// Process complete order - handles everything from validation to database storage
function processOrder($orderData) {
    try {
        // Validate required fields
        if (empty($orderData['customer_name']) || empty($orderData['customer_phone']) || empty($orderData['cart_items'])) {
            throw new Exception('Missing required fields');
        }
        
        // Find or create customer
        $customerId = findOrCreateCustomer(
            $orderData['customer_name'],
            $orderData['customer_email'] ?? '',
            $orderData['customer_phone']
        );
        
        if (!$customerId) {
            throw new Exception('Failed to create customer record');
        }
        
        // Calculate total
        $total = calculateOrderTotal($orderData['cart_items']);
        
        // Prepare order data
        $paymentMethod = $orderData['payment_method'] ?? 'cash';
        $orderStatus = ($paymentMethod === 'cash') ? 'pending' : 'confirmed';
        
        $dbOrderData = [
            'customer_id' => $customerId,
            'customer_name' => $orderData['customer_name'],
            'customer_phone' => $orderData['customer_phone'],
            'customer_email' => $orderData['customer_email'] ?? '',
            'order_type' => $orderData['order_type'],
            'order_status' => $orderStatus,
            'delivery_address' => $orderData['delivery_address'] ?? '',
            'special_instructions' => $orderData['special_instructions'] ?? '',
            'total_amount' => $total
        ];
        
        // Create order
        $orderId = createOrder($dbOrderData);
        
        if (!$orderId) {
            throw new Exception('Failed to create order');
        }
        
        // Add order items
        $success = addOrderItems($orderId, $orderData['cart_items']);
        
        if (!$success) {
            throw new Exception('Failed to add order items');
        }
        
        // Return success response
        return [
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $orderId,
            'order_number' => sprintf('EFH-%06d', $orderId)
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Order Management Functions for Admin
function getAllOrders($limit = null, $status = null, $orderBy = 'order_date DESC') {
    global $connection;
    
    $sql = "SELECT o.*, c.first_name, c.last_name, c.email as customer_email 
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id";
    
    if ($status) {
        $sql .= " WHERE o.order_status = '" . mysqli_real_escape_string($connection, $status) . "'";
    }
    
    $sql .= " ORDER BY " . $orderBy;
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = mysqli_query($connection, $sql);
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    return $orders;
}

function getOrderById($orderId) {
    global $connection;
    
    $orderId = intval($orderId);
    $sql = "SELECT o.*, c.first_name, c.last_name, c.email as customer_email 
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            WHERE o.order_id = $orderId";
    
    $result = mysqli_query($connection, $sql);
    return mysqli_fetch_assoc($result);
}

function getOrderItemsById($orderId) {
    global $connection;
    
    $orderId = intval($orderId);
    $sql = "SELECT oi.*, m.name as menu_name 
            FROM order_items oi 
            JOIN menu m ON oi.menu_id = m.menu_id 
            WHERE oi.order_id = $orderId";
    
    $result = mysqli_query($connection, $sql);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    return $items;
}

function getOrderStatusCounts() {
    global $connection;
    
    $sql = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
    $result = mysqli_query($connection, $sql);
    
    $counts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $counts[$row['order_status']] = $row['count'];
    }
    return $counts;
}

function getTodaysOrders() {
    global $connection;
    
    $sql = "SELECT COUNT(*) as count, SUM(total_amount) as total_revenue 
            FROM orders 
            WHERE DATE(order_date) = CURDATE()";
    
    $result = mysqli_query($connection, $sql);
    return mysqli_fetch_assoc($result);
}

function getRecentOrders($limit = 5) {
    return getAllOrders($limit, null, 'order_date DESC');
}

function searchOrders($searchTerm, $status = null) {
    global $connection;
    
    $searchTerm = mysqli_real_escape_string($connection, $searchTerm);
    
    $sql = "SELECT o.*, c.first_name, c.last_name, c.email as customer_email 
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            WHERE (o.customer_name LIKE '%$searchTerm%' 
                OR o.customer_phone LIKE '%$searchTerm%' 
                OR o.customer_email LIKE '%$searchTerm%' 
                OR o.order_id LIKE '%$searchTerm%' 
                OR CONCAT('EFH-', LPAD(o.order_id, 6, '0')) LIKE '%$searchTerm%')";
    
    if ($status) {
        $sql .= " AND o.order_status = '" . mysqli_real_escape_string($connection, $status) . "'";
    }
    
    $sql .= " ORDER BY o.order_date DESC";
    
    $result = mysqli_query($connection, $sql);
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    return $orders;
}

function getTodaysRevenue() {
    global $connection;
    
    $sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue 
            FROM orders 
            WHERE DATE(order_date) = CURDATE() 
            AND order_status != 'cancelled'";
    
    $result = mysqli_query($connection, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['revenue'];
}

function closeConnection() {
    global $connection;
    mysqli_close($connection);
}
?>
