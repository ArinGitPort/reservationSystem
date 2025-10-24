<?php
/**
 * Event Display Management View
 */

// Require authentication
require_once '../../controllers/AuthController.php';
AuthController::requireAuth();

// Include the EventDisplayManagementController
require_once '../../controllers/EventDisplayManagementController.php';

// Initialize controller and handle requests
$eventController = new EventDisplayManagementController();
$eventController->handleRequest();

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
$banners = $eventController->getAllBanners();
$bannerDisplayData = $eventController->getBannerDisplayData();
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
                // Pure view logic - render banner data
                if ($banners && count($banners) > 0) {
                    foreach ($banners as $index => $banner) {
                        echo '<tr>';
                        echo '<td>' . ($index + 1) . '</td>';
                        
                        // Banner preview column
                        echo '<td>';
                        if (isset($banner['filename']) && $banner['filename'] && $banner['image_exists']) {
                            echo '<img src="../../uploads/banners/' . htmlspecialchars($banner['filename']) . '" 
                                     alt="' . htmlspecialchars($banner['title']) . '" 
                                     style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">';
                        } else {
                            echo '<div style="width: 80px; height: 50px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image text-muted"></i>
                                  </div>';
                        }
                        echo '</td>';
                        
                        // Event details column
                        echo '<td>';
                        echo '<div class="fw-bold mb-1">' . htmlspecialchars($banner['title']) . '</div>';
                        if (isset($banner['description']) && $banner['description']) {
                            $description = htmlspecialchars(substr($banner['description'], 0, 80));
                            $description .= strlen($banner['description']) > 80 ? '...' : '';
                            echo '<small class="text-muted">' . $description . '</small>';
                        } else {
                            echo '<small class="text-muted">No description</small>';
                        }
                        echo '</td>';
                        
                        // Event date column
                        echo '<td>';
                        if (isset($banner['event_start_date']) && $banner['event_start_date']) {
                            echo '<div class="fw-bold text-primary">' . date('M d, Y', strtotime($banner['event_start_date'])) . '</div>';
                            echo '<small class="text-muted">to</small>';
                            echo '<div class="fw-bold text-primary">' . date('M d, Y', strtotime($banner['event_end_date'])) . '</div>';
                        } elseif (isset($banner['event_date']) && $banner['event_date']) {
                            echo '<div class="fw-bold text-primary">' . date('M d, Y', strtotime($banner['event_date'])) . '</div>';
                        } else {
                            echo '<span class="text-muted">Not set</span>';
                        }
                        echo '</td>';
                        
                        // Status column
                        echo '<td>';
                        echo '<div class="d-flex flex-column gap-1">';
                        $statusClass = $banner['active'] ? 'success' : 'secondary';
                        $statusText = $banner['active'] ? 'Active' : 'Inactive';
                        if (isset($banner['event_status'])) {
                            switch ($banner['event_status']) {
                                case 'expired':
                                    $statusClass = 'warning';
                                    $statusText .= ' (Expired)';
                                    break;
                                case 'upcoming':
                                    $statusClass = 'info';
                                    $statusText .= ' (Upcoming)';
                                    break;
                            }
                        }
                        echo '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>';
                        echo '</div>';
                        echo '</td>';
                        
                        // Uploaded date column
                        echo '<td>' . date('M d, Y', strtotime($banner['date_uploaded'])) . '</td>';
                        
                        // Action buttons column
                        $bannerId = $banner['banner_id'];
                        echo '<td>';
                        echo '<div class="action-buttons">';
                        
                        // Create JSON data for edit functionality
                        $bannerJson = htmlspecialchars(json_encode($banner), ENT_QUOTES, 'UTF-8');
                        echo '<button class="btn btn-sm btn-outline-primary edit-banner-btn" 
                                    data-banner="' . $bannerJson . '"
                                    data-bs-toggle="modal" data-bs-target="#editBannerModal" title="Edit Banner">
                                <i class="fas fa-edit"></i>
                              </button>';
                        echo '<form method="POST" style="display: inline;">';
                        echo '<input type="hidden" name="action" value="toggle_banner">';
                        echo '<input type="hidden" name="banner_id" value="' . $bannerId . '">';
                        echo '<input type="hidden" name="current_status" value="' . $banner['active'] . '">';
                        echo '<button type="submit" class="btn btn-sm btn-outline-secondary" title="Toggle Status">';
                        echo '<i class="fas fa-' . ($banner['active'] ? 'eye-slash' : 'eye') . '"></i>';
                        echo '</button>';
                        echo '</form>';
                        echo '<button class="btn btn-sm btn-outline-danger" 
                                    onclick="confirmDelete(\'' . $bannerId . '\', \'' . htmlspecialchars($banner['filename']) . '\')"
                                    data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" title="Delete Banner">
                                <i class="fas fa-trash"></i>
                              </button>';
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7" class="text-center py-4"><em>No event banners found</em></td></tr>';
                }
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
    
    <!-- Edit Banner Modal -->
    <div class="modal fade" id="editBannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Event Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_banner">
                        <input type="hidden" name="banner_id" id="editBannerId">
                        <input type="hidden" name="current_filename" id="editCurrentFilename">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editTitle" class="form-label">Event Title *</label>
                                    <input type="text" class="form-control" name="title" id="editTitle" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editActive" class="form-label">Status</label>
                                    <select class="form-select" name="active" id="editActive">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Event Description</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3" placeholder="Optional event description..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editEventStartDate" class="form-label">Event Start Date</label>
                                    <input type="date" class="form-control" name="event_start_date" id="editEventStartDate">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editEventEndDate" class="form-label">Event End Date</label>
                                    <input type="date" class="form-control" name="event_end_date" id="editEventEndDate">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editBannerImage" class="form-label">Replace Banner Image</label>
                            <input type="file" class="form-control" name="banner_image" id="editBannerImage" accept="image/*">
                            <div class="form-text">Leave empty to keep current image. Accepted formats: JPG, PNG (Max: 5MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Image Preview</label>
                            <div class="border rounded p-2 bg-light">
                                <img id="editCurrentImagePreview" src="" alt="Current banner" style="max-width: 200px; max-height: 150px; object-fit: cover;" class="rounded">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Banner
                        </button>
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
        
        function editBanner(bannerData) {
            // Parse the banner data
            const banner = JSON.parse(bannerData);
            
            console.log('Banner data:', banner); // Debug log to see what we're getting
            
            // Populate the edit form
            document.getElementById('editBannerId').value = banner.banner_id;
            document.getElementById('editCurrentFilename').value = banner.filename;
            document.getElementById('editTitle').value = banner.title || '';
            document.getElementById('editDescription').value = banner.description || '';
            document.getElementById('editActive').value = banner.active;
            
            // Handle date fields - convert null/undefined/empty to empty string
            const startDate = banner.event_start_date;
            const endDate = banner.event_end_date;
            
            // More robust null checking
            function formatDateForInput(dateValue) {
                if (!dateValue || dateValue === null || dateValue === 'null' || dateValue === '') {
                    return '';
                }
                // If it's a valid date string, return it as is
                return dateValue;
            }
            
            document.getElementById('editEventStartDate').value = formatDateForInput(startDate);
            document.getElementById('editEventEndDate').value = formatDateForInput(endDate);
            
            // Set current image preview
            const currentImagePreview = document.getElementById('editCurrentImagePreview');
            currentImagePreview.src = '../../uploads/banners/' + banner.filename;
            currentImagePreview.alt = banner.title || 'Banner image';
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
            const editStartDateInput = document.querySelector('#editEventStartDate');
            const editEndDateInput = document.querySelector('#editEventEndDate');
            
            // Function to set date constraints
            function setDateConstraints(startInput, endInput) {
                if (startInput) {
                    startInput.setAttribute('min', today);
                    startInput.addEventListener('change', function() {
                        endInput.setAttribute('min', this.value);
                        if (endInput.value && endInput.value < this.value) {
                            endInput.value = this.value;
                        }
                    });
                }
                
                if (endInput) {
                    endInput.setAttribute('min', today);
                }
            }
            
            // Apply constraints to both add and edit forms
            setDateConstraints(startDateInput, endDateInput);
            setDateConstraints(editStartDateInput, editEndDateInput);
            
            // Add event listener for edit banner buttons using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-banner-btn')) {
                    const button = e.target.closest('.edit-banner-btn');
                    const bannerData = button.getAttribute('data-banner');
                    editBanner(bannerData);
                }
            });
        });
    </script>
</body>
</html>
