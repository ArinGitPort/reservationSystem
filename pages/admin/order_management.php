<?php
session_start();
require_once '../../config/db_model.php';
require_once '../../controllers/OrderController.php';

// Handle AJAX requests through OrderController
if (isset($_POST['action']) || isset($_GET['action'])) {
    OrderController::handleRequest();
    exit; // Ensure script stops after controller sends JSON response
}

// Get filter and search parameters
$statusFilter = $_GET['status'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Get orders based on filters (using db_model functions directly)
if ($searchTerm) {
    $orders = searchOrders($searchTerm, $statusFilter ?: null);
} else {
    $orders = getAllOrders(null, $statusFilter ?: null);
}

// Get dashboard data (using db_model functions directly)
$statusCounts = getOrderStatusCounts();
$todaysData = getTodaysOrders();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Ellen's Food House</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/account_management.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <style>
        /* Order Management Specific Styles */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .dashboard-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: #343a40;
        }
        
        .dashboard-card p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .card-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .icon-pending { color: #ffc107; }
        .icon-revenue { color: #28a745; }
        
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-buttons .btn {
            padding: 0.4rem 0.8rem;
            margin: 0 2px;
            border-radius: 6px;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-shopping-bag me-2"></i>Order Management</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="refreshOrders()">
                    <i class="fas fa-refresh me-2"></i>Refresh
                </button>
            </div>
        </div>
        
        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <div class="card-icon icon-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?= $statusCounts['pending'] ?? 0 ?></h3>
                <p>Pending Orders</p>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon icon-pending">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3><?= array_sum($statusCounts) ?></h3>
                <p>Total Orders</p>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon icon-revenue">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <h3>₱<?= number_format($todaysData['total_revenue'] ?? 0, 2) ?></h3>
                <p>Today's Revenue</p>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Orders</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?= htmlspecialchars($searchTerm) ?>" 
                           placeholder="Search by customer, phone, email, or order ID...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="preparing" <?= $statusFilter === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                        <option value="ready" <?= $statusFilter === 'ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <a href="order_management.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Orders Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <i class="fas fa-shopping-bag"></i>
                                    <h5>No orders found</h5>
                                    <p>Try adjusting your search or filter criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $index => $order): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong>EFH-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <?php if ($order['customer_email']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-<?= $order['order_type'] === 'dine-in' ? 'utensils' : ($order['order_type'] === 'takeout' ? 'shopping-bag' : 'truck') ?> me-1"></i>
                                            <?= ucfirst(str_replace('-', ' ', $order['order_type'])) ?>
                                        </span>
                                    </td>
                                    <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                                    <td>
                                        <select class="form-select form-select-sm status-select" 
                                                data-order-id="<?= $order['order_id'] ?>" 
                                                data-current-status="<?= $order['order_status'] ?>">
                                            <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="preparing" <?= $order['order_status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                            <option value="ready" <?= $order['order_status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                            <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($order['order_date'])) ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($order['order_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-dark" 
                                                    onclick="viewOrderDetails(<?= $order['order_id'] ?>)" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/order_management.js"></script>
    <script>
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>
</body>
</html>