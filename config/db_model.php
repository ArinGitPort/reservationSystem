<?php
define("DB_SERVER", "localhost");
define("DB_NAME", "ellenfoodhouse");
define("DB_USER", "root");
define("DB_PASS", "1234");

$connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
	if (mysqli_connect_errno()) {
    die("Database connection failed: " .
        mysqli_connect_error() .
        "(" . mysqli_connect_errno() . ")"
    );
}

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
                    $output_list .= "<form method='POST' style='display: inline;'>";
                    $output_list .= "<input type='hidden' name='action' value='toggle_banner'>";
                    $output_list .= "<input type='hidden' name='banner_id' value='$bannerId'>";
                    $output_list .= "<input type='hidden' name='current_status' value='" . $row['active'] . "'>";
                    $output_list .= "<button type='submit' class='btn btn-sm btn-outline-primary' title='Toggle Status'>";
                    $output_list .= "<i class='fas fa-" . ($row['active'] ? 'eye-slash' : 'eye') . "'></i>";
                    $output_list .= "</button>";
                    $output_list .= "</form>";
                    $output_list .= "<button class='btn btn-sm btn-outline-danger' 
                                onclick=\"confirmDelete('$bannerId', '" . htmlspecialchars($row['filename']) . "')\"
                                data-bs-toggle='modal' data-bs-target='#confirmDeleteModal'>
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
    
    if ($data === null) {
        // Old way: raw SQL query
        $result = mysqli_query($connection, $tableOrSql);
        
        if ($result) {
            $insertId = mysqli_insert_id($connection);
            
            // Handle file upload if specified
            if ($fileField && $uploadDir && isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] == 0) {
                $extension = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
                $newname = "$insertId.$extension";
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newname);
                
                // Update the record with image path if it's a table-based insert
                if (strpos($tableOrSql, 'INSERT INTO') !== false) {
                    // Extract table name from INSERT query
                    preg_match('/INSERT INTO (\w+)/', $tableOrSql, $matches);
                    if ($matches[1]) {
                        $tableName = $matches[1];
                        
                        // Determine correct ID column and image column
                        $idColumn = 'id'; // default
                        $imageColumn = 'image_path'; // default
                        if ($tableName === 'menu') {
                            $idColumn = 'menu_id';
                        } elseif ($tableName === 'banners') {
                            $idColumn = 'banner_id';
                            $imageColumn = 'filename'; // banners table uses 'filename' instead of 'image_path'
                        }
                        
                        mysqli_query($connection, "UPDATE $tableName SET $imageColumn = '$newname' WHERE $idColumn = $insertId");
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
                if (in_array($fileType, $allowedTypes)) {
                    $extension = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
                    $newname = "$insertId.$extension";
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newname);
                    
                    // Update the record with image path - determine correct ID column and image column
                    $idColumn = 'id'; // default
                    $imageColumn = 'image_path'; // default
                    if ($table === 'menu') {
                        $idColumn = 'menu_id';
                    } elseif ($table === 'banners') {
                        $idColumn = 'banner_id';
                        $imageColumn = 'filename'; // banners table uses 'filename' instead of 'image_path'
                    }
                    
                    $updateSql = "UPDATE {$table} SET {$imageColumn} = ? WHERE {$idColumn} = ?";
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
