<?php
$configPath = file_exists('../config/db_model.php') ? '../config/db_model.php' : '../../config/db_model.php';
require_once $configPath;

class ReservationManagementController {
    private $reservationTable = 'reservations';
    private $customerTable = 'customers';
    
    // DRY: Define valid statuses in one place
    public static function getValidStatuses() {
        return ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no-show'];
    }
    
    public static function getStatusLabels() {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed', 
            'seated' => 'Seated',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no-show' => 'No Show'
        ];
    }
    
    public static function getStatusColors() {
        return [
            'pending' => 'warning',
            'confirmed' => 'info',
            'seated' => 'primary', 
            'completed' => 'success',
            'cancelled' => 'danger',
            'no-show' => 'secondary'
        ];
    }
    
    // DRY: Define active statuses that occupy tables
    public static function getActiveStatuses() {
        return ['pending', 'confirmed', 'seated'];
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_reservation':
                    return $this->addReservation();
                case 'update_reservation':
                    return $this->updateReservation();
                case 'update_reservation_status':
                    return $this->updateReservationStatus();
                case 'delete_reservation':
                    return $this->deleteReservation();
                case 'bulk_delete':
                    return $this->bulkDeleteReservations();

                default:
                    return $this->redirectWithError("Invalid action.");
            }
        }
        return null;
    }
    
    /**
     * Get all reservations with optional filtering - using DRY fetch() function
     */
    public function getAllReservations($search = '', $dateFrom = '', $dateTo = '', $status = 'all') {
        global $connection;
        
        $conditions = [];
        
        // Search functionality
        if (!empty($search)) {
            $search = mysqli_real_escape_string($connection, $search);
            $conditions[] = "(c.first_name LIKE '%$search%' OR c.last_name LIKE '%$search%' OR c.email LIKE '%$search%' OR r.table_number LIKE '%$search%')";
        }
        
        // Date range filter
        if (!empty($dateFrom)) {
            $conditions[] = "DATE(r.reservation_date) >= '$dateFrom'";
        }
        if (!empty($dateTo)) {
            $conditions[] = "DATE(r.reservation_date) <= '$dateTo'";
        }
        
        // Status filter
        if ($status !== 'all') {
            $conditions[] = "r.status = '" . mysqli_real_escape_string($connection, $status) . "'";
        }
        
        $whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
        
        // Use complex query since we need JOIN (fetch() is for single table)
        $sql = "SELECT r.*, c.first_name, c.last_name, c.email 
                FROM {$this->reservationTable} r 
                JOIN {$this->customerTable} c ON r.customer_id = c.id 
                $whereClause
                ORDER BY r.reservation_date DESC, r.reservation_time DESC";
        
        $result = mysqli_query($connection, $sql);
        $reservations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reservations[] = $row;
        }
        return $reservations;
    }
    
    /**
     * Get reservation statistics
     */
    public function getReservationStatistics() {
        $stats = [];
        
        // Total reservations
        $totalReservations = fetch($this->reservationTable);
        $stats['total_reservations'] = $totalReservations ? count($totalReservations) : 0;
        
        // Today's reservations
        $todayReservations = fetch($this->reservationTable, "DATE(reservation_date) = CURDATE()");
        $stats['today_reservations'] = $todayReservations ? count($todayReservations) : 0;
        
        // Pending reservations
        $pendingReservations = fetch($this->reservationTable, "status = 'pending'");
        $stats['pending_reservations'] = $pendingReservations ? count($pendingReservations) : 0;
        
        // Confirmed reservations
        $confirmedReservations = fetch($this->reservationTable, "status = 'confirmed'");
        $stats['confirmed_reservations'] = $confirmedReservations ? count($confirmedReservations) : 0;
        
        return $stats;
    }
    
    /**
     * Get all customers for dropdown - using DRY fetch() function
     */
    public function getAllCustomers() {
        return fetch($this->customerTable, '', 'first_name ASC');
    }
    
    /**
     * Add reservation with validation
     */
    private function addReservation() {
        $customerId = intval($_POST['customer_id'] ?? 0);
        $reservationDate = $_POST['reservation_date'] ?? '';
        $reservationTime = $_POST['reservation_time'] ?? '';
        $partySize = intval($_POST['party_size'] ?? 0);
        $tableNumber = $_POST['table_number'] ? intval($_POST['table_number']) : null;
        $specialRequests = trim($_POST['special_requests'] ?? '');
        
        // Enhanced validation
        if ($customerId <= 0 || empty($reservationDate) || empty($reservationTime) || $partySize <= 0) {
            return $this->redirectWithError("Customer, date, time, and party size are required.");
        }
        
        // Validate date is not in the past
        if (strtotime($reservationDate) < strtotime(date('Y-m-d'))) {
            return $this->redirectWithError("Reservation date cannot be in the past.");
        }
        
        // Check if customer exists
        $customer = fetch($this->customerTable, "id = $customerId");
        if (!$customer) {
            return $this->redirectWithError("Selected customer does not exist.");
        }
        
        // Check for duplicate reservations (same customer, date, time)
        $existingReservation = fetch($this->reservationTable, "customer_id = $customerId AND reservation_date = '$reservationDate' AND reservation_time = '$reservationTime'");
        if ($existingReservation) {
            return $this->redirectWithError("Customer already has a reservation at this date and time.");
        }
        
        // Check table availability if table number specified
        if ($tableNumber) {
            $activeStatuses = "'" . implode("', '", self::getActiveStatuses()) . "'";
            $tableConflict = fetch($this->reservationTable, "table_number = $tableNumber AND reservation_date = '$reservationDate' AND reservation_time = '$reservationTime' AND status IN ($activeStatuses)");
            if ($tableConflict) {
                return $this->redirectWithError("Table $tableNumber is already reserved for this date and time.");
            }
        }
        
        // Prepare data for insertion using generic save function
        $reservationData = [
            'customer_id' => $customerId,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'party_size' => $partySize,
            'table_number' => $tableNumber,
            'special_requests' => $specialRequests,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $reservationId = save($this->reservationTable, $reservationData);
        
        if ($reservationId) {
            return $this->redirectWithSuccess("Reservation added successfully!");
        } else {
            return $this->redirectWithError("Failed to add reservation. Please try again.");
        }
    }
    
    /**
     * Update reservation
     */
    private function updateReservation() {
        $id = intval($_POST['reservation_id'] ?? 0);
        $customerId = intval($_POST['customer_id'] ?? 0);
        $reservationDate = $_POST['reservation_date'] ?? '';
        $reservationTime = $_POST['reservation_time'] ?? '';
        $partySize = intval($_POST['party_size'] ?? 0);
        $tableNumber = $_POST['table_number'] ? intval($_POST['table_number']) : null;
        $specialRequests = trim($_POST['special_requests'] ?? '');
        
        // Enhanced validation
        if ($id <= 0 || $customerId <= 0 || empty($reservationDate) || empty($reservationTime) || $partySize <= 0) {
            return $this->redirectWithError("All required fields must be filled.");
        }
        
        // Check if reservation exists
        $existingReservation = fetch($this->reservationTable, "id = $id");
        if (!$existingReservation) {
            return $this->redirectWithError("Reservation not found.");
        }
        
        // Check table availability if table number changed
        if ($tableNumber) {
            $activeStatuses = "'" . implode("', '", self::getActiveStatuses()) . "'";
            $tableConflict = fetch($this->reservationTable, "table_number = $tableNumber AND reservation_date = '$reservationDate' AND reservation_time = '$reservationTime' AND status IN ($activeStatuses) AND id != $id");
            if ($tableConflict) {
                return $this->redirectWithError("Table $tableNumber is already reserved for this date and time.");
            }
        }
        
        // Prepare update data using generic update function
        $updateData = [
            'customer_id' => $customerId,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'party_size' => $partySize,
            'table_number' => $tableNumber,
            'special_requests' => $specialRequests,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (update($this->reservationTable, $updateData, "id = $id")) {
            return $this->redirectWithSuccess("Reservation updated successfully!");
        } else {
            return $this->redirectWithError("Failed to update reservation.");
        }
    }
    
    /**
     * Update reservation status - using DRY update() function
     */
    private function updateReservationStatus() {
        $id = intval($_POST['reservation_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        $validStatuses = self::getValidStatuses();
        if ($id <= 0 || !in_array($status, $validStatuses)) {
            return $this->redirectWithError("Invalid reservation ID or status.");
        }
        
        $updateData = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        
        if (update($this->reservationTable, $updateData, "id = $id")) {
            return $this->redirectWithSuccess("Reservation status updated successfully!");
        } else {
            return $this->redirectWithError("Failed to update reservation status.");
        }
    }
    
    /**
     * Delete reservation - using DRY delete() function
     */
    private function deleteReservation() {
        $id = intval($_POST['reservation_id'] ?? 0);
        
        if ($id <= 0) {
            return $this->redirectWithError("Reservation ID is required.");
        }
        
        // Check if reservation exists
        $reservation = fetch($this->reservationTable, "id = $id");
        if (!$reservation) {
            return $this->redirectWithError("Reservation not found.");
        }
        
        // Prevent deletion of confirmed reservations
        if ($reservation[0]['status'] === 'confirmed') {
            return $this->redirectWithError("Cannot delete confirmed reservations. Please cancel first.");
        }
        
        if (delete($this->reservationTable, $id)) {
            return $this->redirectWithSuccess("Reservation deleted successfully!");
        } else {
            return $this->redirectWithError("Failed to delete reservation.");
        }
    }
    
    /**
     * Bulk delete reservations
     */
    private function bulkDeleteReservations() {
        $reservationIds = $_POST['reservation_ids'] ?? [];
        
        if (empty($reservationIds) || !is_array($reservationIds)) {
            return $this->redirectWithError("No reservations selected for deletion.");
        }
        
        $deletedCount = 0;
        $errorMessages = [];
        
        foreach ($reservationIds as $reservationId) {
            $reservationId = intval($reservationId);
            
            // Check reservation status
            $reservation = fetch($this->reservationTable, "id = $reservationId");
            if (!$reservation) {
                $errorMessages[] = "Reservation #$reservationId not found";
                continue;
            }
            
            if ($reservation[0]['status'] === 'confirmed') {
                $errorMessages[] = "Cannot delete confirmed reservation #$reservationId";
                continue;
            }
            
            // Delete reservation
            if (delete($this->reservationTable, $reservationId)) {
                $deletedCount++;
            }
        }
        
        $message = "Successfully deleted $deletedCount reservation(s).";
        if (!empty($errorMessages)) {
            $message .= " Errors: " . implode(', ', $errorMessages);
        }
        
        return $this->redirectWithSuccess($message);
    }
    

    
    private function redirectWithSuccess($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "success");
    }
    
    private function redirectWithError($message) {
        return redirect_with_message($_SERVER['PHP_SELF'], $message, "error");
    }
}
?>