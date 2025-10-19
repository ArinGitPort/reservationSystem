<?php
// Include the MenuManagementController
require_once '../../controllers/MenuManagementController.php';
require_once '../../includes/search_filter.php';

// Initialize controller and handle requests
$menuController = new MenuManagementController();
$menuController->handleRequest();

// Handle GET parameters for messages (after redirect)
$message = '';
$messageType = '';

if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $messageType = $_GET['type'];
    
    // Clear the URL parameters to prevent message showing on refresh
    echo "<script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>";
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$bestSeller = $_GET['best_seller'] ?? 'all';

// Get data for display
$menuItems = $menuController->getAllMenuItems($search, 'all', $bestSeller);
$stats = $menuController->getMenuStatistics();

// Prepare data for PDF export
$pdfData = [];
foreach ($menuItems as $item) {
    $pdfData[] = [
        'ID' => $item['menu_id'],
        'Name' => $item['name'],
        'Price' => '₱' . number_format($item['price'], 2),
        'Best Seller' => $item['is_best_seller'] ? 'Yes' : 'No',
        'Image' => $item['image_path'] ? 'Yes' : 'No'
    ];
}

// SQL Query for display_all function (legacy support)
$menuQuery = "SELECT menu_id, name, price, image_path, is_best_seller FROM menu ORDER BY name ASC";
$columnMappings = []; // Not used for menu_management special handling
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Ellen's Food House</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/account_management.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-utensils me-2"></i>Menu Management</h3>
            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                <i class="fas fa-plus me-2"></i>Add Menu Item
            </button>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $stats['total_items']; ?></h4>
                                <p class="mb-0">Total Items</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-utensils fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $stats['best_sellers']; ?></h4>
                                <p class="mb-0">Best Sellers</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-star fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">₱<?php echo number_format($stats['average_price'], 2); ?></h4>
                                <p class="mb-0">Average Price</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calculator fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">₱<?php echo number_format($stats['min_price'], 2); ?> - ₱<?php echo number_format($stats['max_price'], 2); ?></h4>
                                <p class="mb-0">Price Range</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <?php 
        renderSearchFilter([
            'placeholder' => 'Search by menu item name or price...',
            'search_label' => 'Search Menu Items',
            'filters' => [
                'best_seller' => [
                    'label' => 'Best Seller Filter',
                    'type' => 'select',
                    'options' => [
                        'all' => 'All Items',
                        'yes' => 'Best Sellers Only',
                        'no' => 'Regular Items Only'
                    ],
                    'selected' => $bestSeller
                ]
            ],
            'additional_buttons' => [
                [
                    'text' => 'Export PDF',
                    'icon' => 'fas fa-file-pdf',
                    'class' => 'btn-outline-danger',
                    'type' => 'pdf_export',
                    'data' => $pdfData,
                    'report_title' => 'Menu Items Report',
                    'company_name' => 'Ellen\'s Food House',
                    'stats' => [
                        'total_items' => ['label' => 'Total Items', 'value' => $stats['total_items']],
                        'best_sellers' => ['label' => 'Best Sellers', 'value' => $stats['best_sellers']],
                        'average_price' => ['label' => 'Average Price', 'value' => '₱' . number_format($stats['average_price'], 2)]
                    ]
                ]
            ]
        ]);
        ?>
        
        <!-- Menu Items Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Menu Items 
                    <span class="badge bg-secondary ms-2"><?php echo count($menuItems); ?> items</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($menuItems)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No menu items found matching your criteria.</p>
                        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                            <i class="fas fa-plus me-2"></i>Add First Menu Item
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">IMAGE</th>
                                    <th width="35%">NAME</th>
                                    <th width="15%">PRICE</th>
                                    <th width="15%">BEST SELLER</th>
                                    <th width="15%">MANAGE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menuItems as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php if ($item['image_path']): ?>
                                            <img src="../../uploads/menu/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px; border-radius: 4px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">₱<?php echo number_format($item['price'], 2); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($item['is_best_seller']): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-star me-1"></i>Best Seller
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#editMenuModal"
                                                    onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                                    onclick="confirmDelete(<?php echo $item['menu_id']; ?>, '<?php echo htmlspecialchars($item['image_path']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- End Main Content -->
    
    <!-- Add Menu Item Modal -->
    <div class="modal fade" id="addMenuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_menu_item">
                        <div class="mb-3">
                            <label for="name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="menu_image" class="form-label">Menu Item Image</label>
                            <input type="file" class="form-control" name="menu_image" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Accepted formats: JPG, PNG. Max size: 2MB (Optional)</div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="is_best_seller" value="1">
                            <label class="form-check-label">Mark as Best Seller</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Menu Item Modal -->
    <div class="modal fade" id="editMenuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_menu_item">
                        <input type="hidden" name="menu_id" id="edit_menu_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_menu_image" class="form-label">Menu Item Image</label>
                            <input type="file" class="form-control" name="menu_image" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Leave empty to keep current image. Accepted formats: JPG, PNG. Max size: 2MB</div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="is_best_seller" value="1" id="edit_is_best_seller">
                            <label class="form-check-label">Mark as Best Seller</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this menu item? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="action" value="delete_menu_item">
                        <input type="hidden" name="menu_id" id="deleteMenuId">
                        <input type="hidden" name="image_path" id="deleteImagePath">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Menu Management JS -->
    <script src="../../assets/js/menu_management.js"></script>
</body>
</html>
