<?php
$page_title = 'Our Services - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

$stmt = $db->prepare("SELECT * FROM services WHERE status = 'active' ORDER BY category, name");
$stmt->execute();
$services = $stmt->fetchAll();

$stmt_cat = $db->prepare("SELECT DISTINCT category FROM services WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category");
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

$has_db_services = !empty($services);

$cat_svc_images = [
    'room_service' => [
        'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop',
    ],
    'dining' => [
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&h=400&fit=crop',
    ],
    'restaurant' => [
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&h=400&fit=crop',
    ],
    'food' => [
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&h=400&fit=crop',
    ],
    'pool' => [
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
    ],
    'swimming' => [
        'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&h=400&fit=crop',
    ],
    'fitness' => [
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?w=600&h=400&fit=crop',
    ],
    'gym' => [
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?w=600&h=400&fit=crop',
    ],
    'wellness' => [
        'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&h=400&fit=crop',
    ],
    'spa' => [
        'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
    ],
    'massage' => [
        'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
    ],
    'events' => [
        'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&h=400&fit=crop',
    ],
    'conference' => [
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&h=400&fit=crop',
    ],
    'transport' => [
        'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
    ],
    'transfer' => [
        'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
    ],
    'concierge' => [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop',
    ],
    'wifi' => [
        'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
    ],
    'internet' => [
        'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
    ],
    'bar' => [
        'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=600&h=400&fit=crop',
    ],
    'laundry' => [
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop',
    ],
    'parking' => [
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
    ],
    'tour' => [
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
    ],
    'childcare' => [
        'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&h=400&fit=crop',
    ],
    'beauty' => [
        'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
    ],
    'shopping' => [
        'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
    ],
];

$all_svc_images = [
    'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=400&fit=crop',
];

function getSvcImage($service, $category, $idx) {
    global $cat_svc_images, $all_svc_images;
    if (!empty($service['image'])) {
        $img = $service['image'];
        if (strpos($img, 'http') === 0) {
            return $img;
        }
        if (file_exists(__DIR__ . '/' . $img)) {
            return BASE_URL . $img;
        }
    }
    $cat = strtolower(trim($category));
    if (isset($cat_svc_images[$cat]) && isset($cat_svc_images[$cat][$idx])) {
        return $cat_svc_images[$cat][$idx];
    }
    if (isset($cat_svc_images[$cat])) {
        return $cat_svc_images[$cat][$idx % count($cat_svc_images[$cat])];
    }
    return $all_svc_images[$idx % count($all_svc_images)];
}

$icon_map = [
    'room_service' => 'bell',
    'restaurant' => 'cup-straw',
    'pool' => 'water',
    'gym' => 'dumbbell',
    'spa' => 'flower1',
    'concierge' => 'gem',
    'transport' => 'car-front',
    'events' => 'building',
    'wifi' => 'wifi',
];

$cat_colors = [
    'room_service' => ['bg' => 'rgba(201,168,76,0.08)', 'border' => 'rgba(201,168,76,0.15)', 'color' => '#c9a84c', 'accent' => '#a8882e'],
    'restaurant' => ['bg' => 'rgba(239,68,68,0.08)', 'border' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444', 'accent' => '#dc2626'],
    'pool' => ['bg' => 'rgba(59,130,246,0.08)', 'border' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6', 'accent' => '#2563eb'],
    'gym' => ['bg' => 'rgba(249,115,22,0.08)', 'border' => 'rgba(249,115,22,0.15)', 'color' => '#f97316', 'accent' => '#ea580c'],
    'spa' => ['bg' => 'rgba(16,185,129,0.08)', 'border' => 'rgba(16,185,129,0.15)', 'color' => '#10b981', 'accent' => '#059669'],
    'events' => ['bg' => 'rgba(139,92,246,0.08)', 'border' => 'rgba(139,92,246,0.15)', 'color' => '#8b5cf6', 'accent' => '#7c3aed'],
    'transport' => ['bg' => 'rgba(6,182,212,0.08)', 'border' => 'rgba(6,182,212,0.15)', 'color' => '#06b6d4', 'accent' => '#0891b2'],
    'concierge' => ['bg' => 'rgba(201,168,76,0.08)', 'border' => 'rgba(201,168,76,0.15)', 'color' => '#c9a84c', 'accent' => '#a8882e'],
    'wifi' => ['bg' => 'rgba(99,102,241,0.08)', 'border' => 'rgba(99,102,241,0.15)', 'color' => '#6366f1', 'accent' => '#4f46e5'],
];

$cat_icons = [
    'room_service' => 'bell-fill',
    'restaurant' => 'cup-straw',
    'pool' => 'water',
    'gym' => 'dumbbell',
    'spa' => 'flower1',
    'events' => 'building',
    'transport' => 'car-front',
    'concierge' => 'gem',
    'wifi' => 'wifi',
];
?>

<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Services</span>
        </nav>
        <h1 class="page-hero-title">Our Services</h1>
        <p class="hero-subtitle">World-class amenities and services tailored for your comfort</p>
        <div class="page-hero-decoration"></div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <?php if ($has_db_services && !empty($categories)): ?>
            <?php foreach ($categories as $catIdx => $category): ?>
            <?php $cc = $cat_colors[$category] ?? ['bg' => 'rgba(201,168,76,0.08)', 'border' => 'rgba(201,168,76,0.15)', 'color' => '#c9a84c', 'accent' => '#a8882e']; ?>
            <?php $catIcon = $cat_icons[$category] ?? 'star'; ?>
            <div class="mb-5" data-animate>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?php echo $cc['bg']; ?>;border:1px solid <?php echo $cc['border']; ?>;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-<?php echo $catIcon; ?>" style="color:<?php echo $cc['color']; ?>;font-size:1.2rem;"></i>
                    </div>
                    <div>
                        <h3 style="font-family:var(--font-heading);font-size:1.5rem;color:var(--charcoal);margin:0;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $category))); ?></h3>
                        <p style="font-size:0.82rem;color:var(--text-light);margin:0;">Premium <?php echo htmlspecialchars(strtolower(str_replace('_', ' ', $category))); ?> experience</p>
                    </div>
                </div>
                <div class="row g-4">
                    <?php $svc_idx = 0; foreach ($services as $service): ?>
                        <?php if ($service['category'] === $category): ?>
                        <div class="col-lg-4 col-md-6" data-animate data-delay="<?php echo $svc_idx * 80; ?>">
                            <div class="svc-card">
                                <div class="svc-image">
                                    <img src="<?php echo getSvcImage($service, $category, $svc_idx); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" loading="lazy">
                                    <div class="svc-overlay"></div>
                                    <div class="svc-icon-wrap"><i class="bi bi-<?php echo $catIcon; ?>"></i></div>
                                </div>
                                <div class="svc-body">
                                    <h3 class="svc-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <p class="svc-desc"><?php echo htmlspecialchars(truncate($service['description'] ?? 'Premium service to enhance your stay.', 100)); ?></p>
                                    <div class="svc-footer">
                                        <div class="svc-price"><?php echo !empty($service['price']) ? formatMoney($service['price']) : 'Complimentary'; ?></div>
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
            <div class="row g-4">
                <?php $svcs = [
                    ['icon' => 'bell-fill', 'title' => '24/7 Room Service', 'desc' => 'Round-the-clock in-room dining and concierge service. Enjoy gourmet meals whenever you need it.', 'price' => 'Complimentary', 'img' => 'room_service', 'idx' => 0],
                    ['icon' => 'cup-straw', 'title' => 'Fine Dining Restaurant', 'desc' => 'Award-winning restaurant serving exquisite local and international cuisine by world-class chefs.', 'price' => '&#8358;15,000', 'img' => 'restaurant', 'idx' => 0],
                    ['icon' => 'water', 'title' => 'Infinity Pool', 'desc' => 'Stunning infinity-edge swimming pool with panoramic city views and poolside bar service.', 'price' => 'Complimentary', 'img' => 'pool', 'idx' => 0],
                    ['icon' => 'dumbbell', 'title' => 'Fitness Center', 'desc' => 'State-of-the-art facility with premium cardio machines, free weights, and personal training.', 'price' => 'Complimentary', 'img' => 'gym', 'idx' => 0],
                    ['icon' => 'flower1', 'title' => 'Spa &amp; Wellness', 'desc' => 'Full-service spa offering massages, facials, body treatments, and holistic wellness therapies.', 'price' => '&#8358;25,000', 'img' => 'spa', 'idx' => 0],
                    ['icon' => 'building', 'title' => 'Conference &amp; Events', 'desc' => 'Versatile event spaces for business meetings, conferences, weddings, and celebrations.', 'price' => '&#8358;100,000', 'img' => 'events', 'idx' => 0],
                    ['icon' => 'car-front', 'title' => 'Airport Transfers', 'desc' => 'Luxury airport transfers in premium vehicles with professional chauffeurs at your service.', 'price' => '&#8358;30,000', 'img' => 'transport', 'idx' => 0],
                    ['icon' => 'wifi', 'title' => 'High-Speed WiFi', 'desc' => 'Complimentary high-speed wireless internet throughout all properties and public areas.', 'price' => 'Complimentary', 'img' => 'wifi', 'idx' => 0],
                    ['icon' => 'gem', 'title' => 'Concierge Service', 'desc' => 'Personalized concierge service for bookings, recommendations, and special requests.', 'price' => 'Complimentary', 'img' => 'concierge', 'idx' => 0],
                ]; ?>
                <?php foreach ($svcs as $i => $s): ?>
                <div class="col-lg-4 col-md-6" data-animate data-delay="<?php echo $i * 80; ?>">
                    <div class="svc-card">
                        <div class="svc-image">
                            <img src="<?php echo $cat_svc_images[$s['img']][$s['idx']]; ?>" alt="<?php echo $s['title']; ?>" loading="lazy">
                            <div class="svc-overlay"></div>
                            <div class="svc-icon-wrap"><i class="bi bi-<?php echo $s['icon']; ?>"></i></div>
                        </div>
                        <div class="svc-body">
                            <h3 class="svc-title"><?php echo $s['title']; ?></h3>
                            <p class="svc-desc"><?php echo $s['desc']; ?></p>
                            <div class="svc-footer">
                                <div class="svc-price"><?php echo $s['price']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section-padding" style="background:linear-gradient(135deg,#f8f7f4,#fff);">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Why Choose Us</span>
            <h2 class="section-title">The FFB Difference</h2>
            <div class="gold-divider"></div>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 col-md-6" data-animate data-delay="0">
                <div style="text-align:center;padding:32px 20px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(201,168,76,0.12),rgba(201,168,76,0.04));border:1px solid rgba(201,168,76,0.15);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="bi bi-award" style="font-size:1.4rem;color:var(--gold);"></i>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-size:1.15rem;margin-bottom:8px;">Award-Winning</h4>
                    <p style="color:var(--text-light);font-size:0.85rem;line-height:1.7;margin:0;">Recognized globally for excellence in hospitality and guest satisfaction.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="80">
                <div style="text-align:center;padding:32px 20px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(59,130,246,0.12),rgba(59,130,246,0.04));border:1px solid rgba(59,130,246,0.15);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="bi bi-headset" style="font-size:1.4rem;color:#3b82f6;"></i>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-size:1.15rem;margin-bottom:8px;">24/7 Support</h4>
                    <p style="color:var(--text-light);font-size:0.85rem;line-height:1.7;margin:0;">Round-the-clock assistance for anything you need during your stay.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="160">
                <div style="text-align:center;padding:32px 20px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(16,185,129,0.12),rgba(16,185,129,0.04));border:1px solid rgba(16,185,129,0.15);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="bi bi-shield-check" style="font-size:1.4rem;color:#10b981;"></i>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-size:1.15rem;margin-bottom:8px;">Premium Quality</h4>
                    <p style="color:var(--text-light);font-size:0.85rem;line-height:1.7;margin:0;">Only the finest facilities, products, and services meet our standards.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="240">
                <div style="text-align:center;padding:32px 20px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(139,92,246,0.12),rgba(139,92,246,0.04));border:1px solid rgba(139,92,246,0.15);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="bi bi-heart" style="font-size:1.4rem;color:#8b5cf6;"></i>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-size:1.15rem;margin-bottom:8px;">Personalized Care</h4>
                    <p style="color:var(--text-light);font-size:0.85rem;line-height:1.7;margin:0;">Tailored experiences that adapt to your unique preferences and needs.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background:linear-gradient(135deg,var(--navy),var(--navy-dark));">
    <div class="container text-center" data-animate>
        <span class="section-subtitle" style="color:var(--gold);font-size:0.8rem;text-transform:uppercase;letter-spacing:4px;font-weight:600;">Ready to Begin?</span>
        <h2 style="font-family:var(--font-heading);font-size:2.5rem;color:var(--white);margin:12px 0 16px;">Experience Premium Service Today</h2>
        <p style="color:rgba(255,255,255,0.5);max-width:520px;margin:0 auto 28px;font-size:1rem;line-height:1.7;">Book your stay and enjoy complimentary access to our world-class facilities and personalized service.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg"><i class="bi bi-calendar-check me-2"></i>Book Your Stay</a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-gold btn-lg"><i class="bi bi-telephone me-2"></i>Contact Us</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
