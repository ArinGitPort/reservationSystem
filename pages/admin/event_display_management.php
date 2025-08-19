<?php
require_once '../../config/db_connect.php';
require_once '../../config/db_model.php';

// Handle form submissions
$message = '';
$messageType = '';

// Handle GET parameters for messages (after redirect)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $messageType = $_GET['type'];
    
    // Clear the URL parameters to prevent message showing on refresh
    echo "<script>
        if (window.history.replaceState) {Q
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_banner':
                $title = $_POST['title'];
                $description = $_POST['description'];
                $event_start_date = $_POST['event_start_date'];
                $event_end_date = $_POST['event_end_date'];
                $active = isset($_POST['active']) ? 1 : 0;
                
                // Validate dates
                if ($event_end_date < $event_start_date) {
                    redirect_with_message($_SERVER['PHP_SELF'], "End date cannot be earlier than start date.", "error");
                    break;
                }
                
                // Prepare data for insertion using enhanced save function
                $bannerData = [
                    'title' => $title,
                    'description' => $description,
                    'event_start_date' => $event_start_date,
                    'event_end_date' => $event_end_date,
                    'active' => $active,
                    'filename' => ''
                ];
                
                // Insert banner with automatic image handling using enhanced save function
                $bannerId = save('banners', $bannerData, 'banner_image', '../../uploads/banners/');
                
                if ($bannerId) {
                    redirect_with_message($_SERVER['PHP_SELF'], "Event banner uploaded successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to save event banner to database.", "error");
                }
                break;
                
            case 'toggle_banner':
                $bannerId = $_POST['banner_id'];
                $currentStatus = $_POST['current_status'];
                $newStatus = $currentStatus == 1 ? 0 : 1;
                
                // Use enhanced update function
                $updateData = ['active' => $newStatus];
                if (update('banners', $updateData, "banner_id = $bannerId")) {
                    $statusText = $newStatus ? 'activated' : 'deactivated';
                    redirect_with_message($_SERVER['PHP_SELF'], "Banner {$statusText} successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to update banner status.", "error");
                }
                break;
                
            case 'delete_banner':
                $bannerId = $_POST['banner_id'];
                $filename = $_POST['filename'];
                
                // Delete from database using enhanced delete function
                if (delete('banners', $bannerId, 'banner_id')) {
                    // Delete image file if exists
                    if ($filename) {
                        $filePath = "../../uploads/banners/" . $filename;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    redirect_with_message($_SERVER['PHP_SELF'], "Banner deleted successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to delete banner.", "error");
                }
                break;
        }
    }
}

// Get all banners for display using enhanced functions and auto-deactivate expired events
$today = date('Y-m-d');

// First, automatically deactivate expired events using enhanced update function
update('banners', ['active' => 0], "event_end_date < '$today' AND active = 1");

// Then get all banners for display using fetch function
// Since we need complex SELECT with CASE, we'll use direct query for this specific case
global $connection;
$result = mysqli_query($connection, "SELECT *, 
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
    FROM banners ORDER BY date_uploaded DESC");
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Display Management - Ellen's Food House</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/account_management.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
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
        
        <!-- Event Display Management -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-calendar-alt me-2"></i>Event Display Management</h3>
            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#uploadBannerModal">
                <i class="fas fa-upload me-2"></i>Upload Event Banner
            </button>
        </div>
        
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>PREVIEW</th>
                    <th>EVENT DETAILS</th>
                    <th>EVENT PERIOD</th>
                    <th>STATUS</th>
                    <th>UPLOADED</th>
                    <th>MANAGE</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Using display_all function with table format for banners
                $sql = "SELECT banner_id, title, description, event_start_date, event_end_date, event_date, 
                               active, date_uploaded, filename
                        FROM banners ORDER BY date_uploaded DESC";
                $column_mappings = []; // Not used for event_display_management special handling
                display_all($sql, $column_mappings, 'event_display_management.php', 'table');
                ?>
            </tbody>
        </table>
    </div>
    <!-- End Main Content -->
    
    <!-- Upload Banner Modal -->
    <div class="modal fade" id="uploadBannerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Event Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload_banner">
                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title</label>
                            <input type="text" class="form-control" name="title" placeholder="e.g., Christmas Special Menu" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Event Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe what this event is about, special offers, etc." required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_start_date" class="form-label">Event Start Date</label>
                                    <input type="date" class="form-control" name="event_start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_end_date" class="form-label">Event End Date</label>
                                    <input type="date" class="form-control" name="event_end_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="banner_image" class="form-label">Event Banner Image</label>
                            <input type="file" class="form-control" name="banner_image" accept=".jpg,.jpeg,.png" required>
                            <div class="form-text">Accepted formats: JPG, PNG. Max size: 2MB. Recommended size: 1200x400px</div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="active" value="1" checked>
                            <label class="form-check-label">Set as Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Upload Event Banner</button>
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
                    <p>Are you sure you want to delete this banner? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="action" value="delete_banner">
                        <input type="hidden" name="banner_id" id="deleteBannerId">
                        <input type="hidden" name="filename" id="deleteFilename">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(bannerId, filename) {
            document.getElementById('deleteBannerId').value = bannerId;
            document.getElementById('deleteFilename').value = filename;
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
                }, 5000);
            });
            
            // Set minimum date for event dates to today
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.querySelector('input[name="event_start_date"]');
            const endDateInput = document.querySelector('input[name="event_end_date"]');
            
            if (startDateInput) {
                startDateInput.setAttribute('min', today);
                startDateInput.addEventListener('change', function() {
                    endDateInput.setAttribute('min', this.value);
                    if (endDateInput.value && endDateInput.value < this.value) {
                        endDateInput.value = this.value;
                    }
                });
            }
            
            if (endDateInput) {
                endDateInput.setAttribute('min', today);
            }
        });
    </script>
</body>
</html>
