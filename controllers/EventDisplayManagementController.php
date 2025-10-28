<?php
/**
 * Event Display Management Controller
 * Handles all backend logic and data processing for event banner management
 * 
 * This file contains NO HTML/view logic - only controller/business logic
 */

require_once __DIR__ . '/../models/db_model.php';
require_once __DIR__ . '/ControllerHelper.php';

class EventDisplayManagementController {
    private $bannerTable = 'banners';
    private $bannerImageField = 'banner_image';
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'upload_banner':
                    return $this->uploadBanner();
                case 'toggle_banner':
                    return $this->toggleBanner();
                case 'delete_banner':
                    return $this->deleteBanner();
                case 'edit_banner':
                    return $this->editBanner();
                default:
                    return $this->redirectWithError("Invalid action.");
            }
        }
        return null;
    }
    
    /**
     * Get all banners with automatic expiration handling - DRY: Use generic functions
     */
    public function getAllBanners() {
        // DRY: Auto-deactivate expired events using generic update function
        $this->deactivateExpiredBanners();
        
        // DRY: Use executeQuery for complex banner retrieval with status calculation
        $today = date('Y-m-d');
        $sql = "SELECT *, 
            CASE 
                WHEN event_end_date < ? THEN 'expired'
                WHEN event_start_date <= ? AND event_end_date >= ? THEN 'active'
                WHEN event_start_date > ? THEN 'upcoming'
                ELSE 'unknown'
            END as event_status,
            CASE 
                WHEN filename IS NOT NULL AND filename != '' THEN CONCAT(?, filename)
                ELSE ''
            END as image_path
            FROM {$this->bannerTable} ORDER BY date_uploaded DESC";
        
        $uploadPath = UPLOAD_CONFIG['base_path'] . 'banners/';
        $banners = executeQuery($sql, [$today, $today, $today, $today, $uploadPath], 'sssss');
        
        // DRY: Add image existence check using helper method
        return $this->addImageExistenceInfo($banners);
    }
    
    /**
     * Get banner data for display - DRY: Use executeQuery for consistency
     */
    public function getBannerDisplayData() {
        $sql = "SELECT banner_id, title, description, event_start_date, event_end_date, event_date, 
                       active, date_uploaded, filename
                FROM {$this->bannerTable} ORDER BY date_uploaded DESC";
        
        return executeQuery($sql, [], '');
    }
    
    /**
     * Upload new banner - DRY: Extract validation and use generic save
     */
    private function uploadBanner() {
        $bannerData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'event_start_date' => $_POST['event_start_date'] ?? '',
            'event_end_date' => $_POST['event_end_date'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'filename' => ''
        ];
        
        // DRY: Use validation helper
        $validationErrors = $this->validateBannerData($bannerData);
        if (!empty($validationErrors)) {
            return $this->redirectWithError(implode(' ', $validationErrors));
        }
        
        // DRY: Use generic save with automatic image handling
        $bannerId = save($this->bannerTable, $bannerData, $this->bannerImageField);
        
        return $bannerId 
            ? $this->redirectWithSuccess("Event banner uploaded successfully!")
            : $this->redirectWithError("Failed to save event banner to database.");
    }
    
    /**
     * Toggle banner active status - DRY: Simplified using generic update
     */
    private function toggleBanner() {
        $bannerId = intval($_POST['banner_id'] ?? 0);
        $newStatus = intval($_POST['current_status'] ?? 0) == 1 ? 0 : 1;
        
        if (!$this->isValidId($bannerId)) {
            return $this->redirectWithError("Invalid banner ID.");
        }
        
        $updateData = ['active' => $newStatus];
        $statusText = $newStatus ? 'activated' : 'deactivated';
        
        return update($this->bannerTable, $updateData, "banner_id = $bannerId")
            ? $this->redirectWithSuccess("Banner {$statusText} successfully!")
            : $this->redirectWithError("Failed to update banner status.");
    }
    
    /**
     * Delete banner and associated file - DRY: Use helper for file operations
     */
    private function deleteBanner() {
        $bannerId = intval($_POST['banner_id'] ?? 0);
        $filename = $_POST['filename'] ?? '';
        
        if (!$this->isValidId($bannerId)) {
            return $this->redirectWithError("Invalid banner ID.");
        }
        
        // DRY: Use transaction wrapper helper
        return $this->executeWithTransaction(function() use ($bannerId, $filename) {
            // Delete from database using generic delete function
            if (!delete($this->bannerTable, $bannerId, 'banner_id')) {
                throw new Exception("Failed to delete banner from database.");
            }
            
            // DRY: Use helper for file deletion
            $this->deleteUploadedFile($filename, 'banners');
            
            return "Banner deleted successfully!";
        });
    }
    
    /**
     * Edit existing banner - DRY: Extract validation and use transaction helper
     */
    private function editBanner() {
        $bannerId = intval($_POST['banner_id'] ?? 0);
        $bannerData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'event_start_date' => $_POST['event_start_date'] ?? '',
            'event_end_date' => $_POST['event_end_date'] ?? '',
            'active' => intval($_POST['active'] ?? 0)
        ];
        $currentFilename = $_POST['current_filename'] ?? '';
        
        // DRY: Validate input data
        if (!$this->isValidId($bannerId)) {
            return $this->redirectWithError("Invalid banner ID.");
        }
        
        $validationErrors = $this->validateBannerData($bannerData);
        if (!empty($validationErrors)) {
            return $this->redirectWithError(implode(' ', $validationErrors));
        }
        
        // DRY: Use transaction helper for atomic operation
        return $this->executeWithTransaction(function() use ($bannerId, $bannerData, $currentFilename) {
            // Handle image upload if provided
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
                $uploadResult = $this->handleImageUpload($bannerId, $currentFilename);
                if ($uploadResult['success']) {
                    $bannerData['filename'] = $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            // Update using generic update function
            if (!update($this->bannerTable, $bannerData, "banner_id = $bannerId")) {
                throw new Exception("Failed to update banner in database.");
            }
            
            return "Banner updated successfully!";
        });
    }
    
    /**
     * DRY Helper: Deactivate expired banners
     */
    private function deactivateExpiredBanners() {
        $today = date('Y-m-d');
        update($this->bannerTable, ['active' => 0], "event_end_date < '$today' AND active = 1");
    }
    
    /**
     * DRY Helper: Add image existence information to banner data
     */
    private function addImageExistenceInfo($banners) {
        $uploadPath = UPLOAD_CONFIG['base_path'] . 'banners/';
        
        foreach ($banners as &$banner) {
            $banner['image_exists'] = $banner['filename'] 
                ? file_exists($uploadPath . $banner['filename']) 
                : false;
        }
        
        return $banners;
    }
    
    /**
     * DRY Helper: Validate banner data
     */
    private function validateBannerData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = "Event title is required.";
        }
        
        if (!empty($data['event_start_date']) && !empty($data['event_end_date'])) {
            if ($data['event_end_date'] < $data['event_start_date']) {
                $errors[] = "End date cannot be earlier than start date.";
            }
        }
        
        return $errors;
    }
    
    /**
     * DRY Helper: Check if ID is valid
     */
    private function isValidId($id) {
        return is_numeric($id) && $id > 0;
    }
    
    /**
     * DRY Helper: Execute operation within transaction
     */
    private function executeWithTransaction($operation) {
        beginTransaction();
        
        try {
            $message = $operation();
            commitTransaction();
            return $this->redirectWithSuccess($message);
        } catch (Exception $e) {
            rollbackTransaction();
            return $this->redirectWithError($e->getMessage());
        }
    }
    
    /**
     * DRY Helper: Delete uploaded file using unified delete() function from db_model
     */
    private function deleteUploadedFile($filename, $subfolder) {
        if ($filename) {
            return delete($filename, $subfolder);
        }
        return false;
    }
    
    /**
     * DRY Helper: Handle image upload with validation
     */
    private function handleImageUpload($bannerId, $currentFilename = '') {
        $allowedTypes = UPLOAD_CONFIG['allowed_image_types'];
        $maxSize = UPLOAD_CONFIG['max_file_size'];
        
        // Validate file
        if (!in_array($_FILES['banner_image']['type'], $allowedTypes)) {
            return ['success' => false, 'message' => "Invalid image format. Please use JPG or PNG."];
        }
        
        if ($_FILES['banner_image']['size'] > $maxSize) {
            $maxMB = round($maxSize / (1024 * 1024), 1);
            return ['success' => false, 'message' => "File too large. Maximum size is {$maxMB}MB."];
        }
        
        $extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        $newFilename = $bannerId . "." . $extension;
        $uploadDir = UPLOAD_CONFIG['base_path'] . 'banners/';
        
        // Create directory if needed
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => "Failed to create upload directory."];
            }
        }
        
        // Delete old image if different
        if ($currentFilename && $currentFilename !== $newFilename) {
            $this->deleteUploadedFile($currentFilename, 'banners');
        }
        
        // Upload new image
        if (!move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadDir . $newFilename)) {
            return ['success' => false, 'message' => "Failed to upload file."];
        }
        
        return ['success' => true, 'filename' => $newFilename];
    }
    
    // DRY: Redirect methods now moved to ControllerHelper.php as generic functions
    private function redirectWithSuccess($message) {
        return redirect_with_success($message);
    }
    
    private function redirectWithError($message) {
        return redirect_with_error($message);
    }
}
?>