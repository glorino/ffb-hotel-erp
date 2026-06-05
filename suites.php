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
        <!-- Penthouse -->
        <div class="suite-card">
            <div class="suite-gallery">
                <div class="suite-main-img" style="background: linear-gradient(135deg, #0a0a1a, #1a1a2e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:4rem;">
                    <i class="bi bi-building"></i>
                </div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #16213e, #1a1a2e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;">
                    <i class="bi bi-house-heart"></i>
                </div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #1a1a2e, #16213e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;">
                    <i class="bi bi-stars"></i>
                </div>
            </div>
            <div class="suite-content">
                <div class="suite-type">&#9733; Ultimate Luxury</div>
                <h2 class="suite-name">The Penthouse</h2>
                <p class="suite-desc">
                    The epitome of luxury living, our Penthouse spans the entire top floor with a private rooftop terrace, 
                    infinity pool, and 360-degree panoramic city views. Featuring a grand living room, formal dining for 
                    eight, a fully equipped gourmet kitchen, private cinema, and a master suite with a spa-like bathroom. 
                    Your personal butler is available 24/7 to cater to your every need.
                </p>
                <div class="suite-amenities">
                    <span class="amenity-badge"><i class="bi bi-wifi"></i> Free WiFi</span>
                    <span class="amenity-badge"><i class="bi bi-snow"></i> A/C</span>
                    <span class="amenity-badge"><i class="bi bi-tv"></i> Cinema Room</span>
                    <span class="amenity-badge"><i class="bi bi-water"></i> Private Pool</span>
                    <span class="amenity-badge"><i class="bi bi-cup-hot"></i> Full Kitchen</span>
                    <span class="amenity-badge"><i class="bi bi-person-badge"></i> Butler Service</span>
                    <span class="amenity-badge"><i class="bi bi-tree"></i> Rooftop Terrace</span>
                    <span class="amenity-badge"><i class="bi bi-people"></i> 6 Guests</span>
                </div>
                <div class="suite-footer">
                    <div>
                        <div class="suite-price"><?php echo CURRENCY_SYMBOL; ?>950,000 <small>/ night</small></div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>booking.php?room_type=5" class="btn btn-gold btn-lg">
                        <i class="bi bi-calendar-check me-2"></i>Book This Suite
                    </a>
                </div>
            </div>
        </div>

        <!-- Presidential Suite -->
        <div class="suite-card">
            <div class="suite-gallery">
                <div class="suite-main-img" style="background: linear-gradient(135deg, #1a1a2e, #16213e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:4rem;">
                    <i class="bi bi-building"></i>
                </div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #16213e, #1a1a2e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;">
                    <i class="bi bi-journal"></i>
                </div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #1a1a2e, #16213e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;">
                    <i class="bi bi-cup-straw"></i>
                </div>
            </div>
            <div class="suite-content">
                <div class="suite-type">&#9733; Presidential Class</div>
                <h2 class="suite-name">Presidential Suite</h2>
                <p class="suite-desc">
                    A statement of prestige and sophistication, our Presidential Suite offers a magnificent living space 
                    with separate dining room, study, and a palatial bedroom. Floor-to-ceiling windows frame breathtaking 
                    city panoramas. Enjoy exclusive lounge access, private check-in, and a dedicated concierge. The marble 
                    bathroom features a deep soaking tub and rain shower.
                </p>
                <div class="suite-amenities">
                    <span class="amenity-badge"><i class="bi bi-wifi"></i> Free WiFi</span>
                    <span class="amenity-badge"><i class="bi bi-snow"></i> A/C</span>
                    <span class="amenity-badge"><i class="bi bi-tv"></i> 65" TV</span>
                    <span class="amenity-badge"><i class="bi bi-water"></i> Jacuzzi</span>
                    <span class="amenity-badge"><i class="bi bi-book"></i> Study Room</span>
                    <span class="amenity-badge"><i class="bi bi-person-badge"></i> Butler Service</span>
                    <span class="amenity-badge"><i class="bi bi-cup-straw"></i> Private Dining</span>
                    <span class="amenity-badge"><i class="bi bi-people"></i> 4 Guests</span>
                </div>
                <div class="suite-footer">
                    <div>
                        <div class="suite-price"><?php echo CURRENCY_SYMBOL; ?>600,000 <small>/ night</small></div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>booking.php?room_type=4" class="btn btn-gold btn-lg">
                        <i class="bi bi-calendar-check me-2"></i>Book This Suite
                    </a>
                </div>
            </div>
        </div>

        <!-- Luxury Suite -->
        <div class="suite-card">
            <div class="suite-gallery">
                <div class="suite-main-img" style="background: linear-gradient(135deg, #16213e, #1a1a2e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:4rem;">
                    <i class="bi bi-building"></i>
                </div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #1a1a2e, #16213e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;">
                    <i class="bi bi-heart"></i>
                </div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #16213e, #1a1a2e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;">
                    <i class="bi bi-flower1"></i>
                </div>
            </div>
            <div class="suite-content">
                <div class="suite-type">&#9733; Luxury Experience</div>
                <h2 class="suite-name">Luxury Suite</h2>
                <p class="suite-desc">
                    Elegantly designed with a separate living area and sumptuous bedroom, the Luxury Suite offers 
                    the perfect balance of space and sophistication. Unwind in your private jacuzzi, enjoy complimentary 
                    champagne upon arrival, and experience the personalized attention of our dedicated suite concierge. 
                    Every detail has been carefully curated for an unforgettable stay.
                </p>
                <div class="suite-amenities">
                    <span class="amenity-badge"><i class="bi bi-wifi"></i> Free WiFi</span>
                    <span class="amenity-badge"><i class="bi bi-snow"></i> A/C</span>
                    <span class="amenity-badge"><i class="bi bi-tv"></i> 55" TV</span>
                    <span class="amenity-badge"><i class="bi bi-water"></i> Jacuzzi</span>
                    <span class="amenity-badge"><i class="bi bi-house-heart"></i> Living Room</span>
                    <span class="amenity-badge"><i class="bi bi-person-badge"></i> Butler Service</span>
                    <span class="amenity-badge"><i class="bi bi-cup-hot"></i> Mini Bar</span>
                    <span class="amenity-badge"><i class="bi bi-people"></i> 3 Guests</span>
                </div>
                <div class="suite-footer">
                    <div>
                        <div class="suite-price"><?php echo CURRENCY_SYMBOL; ?>350,000 <small>/ night</small></div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>booking.php?room_type=3" class="btn btn-gold btn-lg">
                        <i class="bi bi-calendar-check me-2"></i>Book This Suite
                    </a>
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
        <?php foreach ($suites as $suite): 
            $amenities_list = explode(',', $suite['amenities'] ?? '');
        ?>
        <div class="suite-card">
            <div class="suite-gallery">
                <div class="suite-main-img" style="background: linear-gradient(135deg, #1a1a2e, #16213e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:4rem;">
                    <?php if ($suite['image']): ?>
                    <img src="<?php echo BASE_URL; ?>assets/images/rooms/<?php echo htmlspecialchars($suite['image']); ?>" alt="<?php echo htmlspecialchars($suite['type_name']); ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <i class="bi bi-building"></i>
                    <?php endif; ?>
                </div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #16213e, #1a1a2e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;"><i class="bi bi-house-heart"></i></div>
                <div class="suite-sub-img" style="background: linear-gradient(135deg, #1a1a2e, #16213e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:2rem;"><i class="bi bi-stars"></i></div>
            </div>
            <div class="suite-content">
                <div class="suite-type">&#9733; <?php echo $suite['type_name'] === 'Penthouse' ? 'Ultimate Luxury' : ($suite['type_name'] === 'Presidential Suite' ? 'Presidential Class' : 'Luxury Experience'); ?></div>
                <h2 class="suite-name"><?php echo htmlspecialchars($suite['type_name']); ?> - Room <?php echo htmlspecialchars($suite['room_number']); ?></h2>
                <p class="suite-desc"><?php echo htmlspecialchars($suite['description'] ?? $suite['type_description'] ?? 'Experience the height of luxury in this exceptional suite.'); ?></p>
                <div class="suite-amenities">
                    <?php foreach ($amenities_list as $amenity): ?>
                    <span class="amenity-badge"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                    <?php endforeach; ?>
                    <span class="amenity-badge"><i class="bi bi-people"></i> <?php echo $suite['max_guests']; ?> Guests</span>
                </div>
                <div class="suite-footer">
                    <div>
                        <div class="suite-price"><?php echo formatMoney($suite['price_per_night']); ?> <small>/ night</small></div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>booking.php?room_id=<?php echo $suite['id']; ?>" class="btn btn-gold btn-lg">
                        <i class="bi bi-calendar-check me-2"></i>Book This Suite
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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
