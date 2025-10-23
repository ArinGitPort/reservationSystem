<?php
$configPath = file_exists('../config/db_model.php') ? '../config/db_model.php' : '../../config/db_model.php';
require_once $configPath;
require_once __DIR__ . '/ControllerHelper.php';

class AccountManagementController {
    private $customerTable = 'customers';
    private $orderTable = 'orders';
    private $profileImageField = 'profile_image';
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_customer':
                    return $this->addCustomer();
                case 'update_customer':
                    return $this->updateCustomer();
                case 'delete_customer':
                    return $this->deleteCustomer();
                case 'bulk_delete':
                    return $this->bulkDeleteCustomers();
                case 'export_pdf':
                    return $this->exportCustomersPDF();
                case 'export_csv':
                    return $this->exportCustomersCSV();
                default:
                    return $this->redirectWithError("Invalid action.");
            }
        }
        return null;
    }
    
    /**
     * Get all customers with optional filtering - using DRY fetch() function
     */
    public function getAllCustomers($search = '', $dateFrom = '', $dateTo = '', $status = 'all') {
        $conditions = [];
        
        // Search functionality
        if (!empty($search)) {
            $search = mysqli_real_escape_string($GLOBALS['connection'], $search);
            $conditions[] = "(first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
        }
        
        // Date range filter
        if (!empty($dateFrom)) {
            $conditions[] = "DATE(created_at) >= '$dateFrom'";
        }
        if (!empty($dateTo)) {
            $conditions[] = "DATE(created_at) <= '$dateTo'";
        }
        
        // Status filter (active customers with orders vs all)
        if ($status === 'active') {
            $conditions[] = "id IN (SELECT DISTINCT customer_id FROM orders WHERE customer_id IS NOT NULL)";
        } elseif ($status === 'inactive') {
            $conditions[] = "id NOT IN (SELECT DISTINCT customer_id FROM orders WHERE customer_id IS NOT NULL)";
        }
        
        $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '';
        return fetch($this->customerTable, $whereClause, 'created_at DESC');
    }
    
    /**
     * Get customer statistics
     */
    public function getCustomerStatistics() {
        $stats = [];
        
        // Use selectData() with COUNT instead of fetching all records and counting
        $totalCustomersResult = selectData($this->customerTable, ['COUNT(*) as count']);
        $stats['total_customers'] = $totalCustomersResult ? $totalCustomersResult[0]['count'] : 0;
        
        // New customers this month - use selectData() with COUNT
        $newThisMonthResult = selectData($this->customerTable, ['COUNT(*) as count'], ['DATE(created_at) >=' => date('Y-m-01')]);
        $stats['new_this_month'] = $newThisMonthResult ? $newThisMonthResult[0]['count'] : 0;
        
        // Active customers (with orders) - use fetch() for complex subquery
        $activeCustomers = fetch($this->customerTable, "id IN (SELECT DISTINCT customer_id FROM orders WHERE customer_id IS NOT NULL)");
        $stats['active_customers'] = $activeCustomers ? count($activeCustomers) : 0;
        
        // Use selectData() with JOIN for top customer query
        $topCustomerResult = selectData(
            'customers c', 
            ['c.first_name', 'c.last_name', 'COUNT(o.order_id) as order_count'], 
            [], 
            'order_count DESC', 
            1, 
            0, 
            ['LEFT JOIN orders o ON c.id = o.customer_id GROUP BY c.id']
        );
        
        if ($topCustomerResult && !empty($topCustomerResult)) {
            $topCustomer = $topCustomerResult[0];
            $stats['top_customer'] = $topCustomer['first_name'] . ' ' . $topCustomer['last_name'] . ' (' . $topCustomer['order_count'] . ' orders)';
        } else {
            $stats['top_customer'] = 'N/A';
        }
        
        return $stats;
    }
    
    /**
     * Get customer activity (order history)
     */
    public function getCustomerActivity($customerId) {
        $orders = fetch($this->orderTable, "customer_id = $customerId", 'order_date DESC');
        return $orders ? $orders : [];
    }
    
    /**
     * Add customer with validation
     */
    private function addCustomer() {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Enhanced validation
        if (empty($firstName) || empty($lastName) || empty($email)) {
            return $this->redirectWithError("First name, last name, and email are required.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->redirectWithError("Please enter a valid email address.");
        }
        
        // Check if email already exists
        $existingCustomer = fetch($this->customerTable, "email = '$email'");
        if (!empty($existingCustomer)) {
            return $this->redirectWithError("Email already exists. Please use a different email.");
        }
        
        // Prepare data for insertion using generic save function
        $customerData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'image_path' => '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert customer with automatic image handling using save function
        $customerId = save($this->customerTable, $customerData, $this->profileImageField);
        
        if ($customerId) {
            return $this->redirectWithSuccess("Customer added successfully!");
        } else {
            return $this->redirectWithError("Failed to add customer. Please try again.");
        }
    }
    
    /**
     * Update customer with enhanced validation
     */
    private function updateCustomer() {
        $id = $_POST['customer_id'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Enhanced validation
        if (empty($id) || empty($firstName) || empty($lastName) || empty($email)) {
            return $this->redirectWithError("All fields except phone are required.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->redirectWithError("Please enter a valid email address.");
        }
        
        // Check if email exists for other customers
        $existingCustomer = fetch($this->customerTable, "email = '$email' AND id != $id");
        if (!empty($existingCustomer)) {
            return $this->redirectWithError("Email already exists. Please use a different email.");
        }
        
        // Prepare update data using generic update function
        $updateData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle profile image upload if provided
        if (isset($_FILES[$this->profileImageField]) && $_FILES[$this->profileImageField]['error'] == 0) {
            // Get old image to delete it if extension changed
            $oldCustomerData = fetch($this->customerTable, "id = $id");
            $oldCustomer = !empty($oldCustomerData) ? $oldCustomerData[0] : null;
            
            $extension = strtolower(pathinfo($_FILES[$this->profileImageField]['name'], PATHINFO_EXTENSION));
            $newFilename = $id . '.' . $extension;
            
            // Validate file type and size
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $fileType = $_FILES[$this->profileImageField]['type'];
            $fileSize = $_FILES[$this->profileImageField]['size'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($fileType, $allowedTypes)) {
                return $this->redirectWithError("Invalid file type. Please upload JPG or PNG images only.");
            }
            
            if ($fileSize > $maxSize) {
                return $this->redirectWithError("File size too large. Maximum size is 2MB.");
            }
            
            // Create directory if it doesn't exist
            $uploadDir = '../../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Delete old image if it has different extension
            if ($oldCustomer && $oldCustomer['image_path'] && $oldCustomer['image_path'] !== $newFilename) {
                $oldImagePath = $uploadDir . $oldCustomer['image_path'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES[$this->profileImageField]['tmp_name'], $uploadDir . $newFilename)) {
                $updateData['image_path'] = $newFilename;
            } else {
                return $this->redirectWithError("Failed to upload profile image.");
            }
        }
        
        // Update customer using generic update function
        if (update($this->customerTable, $updateData, "id = $id")) {
            return $this->redirectWithSuccess("Customer updated successfully!");
        } else {
            return $this->redirectWithError("Failed to update customer.");
        }
    }
    
    /**
     * Delete customer with order check
     */
    private function deleteCustomer() {
        $id = $_POST['customer_id'] ?? '';
        
        if (empty($id)) {
            return $this->redirectWithError("Customer ID is required.");
        }
        
        // Check if customer has orders
        $customerOrders = fetch($this->orderTable, "customer_id = $id");
        if (!empty($customerOrders)) {
            return $this->redirectWithError("Cannot delete customer with existing orders. Please cancel or complete all orders first.");
        }
        
        // Get image filename from DB using fetch function
        $customerData = fetch($this->customerTable, "id = $id");
        $customer = !empty($customerData) ? $customerData[0] : null;
        $imagePath = $customer ? $customer['image_path'] : '';
        
        // Delete from database using delete function
        if (delete($this->customerTable, $id)) {
            // Delete profile image file if exists
            if ($imagePath) {
                $filePath = "../../uploads/profiles/" . $imagePath;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            return $this->redirectWithSuccess("Customer deleted successfully!");
        } else {
            return $this->redirectWithError("Failed to delete customer.");
        }
    }
    
    /**
     * Bulk delete customers
     */
    private function bulkDeleteCustomers() {
        $customerIds = $_POST['customer_ids'] ?? [];
        
        if (empty($customerIds) || !is_array($customerIds)) {
            return $this->redirectWithError("No customers selected for deletion.");
        }
        
        $deletedCount = 0;
        $errorMessages = [];
        
        foreach ($customerIds as $customerId) {
            $customerId = intval($customerId);
            
            // Check if customer has orders
            $customerOrders = fetch($this->orderTable, "customer_id = $customerId");
            if (!empty($customerOrders)) {
                $customer = fetch($this->customerTable, "id = $customerId");
                $customerName = $customer ? $customer[0]['first_name'] . ' ' . $customer[0]['last_name'] : 'Customer #' . $customerId;
                $errorMessages[] = "Cannot delete $customerName (has existing orders)";
                continue;
            }
            
            // Get image path before deletion
            $customerData = fetch($this->customerTable, "id = $customerId");
            $customer = !empty($customerData) ? $customerData[0] : null;
            $imagePath = $customer ? $customer['image_path'] : '';
            
            // Delete customer
            if (delete($this->customerTable, $customerId)) {
                // Delete profile image if exists
                if ($imagePath) {
                    $filePath = "../../uploads/profiles/" . $imagePath;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                $deletedCount++;
            }
        }
        
        $message = "Successfully deleted $deletedCount customer(s).";
        if (!empty($errorMessages)) {
            $message .= " Errors: " . implode(', ', $errorMessages);
        }
        
        return $this->redirectWithSuccess($message);
    }
    
    /**
     * Export customers to PDF - Now handled by integrated search_filter component
     */
    private function exportCustomersPDF() {
        // PDF export is now handled by the search_filter component
        // This method is kept for backward compatibility but no longer used
        return $this->redirectWithError("Please use the PDF export button in the search filter section.");
    }
    
    /**
     * Export customers to CSV
     */
    private function exportCustomersCSV() {
        $customers = $this->getAllCustomers();
        
        if (empty($customers)) {
            return $this->redirectWithError("No customers found to export.");
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=customers_' . date('Y-m-d_H-i-s') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Created At']);
        
        // Add customer data
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                $customer['first_name'],
                $customer['last_name'],
                $customer['email'],
                $customer['phone'] ?: 'N/A',
                $customer['created_at']
            ]);
        }
        
        fclose($output);
        exit; // Important: Stop execution after download
    }
    
    private function redirectWithSuccess($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "success");
    }
    
    private function redirectWithError($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "error");
    }
}
?>