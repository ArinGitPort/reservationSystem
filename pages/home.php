<?php 
$page_title = 'Ellen\'s Food House | Home';
require_once '../config/db_model.php';

// Get active banners for carousel and auto-deactivate expired events
$today = date('Y-m-d');

// First, automatically deactivate expired events using enhanced update function
update('banners', ['active' => 0], "event_end_date < '{$today}' AND active = 1");

// Get active banners that are currently running or upcoming using enhanced fetch function
$activeBanners = fetch('banners', "active = 1 AND (event_end_date >= '{$today}' OR event_end_date IS NULL)", "event_start_date ASC, date_uploaded DESC");
include '../includes/header.php';

?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Delicious Filipino Cuisine Awaits</h1>
                <p>Experience authentic Filipino flavors crafted with love and served with pride. From traditional favorites to modern twists, every dish tells a story of our rich culinary heritage.</p>
                <div class="hero-actions">
                    <a href="menu.php" class="btn btn-primary btn-large">
                        <i class="fas fa-utensils"></i>
                        View Our Menu
                    </a>
                    <a href="#features" class="btn btn-outline btn-large">
                        <i class="fas fa-info-circle"></i>
                        Learn More
                    </a>
                </div>
            </div>
            
            <div class="hero-visual">
                <?php if (!empty($activeBanners)): ?>
                <!-- Event Banner Carousel -->
                <div class="banner-carousel">
                    <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                        <div class="carousel-inner">
                            <?php foreach ($activeBanners as $index => $banner): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="../uploads/banners/<?php echo htmlspecialchars($banner['filename']); ?>" 
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
                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                        <h3>No Events Currently</h3>
                        <p>Check back soon for exciting events and special offers!</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Authentic Flavors</h3>
                    <p>Experience the true taste of Filipino cuisine with recipes passed down through generations, using only the finest ingredients.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Fresh Daily</h3>
                    <p>All our dishes are prepared fresh daily using locally sourced ingredients to ensure the highest quality and taste.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Made with Love</h3>
                    <p>Every meal is crafted with care and attention to detail, bringing you the warmth and comfort of home-cooked Filipino food.</p>
                </div>
            </div>
        </section>

        <!-- Call to Action Section -->
        <section class="cta-section">
            <h2>Ready to Experience Ellen's?</h2>
            <p>Join us for an unforgettable dining experience where tradition meets taste</p>
            <div class="hero-actions">
                <a href="menu.php" class="btn btn-primary btn-large">
                    <i class="fas fa-shopping-cart"></i>
                    Order Online
                </a>
                <a href="#contact" class="btn btn-outline btn-large">
                    <i class="fas fa-map-marker-alt"></i>
                    Visit Us
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer" id="contact">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4>Ellen's Food House</h4>
                    <p>Serving authentic Filipino cuisine with pride since our establishment. Experience the rich flavors and warm hospitality that make our restaurant a home away from home.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-phone"></i> +63 123 456 7890</p>
                    <p><i class="fas fa-envelope"></i> info@ellensfoodhouse.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Main Street, City, Philippines</p>
                </div>
                
                <div class="footer-section">
                    <h4>Opening Hours</h4>
                    <p>Monday - Saturday: 10:00 AM - 9:00 PM</p>
                    <p>Sunday: 11:00 AM - 8:00 PM</p>
                    <p>Closed on major holidays</p>
                </div>
                
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <p>Stay connected for updates and special offers</p>
                    <div style="margin-top: 1rem;">
                        <a href="#" style="margin-right: 1rem;"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" style="margin-right: 1rem;"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Ellen's Food House. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS for carousel functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>