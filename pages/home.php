<?php 
include '../includes/header.php'; 
require_once '../config/db_connect.php';

// Get active banners for carousel and auto-deactivate expired events
$today = date('Y-m-d');

// First, automatically deactivate expired events
$conn->query("UPDATE banners SET active = 0 WHERE event_end_date < '$today' AND active = 1");

// Get active banners that are currently running or upcoming
$result = $conn->query("SELECT * FROM banners WHERE active = 1 AND (event_end_date >= '$today' OR event_end_date IS NULL) ORDER BY event_start_date ASC, date_uploaded DESC");
$activeBanners = [];
while ($row = $result->fetch_assoc()) {
    $activeBanners[] = $row;
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
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="hero-section">
    <?php include '../includes/logoheader.php'; ?>
</div>

<?php if (!empty($activeBanners)): ?>
<!-- Event Banner Carousel -->
<div class="banner-carousel">
    <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php foreach ($activeBanners as $index => $banner): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="../uploads/banners/<?php echo htmlspecialchars($banner['filename']); ?>" 
                         class="d-block w-100" 
                         alt="<?php echo htmlspecialchars($banner['title']); ?>">
                    <div class="event-info-overlay">
                        <div class="event-content">
                            <h4><?php echo htmlspecialchars($banner['title']); ?></h4>
                            <?php if (isset($banner['description']) && $banner['description']): ?>
                                <p><?php echo htmlspecialchars($banner['description']); ?></p>
                            <?php endif; ?>
                            <?php if (isset($banner['event_start_date']) && $banner['event_start_date']): ?>
                                <div class="event-dates">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($banner['event_start_date'])); ?>
                                    <?php if ($banner['event_end_date'] != $banner['event_start_date']): ?>
                                        - <?php echo date('M d, Y', strtotime($banner['event_end_date'])); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
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
<?php else: ?>
<div class="no-events">
    <div class="no-events-content">
        <h3>No Events Currently</h3>
        <p>Check back soon for exciting events and special offers!</p>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<!-- Bootstrap JS for carousel functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>