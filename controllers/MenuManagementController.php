<?php
$configPath = file_exists('../models/db_model.php') ? '../models/db_model.php' : '../../models/db_model.php';
require_once $configPath;
require_once __DIR__ . '/ControllerHelper.php';

class MenuManagementController {
    private $menuTable = 'menu';
    
    /**
     * Handle all POST requests for menu management
     */
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_menu_item':
                    return $this->addMenuItem();
                case 'edit_menu_item':
                    return $this->editMenuItem();
                case 'delete_menu_item':
                    return $this->deleteMenuItem();
                case 'bulk_delete':
                    return $this->bulkDeleteMenuItems();
                default:
                    return $this->redirectWithError("Invalid action.");
            }
        }
        return null;
    }
    
    /**
     * Get all menu items with optional filtering - using DRY fetch() function
     */
    public function getAllMenuItems($search = '', $category = 'all', $bestSeller = 'all') {
        $conditions = [];
        
        // Search functionality - simple string building for fetch()
        if (!empty($search)) {
            $searchEscaped = str_replace("'", "''", $search);
            $conditions[] = "(name LIKE '%$searchEscaped%' OR CAST(price AS CHAR) LIKE '%$searchEscaped%')";
        }
        
        // Category filter
        if ($category !== 'all' && !empty($category)) {
            $categoryEscaped = str_replace("'", "''", $category);
            $conditions[] = "category = '$categoryEscaped'";
        }
        
        // Best seller filter
        if ($bestSeller !== 'all') {
            $bestSellerValue = ($bestSeller === 'yes') ? 1 : 0;
            $conditions[] = "is_best_seller = $bestSellerValue";
        }
        
        $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '';
        
        // Use simple fetch() function - much better than complex executeQuery()
        return fetch($this->menuTable, $whereClause, 'name ASC');
    }
    
    /**
     * Get menu statistics for dashboard/reporting
     */
    public function getMenuStatistics() {
        $totalItems = count(fetch($this->menuTable));
        $bestSellers = count(fetch($this->menuTable, 'is_best_seller = 1'));
        
        // Use executeQuery() for price statistics instead of direct queries
        $sql = "SELECT AVG(price) as avg_price FROM {$this->menuTable}";
        $avgPriceResult = executeQuery($sql, [], '');
        $avgPrice = $avgPriceResult ? $avgPriceResult[0]['avg_price'] : 0;
        
        // Get price range using executeQuery()
        $sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM {$this->menuTable}";
        $priceRangeResult = executeQuery($sql, [], '');
        $priceRange = $priceRangeResult ? $priceRangeResult[0] : ['min_price' => 0, 'max_price' => 0];
        
        return [
            'total_items' => $totalItems,
            'best_sellers' => $bestSellers,
            'regular_items' => $totalItems - $bestSellers,
            'average_price' => round($avgPrice, 2),
            'min_price' => $priceRange['min_price'] ?? 0,
            'max_price' => $priceRange['max_price'] ?? 0
        ];
    }
    
    /**
     * Add new menu item - using DRY save() function
     */
    private function addMenuItem() {
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $isBestSeller = isset($_POST['is_best_seller']) ? 1 : 0;
        
        // Validate input
        if (empty($name) || $price < 0) {
            return $this->redirectWithError("Please provide valid menu item details.");
        }
        
        // Check for duplicate names using executeQuery with parameterized query
        $sql = "SELECT * FROM {$this->menuTable} WHERE name = ?";
        $existingItem = executeQuery($sql, [$name], 's');
        if (!empty($existingItem)) {
            return $this->redirectWithError("A menu item with this name already exists.");
        }
        
        // Prepare data for insertion
        $menuData = [
            'name' => $name,
            'price' => $price,
            'image_path' => '',
            'is_best_seller' => $isBestSeller
        ];
        
        // Handle image upload
        $imagePath = $this->handleImageUpload('menu_image');
        if ($imagePath !== false) {
            $menuData['image_path'] = $imagePath;
        }
        
        // Insert using DRY save() function
        $menuId = save($this->menuTable, $menuData);
        
        if ($menuId) {
            // If image was uploaded but save failed to handle it, handle it manually
            if ($imagePath === false && isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] == 0) {
                $this->updateMenuImage($menuId, 'menu_image');
            }
            return $this->redirectWithSuccess("Menu item added successfully!");
        } else {
            return $this->redirectWithError("Failed to save menu item to database.");
        }
    }
    
    /**
     * Edit existing menu item - using DRY update() function
     */
    private function editMenuItem() {
        $menuId = intval($_POST['menu_id']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $isBestSeller = isset($_POST['is_best_seller']) ? 1 : 0;
        
        // Validate input
        if (empty($name) || $price < 0 || $menuId <= 0) {
            return $this->redirectWithError("Please provide valid menu item details.");
        }
        
        // Check if menu item exists
        $existingItem = fetch($this->menuTable, "menu_id = $menuId");
        if (empty($existingItem)) {
            return $this->redirectWithError("Menu item not found.");
        }
        
        // Check for duplicate names using executeQuery to avoid current item
        $sql = "SELECT * FROM {$this->menuTable} WHERE name = ? AND menu_id != ?";
        $duplicateCheck = executeQuery($sql, [$name, $menuId], 'si');
        
        if (!empty($duplicateCheck)) {
            return $this->redirectWithError("A menu item with this name already exists.");
        }
        
        // Prepare update data
        $updateData = [
            'name' => $name,
            'price' => $price,
            'is_best_seller' => $isBestSeller
        ];
        
        // Handle image upload if new image provided
        if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] == 0) {
            $imagePath = $this->handleImageUpload('menu_image', $menuId);
            if ($imagePath !== false) {
                // Delete old image
                $oldImagePath = $existingItem[0]['image_path'];
                if ($oldImagePath && $oldImagePath !== $imagePath) {
                    $this->deleteImageFile($oldImagePath);
                }
                $updateData['image_path'] = $imagePath;
            }
        }
        
        // Update using DRY update() function
        if (update($this->menuTable, $updateData, "menu_id = $menuId")) {
            return $this->redirectWithSuccess("Menu item updated successfully!");
        } else {
            return $this->redirectWithError("Failed to update menu item.");
        }
    }
    
    /**
     * Delete menu item - using DRY delete() function
     */
    private function deleteMenuItem() {
        $menuId = intval($_POST['menu_id']);
        
        if ($menuId <= 0) {
            return $this->redirectWithError("Invalid menu item ID.");
        }
        
        // Get item data to delete associated image
        $itemData = fetch($this->menuTable, "menu_id = $menuId");
        if (empty($itemData)) {
            return $this->redirectWithError("Menu item not found.");
        }
        
        $imagePath = $itemData[0]['image_path'];
        
        // Delete from database using DRY delete() function
        if (delete($this->menuTable, $menuId, 'menu_id')) {
            // Delete associated image file
            if ($imagePath) {
                $this->deleteImageFile($imagePath);
            }
            return $this->redirectWithSuccess("Menu item deleted successfully!");
        } else {
            return $this->redirectWithError("Failed to delete menu item.");
        }
    }
    
    /**
     * Bulk delete menu items
     */
    private function bulkDeleteMenuItems() {
        if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
            return $this->redirectWithError("No items selected for deletion.");
        }
        
        $selectedIds = $_POST['selected_items'];
        $deletedCount = 0;
        $errors = [];
        
        foreach ($selectedIds as $menuId) {
            $menuId = intval($menuId);
            if ($menuId <= 0) continue;
            
            // Get item data for image deletion
            $itemData = fetch($this->menuTable, "menu_id = $menuId");
            if (!empty($itemData)) {
                $imagePath = $itemData[0]['image_path'];
                
                // Delete from database
                if (delete($this->menuTable, $menuId, 'menu_id')) {
                    $deletedCount++;
                    // Delete associated image
                    if ($imagePath) {
                        $this->deleteImageFile($imagePath);
                    }
                } else {
                    $errors[] = "Failed to delete item ID: $menuId";
                }
            }
        }
        
        if ($deletedCount > 0) {
            $message = "$deletedCount menu item(s) deleted successfully!";
            if (!empty($errors)) {
                $message .= " " . implode(", ", $errors);
            }
            return $this->redirectWithSuccess($message);
        } else {
            return $this->redirectWithError("Failed to delete menu items: " . implode(", ", $errors));
        }
    }
    
    /**
     * DRY: Handle image upload with validation using config constants
     */
    private function handleImageUpload($fileInputName, $menuId = null) {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== 0) {
            return false;
        }
        
        $file = $_FILES[$fileInputName];
        $allowedTypes = UPLOAD_CONFIG['allowed_image_types'];
        $maxSize = UPLOAD_CONFIG['max_file_size'];
        $uploadPath = UPLOAD_CONFIG['base_path'] . 'menu/';
        
        // DRY: Validate using config constants
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Generate filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($menuId) {
            $filename = $menuId . '.' . $extension;
        } else {
            // For new items, generate temporary filename
            $filename = 'temp_' . time() . '.' . $extension;
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
            return $filename;
        }
        
        return false;
    }
    
    /**
     * DRY: Update menu item image after initial save using generic update
     */
    private function updateMenuImage($menuId, $fileInputName) {
        $imagePath = $this->handleImageUpload($fileInputName, $menuId);
        if ($imagePath !== false) {
            update($this->menuTable, ['image_path' => $imagePath], "menu_id = $menuId");
        }
    }
    
    /**
     * DRY: Delete image file using unified delete() function from db_model
     */
    private function deleteImageFile($imagePath) {
        if ($imagePath) {
            return delete($imagePath, 'menu');
        }
        return false;
    }
    
    /**
     * Get menu item by ID - using DRY fetch() function
     */
    public function getMenuItemById($menuId) {
        $items = fetch($this->menuTable, "menu_id = " . intval($menuId));
        return !empty($items) ? $items[0] : null;
    }
    
    /**
     * Search menu items for AJAX requests
     */
    public function searchMenuItems() {
        $search = $_GET['search'] ?? '';
        $bestSeller = $_GET['best_seller'] ?? 'all';
        
        $menuItems = $this->getAllMenuItems($search, 'all', $bestSeller);
        
        header('Content-Type: application/json');
        echo json_encode($menuItems);
        exit;
    }
    
    // DRY: Redirect methods now use generic functions from ControllerHelper.php
    private function redirectWithSuccess($message) {
        return redirect_with_success($message);
    }
    
    private function redirectWithError($message) {
        return redirect_with_error($message);
    }
}
?>