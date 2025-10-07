<?php

class OrderController
{
    private $model;
    
    public function __construct() {
        require_once __DIR__ . '/../config/db_model.php';
        $this->model = new DbModel();
    }
    
    /**
     * Main request handler for AJAX calls from admin pages
     * Routes actions to appropriate methods
     */
    public static function handleRequest()
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        
        if (!$action) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No action specified']);
            exit;
        }
        
        // Route to appropriate handler based on action
        switch ($action) {
            case 'update_status':
                $orderId = intval($_POST['order_id']);
                $status = $_POST['status'];
                self::updateOrderStatus($orderId, $status);
                break;
                
            case 'delete_order':
                $orderId = intval($_POST['order_id']);
                self::deleteOrder($orderId);
                break;
                
            case 'get_order':
                $orderId = intval($_GET['id'] ?? 0);
                self::getOrderById($orderId);
                break;
                
            default:
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
                exit;
        }
    }
    
    public static function handle()
    {
        $controller = new self();
        return $controller->processNewOrder();
    }
    
    private function processNewOrder()
    {
        // CORS + JSON response headers (duplicate-safe)
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Respond to preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON payload', 'error' => json_last_error_msg()]);
            exit;
        }

        $validation = $this->validateInput($input);
        if ($validation['ok'] === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            exit;
        }

        // Attach session user id if available (session already started in process_order.php)
        if (!empty($_SESSION['user_id'])) {
            // do not overwrite an explicit customer_id in the payload
            if (empty($input['customer_id'])) {
                $input['customer_id'] = $_SESSION['user_id'];
            }
        }

        try {
            $result = $this->model->processOrder($input);

            if (!is_array($result)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Invalid response from model']);
                exit;
            }

            if (isset($result['success']) && $result['success'] === false) {
                http_response_code(400);
            } else {
                http_response_code(200);
            }

            echo json_encode($result);
        } catch (Exception $e) {
            error_log('OrderController error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Basic, non-opinionated validation for an order payload.
     * Adjust as needed to match processOrder() expectation.
     *
     * Expected minimal shape: ['cart_items' => array([...])]
     */
    private function validateInput($input)
    {
        if (!is_array($input)) {
            return ['ok' => false, 'message' => 'Request body must be a JSON object'];
        }

        if (empty($input['cart_items']) || !is_array($input['cart_items'])) {
            return ['ok' => false, 'message' => 'Missing or invalid "cart_items" array'];
        }

        if (count($input['cart_items']) === 0) {
            return ['ok' => false, 'message' => 'Order must contain at least one item'];
        }

        // basic per-item checks
        foreach ($input['cart_items'] as $i => $item) {
            if (!is_array($item)) {
                return ['ok' => false, 'message' => "Each item must be an object (index: $i)"];
            }
            if (empty($item['menu_id'])) {
                return ['ok' => false, 'message' => "Each item must include a 'menu_id' (index: $i)"];
            }
            if (empty($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] < 1) {
                return ['ok' => false, 'message' => "Each item must include a valid 'quantity' (index: $i)"];
            }
        }

        return ['ok' => true, 'message' => 'ok'];
    }

    /**
     * Get all orders with optional filtering
     * Usage: OrderController::getAllOrders($limit, $status)
     */
    public static function getAllOrders($limit = null, $status = null)
    {
        header('Content-Type: application/json');

        try {
            $orders = getAllOrders($limit, $status);
            
            echo json_encode([
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get order by ID with order items
     * Usage: OrderController::getOrderById($orderId)
     */
    public static function getOrderById($orderId)
    {
        header('Content-Type: application/json');

        if (empty($orderId) || !is_numeric($orderId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        try {
            $order = getOrderById($orderId);
            
            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            $items = getOrderItemsById($orderId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'items' => $items
                ]
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch order',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Update order status
     * Usage: OrderController::updateOrderStatus($orderId, $status)
     */
    public static function updateOrderStatus($orderId, $status)
    {
        header('Content-Type: application/json');

        if (empty($orderId) || !is_numeric($orderId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid order status. Valid statuses: ' . implode(', ', $validStatuses)
            ]);
            exit;
        }

        try {
            $result = updateOrderStatus($orderId, $status);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Order status updated successfully'
                ]);
                exit;
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update order status'
                ]);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating order status',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Delete order
     * Usage: OrderController::deleteOrder($orderId)
     */
    public static function deleteOrder($orderId)
    {
        header('Content-Type: application/json');

        if (empty($orderId) || !is_numeric($orderId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        try {
            // Delete order (will cascade to order_items due to foreign key)
            $result = delete('orders', $orderId, 'order_id');
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Order deleted successfully'
                ]);
                exit;
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete order'
                ]);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting order',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
}
