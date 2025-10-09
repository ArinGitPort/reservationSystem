<?php
$configPath = file_exists('../config/db_model.php') ? '../config/db_model.php' : '../../config/db_model.php';
require_once $configPath;
class CustomerController {
    private $customerTable = 'customers';
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
                default:
                    return $this->redirectWithError("Invalid action.");
            }
        }
        return null;
    }
    
    public function getAllCustomers() {
        return fetch($this->customerTable, '', 'created_at DESC');
    }
    
    private function addCustomer() {
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email)) {
            return $this->redirectWithError("First name, last name, and email are required.");
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
            'image_path' => ''
        ];
        
        // Insert customer with automatic image handling using save function
        $customerId = save($this->customerTable, $customerData, $this->profileImageField);
        
        if ($customerId) {
            return $this->redirectWithSuccess("Customer added successfully!");
        } else {
            return $this->redirectWithError("Failed to add customer. Please try again.");
        }
    }
    
    private function updateCustomer() {
        $id = $_POST['customer_id'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        // Validate required fields
        if (empty($id) || empty($firstName) || empty($lastName) || empty($email)) {
            return $this->redirectWithError("All fields except phone are required.");
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
            'phone' => $phone
        ];
        
        // Handle profile image upload if provided
        if (isset($_FILES[$this->profileImageField]) && $_FILES[$this->profileImageField]['error'] == 0) {
            // Get old image to delete it if extension changed
            $oldCustomerData = fetch($this->customerTable, "id = $id");
            $oldCustomer = !empty($oldCustomerData) ? $oldCustomerData[0] : null;
            
            $extension = strtolower(pathinfo($_FILES[$this->profileImageField]['name'], PATHINFO_EXTENSION));
            $newFilename = $id . '.' . $extension;
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $fileType = $_FILES[$this->profileImageField]['type'];
            
            if (in_array($fileType, $allowedTypes)) {
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
                    // Add image path to update data
                    $updateData['image_path'] = $newFilename;
                } else {
                    return $this->redirectWithError("Failed to upload profile image.");
                }
            } else {
                return $this->redirectWithError("Invalid file type. Please upload JPG or PNG images only.");
            }
        }
        
        // Update customer using generic update function
        if (update($this->customerTable, $updateData, "id = $id")) {
            return $this->redirectWithSuccess("Customer updated successfully!");
        } else {
            return $this->redirectWithError("Failed to update customer.");
        }
    }
    
    private function deleteCustomer() {
        $id = $_POST['customer_id'] ?? '';
        
        if (empty($id)) {
            return $this->redirectWithError("Customer ID is required.");
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
    
    private function redirectWithSuccess($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "success");
    }
    
    private function redirectWithError($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "error");
    }
}
?>