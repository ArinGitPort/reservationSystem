// Reservation management functions
function updateReservationStatus(reservationId, status) {
    if (confirm('Are you sure you want to change the status to ' + status + '?')) {
        document.getElementById('status_reservation_id').value = reservationId;
        document.getElementById('status_value').value = status;
        document.getElementById('statusUpdateForm').submit();
    }
}

function confirmDelete(type, id) {
    document.getElementById('deleteReservationId').value = id;
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
}

// View reservation details
function viewReservationDetails(reservationId) {
    const modal = new bootstrap.Modal(document.getElementById('reservationDetailsModal'));
    const content = document.getElementById('reservationDetailsContent');
    
    // Show loading
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading reservation details...</div>';
    modal.show();
    
    // Find reservation data from current page (for demo - in production use AJAX)
    setTimeout(() => {
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Reservation Information</h6>
                    <p><strong>ID:</strong> ${reservationId}</p>
                    <p><strong>Status:</strong> <span class="badge bg-info">Confirmed</span></p>
                    <p><strong>Created:</strong> ${new Date().toLocaleDateString()}</p>
                </div>
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <p><em>Customer details will be loaded here...</em></p>
                </div>
            </div>
            <hr>
            <h6>Special Requests</h6>
            <p><em>Special requests will be shown here...</em></p>
        `;
    }, 1000);
}

// Edit reservation
function editReservation(reservationId) {
    // In production, you'd fetch the reservation data via AJAX
    // For now, this is a placeholder
    document.getElementById('edit_reservation_id').value = reservationId;
    // You would populate other fields with actual data here
}

// Bulk selection functionality
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const reservationCheckboxes = document.querySelectorAll('.reservation-checkbox');
    
    reservationCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.reservation-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (checkedBoxes.length > 0) {
        bulkDeleteBtn.style.display = 'inline-block';
    } else {
        bulkDeleteBtn.style.display = 'none';
    }
}

function bulkDeleteReservations() {
    const checkedBoxes = document.querySelectorAll('.reservation-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select reservations to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${checkedBoxes.length} reservation(s)? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">';
        
        checkedBoxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reservation_ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Filter functions
function clearReservationFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('status-filter').value = 'all';
    document.getElementById('reservation_date-from').value = '';
    document.getElementById('reservation_date-to').value = '';
    window.location.reload();
}

function refreshReservations() {
    window.location.reload();
}

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
            const statusButton = row.querySelector('.dropdown-toggle');
            const rowStatus = statusButton ? statusButton.textContent.toLowerCase().trim() : '';
            
            let showRow = true;
            
            // Search filter
            if (searchTerm && !text.includes(searchTerm)) {
                showRow = false;
            }
            
            // Status filter
            if (statusValue !== 'all' && statusValue !== rowStatus) {
                showRow = false;
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
