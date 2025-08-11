<?php 
include '../includes/header.php'; 
require_once '../config/db_connect.php';

// Get all menu items
$result = $conn->query("SELECT * FROM menu ORDER BY is_best_seller DESC, name ASC");
$menuItems = [];
while ($row = $result->fetch_assoc()) {
    $menuItems[] = $row;
}
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

<!-- Main Menu Content -->
<main class="main-content">
    <?php if (!empty($menuItems)): ?>
    <section class="menu-section">
        <div class="container">
            <h2 class="section-title">Our Menu</h2>
            <div class="menu-grid">
                <?php foreach ($menuItems as $item): ?>
                    <div class="menu-item <?php echo $item['is_best_seller'] ? 'featured' : ''; ?>">
                        <?php if ($item['image_path']): ?>
                            <div class="item-image">
                                <img src="../uploads/menu/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php if ($item['is_best_seller']): ?>
                                    <div class="bestseller-badge">Best Seller</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="item-content">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-price">₱<?php echo number_format($item['price'], 2); ?></p>
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
