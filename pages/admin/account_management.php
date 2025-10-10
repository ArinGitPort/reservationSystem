<?php
// Include the AccountManagementController
require_once '../../controllers/AccountManagementController.php';

// Initialize controller and handle requests
$accountController = new AccountManagementController();
$accountController->handleRequest();

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

$customers = $accountController->getAllCustomers();
$stats = $accountController->getCustomerStatistics();
$customerQuery = "SELECT id, first_name, last_name, email, phone, image_path, created_at FROM customers ORDER BY created_at DESC";
$columnMappings = []; // Not used for account_management special handling
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Ellen's Food House</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/account_management.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-right: 1rem;
            color: #6c757d;
            min-width: 60px;
        }
        
        .stat-details h4 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: #212529;
        }
        
        .stat-details p {
            margin: 0;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 1rem 0.75rem;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
            
            .stat-icon {
                font-size: 2rem;
                margin-right: 0.75rem;
                min-width: 50px;
            }
            
            .stat-details h4 {
                font-size: 1.5rem;
            }
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
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Customer Statistics Dashboard -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h4><?php echo $stats['total_customers']; ?></h4>
                        <p>Total Customers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-details">
                        <h4><?php echo $stats['new_this_month']; ?></h4>
                        <p>New This Month</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-details">
                        <h4><?php echo $stats['active_customers']; ?></h4>
                        <p>Active Customers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-details">
                        <h4 style="font-size: 0.9rem;"><?php echo substr($stats['top_customer'], 0, 25) . (strlen($stats['top_customer']) > 25 ? '...' : ''); ?></h4>
                        <p>Top Customer</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Management -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-users me-2"></i>Customer Management</h3>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-danger" id="bulkDeleteBtn" style="display: none;" onclick="bulkDeleteCustomers()">
                    <i class="fas fa-trash me-2"></i>Delete Selected
                </button>
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="fas fa-plus me-2"></i>Add Customer
                </button>
            </div>
        </div>
        
        <!-- Search and Filter Component -->
        <?php
        include '../../includes/search_filter.php';
        
        // Prepare customer data for PDF export
        $customerPDFData = [];
        if ($customers) {
            foreach ($customers as $customer) {
                $customerPDFData[] = [
                    'id' => $customer['id'],
                    'name' => $customer['first_name'] . ' ' . $customer['last_name'],
                    'email' => $customer['email'],
                    'phone' => $customer['phone'] ?: 'N/A',
                    'created_at' => $customer['created_at']
                ];
            }
        }
        
        // Prepare stats for PDF
        $pdfStats = [
            'total_customers' => ['value' => $stats['total_customers'], 'label' => 'Total Customers'],
            'new_this_month' => ['value' => $stats['new_this_month'], 'label' => 'New This Month'],
            'active_customers' => ['value' => $stats['active_customers'], 'label' => 'Active Customers']
        ];
        
        renderSearchFilter([
            'placeholder' => 'Search by name, email, or phone...',
            'search_label' => 'Search Customers',
            'filters' => [
                'status' => [
                    'label' => 'Customer Status',
                    'type' => 'select',
                    'options' => [
                        'all' => 'All Customers',
                        'active' => 'Active (With Orders)',
                        'inactive' => 'Inactive (No Orders)'
                    ]
                ],
                'date_added' => [
                    'label' => 'Date Added',
                    'type' => 'daterange'
                ]
            ],
            'additional_buttons' => [
                [
                    'text' => 'Export CSV',
                    'icon' => 'fas fa-file-csv',
                    'class' => 'btn-outline-success',
                    'onclick' => 'exportCustomersCSV()'
                ],
                [
                    'text' => 'Export PDF',
                    'icon' => 'fas fa-file-pdf',
                    'class' => 'btn-outline-danger',
                    'type' => 'pdf_export',
                    'data' => $customerPDFData,
                    'report_title' => 'Customer Report',
                    'company_name' => 'Ellen\'s Food House',
                    'stats' => $pdfStats,
                    'columns' => [
                        ['key' => 'id', 'label' => '#'],
                        ['key' => 'name', 'label' => 'Name'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'phone', 'label' => 'Phone'],
                        ['key' => 'created_at', 'label' => 'Date Added', 'format' => 'date']
                    ],
                    'filename' => 'customers_report_' . date('Y-m-d_H-i-s')
                ]
            ],
            'clear_function' => 'clearCustomerFilters()',
            'refresh_function' => 'refreshCustomers()'
        ]);
        ?>
        
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>#</th>
                        <th>PROFILE</th>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>PHONE</th>
                        <th>STATUS</th>
                        <th>ADDED</th>
                        <th>MANAGE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Enhanced customer display with status and activity
                    if ($customers && count($customers) > 0) {
                        foreach ($customers as $index => $customer) {
                            $customerOrders = $accountController->getCustomerActivity($customer['id']);
                            $orderCount = count($customerOrders);
                            $status = $orderCount > 0 ? 'Active' : 'Inactive';
                            $statusClass = $orderCount > 0 ? 'success' : 'secondary';
                            
                            echo '<tr>';
                            echo '<td><input type="checkbox" class="form-check-input customer-checkbox" value="' . $customer['id'] . '" onchange="updateBulkActions()"></td>';
                            echo '<td>' . ($index + 1) . '</td>';
                            
                            // Profile image
                            echo '<td>';
                            if ($customer['image_path']) {
                                echo '<img src="../../uploads/profiles/' . htmlspecialchars($customer['image_path']) . '" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">';
                            } else {
                                echo '<div style="width: 40px; height: 40px; border-radius: 50%; background: #6c757d; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">' . strtoupper(substr($customer['first_name'], 0, 1)) . '</div>';
                            }
                            echo '</td>';
                            
                            // Name
                            echo '<td><strong>' . htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) . '</strong></td>';
                            
                            // Email
                            echo '<td>' . htmlspecialchars($customer['email']) . '</td>';
                            
                            // Phone
                            echo '<td>' . htmlspecialchars($customer['phone'] ?: 'N/A') . '</td>';
                            
                            // Status with order count
                            echo '<td><span class="badge bg-' . $statusClass . '">' . $status . ' (' . $orderCount . ' orders)</span></td>';
                            
                            // Added date
                            echo '<td>' . date('M d, Y', strtotime($customer['created_at'])) . '</td>';
                            
                            // Actions
                            echo '<td>';
                            echo '<div class="btn-group btn-group-sm">';
                            echo '<button class="btn btn-outline-primary btn-sm" onclick="viewCustomerDetails(' . $customer['id'] . ')" title="View Details"><i class="fas fa-eye"></i></button>';
                            echo '<button class="btn btn-outline-secondary btn-sm" onclick="editCustomer(' . $customer['id'] . ', \'' . htmlspecialchars($customer['first_name']) . '\', \'' . htmlspecialchars($customer['last_name']) . '\', \'' . htmlspecialchars($customer['email']) . '\', \'' . htmlspecialchars($customer['phone']) . '\')" data-bs-toggle="modal" data-bs-target="#editCustomerModal" title="Edit"><i class="fas fa-edit"></i></button>';
                            echo '<button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(\'customer\', ' . $customer['id'] . ')" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" title="Delete"><i class="fas fa-trash"></i></button>';
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9" class="text-center py-4"><em>No customers found</em></td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- End Main Content -->
    
    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_customer">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" name="profile_image" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Accepted formats: JPG, PNG. Max size: 2MB (Optional)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_customer">
                        <input type="hidden" name="customer_id" id="edit_customer_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone">
                        </div>
                        <div class="mb-3">
                            <label for="edit_profile_image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" name="profile_image" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Leave empty to keep current image. Accepted formats: JPG, PNG. Max size: 2MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading customer details...
                    </div>
                </div>
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
                    <p>Are you sure you want to delete this customer? This action cannot be undone.</p>
                    <p><small class="text-muted">Note: Customers with existing orders cannot be deleted.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="customer_id" id="deleteCustomerId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editCustomer(id, firstName, lastName, email, phone) {
            document.getElementById('edit_customer_id').value = id;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
        }
        
        function confirmDelete(type, id) {
            document.getElementById('deleteCustomerId').value = id;
        }
        
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // View customer details
        function viewCustomerDetails(customerId) {
            const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
            const content = document.getElementById('customerDetailsContent');
            
            // Show loading
            content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading customer details...</div>';
            modal.show();
            
            // Simulate loading customer details (replace with actual AJAX call)
            setTimeout(() => {
                // This would be replaced with an actual AJAX request to get customer details
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>ID:</strong> ${customerId}</p>
                            <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                            <!-- Add more customer details here -->
                        </div>
                        <div class="col-md-6">
                            <h6>Order History</h6>
                            <p><em>Order history will be loaded here...</em></p>
                        </div>
                    </div>
                `;
            }, 1000);
        }
        
        // Bulk selection functionality
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
            
            customerCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkedBoxes = document.querySelectorAll('.customer-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            
            if (checkedBoxes.length > 0) {
                bulkDeleteBtn.style.display = 'inline-block';
            } else {
                bulkDeleteBtn.style.display = 'none';
            }
        }
        
        function bulkDeleteCustomers() {
            const checkedBoxes = document.querySelectorAll('.customer-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Please select customers to delete.');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${checkedBoxes.length} customer(s)? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">';
                
                checkedBoxes.forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'customer_ids[]';
                    input.value = checkbox.value;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Customer management functions
        function clearCustomerFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('status-filter').value = 'all';
            document.getElementById('date_added-from').value = '';
            document.getElementById('date_added-to').value = '';
            window.location.reload();
        }
        
        function refreshCustomers() {
            window.location.reload();
        }
        
        // Export functions
        function exportCustomersCSV() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="export_csv">';
            document.body.appendChild(form);
            form.submit();
        }
        
        // PDF export is now handled by the integrated search_filter component
        
        // Enhanced search functionality with filters
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const statusFilter = document.getElementById('status-filter');
            const tableBody = document.querySelector('tbody');
            
            function filterTable() {
                if (!tableBody) return;
                
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const statusValue = statusFilter ? statusFilter.value : 'all';
                const rows = tableBody.querySelectorAll('tr');
                
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length === 0) return; // Skip empty rows
                    
                    const text = row.textContent.toLowerCase();
                    const statusBadge = row.querySelector('.badge');
                    const rowStatus = statusBadge ? statusBadge.textContent.toLowerCase() : '';
                    
                    let showRow = true;
                    
                    // Search filter
                    if (searchTerm && !text.includes(searchTerm)) {
                        showRow = false;
                    }
                    
                    // Status filter
                    if (statusValue !== 'all') {
                        if (statusValue === 'active' && !rowStatus.includes('active')) {
                            showRow = false;
                        } else if (statusValue === 'inactive' && !rowStatus.includes('inactive')) {
                            showRow = false;
                        }
                    }
                    
                    row.style.display = showRow ? '' : 'none';
                });
            }
            
            if (searchInput) {
                searchInput.addEventListener('input', filterTable);
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', filterTable);
            }
        });
        
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
