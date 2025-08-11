<?php 
include '../includes/header.php'; 
require_once '../config/db_connect.php';

// Get active banners for carousel
$result = $conn->query("SELECT * FROM banners WHERE active = 1 ORDER BY date_uploaded DESC");
$activeBanners = [];
while ($row = $result->fetch_assoc()) {
    $activeBanners[] = $row;
}

// Get best seller menu items for showcase
$bestSellersResult = $conn->query("SELECT * FROM menu WHERE is_best_seller = 1 ORDER BY name ASC LIMIT 6");
$bestSellers = [];
while ($row = $bestSellersResult->fetch_assoc()) {
    $bestSellers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ellens Food House | Home</title>
    <link rel="stylesheet" href="../assets/css/home.css">
    <!-- Bootstrap CSS for carousel -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="home-page">

<div class="hero-section">
    <?php include '../includes/logoheader.php'; ?>
</div>

<?php if (!empty($activeBanners)): ?>
<!-- Banner Carousel -->
<div class="banner-carousel">
    <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($activeBanners as $index => $banner): ?>
                <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                        <?php echo $index === 0 ? 'class="active" aria-current="true"' : ''; ?> 
                        aria-label="Slide <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($activeBanners as $index => $banner): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="../uploads/banners/<?php echo htmlspecialchars($banner['filename']); ?>" 
                         class="d-block w-100" 
                         alt="<?php echo htmlspecialchars($banner['title']); ?>">
                    <div class="carousel-caption d-none d-md-block">
                        <h5><?php echo htmlspecialchars($banner['title']); ?></h5>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</div>
<?php endif; ?>

<section class="showcase">
    <h2 class="showcase-title">Our Bestsellers</h2>
    <div class="food-cards">
        <?php if (!empty($bestSellers)): ?>
            <?php foreach ($bestSellers as $item): ?>
                <div class="food-card">
                    <?php if ($item['image_path']): ?>
                        <img src="../uploads/menu/<?php echo htmlspecialchars($item['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php else: ?>
                        <img src="../assets/images/food1.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback to static content if no best sellers in database -->
            <div class="food-card">
                <img src="../assets/images/food1.jpg" alt="Grilled Salmon">
                <h3>Grilled Salmon</h3>
                <p>Freshly grilled salmon served with seasonal vegetables and a lemon butter sauce.</p>
            </div>
            <div class="food-card">
                <img src="../assets/images/food2.jpg" alt="Classic Burger">
                <h3>Classic Burger</h3>
                <p>Juicy beef patty, cheddar cheese, lettuce, tomato, and our signature sauce on a toasted bun.</p>
            </div>
            <div class="food-card">
                <img src="../assets/images/food3.jpg" alt="Caesar Salad">
                <h3>Caesar Salad</h3>
                <p>Crisp romaine lettuce, parmesan, croutons, and creamy Caesar dressing.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($bestSellers)): ?>
        <div class="view-menu-btn">
            <a href="menu.php" class="btn-primary">View Full Menu</a>
        </div>
    <?php endif; ?>
</section>

<?php include '../includes/footer.php'; ?>

<!-- Bootstrap JS for carousel functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>