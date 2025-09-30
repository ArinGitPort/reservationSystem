<?php
header('Content-Type: application/json');

try {
    require_once '../config/db_model.php';
    
    // Test sample order data
    $testOrder = [
        'customer_name' => 'Test User',
        'customer_phone' => '1234567890',
        'customer_email' => 'test@example.com',
        'order_type' => 'pickup',
        'payment_method' => 'cash',
        'cart_items' => [
            [
                'menu_id' => 1,
                'quantity' => 1,
                'price' => 10.99
            ]
        ]
    ];
    
    $result = processOrder($testOrder);
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Fatal Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>