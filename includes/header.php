<?php
$page_title = isset($page_title) ? $page_title : 'Ellen\'s Food House';
$current_page = basename($_SERVER['PHP_SELF']);
$is_home = ($current_page == 'home.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <?php if ($is_home): ?>
    <link rel="stylesheet" href="../assets/css/home.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php elseif ($current_page == 'menu.php'): ?>
    <link rel="stylesheet" href="../assets/css/menu.css">
    <?php endif; ?>
</head>
<body>

<!-- Header -->
<header class="main-header <?php echo $is_home ? 'home-page' : ''; ?>">
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <div class="nav-brand">
                <a href="../pages/home.php" class="brand-link">
                    <i class="fas fa-utensils brand-icon"></i>
                    <span class="brand-text">Ellen's Food House</span>
                </a>
            </div>
            
            <!-- Desktop Navigation -->
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="../pages/home.php" class="nav-link <?php echo $is_home ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../pages/menu.php" class="nav-link <?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book-open"></i> Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#about" class="nav-link">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#contact" class="nav-link">
                        <i class="fas fa-phone"></i> Contact
                    </a>
                </li>
            </ul>
            
            <!-- Mobile Menu Toggle -->
            <div class="hamburger" onclick="toggleMobileMenu()">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>
    
    <?php if ($is_home): ?>
    <!-- Hero Section for Home Page -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="hero-logo">
                <img src="../assets/images/mainlogo.png" alt="Ellen's Food House Logo" class="hero-logo-img">
            </div>
            <h1 class="hero-title">Ellen's Food House</h1>
            <p class="hero-subtitle">Delicious food, cozy atmosphere, and unforgettable moments</p>
            <div class="hero-buttons">
                <a href="../pages/menu.php" class="btn btn-primary">
                    <i class="fas fa-book-open"></i> View Menu
                </a>
                <a href="#about" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i> Learn More
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</header>

<script>
// Mobile Menu Toggle
function toggleMobileMenu() {
    const navbar = document.querySelector('.navbar');
    const navMenu = document.querySelector('.nav-menu');
    const hamburger = document.querySelector('.hamburger');
    
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
    
    // Toggle hamburger animation
    const bars = hamburger.querySelectorAll('.bar');
    if (hamburger.classList.contains('active')) {
        bars[0].style.transform = 'rotate(-45deg) translate(-5px, 6px)';
        bars[1].style.opacity = '0';
        bars[2].style.transform = 'rotate(45deg) translate(-5px, -6px)';
    } else {
        bars[0].style.transform = 'none';
        bars[1].style.opacity = '1';
        bars[2].style.transform = 'none';
    }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const navbar = document.querySelector('.navbar');
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (!navbar.contains(event.target)) {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        
        const bars = hamburger.querySelectorAll('.bar');
        bars[0].style.transform = 'none';
        bars[1].style.opacity = '1';
        bars[2].style.transform = 'none';
    }
});
</script>