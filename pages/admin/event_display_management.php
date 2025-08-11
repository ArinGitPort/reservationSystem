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
                
                // Handle file upload
                if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
                    $uploadResult = uploadBannerImage($_FILES['banner_image']);
                    
                    if ($uploadResult['success']) {
                        // Save to database using basic mysqli
                        $stmt = $conn->prepare("INSERT INTO banners (filename, title, description, event_start_date, event_end_date, active) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssi", $uploadResult['filename'], $title, $description, $event_start_date, $event_end_date, $active);
                        
                        if ($stmt->execute()) {
                            redirect_with_message($_SERVER['PHP_SELF'], "Event banner uploaded successfully!", "success");
                        } else {
                            redirect_with_message($_SERVER['PHP_SELF'], "Failed to save event banner to database.", "error");
                        }
                        $stmt->close();
                    } else {
                        redirect_with_message($_SERVER['PHP_SELF'], $uploadResult['message'], "error");
                    }
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "No file uploaded or upload error.", "error");
                }
                break;
                
            case 'toggle_banner':
                $bannerId = $_POST['banner_id'];
                $currentStatus = $_POST['current_status'];
                $newStatus = $currentStatus == 1 ? 0 : 1;
                
                $stmt = $conn->prepare("UPDATE banners SET active = ? WHERE banner_id = ?");
                $stmt->bind_param("ii", $newStatus, $bannerId);
                
                if ($stmt->execute()) {
                    $statusText = $newStatus ? 'activated' : 'deactivated';
                    redirect_with_message($_SERVER['PHP_SELF'], "Banner {$statusText} successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to update banner status.", "error");
                }
                $stmt->close();
                break;
                
            case 'delete_banner':
                $bannerId = $_POST['banner_id'];
                $filename = $_POST['filename'];
                
                // Delete from database
                $stmt = $conn->prepare("DELETE FROM banners WHERE banner_id = ?");
                $stmt->bind_param("i", $bannerId);
                
                if ($stmt->execute()) {
                    // Delete file from server
                    $filePath = "../../uploads/banners/" . $filename;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    redirect_with_message($_SERVER['PHP_SELF'], "Banner deleted successfully!", "success");
                } else {
                    redirect_with_message($_SERVER['PHP_SELF'], "Failed to delete banner.", "error");
                }
                $stmt->close();
                break;
        }
    }
}

/**
 * Upload banner image with validation
 */
function uploadBannerImage($file) {
    $uploadDir = "../../uploads/banners/";
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    $fileType = $file['type'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPG and PNG files are allowed.'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size must be less than 2MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Get all banners for display and auto-deactivate expired events
$today = date('Y-m-d');

// First, automatically deactivate expired events
$conn->query("UPDATE banners SET active = 0 WHERE event_end_date < '$today' AND active = 1");

// Then get all banners for display
$result = $conn->query("SELECT *, 
    CASE 
        WHEN event_end_date < '$today' THEN 'expired'
        WHEN event_start_date <= '$today' AND event_end_date >= '$today' THEN 'active'
        WHEN event_start_date > '$today' THEN 'upcoming'
        ELSE 'unknown'
    END as event_status
    FROM banners ORDER BY date_uploaded DESC");
$banners = [];
while ($row = $result->fetch_assoc()) {
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
                <?php foreach ($banners as $index => $banner): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td>
                        <img src="../../uploads/banners/<?php echo htmlspecialchars($banner['filename']); ?>" 
                             alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                             style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                    </td>
                    <td>
                        <div class="fw-bold mb-1"><?php echo htmlspecialchars($banner['title']); ?></div>
                        <?php if (isset($banner['description']) && $banner['description']): ?>
                            <small class="text-muted"><?php echo htmlspecialchars(substr($banner['description'], 0, 80)); ?><?php echo strlen($banner['description']) > 80 ? '...' : ''; ?></small>
                        <?php else: ?>
                            <small class="text-muted">No description</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($banner['event_start_date']) && $banner['event_start_date']): ?>
                            <div class="fw-bold text-primary"><?php echo date('M d, Y', strtotime($banner['event_start_date'])); ?></div>
                            <small class="text-muted">to</small>
                            <div class="fw-bold text-primary"><?php echo date('M d, Y', strtotime($banner['event_end_date'])); ?></div>
                        <?php elseif (isset($banner['event_date']) && $banner['event_date']): ?>
                            <div class="fw-bold text-primary"><?php echo date('M d, Y', strtotime($banner['event_date'])); ?></div>
                        <?php else: ?>
                            <span class="text-muted">Not set</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <span class="badge bg-<?php echo $banner['active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $banner['active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <?php if (isset($banner['event_status'])): ?>
                                <?php 
                                $statusClass = '';
                                $statusText = '';
                                switch($banner['event_status']) {
                                    case 'expired':
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Expired';
                                        break;
                                    case 'active':
                                        $statusClass = 'bg-success';
                                        $statusText = 'Running';
                                        break;
                                    case 'upcoming':
                                        $statusClass = 'bg-info';
                                        $statusText = 'Upcoming';
                                        break;
                                    default:
                                        $statusClass = 'bg-secondary';
                                        $statusText = 'Unknown';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>" style="font-size: 0.7rem;">
                                    <?php echo $statusText; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div><?php echo date('d M Y', strtotime($banner['date_uploaded'])); ?></div>
                        <small class="text-muted"><?php echo date('h:i A', strtotime($banner['date_uploaded'])); ?></small>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_banner">
                                <input type="hidden" name="banner_id" value="<?php echo $banner['banner_id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $banner['active']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Toggle Status">
                                    <i class="fas fa-<?php echo $banner['active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                </button>
                            </form>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="confirmDelete('<?php echo $banner['banner_id']; ?>', '<?php echo htmlspecialchars($banner['filename']); ?>')"
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
