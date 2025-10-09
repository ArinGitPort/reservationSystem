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
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Header CSS - Always included -->
    <link rel="stylesheet" href="../assets/css/header_new.css">
    
    <!-- Page-specific CSS -->
    <?php if ($is_home): ?>
    <link rel="stylesheet" href="../assets/css/home_new.css">
    <?php elseif ($current_page == 'menu.php'): ?>
    <link rel="stylesheet" href="../assets/css/menu.css">
    <?php endif; ?>
</head>
<body>

<!-- Header -->
<header class="main-header">
    <div class="header-container">
        <a href="../pages/home.php" class="logo">
            <img src="../assets/images/mainlogo.png" alt="Ellen's Food House Logo">
            <span>Ellen's Food House</span>
        </a>
        
        <nav class="main-nav">
            <ul>
                <li><a href="../pages/home.php" class="<?php echo $is_home ? 'active' : ''; ?>">Home</a></li>
                <li><a href="../pages/menu.php" class="<?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">Menu</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
        
        <div class="header-actions">
            <a href="../pages/menu.php" class="btn btn-primary">
                <i class="fas fa-utensils"></i>
                Order Now
            </a>
        </div>
    </div>
</header>

