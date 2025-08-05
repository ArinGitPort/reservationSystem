<?php
require_once '../../config/db_model.php';

// Handle form submissions
$message = '';
$messageType = '';

// Handle GET parameters for messages (after redirect)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $messageType = $_GET['type'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_customer':
                $firstName = $_POST['first_name'];
                $lastName = $_POST['last_name'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                
                $customerId = addCustomer($firstName, $lastName, $email, $phone);
                if ($customerId) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Customer added successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to add customer. Email might already exist.", "error");
                }
                break;
                
            case 'update_customer':
                $id = $_POST['customer_id'];
                $firstName = $_POST['first_name'];
                $lastName = $_POST['last_name'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                
                if (updateCustomer($id, $firstName, $lastName, $email, $phone)) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Customer updated successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to update customer.", "error");
                }
                break;
                
            case 'delete_customer':
                $id = $_POST['customer_id'];
                if (deleteCustomer($id)) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Customer deleted successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to delete customer.", "error");
                }
                break;
                
            case 'add_reservation':
                $customerId = $_POST['customer_id'];
                $reservationDate = $_POST['reservation_date'];
                $reservationTime = $_POST['reservation_time'];
                $partySize = $_POST['party_size'];
                $tableNumber = $_POST['table_number'];
                $specialRequests = $_POST['special_requests'];
                
                $reservationId = addReservation($customerId, $reservationDate, $reservationTime, $partySize, $tableNumber, $specialRequests);
                if ($reservationId) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Reservation added successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to add reservation.", "error");
                }
                break;
                
            case 'update_reservation_status':
                $id = $_POST['reservation_id'];
                $status = $_POST['status'];
                
                if (updateReservationStatus($id, $status)) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Reservation status updated successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to update reservation status.", "error");
                }
                break;
                
            case 'delete_reservation':
                $id = $_POST['reservation_id'];
                if (deleteReservation($id)) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Reservation deleted successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to delete reservation.", "error");
                }
                break;
        }
    }
}

// Get all customers and reservations for display
$customers = getAllCustomers();
$reservations = getAllReservations();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management - Ellen's Food House</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/account_management.css">
</head>
<body>
    <div class="container-fluid">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Customer Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reservations-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button" role="tab">
                    <i class="fas fa-calendar-alt me-2"></i>Reservation Management
                </button>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="managementTabsContent">
            <!-- Customer Management Tab -->
            <div class="tab-pane fade show active" id="customers" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><i class="fas fa-users me-2"></i>Customer Management</h3>
                    <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                        <i class="fas fa-plus me-2"></i>Add Customer
                    </button>
                </div>
                
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>NAME</th>
                                <th>EMAIL</th>
                                <th>PHONE</th>
                                <th>ADDED</th>
                                <th>MANAGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $index => $customer): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <div><?php echo date('d M Y', strtotime($customer['created_at'])); ?></div>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($customer['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-dark" 
                                                onclick="editCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['first_name']); ?>', '<?php echo htmlspecialchars($customer['last_name']); ?>', '<?php echo htmlspecialchars($customer['email']); ?>', '<?php echo htmlspecialchars($customer['phone']); ?>')"
                                                data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete('customer', <?php echo $customer['id']; ?>)"
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
            </div>
            
            <!-- Reservation Management Tab -->
            <div class="tab-pane fade" id="reservations" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3><i class="fas fa-calendar-alt me-2"></i>Reservation Management</h3>
                    <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                        <i class="fas fa-plus me-2"></i>Add Reservation
                    </button>
                </div>
                
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>CUSTOMER</th>
                                <th>DATE</th>
                                <th>TIME</th>
                                <th>PARTY SIZE</th>
                                <th>TABLE</th>
                                <th>STATUS</th>
                                <th>MANAGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $index => $reservation): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($reservation['email']); ?></small>
                                </td>
                                <td><?php echo date('d M Y', strtotime($reservation['reservation_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?></td>
                                <td><?php echo $reservation['party_size']; ?></td>
                                <td><?php echo $reservation['table_number'] ?: 'TBD'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $reservation['id']; ?>, 'pending')">Set Pending</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $reservation['id']; ?>, 'confirmed')">Set Confirmed</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $reservation['id']; ?>, 'cancelled')">Set Cancelled</a></li>
                                            </ul>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete('reservation', <?php echo $reservation['id']; ?>)"
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
            </div>
        </div>
    </div>
    
    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
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
                <form method="POST">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Reservation Modal -->
    <div class="modal fade" id="addReservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Add New Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_reservation">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Customer</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">Select a customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['email'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reservation_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reservation_time" class="form-label">Time</label>
                                    <input type="time" class="form-control" name="reservation_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="party_size" class="form-label">Party Size</label>
                                    <input type="number" class="form-control" name="party_size" min="1" max="20" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="table_number" class="form-label">Table Number (Optional)</label>
                                    <input type="number" class="form-control" name="table_number" min="1">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" name="special_requests" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Add Reservation</button>
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
                    <p>Are you sure you want to delete this <span id="deleteType"></span>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="action" id="deleteAction">
                        <input type="hidden" name="customer_id" id="deleteCustomerId">
                        <input type="hidden" name="reservation_id" id="deleteReservationId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden form for status updates -->
    <form method="POST" id="statusUpdateForm" style="display: none;">
        <input type="hidden" name="action" value="update_reservation_status">
        <input type="hidden" name="reservation_id" id="status_reservation_id">
        <input type="hidden" name="status" id="status_value">
    </form>
    
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
            document.getElementById('deleteType').textContent = type;
            
            if (type === 'customer') {
                document.getElementById('deleteAction').value = 'delete_customer';
                document.getElementById('deleteCustomerId').value = id;
                document.getElementById('deleteReservationId').value = '';
            } else {
                document.getElementById('deleteAction').value = 'delete_reservation';
                document.getElementById('deleteReservationId').value = id;
                document.getElementById('deleteCustomerId').value = '';
            }
        }
        
        function updateStatus(reservationId, status) {
            document.getElementById('status_reservation_id').value = reservationId;
            document.getElementById('status_value').value = status;
            document.getElementById('statusUpdateForm').submit();
        }
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000); // 5 seconds
            });
        });
    </script>
</body>
</html>
