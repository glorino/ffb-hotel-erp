<?php
$page_title = 'Luxury Suites - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

// Fetch luxury suites - Luxury Suite, Presidential Suite, Penthouse
$stmt = $db->prepare("
    SELECT r.*, rt.name AS type_name, rt.description AS type_description, rt.amenities, rt.max_guests, rt.base_price
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE rt.name IN ('Luxury Suite', 'Presidential Suite', 'Penthouse')
    AND rt.status = 'active'
    ORDER BY r.price_per_night DESC
");
$stmt->execute();
$suites = $stmt->fetchAll();

// If no suites in DB, use static data
if (empty($suites)) {
    ?>
<!-- Page Hero -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <a href="<?php echo BASE_URL; ?>rooms.php">Rooms &amp; Suites</a>
            <span>/</span>
            <span>Luxury Suites</span>
        </nav>
        <h1 class="page-hero-title">Luxury Suites</h1>
        <p class="hero-subtitle">The pinnacle of elegance and comfort</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="listing-grid">
            <div class="listing-card">
                <div class="listing-image">
                    <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?w=400&h=280&fit=crop" alt="The Penthouse" loading="lazy">
                    <button class="listing-wishlist" aria-label="Save to wishlist"><i class="bi bi-heart"></i></button>
                    <div class="listing-status" style="background:rgba(245,158,11,0.85);">Ultimate Luxury</div>
                </div>
                <div class="listing-body">
                    <div class="listing-type">&#9733; The Penthouse</div>
                    <h3 class="listing-title">The Penthouse</h3>
                    <p class="listing-desc">The epitome of luxury living, spanning the entire top floor with a private rooftop terrace, infinity pool, and 360-degree city views.</p>
                    <div class="listing-features">
                        <span><i class="bi bi-people"></i> 6 guests</span>
                        <span><i class="bi bi-water"></i> Pool</span>
                        <span><i class="bi bi-person-badge"></i> Butler</span>
                    </div>
                    <div class="listing-footer">
                        <div class="listing-price"><?php echo CURRENCY_SYMBOL; ?>950,000 <span>/night</span></div>
                        <a href="<?php echo BASE_URL; ?>booking.php?room_type=5" class="listing-book">Book Now</a>
                    </div>
                </div>
            </div>
            <div class="listing-card">
                <div class="listing-image">
                    <img src="https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&h=280&fit=crop" alt="Presidential Suite" loading="lazy">
                    <button class="listing-wishlist" aria-label="Save to wishlist"><i class="bi bi-heart"></i></button>
                    <div class="listing-status" style="background:rgba(245,158,11,0.85);">Presidential Class</div>
                </div>
                <div class="listing-body">
                    <div class="listing-type">&#9733; Presidential Suite</div>
                    <h3 class="listing-title">Presidential Suite</h3>
                    <p class="listing-desc">A statement of prestige with separate dining room, study, and palatial bedroom. Floor-to-ceiling windows with breathtaking city panoramas.</p>
                    <div class="listing-features">
                        <span><i class="bi bi-people"></i> 4 guests</span>
                        <span><i class="bi bi-water"></i> Jacuzzi</span>
                        <span><i class="bi bi-book"></i> Study</span>
                    </div>
                    <div class="listing-footer">
                        <div class="listing-price"><?php echo CURRENCY_SYMBOL; ?>600,000 <span>/night</span></div>
                        <a href="<?php echo BASE_URL; ?>booking.php?room_type=4" class="listing-book">Book Now</a>
                    </div>
                </div>
            </div>
            <div class="listing-card">
                <div class="listing-image">
                    <img src="https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=400&h=280&fit=crop" alt="Luxury Suite" loading="lazy">
                    <button class="listing-wishlist" aria-label="Save to wishlist"><i class="bi bi-heart"></i></button>
                    <div class="listing-status" style="background:rgba(245,158,11,0.85);">Luxury Experience</div>
                </div>
                <div class="listing-body">
                    <div class="listing-type">&#9733; Luxury Suite</div>
                    <h3 class="listing-title">Luxury Suite</h3>
                    <p class="listing-desc">Elegantly designed with a separate living area, private jacuzzi, complimentary champagne, and dedicated suite concierge.</p>
                    <div class="listing-features">
                        <span><i class="bi bi-people"></i> 3 guests</span>
                        <span><i class="bi bi-water"></i> Jacuzzi</span>
                        <span><i class="bi bi-house-heart"></i> Living Room</span>
                    </div>
                    <div class="listing-footer">
                        <div class="listing-price"><?php echo CURRENCY_SYMBOL; ?>350,000 <span>/night</span></div>
                        <a href="<?php echo BASE_URL; ?>booking.php?room_type=3" class="listing-book">Book Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    <?php
} else {
?>
<!-- Page Hero -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <a href="<?php echo BASE_URL; ?>rooms.php">Rooms &amp; Suites</a>
            <span>/</span>
            <span>Luxury Suites</span>
        </nav>
        <h1 class="page-hero-title">Luxury Suites</h1>
        <p class="hero-subtitle">The pinnacle of elegance and comfort</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <?php $suite_photos = ['1631049307264-da0ec9d70304', '1590490362-c33d57733427', '1578683010236-d716f9a3f461', '1582719508461-905c673771fd', '1595576508890-0ad5c879a061']; ?>
        <div class="listing-grid">
            <?php foreach ($suites as $suite): 
                $amenities_list = explode(',', $suite['amenities'] ?? '');
                $amenities_list = array_slice($amenities_list, 0, 3);
            ?>
            <div class="listing-card">
                <div class="listing-image">
                    <?php if ($suite['image']): ?>
                    <img src="<?php echo BASE_URL; ?>assets/images/rooms/<?php echo htmlspecialchars($suite['image']); ?>" alt="<?php echo htmlspecialchars($suite['type_name']); ?>" loading="lazy">
                    <?php else: ?>
                    <img src="https://images.unsplash.com/photo-<?php echo $suite_photos[$suite['id'] % 5]; ?>?w=400&h=280&fit=crop" alt="<?php echo htmlspecialchars($suite['type_name']); ?>" loading="lazy">
                    <?php endif; ?>
                    <button class="listing-wishlist" aria-label="Save to wishlist"><i class="bi bi-heart"></i></button>
                    <div class="listing-status" style="background:rgba(245,158,11,0.85);"><?php echo $suite['type_name'] === 'Penthouse' ? 'Ultimate' : ($suite['type_name'] === 'Presidential Suite' ? 'Presidential' : 'Luxury'); ?></div>
                </div>
                <div class="listing-body">
                    <div class="listing-type">&#9733; <?php echo htmlspecialchars($suite['type_name']); ?></div>
                    <h3 class="listing-title"><?php echo htmlspecialchars($suite['room_number']); ?></h3>
                    <p class="listing-desc"><?php echo htmlspecialchars(truncate($suite['description'] ?? $suite['type_description'] ?? 'Experience the height of luxury.', 60)); ?></p>
                    <div class="listing-features">
                        <span><i class="bi bi-people"></i> <?php echo $suite['max_guests']; ?> guests</span>
                        <?php foreach ($amenities_list as $amenity): ?>
                        <span><i class="bi bi-check-lg"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="listing-footer">
                        <div class="listing-price">
                            <?php echo formatMoney($suite['price_per_night']); ?>
                            <span>/night</span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>booking.php?room_id=<?php echo $suite['id']; ?>" class="listing-book">Book Now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php } ?>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Experience Suite Luxury</h2>
        <p class="cta-desc">Book directly for exclusive benefits and the best rates guaranteed.</p>
        <div class="cta-actions">
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg">
                <i class="bi bi-calendar-check me-2"></i>Book Your Suite
            </a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-light btn-lg ms-3">
                <i class="bi bi-telephone me-2"></i>Speak to Concierge
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
