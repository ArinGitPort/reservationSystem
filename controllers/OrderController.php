<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle different calling contexts (direct vs from admin pages)
$configPath = file_exists('../config/db_model.php') ? '../config/db_model.php' : '../../config/db_model.php';
require_once $configPath;

class OrderController {
    
    /**
     * Handle API requests (for cart checkout)
     */
    public static function handle() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::processCartCheckout();
        } else {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
    }

    /**
     * Handle AJAX requests for order management
     */
    public static function handleRequest() {
        header('Content-Type: application/json');
        
        try {
            // Check if database connection exists
            if (!isset($GLOBALS['connection'])) {
                echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                return;
            }
            
            $action = $_POST['action'] ?? $_GET['action'] ?? '';
            
            switch($action) {
                case 'test_connection':
                    self::testConnection();
                    break;
                case 'get_dashboard_data':
                    self::getDashboardData();
                    break;
                case 'get_orders':
                    self::getOrders();
                    break;
                case 'update_order_status':
                    self::updateOrderStatus();
                    break;
                case 'get_order_details':
                    self::getOrderDetails();
                    break;
                case 'cancel_order':
                    self::cancelOrder();
                    break;
                case 'delete_order':
                    self::deleteOrder();
                    break;
                case 'process_checkout':
                    self::processCartCheckout();
                    break;
                default:
                    echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Controller error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Test database connection
     */
    private static function testConnection() {
        try {
            $db = require_once __DIR__ . '/../config/db_model.php';
            
            // Test basic connection
            $result = fetch("SELECT 1 as test");
            
            // Test orders table
            $orders = fetch("SELECT COUNT(*) as count FROM orders");
            
            echo json_encode([
                'success' => true,
                'message' => 'Connection successful',
                'data' => [
                    'connection_test' => $result,
                    'orders_count' => $orders
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    private static function getDashboardData() {
        try {
            $statusCounts = [];
            $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
            
            foreach ($statuses as $status) {
                $orders = fetch('orders', "order_status = '$status'");
                $statusCounts[$status] = $orders ? count($orders) : 0;
            }
            
            $todayOrders = fetch('orders', "DATE(order_date) = CURDATE()");
            $todaysData = [
                'order_count' => $todayOrders ? count($todayOrders) : 0,
                'total_revenue' => $todayOrders ? array_sum(array_column($todayOrders, 'total_amount')) : 0
            ];
            
            echo json_encode(['success' => true, 'statusCounts' => $statusCounts, 'todaysData' => $todaysData]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get orders list with filtering - using db_model fetch()
     */
    private static function getOrders() {
        try {
            $searchTerm = $_GET['search'] ?? '';
            $statusFilter = $_GET['status'] ?? '';
            
            $conditions = [];
            if ($searchTerm) {
                $searchTerm = mysqli_real_escape_string($GLOBALS['connection'], $searchTerm);
                $conditions[] = "(customer_name LIKE '%$searchTerm%' OR customer_email LIKE '%$searchTerm%' OR customer_phone LIKE '%$searchTerm%')";
            }
            if ($statusFilter) {
                $conditions[] = "order_status = '" . mysqli_real_escape_string($GLOBALS['connection'], $statusFilter) . "'";
            }
            
            $whereClause = implode(' AND ', $conditions);
            $orders = fetch('orders', $whereClause, 'order_date DESC');
            
            echo json_encode(['success' => true, 'orders' => $orders ? $orders : []]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update order status - using db_model update()
     */
    private static function updateOrderStatus() {
        $orderId = $_POST['order_id'] ?? 0;
        $newStatus = $_POST['status'] ?? '';
        
        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
        if (!$orderId || !$newStatus || !in_array($newStatus, $validStatuses)) {
            echo json_encode(['error' => 'Invalid order ID or status']);
            return;
        }
        
        $result = update('orders', ['order_status' => $newStatus], "order_id = $orderId");
        echo json_encode($result ? ['success' => true] : ['error' => 'Update failed']);
    }
    
    /**
     * Get order details - using db_model fetch()
     */
    private static function getOrderDetails() {
        $orderId = $_GET['order_id'] ?? 0;
        if (!$orderId) {
            echo json_encode(['error' => 'Order ID required']);
            return;
        }
        
        try {
            // Get order details using DRY fetch() function
            $order = fetch('orders', "order_id = $orderId");
            if (!$order) {
                echo json_encode(['error' => 'Order not found']);
                return;
            }
            
            // Get order items using DRY fetch() function
            $orderItems = fetch('order_items', "order_id = $orderId");
            $items = [];
            
            // Enhance order items with menu details using DRY fetch()
            if ($orderItems) {
                foreach ($orderItems as $item) {
                    $menuDetails = fetch('menu', "menu_id = " . $item['menu_id']);
                    if ($menuDetails) {
                        $item['name'] = $menuDetails[0]['name'];
                        $item['menu_price'] = $menuDetails[0]['price']; // Avoid conflicts with order_items price
                        $item['image'] = $menuDetails[0]['image_path'] ?? '';
                    }
                    $items[] = $item;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'order' => $order[0], 
                'items' => $items,
                'can_cancel' => strtoupper($order[0]['order_status']) === 'PENDING',
                'can_delete' => strtoupper($order[0]['order_status']) === 'CANCELLED'
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to fetch order details: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel order - using DRY update() function
     */
    private static function cancelOrder() {
        $orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? 0;
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            return;
        }
        
        try {
            // Check if order exists and is cancellable (PENDING status)
            $order = fetch('orders', "order_id = $orderId");
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                return;
            }
            
            if (strtoupper($order[0]['order_status']) !== 'PENDING') {
                echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled']);
                return;
            }
            
            // Update order status to CANCELLED using DRY update() function
            $result = update('orders', ['order_status' => 'cancelled'], "order_id = $orderId");
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete order - using DRY delete() function
     */
    private static function deleteOrder() {
        $orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? 0;
        
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            return;
        }
        
        try {
            // Check if order exists and is deletable (CANCELLED status)
            $order = fetch('orders', "order_id = $orderId");
            if (!$order) {
                echo json_encode(['success' => false, 'message' => "Order not found"]);
                return;
            }
            
            $orderStatus = strtolower($order[0]['order_status']);
            if ($orderStatus !== 'cancelled') {
                echo json_encode(['success' => false, 'message' => "Only cancelled orders can be deleted. Please cancel this order first."]);
                return;
            }
            
            // Delete order items first (foreign key constraint)
            global $connection;
            
            $deleteItemsQuery = "DELETE FROM order_items WHERE order_id = ?";
            $stmt = mysqli_prepare($connection, $deleteItemsQuery);
            
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order items']);
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "i", $orderId);
            $itemsDeleteResult = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if (!$itemsDeleteResult) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order items']);
                return;
            }
            
            // Delete the main order
            $deleteOrderQuery = "DELETE FROM orders WHERE order_id = ?";
            $orderStmt = mysqli_prepare($connection, $deleteOrderQuery);
            
            if (!$orderStmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
                return;
            }
            
            mysqli_stmt_bind_param($orderStmt, "i", $orderId);
            $result = mysqli_stmt_execute($orderStmt);
            $orderAffectedRows = mysqli_stmt_affected_rows($orderStmt);
            mysqli_stmt_close($orderStmt);
            
            if ($result && $orderAffectedRows > 0) {
                echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting order: ' . $e->getMessage()]);
        }
    }
    

    
    /**
     * Process cart checkout - using db_model save()
     */
    private static function processCartCheckout() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Basic validation
        $required = ['customer_name', 'customer_phone', 'order_type', 'cart_items', 'total_amount'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing: $field"]);
                return;
            }
        }
        
        if (!in_array($input['order_type'], ['dine-in', 'takeout', 'delivery'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid order type']);
            return;
        }
        
        // Get or create customer
        $customerId = self::getOrCreateCustomer($input);
        
        // Create order using your save() function
        $orderData = [
            'customer_id' => $customerId,
            'total_amount' => $input['total_amount'],
            'order_status' => 'pending',
            'order_type' => $input['order_type'],
            'customer_name' => $input['customer_name'],
            'customer_phone' => $input['customer_phone'],
            'customer_email' => $input['customer_email'] ?? '',
            'delivery_address' => $input['delivery_address'] ?? '',
            'special_instructions' => $input['special_instructions'] ?? ''
        ];
        
        $orderId = save('orders', $orderData);
        
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Failed to create order']);
            return;
        }
        
        // Add order items using your save() function
        foreach ($input['cart_items'] as $item) {
            save('order_items', [
                'order_id' => $orderId,
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity']
            ]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order placed successfully!',
            'order_id' => $orderId,
            'order_number' => 'EFH-' . str_pad($orderId, 6, '0', STR_PAD_LEFT)
        ]);
    }
    
    /**
     * Get or create customer - using db_model fetch() and save()
     */
    private static function getOrCreateCustomer($input) {
        if (!empty($input['customer_email'])) {
            $existing = fetch('customers', "email = '" . mysqli_real_escape_string($GLOBALS['connection'], $input['customer_email']) . "'");
            if ($existing) return $existing[0]['id'];
        }
        
        return save('customers', [
            'first_name' => $input['customer_name'],
            'last_name' => '', 
            'email' => $input['customer_email'] ?? 'guest@ellenfoodhouse.com',
            'phone' => $input['customer_phone']
        ]);
    }
}

// Handle direct requests to this file
if (strpos($_SERVER['SCRIPT_NAME'], 'OrderController.php') !== false || strpos($_SERVER['REQUEST_URI'], 'OrderController.php') !== false) {
    // Set CORS headers for frontend requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Check if this is a cart checkout request (JSON data) or admin action request
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);
    
    if ($jsonData && !isset($jsonData['action'])) {
        // This is a cart checkout request (JSON without action parameter)
        OrderController::handle();
    } elseif (isset($_POST['action']) || isset($_GET['action'])) {
        // This is an admin panel request
        OrderController::handleRequest();
    } else {
        // Default to cart checkout for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            OrderController::handle();
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'No valid action found',
                'method' => $_SERVER['REQUEST_METHOD'],
                'get_params' => $_GET,
                'post_params' => $_POST,
                'script_name' => $_SERVER['SCRIPT_NAME'],
                'request_uri' => $_SERVER['REQUEST_URI']
            ]);
        }
    }
}
?>