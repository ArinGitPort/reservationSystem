<?php
session_start();
// This is a pure VIEW file - no backend logic here
// All data fetching is handled via AJAX calls to OrderController.php
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
    <link rel="stylesheet" href="../../assets/css/order_management.css">
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
        </div>
        
        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <div class="card-icon icon-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-content">
                    <h3 id="pending-count">0</h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon icon-pending">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="card-content">
                    <h3 id="total-orders">0</h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon icon-revenue">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div class="card-content">
                    <h3 id="today-revenue">₱0.00</h3>
                    <p>Today's Revenue</p>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Component -->
        <?php
        include '../../includes/search_filter.php';
        renderSearchFilter([
            'placeholder' => 'Search by customer, phone, email, or order ID...',
            'search_label' => 'Search Orders',
            'filters' => [
                'status' => [
                    'label' => 'Filter by Status',
                    'type' => 'select',
                    'options' => [
                        '' => 'All Statuses',
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'preparing' => 'Preparing',
                        'ready' => 'Ready',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled'
                    ]
                ],
                'date' => [
                    'label' => 'Order Date',
                    'type' => 'date'
                ]
            ],
            'additional_buttons' => [
                [
                    'text' => 'Export',
                    'icon' => 'fas fa-download',
                    'class' => 'btn-outline-success',
                    'onclick' => 'exportOrders()'
                ]
            ],
            'clear_function' => 'clearFilters()',
            'refresh_function' => 'refreshOrders()'
        ]);
        ?>
        
        
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
                    <tbody id="orders-table-body">
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div class="empty-content">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <h5>Loading orders...</h5>
                                    <p>Please wait while we fetch your order data.</p>
                                </div>
                            </td>
                        </tr>
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
                <div class="modal-footer" id="orderDetailsFooter">
                    <!-- Action buttons will be loaded here based on order status -->
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="confirmModalHeader">
                    <h5 class="modal-title" id="confirmModalTitle">
                        <i class="fas fa-exclamation-circle me-2"></i>Confirm Action
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    Are you sure you want to proceed with this action?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmModalButton">
                        <i class="fas fa-check me-1"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Order Management JavaScript -->
    <script src="../../assets/js/order_management.js"></script>
</body>
</html>