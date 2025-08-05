<?php

function save($sql, $types, ...$params) {
    global $connection;
    $stmt = mysqli_prepare($connection, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        $insertId = mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);
        return $insertId;
    } else {
        mysqli_stmt_close($stmt);
        return false;
    }
}

// Generic update function
function executeUpdate($sql, $types, ...$params) {
    global $connection;
    $stmt = mysqli_prepare($connection, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Generic delete function
function executeDelete($table, $id) {
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
function executeSelectOne($sql, $types = null, ...$params) {
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
function executeSelectAll($sql, $types = null, ...$params) {
    global $connection;
    
    if ($types) {
        $stmt = mysqli_prepare($connection, $sql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $data;
    } else {
        $result = mysqli_query($connection, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }
}
?>
