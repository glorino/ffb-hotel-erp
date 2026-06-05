<?php
$page_title = 'Our Services - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

// Fetch services from DB
$stmt = $db->prepare("SELECT * FROM services WHERE status = 'active' ORDER BY category, name");
$stmt->execute();
$services = $stmt->fetchAll();

// Fetch unique categories for grouping
$stmt_cat = $db->prepare("SELECT DISTINCT category FROM services WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category");
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

$has_db_services = !empty($services);
?>
<!-- Page Hero -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Services</span>
        </nav>
        <h1 class="page-hero-title">Our Services</h1>
        <p class="hero-subtitle">World-class amenities and services tailored for your comfort</p>
    </div>
</section>

<!-- Services Grid -->
<section class="section-padding">
    <div class="container">
        <?php if ($has_db_services): ?>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <div class="mb-5">
                    <h3 style="font-family: var(--font-serif); color: var(--charcoal); margin-bottom: 24px;">
                        <i class="bi bi-<?php echo $category === 'room_service' ? 'bell' : ($category === 'restaurant' ? 'cup-straw' : ($category === 'pool' ? 'water' : ($category === 'gym' ? 'dumbbell' : ($category === 'spa' ? 'flower1' : 'star')))); ?> me-2" style="color: var(--gold);"></i>
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $category))); ?>
                    </h3>
                    <div class="row g-4">
                        <?php foreach ($services as $service): ?>
                            <?php if ($service['category'] === $category): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="service-card">
                                    <div class="service-icon">
                                        <i class="bi bi-<?php echo $category === 'room_service' ? 'bell' : ($category === 'restaurant' ? 'cup-straw' : ($category === 'pool' ? 'water' : ($category === 'gym' ? 'dumbbell' : ($category === 'spa' ? 'flower1' : 'star')))); ?>"></i>
                                    </div>
                                    <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <p class="service-desc"><?php echo htmlspecialchars($service['description'] ?? 'Premium service to enhance your stay.'); ?></p>
                                    <div class="service-price">
                                        <?php echo $service['price'] > 0 ? formatMoney($service['price']) : 'Complimentary'; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($services as $service): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="service-card">
                            <div class="service-icon"><i class="bi bi-star"></i></div>
                            <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="service-desc"><?php echo htmlspecialchars($service['description'] ?? 'Premium service to enhance your stay.'); ?></p>
                            <div class="service-price">
                                <?php echo $service['price'] > 0 ? formatMoney($service['price']) : 'Complimentary'; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Static premium services -->
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-bell"></i></div>
                        <h3 class="service-title">24/7 Room Service</h3>
                        <p class="service-desc">Round-the-clock in-room dining and concierge service. Enjoy gourmet meals, refreshments, and assistance whenever you need it.</p>
                        <div class="service-price">Complimentary</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-cup-straw"></i></div>
                        <h3 class="service-title">Fine Dining Restaurant</h3>
                        <p class="service-desc">Award-winning restaurant serving exquisite local and international cuisine crafted by our world-class chefs.</p>
                        <div class="service-price">From <?php echo CURRENCY_SYMBOL; ?>15,000</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-water"></i></div>
                        <h3 class="service-title">Infinity Pool</h3>
                        <p class="service-desc">Stunning infinity-edge swimming pool with panoramic city views, poolside bar, and lounge area.</p>
                        <div class="service-price">Complimentary</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-dumbbell"></i></div>
                        <h3 class="service-title">Fitness Center</h3>
                        <p class="service-desc">State-of-the-art fitness facility equipped with premium cardio machines, free weights, and personal training.</p>
                        <div class="service-price">Complimentary</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-flower1"></i></div>
                        <h3 class="service-title">Spa &amp; Wellness</h3>
                        <p class="service-desc">Full-service spa offering massages, facials, body treatments, and holistic wellness therapies.</p>
                        <div class="service-price">From <?php echo CURRENCY_SYMBOL; ?>25,000</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-building"></i></div>
                        <h3 class="service-title">Conference &amp; Events</h3>
                        <p class="service-desc">Versatile event spaces for business meetings, conferences, weddings, and social celebrations.</p>
                        <div class="service-price">From <?php echo CURRENCY_SYMBOL; ?>100,000</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-car-front"></i></div>
                        <h3 class="service-title">Airport Transfers</h3>
                        <p class="service-desc">Luxury airport transfers in premium vehicles with professional chauffeurs.</p>
                        <div class="service-price">From <?php echo CURRENCY_SYMBOL; ?>30,000</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-wifi"></i></div>
                        <h3 class="service-title">High-Speed WiFi</h3>
                        <p class="service-desc">Complimentary high-speed wireless internet throughout all properties.</p>
                        <div class="service-price">Complimentary</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon"><i class="bi bi-bag"></i></div>
                        <h3 class="service-title">Concierge Service</h3>
                        <p class="service-desc">Personalized concierge service to assist with bookings, recommendations, and special requests.</p>
                        <div class="service-price">Complimentary</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Experience Premium Service?</h2>
        <p class="cta-desc">Book your stay today and enjoy complimentary access to our premium facilities.</p>
        <div class="cta-actions">
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg">
                <i class="bi bi-calendar-check me-2"></i>Book Now
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
