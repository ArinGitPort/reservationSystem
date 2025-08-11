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

// ===================== HELPER FUNCTIONS =====================

// Generic save function (INSERT with auto-increment ID return)
function save($sql) {
    global $connection;
    $result = mysqli_query($connection, $sql);
    
    if ($result) {
        return mysqli_insert_id($connection);
    } else {
        return false;
    }
}

// Generic update function
function update($sql) {
    global $connection;
    return mysqli_query($connection, $sql);
}

// Generic delete function
function delete($table, $id) {
    global $connection;
    $sql = "DELETE FROM {$table} WHERE id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Generic select single record function
function selectOne($sql, $types = null, ...$params) {
    global $connection;
    
    if ($types) {
        $stmt = mysqli_prepare($connection, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $data;
    } else {
        $result = mysqli_query($connection, $sql);
        return mysqli_fetch_assoc($result);
    }
}

// Generic select multiple records function
function selectAll($sql) {
    global $connection;
    $result = mysqli_query($connection, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
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

// ===================== UTILITY FUNCTIONS =====================

function closeConnection() {
    global $connection;
    mysqli_close($connection);
}
?>
