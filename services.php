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
                    <h3 style="font-family: var(--font-serif); color: var(--charcoal); margin-bottom: 20px;">
                        <i class="bi bi-<?php echo $category === 'room_service' ? 'bell' : ($category === 'restaurant' ? 'cup-straw' : ($category === 'pool' ? 'water' : ($category === 'gym' ? 'dumbbell' : ($category === 'spa' ? 'flower1' : 'star')))); ?> me-2" style="color: var(--gold);"></i>
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $category))); ?>
                    </h3>
                    <div class="listing-grid">
                        <?php $svc_idx = 0; ?>
                        <?php foreach ($services as $service): ?>
                            <?php if ($service['category'] === $category): ?>
                            <?php $svc_grads = ['0b1320,#1a2a4a','1a0a20,#2a1a3a','0a2010,#1a3a2a','20100a,#3a2a1a','100a20,#2a1a3a']; ?>
                            <div class="listing-card">
                                <div class="listing-image">
                                    <div class="listing-img-bg" style="background:linear-gradient(135deg,<?php echo $svc_grads[$svc_idx % 5]; ?>)"></div>
                                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:2.2rem;"><i class="bi bi-<?php echo $category === 'room_service' ? 'bell' : ($category === 'restaurant' ? 'cup-straw' : ($category === 'pool' ? 'water' : ($category === 'gym' ? 'dumbbell' : ($category === 'spa' ? 'flower1' : 'star')))); ?>"></i></div>
                                </div>
                                <div class="listing-body">
                                    <h3 class="listing-title" style="font-size:0.95rem;"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <p class="listing-desc"><?php echo htmlspecialchars(truncate($service['description'] ?? 'Premium service to enhance your stay.', 70)); ?></p>
                                    <div class="listing-footer" style="border:none;padding:8px 0 12px;">
                                        <div class="listing-price" style="font-size:0.85rem;font-family:var(--font-accent);font-weight:500;">
                                            <?php echo !empty($service['price']) ? formatMoney($service['price']) : 'Complimentary'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $svc_idx++; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="listing-grid">
                    <?php $svc_idx = 0; ?>
                    <?php foreach ($services as $service): ?>
                    <?php $svc_grads = ['0b1320,#1a2a4a','1a0a20,#2a1a3a','0a2010,#1a3a2a','20100a,#3a2a1a','100a20,#2a1a3a']; ?>
                    <div class="listing-card">
                        <div class="listing-image">
                            <div class="listing-img-bg" style="background:linear-gradient(135deg,<?php echo $svc_grads[$svc_idx % 5]; ?>)"></div>
                            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:2.2rem;"><i class="bi bi-star"></i></div>
                        </div>
                        <div class="listing-body">
                            <h3 class="listing-title" style="font-size:0.95rem;"><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="listing-desc"><?php echo htmlspecialchars(truncate($service['description'] ?? 'Premium service to enhance your stay.', 70)); ?></p>
                            <div class="listing-footer" style="border:none;padding:8px 0 12px;">
                                <div class="listing-price" style="font-size:0.85rem;font-family:var(--font-accent);font-weight:500;">
                                    <?php echo !empty($service['price']) ? formatMoney($service['price']) : 'Complimentary'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $svc_idx++; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="listing-grid">
                <?php $svcs = [
                    ['icon' => 'bell', 'title' => '24/7 Room Service', 'desc' => 'Round-the-clock in-room dining and concierge service. Enjoy gourmet meals whenever you need it.', 'price' => 'Complimentary', 'grad' => '0b1320,#1a2a4a'],
                    ['icon' => 'cup-straw', 'title' => 'Fine Dining Restaurant', 'desc' => 'Award-winning restaurant serving exquisite local and international cuisine by world-class chefs.', 'price' => 'From '.CURRENCY_SYMBOL.'15,000', 'grad' => '1a0a20,#2a1a3a'],
                    ['icon' => 'water', 'title' => 'Infinity Pool', 'desc' => 'Stunning infinity-edge swimming pool with panoramic city views and poolside bar.', 'price' => 'Complimentary', 'grad' => '0a2010,#1a3a2a'],
                    ['icon' => 'dumbbell', 'title' => 'Fitness Center', 'desc' => 'State-of-the-art facility with premium cardio machines, free weights, and personal training.', 'price' => 'Complimentary', 'grad' => '20100a,#3a2a1a'],
                    ['icon' => 'flower1', 'title' => 'Spa &amp; Wellness', 'desc' => 'Full-service spa offering massages, facials, body treatments, and holistic wellness therapies.', 'price' => 'From '.CURRENCY_SYMBOL.'25,000', 'grad' => '100a20,#2a1a3a'],
                    ['icon' => 'building', 'title' => 'Conference &amp; Events', 'desc' => 'Versatile event spaces for business meetings, conferences, weddings, and celebrations.', 'price' => 'From '.CURRENCY_SYMBOL.'100,000', 'grad' => '0b1320,#1a2a4a'],
                    ['icon' => 'car-front', 'title' => 'Airport Transfers', 'desc' => 'Luxury airport transfers in premium vehicles with professional chauffeurs.', 'price' => 'From '.CURRENCY_SYMBOL.'30,000', 'grad' => '1a0a20,#2a1a3a'],
                    ['icon' => 'wifi', 'title' => 'High-Speed WiFi', 'desc' => 'Complimentary high-speed wireless internet throughout all properties.', 'price' => 'Complimentary', 'grad' => '0a2010,#1a3a2a'],
                    ['icon' => 'bag', 'title' => 'Concierge Service', 'desc' => 'Personalized concierge service for bookings, recommendations, and special requests.', 'price' => 'Complimentary', 'grad' => '20100a,#3a2a1a'],
                ]; ?>
                <?php foreach ($svcs as $s): ?>
                <div class="listing-card">
                    <div class="listing-image">
                        <div class="listing-img-bg" style="background:linear-gradient(135deg,<?php echo $s['grad']; ?>)"></div>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:2.2rem;"><i class="bi bi-<?php echo $s['icon']; ?>"></i></div>
                    </div>
                    <div class="listing-body">
                        <h3 class="listing-title" style="font-size:0.95rem;"><?php echo $s['title']; ?></h3>
                        <p class="listing-desc"><?php echo $s['desc']; ?></p>
                        <div class="listing-footer" style="border:none;padding:8px 0 12px;">
                            <div class="listing-price" style="font-size:0.85rem;font-family:var(--font-accent);font-weight:500;">
                                <?php echo $s['price']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
