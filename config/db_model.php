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

// Redirect function
function redirect_to($new_location) {
    header("Location: ".$new_location);
    exit();
}

// ===================== CUSTOMER FUNCTIONS =====================

// Add a new customer
function addCustomer($firstName, $lastName, $email, $phone = null) {
    global $connection;
    $stmt = mysqli_prepare($connection, 
        "INSERT INTO customers (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)");
    
    mysqli_stmt_bind_param($stmt, "ssss", $firstName, $lastName, $email, $phone);
    
    if (mysqli_stmt_execute($stmt)) {
        $customerId = mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);
        return $customerId;
    } else {
        mysqli_stmt_close($stmt);
        return false;
    }
}

// Get customer by ID
function getCustomerById($id) {
    global $connection;
    $stmt = mysqli_prepare($connection, "SELECT * FROM customers WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $customer = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $customer;
}

// Get all customers
function getAllCustomers() {
    global $connection;
    $query = "SELECT * FROM customers ORDER BY created_at DESC";
    $result = mysqli_query($connection, $query);
    
    $customers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
    
    return $customers;
}

// Update customer
function updateCustomer($id, $firstName, $lastName, $email, $phone = null) {
    global $connection;
    $stmt = mysqli_prepare($connection, 
        "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    
    mysqli_stmt_bind_param($stmt, "ssssi", $firstName, $lastName, $email, $phone, $id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Delete customer
function deleteCustomer($id) {
    global $connection;
    $stmt = mysqli_prepare($connection, "DELETE FROM customers WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// ===================== RESERVATION FUNCTIONS =====================

// Add a new reservation
function addReservation($customerId, $reservationDate, $reservationTime, $partySize, $tableNumber = null, $specialRequests = null) {
    global $connection;
    $stmt = mysqli_prepare($connection, 
        "INSERT INTO reservations (customer_id, reservation_date, reservation_time, party_size, table_number, special_requests) 
         VALUES (?, ?, ?, ?, ?, ?)");
    
    mysqli_stmt_bind_param($stmt, "isssis", $customerId, $reservationDate, $reservationTime, $partySize, $tableNumber, $specialRequests);
    
    if (mysqli_stmt_execute($stmt)) {
        $reservationId = mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);
        return $reservationId;
    } else {
        mysqli_stmt_close($stmt);
        return false;
    }
}

// Get reservation by ID with customer details
function getReservationById($id) {
    global $connection;
    $stmt = mysqli_prepare($connection, 
        "SELECT r.*, c.first_name, c.last_name, c.email, c.phone 
         FROM reservations r 
         JOIN customers c ON r.customer_id = c.id 
         WHERE r.id = ?");
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $reservation = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $reservation;
}

// Get all reservations with customer details
function getAllReservations() {
    global $connection;
    $query = "SELECT r.*, c.first_name, c.last_name, c.email 
              FROM reservations r 
              JOIN customers c ON r.customer_id = c.id 
              ORDER BY r.reservation_date DESC, r.reservation_time DESC";
    
    $result = mysqli_query($connection, $query);
    
    $reservations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
    
    return $reservations;
}

// Update reservation status
function updateReservationStatus($id, $status) {
    global $connection;
    $stmt = mysqli_prepare($connection, "UPDATE reservations SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Get reservations by date
function getReservationsByDate($date) {
    global $connection;
    $stmt = mysqli_prepare($connection, 
        "SELECT r.*, c.first_name, c.last_name, c.email 
         FROM reservations r 
         JOIN customers c ON r.customer_id = c.id 
         WHERE r.reservation_date = ? 
         ORDER BY r.reservation_time");
    
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $reservations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    return $reservations;
}

// Delete reservation
function deleteReservation($id) {
    global $connection;
    $stmt = mysqli_prepare($connection, "DELETE FROM reservations WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Get all reservations for a specific customer
function getCustomerReservations($customerId) {
    global $connection;
    $stmt = mysqli_prepare($connection, 
        "SELECT * FROM reservations WHERE customer_id = ? ORDER BY reservation_date DESC");
    
    mysqli_stmt_bind_param($stmt, "i", $customerId);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $reservations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    return $reservations;
}

// ===================== UTILITY FUNCTIONS =====================

// Generic save function (from your original code)
function save($insertQuery) {
    global $connection;
    $sql = mysqli_query($connection, $insertQuery);
}

// Display by ID (placeholder for future implementation)
function display_by_id($id) {
    // Implementation placeholder
}

// Display by table (placeholder for future implementation)
function display_by_table($table_map) {
    // Implementation placeholder
}

// Close database connection
function closeConnection() {
    global $connection;
    mysqli_close($connection);
}
?>