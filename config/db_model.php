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

// Include helper functions
require_once 'db_helpers.php';xecuteInsert($sql, "ssss", $firstName, $lastName, $email, $phone);
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


// Add a new customer
function addCustomer($firstName, $lastName, $email, $phone = null) {
    $sql = "INSERT INTO customers (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)";
    return e
// Get customer by ID
function getCustomerById($id) {
    $sql = "SELECT * FROM customers WHERE id = ?";
    return executeSelectOne($sql, "i", $id);
}

// Get all customers
function getAllCustomers() {
    $sql = "SELECT * FROM customers ORDER BY created_at DESC";
    return executeSelectAll($sql);
}

// Update customer
function updateCustomer($id, $firstName, $lastName, $email, $phone = null) {
    $sql = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
    return executeUpdate($sql, "ssssi", $firstName, $lastName, $email, $phone, $id);
}

// Delete customer
function deleteCustomer($id) {
    return executeDelete("customers", $id);
}


// Add a new reservation
function addReservation($customerId, $reservationDate, $reservationTime, $partySize, $tableNumber = null, $specialRequests = null) {
    $sql = "INSERT INTO reservations (customer_id, reservation_date, reservation_time, party_size, table_number, special_requests) VALUES (?, ?, ?, ?, ?, ?)";
    return executeInsert($sql, "isssis", $customerId, $reservationDate, $reservationTime, $partySize, $tableNumber, $specialRequests);
}



// Get all reservations with customer details
function getAllReservations() {
    $sql = "SELECT r.*, c.first_name, c.last_name, c.email 
            FROM reservations r 
            JOIN customers c ON r.customer_id = c.id 
            ORDER BY r.reservation_date DESC, r.reservation_time DESC";
    return executeSelectAll($sql);
}

// Update reservation status
function updateReservationStatus($id, $status) {
    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    return executeUpdate($sql, "si", $status, $id);
}

// Delete reservation
function deleteReservation($id) {
    return executeDelete("reservations", $id);
}


function save($insertQuery) {
    global $connection;
    $sql = mysqli_query($connection, $insertQuery);
}


function closeConnection() {
    global $connection;
    mysqli_close($connection);
}
?>