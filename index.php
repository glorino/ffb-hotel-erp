<?php
$page_title = 'FFB Hotel – Where Luxury Meets Comfort';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

$stmt = $db->prepare("
    SELECT r.*, rt.name AS type_name, rt.amenities, rt.max_guests
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.status = 'available' AND rt.status = 'active'
    ORDER BY r.price_per_night DESC
    LIMIT 3
");
$stmt->execute();
$featured_rooms = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM services WHERE status = 'active' LIMIT 3");
$stmt->execute();
$services = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM gallery_items WHERE status = 'active' ORDER BY created_at DESC LIMIT 4");
$stmt->execute();
$gallery_items = $stmt->fetchAll();

$branches = getBranches();

$testimonials_json = getSetting('testimonials', '[]');
$testimonials = json_decode($testimonials_json, true) ?: [
    ['name' => 'Victoria Adeyemi', 'role' => 'Business Executive', 'text' => 'The presidential suite exceeded all expectations. Impeccable service, breathtaking views, and world-class amenities. Truly a five-star experience.', 'rating' => 5],
    ['name' => 'James Okafor', 'role' => 'Travel Blogger', 'text' => 'FFB Hotel redefines luxury hospitality. From the moment I stepped into the lobby, I knew this was something special.', 'rating' => 5],
    ['name' => 'Sarah Williams', 'role' => 'International Guest', 'text' => 'An extraordinary stay. The staff anticipated every need, the cuisine was exceptional, and the spa treatments were heavenly.', 'rating' => 5],
];

$room_types = getRoomTypes();
?>

<section class="hero" id="home">
    <div class="hero-particles" id="heroParticles"></div>

    <div class="hero-content">
        <span class="hero-badge">Premier Luxury Destination</span>
        <h1 class="hero-title">
            <span class="hero-welcome">Welcome to</span>
            <span class="hero-brand">FFB Hotel</span>
        </h1>
        <p class="hero-subtitle">Where timeless elegance meets unparalleled hospitality — every moment crafted for the discerning traveler.</p>
        <div class="hero-actions">
            <a href="<?php echo BASE_URL; ?>rooms.php" class="btn btn-gold btn-lg">
                <i class="bi bi-building me-2"></i>Explore Rooms
            </a>
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-outline-gold btn-lg">
                <i class="bi bi-calendar-check me-2"></i>Book Now
            </a>
        </div>
    </div>

    <div class="hero-scroll-indicator">
        <span>Scroll</span>
        <div class="scroll-arrow"></div>
    </div>
</section>

<div class="booking-search-card">
    <div class="container">
        <div class="booking-search-inner" data-animate>
            <form action="<?php echo BASE_URL; ?>rooms.php" method="GET" class="search-form">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Check-In</label>
                        <input type="date" class="form-control" name="check_in" id="heroCheckIn" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Check-Out</label>
                        <input type="date" class="form-control" name="check_out" id="heroCheckOut" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Guests</label>
                        <select class="form-select" name="guests">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Room Type</label>
                        <select class="form-select" name="room_type">
                            <option value="">All Types</option>
                            <?php foreach ($room_types as $rt): ?>
                            <option value="<?php echo $rt['id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <button type="submit" class="btn btn-gold btn-search">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="section-padding" id="rooms">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Accommodation</span>
            <h2 class="section-title">Rooms &amp; Suites</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Discover our collection of meticulously appointed rooms and suites, each designed to provide an unparalleled luxury experience.</p>
        </div>

        <div class="row g-4">
            <?php if (empty($featured_rooms)): ?>
            <?php $fallback = [
                ['price_per_night' => 350000, 'type_name' => 'Presidential Suite', 'description' => 'Our crown jewel — a magnificent suite with panoramic city views, private butler service, jacuzzi, and a separate living area. The epitome of luxury.', 'amenities' => 'WiFi, Air Conditioning, Full Bar, Living Room, Jacuzzi, TV, Butler Service, Wine Cellar', 'max_guests' => 3, 'status' => 'available'],
                ['price_per_night' => 180000, 'type_name' => 'Executive Room', 'description' => 'Executive-level accommodation with exclusive lounge access, premium business amenities, and refined furnishings for the discerning traveler.', 'amenities' => 'WiFi, Air Conditioning, Mini Bar, TV, Lounge Access, Coffee Machine, Work Desk', 'max_guests' => 2, 'status' => 'available'],
                ['price_per_night' => 120000, 'type_name' => 'Deluxe Room', 'description' => 'Spacious and elegantly appointed featuring premium bedding, city views, and every modern convenience for a comfortable stay.', 'amenities' => 'WiFi, Air Conditioning, Mini Bar, Flat-screen TV, Safe, Work Desk, Rain Shower', 'max_guests' => 2, 'status' => 'available'],
            ];
            foreach ($fallback as $room): ?>
            <div class="col-lg-4 col-md-6" data-animate data-delay="200">
                <div class="room-card">
                    <div class="room-card-image">
                        <div class="room-img-fallback"><i class="bi bi-building"></i></div>
                        <div class="room-overlay-badges">
                            <div class="badge-group">
                                <span class="room-badge badge-status"><i class="bi bi-check-circle"></i> Available</span>
                                <span class="room-badge badge-type"><?php echo htmlspecialchars($room['type_name']); ?></span>
                            </div>
                            <span class="room-badge badge-price"><?php echo formatMoney($room['price_per_night']); ?> <small>/ night</small></span>
                        </div>
                    </div>
                    <div class="room-card-body">
                        <h3 class="room-type"><?php echo htmlspecialchars($room['type_name']); ?></h3>
                        <p class="room-desc"><?php echo htmlspecialchars($room['description']); ?></p>
                        <div class="room-amenities">
                            <span class="amenity-tag"><i class="bi bi-people"></i> <?php echo $room['max_guests']; ?> Guests</span>
                            <?php $amenities_list = array_slice(explode(',', $room['amenities']), 0, 3);
                            foreach ($amenities_list as $amenity): ?>
                            <span class="amenity-tag"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="room-card-footer">
                        <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold">Book Now</a>
                        <a href="<?php echo BASE_URL; ?>rooms.php" class="btn btn-outline-gold">Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <?php foreach ($featured_rooms as $i => $room): ?>
            <div class="col-lg-4 col-md-6" data-animate data-delay="<?php echo ($i * 100) + 100; ?>">
                <div class="room-card">
                    <div class="room-card-image">
                        <?php if ($room['image'] && file_exists(__DIR__ . '/assets/images/rooms/' . $room['image'])): ?>
                        <img src="<?php echo BASE_URL; ?>assets/images/rooms/<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>">
                        <?php else: ?>
                        <div class="room-img-fallback"><i class="bi bi-building"></i></div>
                        <?php endif; ?>
                        <div class="room-overlay-badges">
                            <div class="badge-group">
                                <span class="room-badge badge-status"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars(ucfirst($room['status'])); ?></span>
                                <span class="room-badge badge-type"><?php echo htmlspecialchars($room['type_name']); ?></span>
                            </div>
                            <span class="room-badge badge-price"><?php echo formatMoney($room['price_per_night']); ?> <small>/ night</small></span>
                        </div>
                    </div>
                    <div class="room-card-body">
                        <h3 class="room-type"><?php echo htmlspecialchars($room['type_name']); ?></h3>
                        <p class="room-desc"><?php echo htmlspecialchars(truncate($room['description'] ?? 'Experience luxury at its finest in our beautifully appointed accommodations.', 120)); ?></p>
                        <div class="room-amenities">
                            <span class="amenity-tag"><i class="bi bi-people"></i> <?php echo $room['max_guests']; ?> Guests</span>
                            <?php $amenities_list = array_slice(explode(',', $room['amenities'] ?? ''), 0, 3);
                            foreach ($amenities_list as $amenity): ?>
                            <span class="amenity-tag"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="room-card-footer">
                        <a href="<?php echo BASE_URL; ?>booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-gold">Book Now</a>
                        <a href="<?php echo BASE_URL; ?>rooms.php" class="btn btn-outline-gold">Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5" data-animate>
            <a href="<?php echo BASE_URL; ?>rooms.php" class="btn btn-outline-gold btn-lg">
                View All Rooms &amp; Suites <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<section class="section-padding bg-cream" id="restaurant">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6" data-animate>
                <div class="section-header text-start mb-4">
                    <span class="section-subtitle">Fine Dining</span>
                    <h2 class="section-title">A Culinary Journey</h2>
                    <div class="gold-divider" style="margin: 0 0 20px;"></div>
                    <p class="section-desc" style="margin: 0;">Indulge in an exquisite culinary experience at our signature restaurant. Every dish is a masterpiece, crafted by world-class chefs using the finest ingredients from around the globe.</p>
                </div>
                <p style="color: var(--text-light); line-height: 1.8; margin-bottom: 24px;">
                    Our restaurant offers a sophisticated ambiance with panoramic views, an extensive wine cellar boasting over 500 labels, and a menu that celebrates both local heritage and international culinary artistry.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?php echo BASE_URL; ?>order.php" class="btn btn-gold">
                        <i class="bi bi-cup-hot me-2"></i>View Menu
                    </a>
                    <a href="<?php echo BASE_URL; ?>reservation.php" class="btn btn-outline-gold">
                        <i class="bi bi-calendar me-2"></i>Reserve a Table
                    </a>
                </div>
            </div>
            <div class="col-lg-6" data-animate data-delay="200">
                <div class="about-image" style="height: 450px;">
                    <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--navy), var(--navy-light));display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:5rem;border-radius:var(--radius-lg);">
                        <i class="bi bi-cup-straw"></i>
                    </div>
                    <div class="experience-badge">
                        <span class="number">50+</span>
                        <span class="label">Wine Labels</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" id="services">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Our Offerings</span>
            <h2 class="section-title">Premium Services</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Beyond exceptional accommodation, we offer a world of curated services designed to make your stay truly unforgettable.</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($services)): ?>
            <?php foreach ($services as $i => $service): ?>
            <div class="col-lg-4 col-md-6" data-animate data-delay="<?php echo $i * 100; ?>">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-<?php echo htmlspecialchars($service['category'] ?? 'star'); ?>"></i></div>
                    <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="service-desc"><?php echo htmlspecialchars(truncate($service['description'] ?? '', 120)); ?></p>
                    <div class="service-price"><?php echo !empty($service['price']) ? formatMoney($service['price']) : 'Complimentary'; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-lg-4 col-md-6" data-animate>
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-water"></i></div>
                    <h3 class="service-title">Infinity Pool</h3>
                    <p class="service-desc">Take a dip in our stunning infinity pool overlooking the city skyline. Relax on sun loungers with dedicated poolside service.</p>
                    <div class="service-price">Complimentary</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="100">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-flower1"></i></div>
                    <h3 class="service-title">Spa &amp; Wellness</h3>
                    <p class="service-desc">Rejuvenate body and mind with our premium spa treatments, therapeutic massages, and holistic wellness therapies in a serene sanctuary.</p>
                    <div class="service-price">From <?php echo CURRENCY_SYMBOL; ?>25,000</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="200">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-dumbbell"></i></div>
                    <h3 class="service-title">Fitness Center</h3>
                    <p class="service-desc">Stay active in our state-of-the-art fitness center equipped with the latest Technogym machines, free weights, and personal training sessions.</p>
                    <div class="service-price">Complimentary</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5" data-animate>
            <a href="<?php echo BASE_URL; ?>services.php" class="btn btn-outline-gold btn-lg">
                Explore All Services <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<section class="section-padding bg-off-white" id="gallery">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Moments Captured</span>
            <h2 class="section-title">Our Gallery</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">A visual journey through the elegance, luxury, and beauty that defines the FFB Hotel experience.</p>
        </div>

        <div class="gallery-grid">
            <?php if (!empty($gallery_items)): ?>
            <?php foreach ($gallery_items as $item): ?>
            <div class="gallery-item" data-animate>
                <?php if ($item['image'] && file_exists(__DIR__ . '/assets/images/gallery/' . $item['image'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/gallery/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--navy),var(--navy-light));display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:2rem;"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                <div class="gallery-overlay">
                    <div>
                        <span class="gallery-category"><?php echo htmlspecialchars(ucfirst($item['category'] ?? 'Hotel')); ?></span>
                        <h4 class="gallery-title"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
                    </div>
                    <div class="gallery-view"><i class="bi bi-arrows-angle-expand"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <?php $captions = ['Luxury Lobby', 'Deluxe Suite', 'Fine Dining', 'Infinity Pool']; ?>
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="gallery-item" data-animate data-delay="<?php echo ($i - 1) * 100; ?>">
                <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--navy),var(--navy-light));display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:2rem;"><i class="bi bi-image"></i></div>
                <div class="gallery-overlay">
                    <div>
                        <span class="gallery-category">Hotel</span>
                        <h4 class="gallery-title"><?php echo $captions[$i - 1]; ?></h4>
                    </div>
                    <div class="gallery-view"><i class="bi bi-arrows-angle-expand"></i></div>
                </div>
            </div>
            <?php endfor; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4" data-animate>
            <a href="<?php echo BASE_URL; ?>gallery.php" class="btn btn-outline-gold btn-lg">
                View Full Gallery <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<section class="section-padding testimonials-section" id="testimonials">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Guest Voices</span>
            <h2 class="section-title" style="color: var(--white);">What Our Guests Say</h2>
            <div class="gold-divider"></div>
            <p class="section-desc" style="color: rgba(255,255,255,0.5);">Hear from our cherished guests about their unforgettable experiences at FFB Hotel.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($testimonials as $i => $t): ?>
            <div class="col-lg-4 col-md-6" data-animate data-delay="<?php echo $i * 100; ?>">
                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <?php for ($s = 0; $s < ($t['rating'] ?? 5); $s++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                    </div>
                    <p class="testimonial-text">"<?php echo htmlspecialchars($t['text']); ?>"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><?php echo strtoupper(substr($t['name'], 0, 1)); ?></div>
                        <div>
                            <div class="testimonial-name"><?php echo htmlspecialchars($t['name']); ?></div>
                            <div class="testimonial-role"><?php echo htmlspecialchars($t['role'] ?? 'Guest'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding bg-white" id="branches">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Our Locations</span>
            <h2 class="section-title">Find Us</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Experience FFB Hotel at our premier locations across the country.</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($branches)): ?>
            <?php foreach ($branches as $i => $branch): ?>
            <div class="col-lg-4 col-md-6" data-animate data-delay="<?php echo $i * 100; ?>">
                <div class="branch-card">
                    <h3 class="branch-name"><?php echo htmlspecialchars($branch['name']); ?></h3>
                    <div class="branch-address">
                        <i class="bi bi-geo-alt"></i>
                        <span><?php echo htmlspecialchars($branch['address'] ?? '') . ', ' . htmlspecialchars($branch['city'] ?? '') . ', ' . htmlspecialchars($branch['state'] ?? ''); ?></span>
                    </div>
                    <div class="branch-contact">
                        <?php if (!empty($branch['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($branch['phone']); ?>"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($branch['phone']); ?></a>
                        <?php endif; ?>
                        <?php if (!empty($branch['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($branch['email']); ?>"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($branch['email']); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-lg-4 col-md-6" data-animate>
                <div class="branch-card">
                    <h3 class="branch-name">FFB Luxury Hotel</h3>
                    <div class="branch-address">
                        <i class="bi bi-geo-alt"></i>
                        <span>123 Hospitality Avenue, Victoria Island, Lagos</span>
                    </div>
                    <div class="branch-contact">
                        <a href="tel:+2348009999999"><i class="bi bi-telephone"></i> +234 800 999 9999</a>
                        <a href="mailto:info@ffbhotel.com"><i class="bi bi-envelope"></i> info@ffbhotel.com</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="100">
                <div class="branch-card">
                    <h3 class="branch-name">FFB Boutique Hotel</h3>
                    <div class="branch-address">
                        <i class="bi bi-geo-alt"></i>
                        <span>456 Luxury Row, Ikoyi, Lagos</span>
                    </div>
                    <div class="branch-contact">
                        <a href="tel:+2348098888888"><i class="bi bi-telephone"></i> +234 809 888 8888</a>
                        <a href="mailto:ikoyi@ffbhotel.com"><i class="bi bi-envelope"></i> ikoyi@ffbhotel.com</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="200">
                <div class="branch-card">
                    <h3 class="branch-name">FFB Resort &amp; Spa</h3>
                    <div class="branch-address">
                        <i class="bi bi-geo-alt"></i>
                        <span>789 Beach Road, Banana Island, Lagos</span>
                    </div>
                    <div class="branch-contact">
                        <a href="tel:+2348077777777"><i class="bi bi-telephone"></i> +234 807 777 7777</a>
                        <a href="mailto:resort@ffbhotel.com"><i class="bi bi-envelope"></i> resort@ffbhotel.com</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container" data-animate>
        <h2 class="cta-title">Experience Luxury Today</h2>
        <p class="cta-desc">Book your stay and discover a world where every detail is crafted for your comfort and delight. Let us create unforgettable memories for you.</p>
        <div class="cta-actions">
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg pulse">
                <i class="bi bi-calendar-check me-2"></i>Book Your Stay
            </a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-telephone me-2"></i>Contact Us
            </a>
        </div>
    </div>
</section>

<section class="newsletter-section section-padding-sm">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center" data-animate>
                <span class="section-subtitle">Stay Connected</span>
                <h3 style="font-family: var(--font-heading); color: var(--text-dark); margin-bottom: 8px; font-size: 1.5rem;">Join Our Exclusive List</h3>
                <p style="color: var(--text-light); margin-bottom: 24px;">Subscribe for exclusive offers, early access, and updates from FFB Hotel.</p>
                <form action="<?php echo BASE_URL; ?>subscribe.php" method="POST" class="newsletter-form" style="max-width: 480px; margin: 0 auto;">
                    <?php echo csrf_field(); ?>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="Your email address" required style="border-radius: var(--radius-sm) 0 0 var(--radius-sm);">
                        <button type="submit" class="btn btn-gold" style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
                            <i class="bi bi-send me-2"></i>Subscribe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="lightbox-overlay" id="lightboxOverlay">
    <button class="lightbox-close" id="lightboxClose">&times;</button>
    <img class="lightbox-img" id="lightboxImg" src="" alt="">
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<?php require_once __DIR__ . '/includes/live-chat-widget.php'; ?>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
