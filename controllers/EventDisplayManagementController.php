<?php
/**
 * Event Display Management Controller
 * Handles all backend logic and data processing for event banner management
 * 
 * This file contains NO HTML/view logic - only controller/business logic
 */

require_once __DIR__ . '/../config/db_model.php';
require_once __DIR__ . '/ControllerHelper.php';

class EventDisplayManagementController {
    private $bannerTable = 'banners';
    private $bannerImageField = 'banner_image';
    private $uploadDir = '../../uploads/banners/';
    
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
     * Get all banners with automatic expiration handling
     */
    public function getAllBanners() {
        global $connection;
        
        // First, automatically deactivate expired events
        $today = date('Y-m-d');
        update($this->bannerTable, ['active' => 0], "event_end_date < '$today' AND active = 1");
        
        // Get all banners with enhanced status information
        $sql = "SELECT *, 
            CASE 
                WHEN event_end_date < '$today' THEN 'expired'
                WHEN event_start_date <= '$today' AND event_end_date >= '$today' THEN 'active'
                WHEN event_start_date > '$today' THEN 'upcoming'
                ELSE 'unknown'
            END as event_status,
            CASE 
                WHEN filename IS NOT NULL AND filename != '' THEN CONCAT('../../uploads/banners/', filename)
                ELSE ''
            END as image_path
            FROM {$this->bannerTable} ORDER BY date_uploaded DESC";
        
        $result = mysqli_query($connection, $sql);
        $banners = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            // Check if image file exists
            if ($row['filename']) {
                $row['image_exists'] = file_exists("../../uploads/banners/" . $row['filename']);
            } else {
                $row['image_exists'] = false;
            }
            $banners[] = $row;
        }
        
        return $banners;
    }
    
    /**
     * Get banner data for display using pure data method
     */
    public function getBannerDisplayData() {
        $sql = "SELECT banner_id, title, description, event_start_date, event_end_date, event_date, 
                       active, date_uploaded, filename
                FROM {$this->bannerTable} ORDER BY date_uploaded DESC";
        
        return display_all_data($sql, [], 'table');
    }
    
    /**
     * Upload new banner
     */
    private function uploadBanner() {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_start_date = $_POST['event_start_date'] ?? '';
        $event_end_date = $_POST['event_end_date'] ?? '';
        $active = isset($_POST['active']) ? 1 : 0;
        
        // Validation
        if (empty($title)) {
            return $this->redirectWithError("Event title is required.");
        }
        
        if (empty($event_start_date) || empty($event_end_date)) {
            return $this->redirectWithError("Event dates are required.");
        }
        
        // Validate dates
        if ($event_end_date < $event_start_date) {
            return $this->redirectWithError("End date cannot be earlier than start date.");
        }
        
        // Prepare data for insertion
        $bannerData = [
            'title' => $title,
            'description' => $description,
            'event_start_date' => $event_start_date,
            'event_end_date' => $event_end_date,
            'active' => $active,
            'filename' => ''
        ];
        
        // Insert banner with automatic image handling
        $bannerId = save($this->bannerTable, $bannerData, $this->bannerImageField);
        
        if ($bannerId) {
            return $this->redirectWithSuccess("Event banner uploaded successfully!");
        } else {
            return $this->redirectWithError("Failed to save event banner to database.");
        }
    }
    
    /**
     * Toggle banner active status
     */
    private function toggleBanner() {
        $bannerId = intval($_POST['banner_id'] ?? 0);
        $currentStatus = intval($_POST['current_status'] ?? 0);
        $newStatus = $currentStatus == 1 ? 0 : 1;
        
        if ($bannerId <= 0) {
            return $this->redirectWithError("Invalid banner ID.");
        }
        
        $updateData = ['active' => $newStatus];
        if (update($this->bannerTable, $updateData, "banner_id = $bannerId")) {
            $statusText = $newStatus ? 'activated' : 'deactivated';
            return $this->redirectWithSuccess("Banner {$statusText} successfully!");
        } else {
            return $this->redirectWithError("Failed to update banner status.");
        }
    }
    
    /**
     * Delete banner and associated file
     */
    private function deleteBanner() {
        $bannerId = intval($_POST['banner_id'] ?? 0);
        $filename = $_POST['filename'] ?? '';
        
        if ($bannerId <= 0) {
            return $this->redirectWithError("Invalid banner ID.");
        }
        
        // Begin transaction for atomic operation
        beginTransaction();
        
        try {
            // Delete from database
            if (!delete($this->bannerTable, $bannerId, 'banner_id')) {
                throw new Exception("Failed to delete banner from database.");
            }
            
            // Delete image file if exists
            if ($filename) {
                $filePath = $this->uploadDir . $filename;
                if (file_exists($filePath)) {
                    if (!unlink($filePath)) {
                        // Log warning but don't fail the transaction
                        error_log("Warning: Failed to delete banner file: " . $filePath);
                    }
                }
            }
            
            commitTransaction();
            return $this->redirectWithSuccess("Banner deleted successfully!");
            
        } catch (Exception $e) {
            rollbackTransaction();
            return $this->redirectWithError("Failed to delete banner: " . $e->getMessage());
        }
    }
    
    /**
     * Edit existing banner
     */
    private function editBanner() {
        $bannerId = intval($_POST['banner_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventStartDate = $_POST['event_start_date'] ?? '';
        $eventEndDate = $_POST['event_end_date'] ?? '';
        $active = intval($_POST['active'] ?? 0);
        $currentFilename = $_POST['current_filename'] ?? '';
        
        // Validation
        if ($bannerId <= 0) {
            return $this->redirectWithError("Invalid banner ID.");
        }
        
        if (empty($title)) {
            return $this->redirectWithError("Event title is required!");
        }
        
        // Begin transaction for atomic operation
        beginTransaction();
        
        try {
            // Prepare update data
            $updateData = [
                'title' => $title,
                'description' => $description,
                'event_start_date' => $eventStartDate ?: null,
                'event_end_date' => $eventEndDate ?: null,
                'active' => $active
            ];
            
            // Handle image upload if new image is provided
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
                $newFilename = $this->handleImageUpload($bannerId, $currentFilename);
                if ($newFilename) {
                    $updateData['filename'] = $newFilename;
                } else {
                    throw new Exception("Failed to upload new image.");
                }
            }
            
            // Update banner in database
            if (!update($this->bannerTable, $updateData, "banner_id = $bannerId")) {
                throw new Exception("Failed to update banner in database.");
            }
            
            commitTransaction();
            return $this->redirectWithSuccess("Banner updated successfully!");
            
        } catch (Exception $e) {
            rollbackTransaction();
            return $this->redirectWithError($e->getMessage());
        }
    }
    
    /**
     * Handle image upload for banner editing
     */
    private function handleImageUpload($bannerId, $currentFilename) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        
        // Validate file type
        if (!in_array($_FILES['banner_image']['type'], $allowedTypes)) {
            throw new Exception("Invalid image format. Please use JPG or PNG.");
        }
        
        $extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        $newFilename = $bannerId . "." . $extension;
        
        // Create directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }
        
        // Delete old image if it exists and is different from new one
        if ($currentFilename && $currentFilename !== $newFilename) {
            $oldFilePath = $this->uploadDir . $currentFilename;
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // Upload new image
        if (!move_uploaded_file($_FILES['banner_image']['tmp_name'], $this->uploadDir . $newFilename)) {
            throw new Exception("Failed to move uploaded file.");
        }
        
        return $newFilename;
    }
    
    private function redirectWithSuccess($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "success");
    }
    
    private function redirectWithError($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "error");
    }
}
?>