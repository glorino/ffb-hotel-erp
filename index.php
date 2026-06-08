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
    LIMIT 8
");
$stmt->execute();
$featured_rooms = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM services WHERE status = 'active' LIMIT 3");
$stmt->execute();
$services = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM gallery_items WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$gallery_items = $stmt->fetchAll();

$branches = getBranches();

$testimonials_json = getSetting('testimonials', '[]');
$testimonials = json_decode($testimonials_json, true);
if (empty($testimonials) || !is_array($testimonials)) {
    $testimonials = [
        ['name' => 'Victoria Adeyemi', 'role' => 'Business Executive', 'text' => 'The presidential suite exceeded all expectations. Impeccable service, breathtaking views, and world-class amenities. Truly a five-star experience.', 'rating' => 5],
        ['name' => 'James Okafor', 'role' => 'Travel Blogger', 'text' => 'FFB Hotel redefines luxury hospitality. From the moment I stepped into the lobby, I knew this was something special.', 'rating' => 5],
        ['name' => 'Sarah Williams', 'role' => 'International Guest', 'text' => 'An extraordinary stay. The staff anticipated every need, the cuisine was exceptional, and the spa treatments were heavenly.', 'rating' => 5],
        ['name' => 'Michael Obi', 'role' => 'Corporate Traveler', 'text' => 'The executive lounge and business facilities are world-class. Made my work trip feel like a vacation.', 'rating' => 5],
        ['name' => 'Chioma Eze', 'role' => 'Wedding Planner', 'text' => 'We hosted our clients\' wedding here and the level of professionalism and elegance was unmatched.', 'rating' => 5],
        ['name' => 'David Martins', 'role' => 'Frequent Guest', 'text' => 'I have stayed at luxury hotels across Africa and FFB stands out for its attention to every detail.', 'rating' => 5],
    ];
}
// Filter out any testimonials with empty text
$testimonials = array_values(array_filter($testimonials, function($t) {
    return !empty(trim($t['text'] ?? ''));
}));

$room_types = getRoomTypes();
?>

<section class="hero" id="home">
    <div class="hero-bg" id="heroBg">
        <video class="hero-video" autoplay muted loop playsinline poster="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&h=900&fit=crop">
            <source src="https://assets.mixkit.co/videos/9902/9902-720.mp4" type="video/mp4">
        </video>
        <div class="hero-video-overlay"></div>
    </div>
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
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-lg" style="background:rgba(201,168,76,0.25);border:2px solid var(--gold);color:var(--gold);padding:14px 36px;font-size:0.88rem;letter-spacing:1.5px;border-radius:var(--radius-sm);transition:var(--transition);backdrop-filter:blur(10px);">
                <i class="bi bi-calendar-check me-2"></i>Book Now
            </a>
        </div>
    </div>

    <div class="hero-scroll-indicator">
        <span>Scroll</span>
        <div class="scroll-arrow"></div>
    </div>
</section>

<section class="section-padding" style="padding-top:0;">
    <div class="container">
        <div class="text-center" style="margin-bottom:-28px;position:relative;z-index:2;">
            <span style="font-family:var(--font-accent);font-size:9px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--gold);">Book Your Stay</span>
            <h3 style="font-family:var(--font-heading);color:var(--text-dark);font-size:1.3rem;margin:4px 0 0;">Find Your Perfect Room</h3>
        </div>
        <div class="mx-auto" style="max-width:840px;">
            <div style="background:var(--navy-mid);border-radius:var(--radius);padding:28px 32px;box-shadow:0 20px 50px rgba(0,0,0,0.25);">
                <form action="<?php echo BASE_URL; ?>rooms.php" method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label style="font-family:var(--font-accent);font-size:9px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:rgba(255,255,255,0.4);display:block;margin-bottom:4px;">Check-In</label>
                            <input type="date" class="form-control" name="check_in" id="heroCheckIn" min="<?php echo date('Y-m-d'); ?>" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:var(--radius-sm);padding:8px 12px;font-size:0.82rem;color:var(--white);width:100%;">
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label style="font-family:var(--font-accent);font-size:9px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:rgba(255,255,255,0.4);display:block;margin-bottom:4px;">Check-Out</label>
                            <input type="date" class="form-control" name="check_out" id="heroCheckOut" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:var(--radius-sm);padding:8px 12px;font-size:0.82rem;color:var(--white);width:100%;">
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label style="font-family:var(--font-accent);font-size:9px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:rgba(255,255,255,0.4);display:block;margin-bottom:4px;">Guests</label>
                            <select name="guests" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:var(--radius-sm);padding:8px 12px;font-size:0.82rem;color:var(--white);width:100%;">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" style="background:var(--navy);color:var(--white);"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <button type="submit" class="btn btn-gold" style="width:100%;padding:8px 16px;font-size:0.78rem;letter-spacing:1.5px;">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" id="rooms">
    <div class="container">
        <div class="section-box">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Accommodation</span>
            <h2 class="section-title">Rooms &amp; Suites</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Discover our collection of meticulously appointed rooms and suites, each designed to provide an unparalleled luxury experience.</p>
        </div>

        <div class="listing-grid">
            <?php if (empty($featured_rooms)): ?>
            <?php $fallback = [
                ['id' => 1, 'room_number' => '301', 'price_per_night' => 350000, 'type_name' => 'Presidential Suite', 'desc' => 'Panoramic city views with private butler service', 'amenities' => 'WiFi, Air Conditioning, Full Bar, Living Room', 'max_guests' => 3, 'area' => 80, 'img' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&h=280&fit=crop', 'status' => 'available'],
                ['id' => 2, 'room_number' => '201', 'price_per_night' => 180000, 'type_name' => 'Executive Room', 'desc' => 'Premium business amenities with lounge access', 'amenities' => 'WiFi, Air Conditioning, Mini Bar, TV', 'max_guests' => 2, 'area' => 45, 'img' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400&h=280&fit=crop', 'status' => 'available'],
                ['id' => 3, 'room_number' => '101', 'price_per_night' => 120000, 'type_name' => 'Deluxe Room', 'desc' => 'Elegant comfort with premium city views', 'amenities' => 'WiFi, Air Conditioning, Mini Bar, Flat-screen TV', 'max_guests' => 2, 'area' => 38, 'img' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=400&h=280&fit=crop', 'status' => 'available'],
                ['id' => 4, 'room_number' => '302', 'price_per_night' => 350000, 'type_name' => 'Presidential Suite', 'desc' => 'Luxury living with separate living area', 'amenities' => 'WiFi, Air Conditioning, Full Bar, Living Room', 'max_guests' => 3, 'area' => 80, 'img' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=400&h=280&fit=crop', 'status' => 'available'],
                ['id' => 5, 'room_number' => '202', 'price_per_night' => 180000, 'type_name' => 'Executive Room', 'desc' => 'Refined furnishings with exclusive perks', 'amenities' => 'WiFi, Air Conditioning, Mini Bar, TV', 'max_guests' => 2, 'area' => 45, 'img' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&h=280&fit=crop', 'status' => 'available'],
                ['id' => 6, 'room_number' => '102', 'price_per_night' => 120000, 'type_name' => 'Deluxe Room', 'desc' => 'Modern amenities in a cozy setting', 'amenities' => 'WiFi, Air Conditioning, Mini Bar, Flat-screen TV', 'max_guests' => 2, 'area' => 38, 'img' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=400&h=280&fit=crop', 'status' => 'available'],
                ['id' => 7, 'room_number' => '304', 'price_per_night' => 250000, 'type_name' => 'Luxury Suite', 'desc' => 'Spacious suite with jacuzzi and cityscape', 'amenities' => 'WiFi, Air Conditioning, Living Room, Jacuzzi', 'max_guests' => 3, 'area' => 60, 'img' => 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=400&h=280&fit=crop', 'status' => 'available'],
                ['id' => 8, 'room_number' => '205', 'price_per_night' => 150000, 'type_name' => 'Executive Room', 'desc' => 'Perfect for business travelers', 'amenities' => 'WiFi, Air Conditioning, Mini Bar, Coffee Machine', 'max_guests' => 2, 'area' => 42, 'img' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=400&h=280&fit=crop', 'status' => 'available'],
            ];
            foreach ($fallback as $room): ?>
            <div class="listing-card" data-animate>
                <div class="listing-image">
                    <img src="<?php echo $room['img']; ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>" loading="lazy" style="background:var(--navy-light);">
                    <button class="listing-wishlist" aria-label="Save to wishlist"><i class="bi bi-heart"></i></button>
                    <div class="listing-status"><?php echo htmlspecialchars(ucfirst($room['status'])); ?></div>
                </div>
                <div class="listing-body">
                    <div class="listing-type"><?php echo htmlspecialchars($room['type_name']); ?></div>
                    <h3 class="listing-title">Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                    <p class="listing-desc"><?php echo htmlspecialchars($room['desc']); ?></p>
                    <div class="listing-features">
                        <span><i class="bi bi-people"></i> <?php echo $room['max_guests']; ?> guests</span>
                        <span><i class="bi bi-rulers"></i> <?php echo $room['area']; ?> m&sup2;</span>
                        <?php $amenities_list = array_slice(explode(',', $room['amenities']), 0, 1);
                        foreach ($amenities_list as $amenity): ?>
                        <span><i class="bi bi-check-lg"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="listing-footer">
                        <div class="listing-price">
                            <?php echo formatMoney($room['price_per_night']); ?>
                            <span>/night</span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>booking.php?room_id=<?php echo $room['id']; ?>" class="listing-book">Book Now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <?php $room_photos = [
                'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=600&h=400&fit=crop',
            ]; ?>
            <?php $room_idx = 0; ?>
            <?php foreach ($featured_rooms as $room): $room_idx++; ?>
            <div class="listing-card" data-animate>
                <div class="listing-image">
                    <img src="<?php echo !empty($room['image']) ? htmlspecialchars($room['image']) : $room_photos[($room_idx - 1) % 8]; ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>" loading="lazy" style="background:var(--navy-light);">
                    <button class="listing-wishlist" aria-label="Save to wishlist"><i class="bi bi-heart"></i></button>
                    <div class="listing-status"><?php echo htmlspecialchars(ucfirst($room['status'])); ?></div>
                </div>
                <div class="listing-body">
                    <div class="listing-type"><?php echo htmlspecialchars($room['type_name']); ?></div>
                    <h3 class="listing-title">Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                    <p class="listing-desc"><?php echo htmlspecialchars(truncate($room['description'] ?? 'Experience luxury at its finest in our beautifully appointed accommodations.', 60)); ?></p>
                    <div class="listing-features">
                        <span><i class="bi bi-people"></i> <?php echo $room['max_guests']; ?> guests</span>
                        <?php $amenities_list = array_slice(explode(',', $room['amenities'] ?? ''), 0, 2);
                        foreach ($amenities_list as $amenity): ?>
                        <span><i class="bi bi-check-lg"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="listing-footer">
                        <div class="listing-price">
                            <?php echo formatMoney($room['price_per_night']); ?>
                            <span>/night</span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>booking.php?room_id=<?php echo $room['id']; ?>" class="listing-book">Book Now</a>
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
    </div>
</section>

<section class="section-padding" id="restaurant">
    <div class="container">
        <div class="mx-auto" style="max-width:880px;">
            <div class="section-box" style="padding:0;overflow:hidden;">
            <div class="row g-0 align-items-stretch">
                <div class="col-md-5" data-animate>
                    <div style="height:100%;min-height:280px;position:relative;overflow:hidden;background:var(--navy-dark);">
                        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop" alt="Fine Dining" style="width:100%;height:100%;object-fit:cover;transition:transform 0.6s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(9,18,32,0.3),transparent 50%);pointer-events:none;"></div>
                    </div>
                </div>
                <div class="col-md-7" data-animate data-delay="100">
                    <div style="padding:36px 44px;">
                        <span style="font-family:var(--font-accent);font-size:9px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--gold);">Fine Dining</span>
                        <h3 style="font-family:var(--font-heading);font-size:1.7rem;color:var(--charcoal);margin:6px 0 12px;">A Culinary Journey</h3>
                        <div style="width:44px;height:2px;background:var(--gold);margin-bottom:14px;"></div>
                        <p style="color:var(--text-light);font-size:0.88rem;line-height:1.8;margin-bottom:20px;">Indulge in an exquisite culinary experience at our signature restaurant. Every dish is a masterpiece, crafted by world-class chefs using the finest locally-sourced ingredients, paired with an extensive selection of fine wines from around the globe.</p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="<?php echo BASE_URL; ?>order.php" class="btn btn-gold">
                                <i class="bi bi-cup-hot me-1"></i>View Menu
                            </a>
                            <a href="<?php echo BASE_URL; ?>reservation.php" class="btn btn-outline-gold">
                                <i class="bi bi-calendar me-1"></i>Reserve a Table
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" id="services">
    <div class="container">
        <div class="section-box">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Our Offerings</span>
            <h2 class="section-title">Premium Services</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Beyond exceptional accommodation, we offer a world of curated services designed to make your stay truly unforgettable.</p>
        </div>

        <div class="listing-grid">
            <?php if (!empty($services)): ?>
            <?php foreach ($services as $i => $service): ?>
            <div class="svc-card" data-animate data-delay="<?php echo $i * 100; ?>">
                <div class="svc-image">
                    <img src="https://images.unsplash.com/<?php echo ['photo-1571902943202-507ec2618e8f?w=400&h=280&fit=crop', 'photo-1563911302283-d2bc129e7570?w=400&h=280&fit=crop', 'photo-1534438327276-14e5300c3a48?w=400&h=280&fit=crop', 'photo-1414235077428-338989a2e8c0?w=400&h=280&fit=crop', 'photo-1544636331-e26879cd4d9b?w=400&h=280&fit=crop', 'photo-1497366216548-37526070297c?w=400&h=280&fit=crop'][$i % 6]; ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" loading="lazy">
                    <div class="svc-overlay"></div>
                    <div class="svc-icon-wrap">
                        <i class="bi bi-<?php echo htmlspecialchars($service['category'] ?? 'star'); ?>"></i>
                    </div>
                </div>
                <div class="svc-body">
                    <h3 class="svc-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="svc-desc"><?php echo htmlspecialchars(truncate($service['description'] ?? '', 60)); ?></p>
                    <div class="svc-footer">
                        <span class="svc-price"><?php echo !empty($service['price']) ? formatMoney($service['price']) : 'Complimentary'; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <?php $svcs = [
                ['icon' => 'water', 'title' => 'Infinity Pool', 'desc' => 'Take a dip in our stunning infinity pool overlooking the city skyline.', 'price' => 'Complimentary', 'img' => 'photo-1571902943202-507ec2618e8f?w=400&h=280&fit=crop'],
                ['icon' => 'flower1', 'title' => 'Spa &amp; Wellness', 'desc' => 'Rejuvenate body and mind with premium spa treatments and holistic therapies.', 'price' => 'From N25,000', 'img' => 'photo-1563911302283-d2bc129e7570?w=400&h=280&fit=crop'],
                ['icon' => 'dumbbell', 'title' => 'Fitness Center', 'desc' => 'Stay active in our state-of-the-art fitness center with Technogym machines.', 'price' => 'Complimentary', 'img' => 'photo-1534438327276-14e5300c3a48?w=400&h=280&fit=crop'],
                ['icon' => 'cup-straw', 'title' => 'Fine Dining', 'desc' => 'Indulge in world-class cuisine at our signature restaurant with panoramic views.', 'price' => 'From N35,000', 'img' => 'photo-1414235077428-338989a2e8c0?w=400&h=280&fit=crop'],
                ['icon' => 'car-front', 'title' => 'Chauffeur Service', 'desc' => 'Luxury airport transfers and city transportation with our premium fleet.', 'price' => 'From N15,000', 'img' => 'photo-1544636331-e26879cd4d9b?w=400&h=280&fit=crop'],
                ['icon' => 'building', 'title' => 'Conference Rooms', 'desc' => 'Fully-equipped business and meeting spaces for up to 50 guests.', 'price' => 'From N50,000', 'img' => 'photo-1497366216548-37526070297c?w=400&h=280&fit=crop'],
            ];
            foreach ($svcs as $s): ?>
            <div class="svc-card" data-animate>
                <div class="svc-image">
                    <img src="https://images.unsplash.com/<?php echo $s['img']; ?>" alt="<?php echo $s['title']; ?>" loading="lazy">
                    <div class="svc-overlay"></div>
                    <div class="svc-icon-wrap">
                        <i class="bi bi-<?php echo $s['icon']; ?>"></i>
                    </div>
                </div>
                <div class="svc-body">
                    <h3 class="svc-title"><?php echo $s['title']; ?></h3>
                    <p class="svc-desc"><?php echo $s['desc']; ?></p>
                    <div class="svc-footer">
                        <span class="svc-price"><?php echo $s['price']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4" data-animate>
            <a href="<?php echo BASE_URL; ?>services.php" class="btn btn-outline-gold btn-lg">
                Explore All Services <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
    </div>
</section>

<section class="section-padding" id="gallery">
    <div class="container">
        <div class="section-box">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Moments Captured</span>
            <h2 class="section-title">Our Gallery</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">A visual journey through the elegance, luxury, and beauty that defines the FFB Hotel experience.</p>
        </div>

        <div class="gallery-grid">
            <?php if (!empty($gallery_items)): ?>
            <?php foreach ($gallery_items as $g_idx => $item): ?>
            <div class="gallery-item" data-animate>
                <?php if ($item['image'] && file_exists(__DIR__ . '/assets/images/gallery/' . $item['image'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/gallery/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                <?php else: ?>
                <img src="https://images.unsplash.com/photo-<?php echo ['1582719508461-905c673771fd', '1611892440504-42a792e24d32', '1559339352-11d035aa65de', '1571902943202-507ec2618e8f', '1563911302283-d2bc129e7570', '1596394516093-501ba68a0ba6'][$g_idx % 6]; ?>?w=800&h=600&fit=crop" alt="<?php echo htmlspecialchars($item['title'] ?? 'Gallery'); ?>" loading="lazy">
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
            <?php $gallery_fb = [
                ['src' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&h=600&fit=crop', 'cat' => 'Lobby', 'title' => 'Luxury Lobby'],
                ['src' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&h=600&fit=crop', 'cat' => 'Rooms', 'title' => 'Deluxe Suite'],
                ['src' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&h=400&fit=crop', 'cat' => 'Restaurant', 'title' => 'Fine Dining'],
                ['src' => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=600&h=400&fit=crop', 'cat' => 'Pool', 'title' => 'Infinity Pool'],
                ['src' => 'https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=600&h=400&fit=crop', 'cat' => 'Spa', 'title' => 'Serenity Spa'],
                ['src' => 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=600&h=400&fit=crop', 'cat' => 'Events', 'title' => 'Grand Ballroom'],
            ]; ?>
            <?php foreach ($gallery_fb as $i => $g): ?>
            <div class="gallery-item" data-animate data-delay="<?php echo $i * 100; ?>">
                <img src="<?php echo $g['src']; ?>" alt="<?php echo $g['title']; ?>" loading="lazy">
                <div class="gallery-overlay">
                    <div>
                        <span class="gallery-category"><?php echo $g['cat']; ?></span>
                        <h4 class="gallery-title"><?php echo $g['title']; ?></h4>
                    </div>
                    <div class="gallery-view"><i class="bi bi-arrows-angle-expand"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4" data-animate>
            <a href="<?php echo BASE_URL; ?>gallery.php" class="btn btn-outline-gold btn-lg">
                View Full Gallery <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
    </div>
</section>

<section class="section-padding" id="testimonials">
    <div class="container">
        <div class="section-box-dark">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Guest Voices</span>
            <h2 class="section-title" style="color: var(--white);">What Our Guests Say</h2>
            <div class="gold-divider"></div>
            <p class="section-desc" style="color: rgba(255,255,255,0.5);">Hear from our cherished guests about their unforgettable experiences at FFB Hotel.</p>
        </div>

        <div class="row g-4" data-animate>
            <?php foreach ($testimonials as $i => $t): ?>
            <div class="col-lg-4 col-md-6" data-animate data-delay="<?php echo $i * 80; ?>">
                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <?php for ($s = 0; $s < ($t['rating'] ?? 5); $s++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                    </div>
                    <p class="testimonial-text"><?php echo htmlspecialchars($t['text']); ?></p>
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
    </div>
</section>



<section class="section-padding" id="branches">
    <div class="container-fluid" style="max-width:1400px;">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Our Locations</span>
            <h2 class="section-title">Find Us</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">Experience FFB Hotel at our premier locations across the country.</p>
        </div>

        <div class="row g-0 branch-row">
            <div class="col-lg-5">
                <div class="branch-cards-wrap">
                    <?php if (!empty($branches)): ?>
                    <?php foreach ($branches as $i => $branch): ?>
                    <div class="branch-card" data-animate data-delay="<?php echo $i * 100; ?>">
                        <div class="branch-card-top">
                            <div class="branch-card-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div class="branch-card-badge">Location <?php echo $i + 1; ?></div>
                        </div>
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
                        <a href="https://maps.google.com/?q=<?php echo urlencode(($branch['address'] ?? '') . ', ' . ($branch['city'] ?? '') . ', ' . ($branch['state'] ?? '')); ?>" target="_blank" rel="noopener" class="branch-directions">
                            <i class="bi bi-sign-turn-right"></i> Get Directions
                        </a>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="branch-card" data-animate>
                        <div class="branch-card-top">
                            <div class="branch-card-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div class="branch-card-badge">Location 1</div>
                        </div>
                        <h3 class="branch-name">FFB Luxury Hotel</h3>
                        <div class="branch-address">
                            <i class="bi bi-geo-alt"></i>
                            <span>123 Hospitality Avenue, Victoria Island, Lagos</span>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+2348009999999"><i class="bi bi-telephone"></i> +234 800 999 9999</a>
                            <a href="mailto:info@ffbhotel.com"><i class="bi bi-envelope"></i> info@ffbhotel.com</a>
                        </div>
                        <a href="https://maps.google.com/?q=123+Hospitality+Avenue+Victoria+Island+Lagos" target="_blank" rel="noopener" class="branch-directions">
                            <i class="bi bi-sign-turn-right"></i> Get Directions
                        </a>
                    </div>
                    <div class="branch-card" data-animate data-delay="100">
                        <div class="branch-card-top">
                            <div class="branch-card-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div class="branch-card-badge">Location 2</div>
                        </div>
                        <h3 class="branch-name">FFB Boutique Hotel</h3>
                        <div class="branch-address">
                            <i class="bi bi-geo-alt"></i>
                            <span>456 Luxury Row, Ikoyi, Lagos</span>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+2348098888888"><i class="bi bi-telephone"></i> +234 809 888 8888</a>
                            <a href="mailto:ikoyi@ffbhotel.com"><i class="bi bi-envelope"></i> ikoyi@ffbhotel.com</a>
                        </div>
                        <a href="https://maps.google.com/?q=456+Luxury+Row+Ikoyi+Lagos" target="_blank" rel="noopener" class="branch-directions">
                            <i class="bi bi-sign-turn-right"></i> Get Directions
                        </a>
                    </div>
                    <div class="branch-card" data-animate data-delay="200">
                        <div class="branch-card-top">
                            <div class="branch-card-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div class="branch-card-badge">Location 3</div>
                        </div>
                        <h3 class="branch-name">FFB Resort &amp; Spa</h3>
                        <div class="branch-address">
                            <i class="bi bi-geo-alt"></i>
                            <span>789 Beach Road, Banana Island, Lagos</span>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+2348077777777"><i class="bi bi-telephone"></i> +234 807 777 7777</a>
                            <a href="mailto:resort@ffbhotel.com"><i class="bi bi-envelope"></i> resort@ffbhotel.com</a>
                        </div>
                        <a href="https://maps.google.com/?q=789+Beach+Road+Banana+Island+Lagos" target="_blank" rel="noopener" class="branch-directions">
                            <i class="bi bi-sign-turn-right"></i> Get Directions
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7" data-animate data-delay="200">
                <div class="branch-map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127066.99098080968!2d3.2487773!3d6.5480699!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103b8b4a8c1e2a5b%3A0x4a4e8b0c5e2a5b6c!2sLagos%2C%20Nigeria!5e0!3m2!1sen!2sng!4v1700000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="mx-auto" style="max-width:760px;">
        <div class="section-box-dark text-center" data-animate style="padding:48px 40px;">
            <h2 style="font-family:var(--font-heading);font-size:1.8rem;color:var(--white);margin-bottom:10px;">Experience Luxury Today</h2>
            <p style="color:rgba(255,255,255,0.5);max-width:520px;margin:0 auto 22px;font-size:0.88rem;">Book your stay and discover a world where every detail is crafted for your comfort and delight. Let us create unforgettable memories for you.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg pulse">
                    <i class="bi bi-calendar-check me-2"></i>Book Your Stay
                </a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-telephone me-2"></i>Contact Us
                </a>
            </div>
        </div>
        </div>
    </div>
</section>

<section class="section-padding-sm">
    <div class="container">
        <div class="mx-auto" style="max-width:700px;">
        <div class="section-box">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center" data-animate>
                <span class="section-subtitle">Stay Connected</span>
                <h3 style="font-family: var(--font-heading); color: var(--text-dark); margin-bottom: 8px; font-size: 1.3rem;">Join Our Exclusive List</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;font-size:0.85rem;">Subscribe for exclusive offers, early access, and updates from FFB Hotel.</p>
                <form action="<?php echo BASE_URL; ?>subscribe.php" method="POST" class="newsletter-form" style="max-width: 440px; margin: 0 auto;">
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
