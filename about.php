<?php
$page_title = 'About Us - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

// Count stats from DB
$rooms_count = 0;
$staff_count = 0;
$awards_count = 0;
$years_count = date('Y') - 2018;

try {
    $stmt = $db->query("SELECT COUNT(*) FROM rooms");
    $rooms_count = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id NOT IN (SELECT id FROM roles WHERE slug = 'customer')");
    $staff_count = (int)$stmt->fetchColumn();

    $awards_json = getSetting('awards_count', '15');
    $awards_count = (int)$awards_json;
} catch (Exception $e) {
    $rooms_count = 15;
    $staff_count = 120;
    $awards_count = 15;
}
?>
<!-- Page Hero -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>About Us</span>
        </nav>
        <h1 class="page-hero-title">About Us</h1>
        <p class="hero-subtitle">Discover the story behind FFB Hotel</p>
    </div>
</section>

<!-- Story Section -->
<section class="section-padding">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div style="border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-lg); height: 450px; background: linear-gradient(135deg, #1a1a2e, #16213e); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 6rem;">
                    <i class="bi bi-building"></i>
                </div>
            </div>
            <div class="col-lg-6">
                <span class="section-subtitle" style="color: var(--gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 4px; font-weight: 600;">Our Story</span>
                <h2 style="font-family: var(--font-serif); font-size: 2.5rem; color: var(--charcoal); margin: 12px 0 20px;">A Legacy of Excellence Since 2018</h2>
                <p style="color: var(--mid-gray); line-height: 1.8; margin-bottom: 16px;">
                    Founded in 2018, FFB Hotel was born from a vision to redefine luxury accommodation in Africa. What began as a single boutique hotel has grown into a premier hospitality brand known for its unwavering commitment to excellence, personalized service, and exquisite attention to detail.
                </p>
                <p style="color: var(--mid-gray); line-height: 1.8; margin-bottom: 16px;">
                    Our journey has been defined by a passion for creating memorable experiences. Every property we manage reflects our dedication to blending contemporary elegance with timeless hospitality traditions, ensuring that each guest feels truly valued and cared for.
                </p>
                <p style="color: var(--mid-gray); line-height: 1.8;">
                    Today, FFB Hotel stands as a symbol of luxury and sophistication, welcoming discerning travelers from around the world to experience the very best in accommodation, dining, and wellness.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Counter -->
<section style="background: linear-gradient(135deg, var(--navy), var(--charcoal)); padding: 80px 0;">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $years_count; ?>+</div>
                    <div class="stat-label">Years of Excellence</div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($rooms_count); ?>+</div>
                    <div class="stat-label">Luxury Rooms</div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($staff_count); ?>+</div>
                    <div class="stat-label">Dedicated Staff</div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($awards_count); ?>+</div>
                    <div class="stat-label">Industry Awards</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission, Vision, Values -->
<section class="section-padding bg-white">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">Our Foundation</span>
            <h2 class="section-title">Mission, Vision &amp; Values</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">The principles that guide every aspect of our service and operations.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="mission-box">
                    <div class="mission-icon"><i class="bi bi-bullseye"></i></div>
                    <h3 class="mission-title">Our Mission</h3>
                    <p class="mission-text">To provide unparalleled luxury hospitality experiences that exceed expectations, creating lasting memories for every guest through impeccable service, elegant accommodations, and genuine care.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="mission-box">
                    <div class="mission-icon"><i class="bi bi-eye"></i></div>
                    <h3 class="mission-title">Our Vision</h3>
                    <p class="mission-text">To be Africa's most celebrated hospitality brand, setting the global standard for luxury accommodation, fine dining, and wellness services while fostering sustainable tourism and community development.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="mission-box">
                    <div class="mission-icon"><i class="bi bi-heart"></i></div>
                    <h3 class="mission-title">Our Values</h3>
                    <p class="mission-text">Excellence in every detail, integrity in all we do, warmth in every interaction, innovation that enhances experiences, and a deep respect for our guests, our people, and our environment.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">Leadership</span>
            <h2 class="section-title">Meet Our Team</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">The passionate individuals who bring the FFB Hotel experience to life every day.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="bi bi-person-circle"></i></div>
                    <div class="team-info">
                        <h3 class="team-name">Dr. Adewale FFB</h3>
                        <div class="team-role">Founder &amp; CEO</div>
                        <p class="team-bio">Visionary leader with over 25 years in luxury hospitality. Founded FFB Hotel to showcase African excellence.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="bi bi-person-circle"></i></div>
                    <div class="team-info">
                        <h3 class="team-name">Chioma Eze</h3>
                        <div class="team-role">Chief Operations Officer</div>
                        <p class="team-bio">Operations expert ensuring seamless experiences across all properties with meticulous attention to quality.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="bi bi-person-circle"></i></div>
                    <div class="team-info">
                        <h3 class="team-name">Emeka Obi</h3>
                        <div class="team-role">Executive Chef</div>
                        <p class="team-bio">World-renowned chef crafting extraordinary culinary experiences that celebrate local and international flavors.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="bi bi-person-circle"></i></div>
                    <div class="team-info">
                        <h3 class="team-name">Amara Nwachukwu</h3>
                        <div class="team-role">Director of Guest Experience</div>
                        <p class="team-bio">Dedicated to creating personalized journeys that make every stay at FFB Hotel truly unforgettable.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values List -->
<section class="section-padding" style="background: linear-gradient(135deg, #f8f7f4, #ffffff);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-subtitle" style="color: var(--gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 4px; font-weight: 600;">Why Choose Us</span>
                <h2 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--charcoal); margin: 12px 0 24px;">The FFB Hotel Difference</h2>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;"><i class="bi bi-award"></i></div>
                        <div>
                            <h5 style="font-weight: 600; color: var(--charcoal); margin-bottom: 4px;">Award-Winning Service</h5>
                            <p style="color: var(--mid-gray); font-size: 0.9rem; margin: 0;">Recognized globally for excellence in hospitality and guest satisfaction.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h5 style="font-weight: 600; color: var(--charcoal); margin-bottom: 4px;">Premium Standards</h5>
                            <p style="color: var(--mid-gray); font-size: 0.9rem; margin: 0;">Every property adheres to the highest standards of luxury and comfort.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;"><i class="bi bi-person-hearts"></i></div>
                        <div>
                            <h5 style="font-weight: 600; color: var(--charcoal); margin-bottom: 4px;">Personalized Touch</h5>
                            <p style="color: var(--mid-gray); font-size: 0.9rem; margin: 0;">Tailored experiences that cater to your unique preferences and desires.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;"><i class="bi bi-globe2"></i></div>
                        <div>
                            <h5 style="font-weight: 600; color: var(--charcoal); margin-bottom: 4px;">Global Reach, Local Heart</h5>
                            <p style="color: var(--mid-gray); font-size: 0.9rem; margin: 0;">International standards infused with authentic local hospitality and culture.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div style="border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-lg); height: 400px; background: linear-gradient(135deg, #1a1a2e, #16213e); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 5rem;">
                    <i class="bi bi-stars"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
