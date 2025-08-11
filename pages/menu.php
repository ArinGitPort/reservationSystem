<?php 
include '../includes/header.php'; 
require_once '../config/db_connect.php';

// Get all menu items
$result = $conn->query("SELECT * FROM menu ORDER BY is_best_seller DESC, name ASC");
$menuItems = [];
while ($row = $result->fetch_assoc()) {
    $menuItems[] = $row;
}

// Separate best sellers from regular items
$bestSellers = array_filter($menuItems, function($item) {
    return $item['is_best_seller'] == 1;
});

$regularItems = array_filter($menuItems, function($item) {
    return $item['is_best_seller'] == 0;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Ellen's Food House</title>
    <link rel="stylesheet" href="../assets/css/menu.css">
</head>
<body>

<!-- Hero Section -->
<div class="menu-hero">
    <div class="hero-content">
        <h1>Our Menu</h1>
        <p>Discover our carefully crafted dishes made with the finest ingredients</p>
    </div>
</div>

<!-- Main Menu Content -->
<main class="main-content">
    <?php if (!empty($bestSellers)): ?>
    <section class="menu-section">
        <div class="container">
            <h2 class="section-title">
                <span class="star-icon">⭐</span>
                Best Sellers
            </h2>
            <div class="menu-grid">
                <?php foreach ($bestSellers as $item): ?>
                    <div class="menu-item featured">
                        <?php if ($item['image_path']): ?>
                            <div class="item-image">
                                <img src="../uploads/menu/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="bestseller-badge">Best Seller</div>
                            </div>
                        <?php endif; ?>
                        <div class="item-content">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($regularItems)): ?>
    <section class="menu-section">
        <div class="container">
            <h2 class="section-title">All Menu Items</h2>
            <div class="menu-grid">
                <?php foreach ($regularItems as $item): ?>
                    <div class="menu-item">
                        <?php if ($item['image_path']): ?>
                            <div class="item-image">
                                <img src="../uploads/menu/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="item-content">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (empty($menuItems)): ?>
    <section class="menu-section">
        <div class="container">
            <div class="empty-menu">
                <h2>Menu Coming Soon</h2>
                <p>We're working on bringing you an amazing menu experience.</p>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
