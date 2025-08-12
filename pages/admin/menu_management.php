<?php
require_once '../../config/db_connect.php';
require_once '../../config/db_model.php';

// Handle form submissions
$message = '';
$messageType = '';

// Handle GET parameters for messages (after redirect)
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

            case 'add_menu_item':
                $name = $_POST['name'];
                $price = $_POST['price'];
                $isBestSeller = isset($_POST['is_best_seller']) ? 1 : 0;
                
                // Prepare data for insertion
                $menuData = [
                    'name' => $name,
                    'price' => $price,
                    'image_path' => '',
                    'is_best_seller' => $isBestSeller
                ];
                
                // Insert menu item with automatic image handling
                $menuId = save('menu', $menuData, 'menu_image', '../../uploads/menu/');
                
                if ($menuId) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Menu item added successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to save menu item to database.", "error");
                }
                break;
                
            case 'edit_menu_item':
                $menuId = $_POST['menu_id'];
                $name = $_POST['name'];
                $price = $_POST['price'];
                $isBestSeller = isset($_POST['is_best_seller']) ? 1 : 0;
                
                // Prepare update data
                $updateData = [
                    'name' => $name,
                    'price' => $price,
                    'is_best_seller' => $isBestSeller
                ];
                
                // Handle file upload if new image provided
                if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] == 0) {
                    // Get old image to delete it if extension changed
                    $oldItem = selectOne("SELECT image_path FROM menu WHERE menu_id = ?", "i", $menuId);
                    
                    $extension = strtolower(pathinfo($_FILES['menu_image']['name'], PATHINFO_EXTENSION));
                    $newFilename = $menuId . '.' . $extension;
                    
                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                    $fileType = $_FILES['menu_image']['type'];
                    if (in_array($fileType, $allowedTypes)) {
                        // Create directory if it doesn't exist
                        if (!is_dir('../../uploads/menu/')) {
                            mkdir('../../uploads/menu/', 0755, true);
                        }
                        
                        // Delete old image if it has different extension
                        if ($oldItem && $oldItem['image_path'] && $oldItem['image_path'] !== $newFilename) {
                            $oldImagePath = "../../uploads/menu/" . $oldItem['image_path'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        
                        // Move uploaded file
                        if (move_uploaded_file($_FILES['menu_image']['tmp_name'], '../../uploads/menu/' . $newFilename)) {
                            // Add image path to update data
                            $updateData['image_path'] = $newFilename;
                        }
                    }
                }
                
                // Update menu item using generic update function
                if (update('menu', $updateData, "menu_id = $menuId")) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Menu item updated successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to update menu item.", "error");
                }
                break;
                
            case 'delete_menu_item':
                $menuId = $_POST['menu_id'];
                
                // Get image filename from DB using generic selectOne function
                $item = selectOne("SELECT image_path FROM menu WHERE menu_id = ?", "i", $menuId);
                $imagePath = $item ? $item['image_path'] : '';
                
                // Delete from database using generic delete function
                if (delete('menu', $menuId, 'menu_id')) {
                    // Delete image file if exists using simple unlink
                    if ($imagePath) {
                        $filePath = "../../uploads/menu/" . $imagePath;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    redirect_with_message($_SERVER['PHP_SELF'], "Menu item deleted successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to delete menu item.", "error");
                }
                break;
        }
    }
}

// Get all menu items for display using generic fetch function
$menuItems = fetch('menu', '', 'menu_id ASC');
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
        
        <!-- Menu Management -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-utensils me-2"></i>Menu Management</h3>
            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                <i class="fas fa-plus me-2"></i>Add Menu Item
            </button>
        </div>
        
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>IMAGE</th>
                    <th>NAME</th>
                    <th>PRICE</th>
                    <th>BEST SELLER</th>
                    <th>MANAGE</th>
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
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                    </td>
                    <td>
                        <span class="fw-bold text-primary">₱<?php echo number_format($item['price'], 2); ?></span>
                    </td>
                    <td>
                        <?php if ($item['is_best_seller']): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-star me-1"></i>Best Seller
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Regular</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)"
                                    data-bs-toggle="modal" data-bs-target="#editMenuModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="confirmDelete('<?php echo $item['menu_id']; ?>', '<?php echo htmlspecialchars($item['image_path']); ?>')"
                                    data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
    
    <script>
        function editMenuItem(item) {
            document.getElementById('edit_menu_id').value = item.menu_id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_is_best_seller').checked = item.is_best_seller == 1;
        }
        
        function confirmDelete(menuId, imagePath) {
            document.getElementById('deleteMenuId').value = menuId;
            document.getElementById('deleteImagePath').value = imagePath;
        }
        
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>
