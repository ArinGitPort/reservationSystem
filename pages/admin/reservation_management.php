<?php
// Include the ReservationManagementController
require_once '../../controllers/ReservationManagementController.php';

// Initialize controller and handle requests
$reservationController = new ReservationManagementController();
$reservationController->handleRequest();

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

// Get data for display
$customers = $reservationController->getAllCustomers();
$reservations = $reservationController->getAllReservations();
$stats = $reservationController->getReservationStatistics();

// SQL Query for display_all function (legacy support)
$reservationQuery = "SELECT r.*, c.first_name, c.last_name, c.email 
                    FROM reservations r 
                    JOIN customers c ON r.customer_id = c.id 
                    ORDER BY r.reservation_date DESC, r.reservation_time DESC";
$columnMappings = []; // Not used for reservation_management special handling
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Management - Ellen's Food House</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/account_management.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/reservation_management.css">
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
        
        <!-- Reservation Statistics Dashboard -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h4><?php echo $stats['total_reservations']; ?></h4>
                        <p>Total Reservations</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-details">
                        <h4><?php echo $stats['today_reservations']; ?></h4>
                        <p>Today's Reservations</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h4><?php echo $stats['pending_reservations']; ?></h4>
                        <p>Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h4><?php echo $stats['confirmed_reservations']; ?></h4>
                        <p>Confirmed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservation Management -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-calendar-alt me-2"></i>Reservation Management</h3>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-danger" id="bulkDeleteBtn" style="display: none;" onclick="bulkDeleteReservations()">
                    <i class="fas fa-trash me-2"></i>Delete Selected
                </button>
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                    <i class="fas fa-plus me-2"></i>Add Reservation
                </button>
            </div>
        </div>
        
        <!-- Search and Filter Component -->
        <?php
        include '../../includes/search_filter.php';
        
        // Prepare reservation data for PDF export
        $reservationPDFData = [];
        if ($reservations) {
            foreach ($reservations as $reservation) {
                $reservationPDFData[] = [
                    'id' => $reservation['id'],
                    'customer' => $reservation['first_name'] . ' ' . $reservation['last_name'],
                    'email' => $reservation['email'],
                    'date' => $reservation['reservation_date'],
                    'time' => $reservation['reservation_time'],
                    'party_size' => $reservation['party_size'],
                    'table' => $reservation['table_number'] ?: 'TBD',
                    'status' => ucfirst($reservation['status']),
                    'special_requests' => $reservation['special_requests'] ?: 'None'
                ];
            }
        }
        
        // Prepare stats for PDF
        $pdfStats = [
            'total_reservations' => ['value' => $stats['total_reservations'], 'label' => 'Total Reservations'],
            'today_reservations' => ['value' => $stats['today_reservations'], 'label' => 'Today\'s Reservations'],
            'pending_reservations' => ['value' => $stats['pending_reservations'], 'label' => 'Pending'],
            'confirmed_reservations' => ['value' => $stats['confirmed_reservations'], 'label' => 'Confirmed']
        ];
        
        renderSearchFilter([
            'placeholder' => 'Search by customer name, email, or table number...',
            'search_label' => 'Search Reservations',
            'filters' => [
                'status' => [
                    'label' => 'Status',
                    'type' => 'select',
                    'options' => array_merge(['all' => 'All Statuses'], ReservationManagementController::getStatusLabels())
                ],
                'reservation_date' => [
                    'label' => 'Reservation Date',
                    'type' => 'daterange'
                ]
            ],
            'additional_buttons' => [
                [
                    'text' => 'PDF',
                    'icon' => 'fas fa-file-pdf',
                    'class' => 'btn-outline-danger',
                    'type' => 'pdf_export',
                    'data' => $reservationPDFData,
                    'report_title' => 'Reservation Report',
                    'company_name' => 'Ellen\'s Food House',
                    'stats' => $pdfStats,
                    'columns' => [
                        ['key' => 'id', 'label' => 'ID'],
                        ['key' => 'customer', 'label' => 'Customer'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'date', 'label' => 'Date', 'format' => 'date'],
                        ['key' => 'time', 'label' => 'Time'],
                        ['key' => 'party_size', 'label' => 'Party Size'],
                        ['key' => 'table', 'label' => 'Table'],
                        ['key' => 'status', 'label' => 'Status'],
                        ['key' => 'special_requests', 'label' => 'Special Requests']
                    ],
                    'filename' => 'reservations_report_' . date('Y-m-d_H-i-s')
                ]
            ],
            'clear_function' => 'clearReservationFilters()',
            'refresh_function' => 'refreshReservations()'
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
                    <?php
                    // Enhanced reservation display
                    if ($reservations && count($reservations) > 0) {
                        $statusColors = ReservationManagementController::getStatusColors();
                        $validStatuses = ReservationManagementController::getValidStatuses();
                        
                        foreach ($reservations as $index => $reservation) {
                            $statusClass = $statusColors[$reservation['status']] ?? 'secondary';
                            
                            echo '<tr>';
                            echo '<td><input type="checkbox" class="form-check-input reservation-checkbox" value="' . $reservation['id'] . '" onchange="updateBulkActions()"></td>';
                            echo '<td>' . ($index + 1) . '</td>';
                            
                            // Customer column
                            echo '<td>';
                            echo '<div class="fw-bold">' . htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) . '</div>';
                            echo '<small class="text-muted">' . htmlspecialchars($reservation['email']) . '</small>';
                            echo '</td>';
                            
                            // Date column
                            echo '<td>' . date('M d, Y', strtotime($reservation['reservation_date'])) . '</td>';
                            
                            // Time column
                            echo '<td>' . date('g:i A', strtotime($reservation['reservation_time'])) . '</td>';
                            
                            // Party size column
                            echo '<td><span class="badge bg-secondary">' . $reservation['party_size'] . ' ' . ($reservation['party_size'] == 1 ? 'person' : 'people') . '</span></td>';
                            
                            // Table column
                            echo '<td>' . ($reservation['table_number'] ? 'Table ' . $reservation['table_number'] : '<em class="text-muted">TBD</em>') . '</td>';
                            
                            // Status column with quick status change
                            echo '<td>';
                            echo '<div class="dropdown">';
                            echo '<button class="btn btn-sm btn-outline-' . $statusClass . ' dropdown-toggle" type="button" data-bs-toggle="dropdown">';
                            echo ucfirst($reservation['status']);
                            echo '</button>';
                            echo '<ul class="dropdown-menu">';
                            foreach ($validStatuses as $status) {
                                if ($status !== $reservation['status']) {
                                    $statusLabel = ReservationManagementController::getStatusLabels()[$status];
                                    echo '<li><a class="dropdown-item" href="#" onclick="updateReservationStatus(' . $reservation['id'] . ', \'' . $status . '\')">' . $statusLabel . '</a></li>';
                                }
                            }
                            echo '</ul>';
                            echo '</div>';
                            echo '</td>';
                            
                            // Actions column
                            echo '<td>';
                            echo '<div class="btn-group btn-group-sm">';
                            echo '<button class="btn btn-outline-primary btn-sm" onclick="viewReservationDetails(' . $reservation['id'] . ')" title="View Details"><i class="fas fa-eye"></i></button>';
                            echo '<button class="btn btn-outline-secondary btn-sm" onclick="editReservation(' . $reservation['id'] . ')" data-bs-toggle="modal" data-bs-target="#editReservationModal" title="Edit"><i class="fas fa-edit"></i></button>';
                            // Only allow deletion of cancelled, no-show, or completed reservations
                            if (in_array($reservation['status'], ['cancelled', 'no-show', 'completed'])) {
                                echo '<button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(\'reservation\', ' . $reservation['id'] . ')" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" title="Delete"><i class="fas fa-trash"></i></button>';
                            }
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9" class="text-center py-4"><em>No reservations found</em></td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- End Main Content -->
    
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
                            <label for="customer_id" class="form-label">Customer *</label>
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
                                    <label for="reservation_date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reservation_time" class="form-label">Time *</label>
                                    <input type="time" class="form-control" name="reservation_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="party_size" class="form-label">Party Size *</label>
                                    <input type="number" class="form-control" name="party_size" min="1" max="20" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="table_number" class="form-label">Table Number</label>
                                    <input type="number" class="form-control" name="table_number" min="1" placeholder="Leave empty for auto-assign">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" name="special_requests" rows="3" placeholder="Any special dietary requirements, seating preferences, etc."></textarea>
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
    
    <!-- Edit Reservation Modal -->
    <div class="modal fade" id="editReservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_reservation">
                        <input type="hidden" name="reservation_id" id="edit_reservation_id">
                        <div class="mb-3">
                            <label for="edit_customer_id" class="form-label">Customer *</label>
                            <select class="form-select" name="customer_id" id="edit_customer_id" required>
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
                                    <label for="edit_reservation_date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" name="reservation_date" id="edit_reservation_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_reservation_time" class="form-label">Time *</label>
                                    <input type="time" class="form-control" name="reservation_time" id="edit_reservation_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_party_size" class="form-label">Party Size *</label>
                                    <input type="number" class="form-control" name="party_size" id="edit_party_size" min="1" max="20" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_table_number" class="form-label">Table Number</label>
                                    <input type="number" class="form-control" name="table_number" id="edit_table_number" min="1" placeholder="Leave empty for auto-assign">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" name="special_requests" id="edit_special_requests" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Update Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reservation Details Modal -->
    <div class="modal fade" id="reservationDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Reservation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reservationDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading reservation details...
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
                    <p>Are you sure you want to delete this reservation? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="action" value="delete_reservation">
                        <input type="hidden" name="reservation_id" id="deleteReservationId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <form method="POST" id="statusUpdateForm" style="display: none;">
        <input type="hidden" name="action" value="update_reservation_status">
        <input type="hidden" name="reservation_id" id="status_reservation_id">
        <input type="hidden" name="status" id="status_value">
    </form>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Reservation Management JS -->
    <script src="../../assets/js/reservation_management.js"></script>
</body>
</html>
