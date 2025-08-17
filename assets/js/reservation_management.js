function confirmDelete(type, id) {
    document.getElementById('deleteReservationId').value = id;
}

function updateStatus(reservationId, status) {
    document.getElementById('status_reservation_id').value = reservationId;
    document.getElementById('status_value').value = status;
    document.getElementById('statusUpdateForm').submit();
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
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
