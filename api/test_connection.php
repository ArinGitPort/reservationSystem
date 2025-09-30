<?php
header('Content-Type: application/json');

try {
    require_once '../config/db_model.php';
    
    // Test database connection
    global $connection;
    
    if ($connection) {
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'connection_status' => 'Connected'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'connection_status' => 'Failed'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>