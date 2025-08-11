<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<header>
    <nav class="navbar">
        <div class="logo">Ellens Food House</div>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" aria-label="Toggle Menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
        
        <!-- Navigation Links -->
        <ul class="nav-links">
            <li><a href="../pages/home.php">Home</a></li>
            <li><a href="../pages/menu.php">Menu</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        
        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay"></div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    const overlay = document.querySelector('.mobile-menu-overlay');
    const body = document.body;
    
    function toggleMobileMenu() {
        const isActive = navLinks.classList.contains('active');
        
        if (isActive) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }
    
    function openMobileMenu() {
        mobileToggle.classList.add('active');
        navLinks.classList.add('active');
        overlay.classList.add('active');
        body.classList.add('mobile-menu-open');
        
        // Ensure nav-links is visible
        navLinks.style.display = 'flex';
        
        // Add slight delay for animation
        setTimeout(() => {
            navLinks.style.visibility = 'visible';
            navLinks.style.opacity = '1';
        }, 10);
    }
    
    function closeMobileMenu() {
        mobileToggle.classList.remove('active');
        navLinks.classList.remove('active');
        overlay.classList.remove('active');
        body.classList.remove('mobile-menu-open');
        
        // Hide with animation
        navLinks.style.visibility = 'hidden';
        navLinks.style.opacity = '0';
        
        // Completely hide after animation
        setTimeout(() => {
            navLinks.style.display = 'none';
        }, 300);
    }
    
    // Event listeners
    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
    }
    
    // Close menu when clicking on a link
    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Reset mobile menu on desktop
            closeMobileMenu();
            navLinks.style.display = '';
            navLinks.style.visibility = '';
            navLinks.style.opacity = '';
        } else {
            // Ensure menu is hidden on mobile
            if (!navLinks.classList.contains('active')) {
                navLinks.style.display = 'none';
                navLinks.style.visibility = 'hidden';
                navLinks.style.opacity = '0';
            }
        }
    });
    
    // Initial setup for mobile
    if (window.innerWidth <= 768) {
        navLinks.style.display = 'none';
        navLinks.style.visibility = 'hidden';
        navLinks.style.opacity = '0';
    }
});
</script>