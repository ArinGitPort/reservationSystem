<div class="sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-utensils me-2"></i>Ellen's Food House</h4>
        <p class="text-muted">Admin Panel</p>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-section">
            <h6 class="menu-title">MANAGEMENT</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="account_management.php" id="customers-link">
                        <i class="fas fa-users me-2"></i>
                        <span>Customer Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reservation_management.php" id="reservations-link">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <span>Reservation Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="banner_management.php" id="banners-link">
                        <i class="fas fa-images me-2"></i>
                        <span>Banner Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="menu_management.php" id="menu-link">
                        <i class="fas fa-utensils me-2"></i>
                        <span>Menu Management</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="menu-section">
            <h6 class="menu-title">ANALYTICS</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-bar me-2"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-line me-2"></i>
                        <span>Statistics</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="menu-section">
            <h6 class="menu-title">SYSTEM</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog me-2"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home me-2"></i>
                        <span>Back to Site</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <div class="admin-info">
            <i class="fas fa-user-shield me-2"></i>
            <span>Administrator</span>
        </div>
        <small class="text-muted">Version 1.0</small>
    </div>
</div>

<script>
// Set active navigation link based on current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    // Remove active class from all links
    navLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to current page link
    if (currentPage.includes('account_management.php')) {
        document.getElementById('customers-link').classList.add('active');
    } else if (currentPage.includes('reservation_management.php')) {
        document.getElementById('reservations-link').classList.add('active');
    } else if (currentPage.includes('banner_management.php')) {
        document.getElementById('banners-link').classList.add('active');
    } else if (currentPage.includes('menu_management.php')) {
        document.getElementById('menu-link').classList.add('active');
    }
});
</script>
