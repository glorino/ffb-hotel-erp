<?php
$page_title = 'About Us - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

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

<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>About Us</span>
        </nav>
        <h1 class="page-hero-title">About Us</h1>
        <p class="hero-subtitle">Discover the story behind FFB Hotel</p>
        <div class="page-hero-decoration"></div>
    </div>
</section>

<section class="about-slider" data-animate>
    <div class="about-slider-track" id="aboutSlider">
        <div class="about-slide active">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&h=500&fit=crop" alt="FFB Hotel Luxury Property" loading="lazy">
            <div class="about-slide-overlay"></div>
        </div>
        <div class="about-slide">
            <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=1200&h=500&fit=crop" alt="FFB Hotel Pool" loading="lazy">
            <div class="about-slide-overlay"></div>
        </div>
        <div class="about-slide">
            <img src="https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200&h=500&fit=crop" alt="FFB Hotel Suite" loading="lazy">
            <div class="about-slide-overlay"></div>
        </div>
        <div class="about-slide">
            <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&h=500&fit=crop" alt="FFB Hotel Dining" loading="lazy">
            <div class="about-slide-overlay"></div>
        </div>
    </div>
    <button class="about-slider-arrow prev" id="aboutSliderPrev"><i class="bi bi-chevron-left"></i></button>
    <button class="about-slider-arrow next" id="aboutSliderNext"><i class="bi bi-chevron-right"></i></button>
    <div class="about-slider-dots" id="aboutSliderDots">
        <button class="about-slider-dot active" data-index="0"></button>
        <button class="about-slider-dot" data-index="1"></button>
        <button class="about-slider-dot" data-index="2"></button>
        <button class="about-slider-dot" data-index="3"></button>
    </div>
    <div class="experience-badge" style="position:absolute;bottom:32px;right:32px;z-index:5;">
        <span class="number"><?php echo $years_count; ?>+</span>
        <span class="label">Years of Excellence</span>
    </div>
</section>

<section class="section-padding" data-animate>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="section-subtitle" style="color: var(--gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 4px; font-weight: 600;">Our Story</span>
                <h2 style="font-family: var(--font-heading); font-size: 2.5rem; color: var(--charcoal); margin: 12px 0 20px;">A Legacy of Excellence Since 2018</h2>
                <div class="gold-divider" style="margin: 0 auto 24px;"></div>
                <p style="color: var(--text-light); line-height: 1.8; margin-bottom: 16px;">
                    Founded in 2018, FFB Hotel was born from a vision to redefine luxury accommodation in Africa. What began as a single boutique hotel has grown into a premier hospitality brand known for its unwavering commitment to excellence, personalized service, and exquisite attention to detail.
                </p>
                <p style="color: var(--text-light); line-height: 1.8; margin-bottom: 16px;">
                    Our journey has been defined by a passion for creating memorable experiences. Every property we manage reflects our dedication to blending contemporary elegance with timeless hospitality traditions, ensuring that each guest feels truly valued and cared for.
                </p>
                <p style="color: var(--text-light); line-height: 1.8;">
                    Today, FFB Hotel stands as a symbol of luxury and sophistication, welcoming discerning travelers from around the world to experience the very best in accommodation, dining, and wellness.
                </p>
            </div>
        </div>
    </div>
</section>

<section style="background: linear-gradient(135deg, var(--navy), var(--charcoal)); padding: 70px 0;" data-animate>
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-3 col-6 position-relative">
                <div class="counter-item">
                    <div class="counter-icon"><i class="bi bi-calendar-check"></i></div>
                    <div class="counter-number stat-number" data-target="<?php echo $years_count; ?>" data-suffix="+">0</div>
                    <div class="counter-label">Years of Excellence</div>
                </div>
                <div style="position:absolute;right:0;top:50%;transform:translateY(-50%);width:1px;height:50px;background:rgba(255,255,255,0.06);"></div>
            </div>
            <div class="col-lg-3 col-6 position-relative">
                <div class="counter-item">
                    <div class="counter-icon"><i class="bi bi-building"></i></div>
                    <div class="counter-number stat-number" data-target="<?php echo $rooms_count; ?>" data-suffix="+">0</div>
                    <div class="counter-label">Luxury Rooms</div>
                </div>
                <div style="position:absolute;right:0;top:50%;transform:translateY(-50%);width:1px;height:50px;background:rgba(255,255,255,0.06);"></div>
            </div>
            <div class="col-lg-3 col-6 position-relative">
                <div class="counter-item">
                    <div class="counter-icon"><i class="bi bi-people"></i></div>
                    <div class="counter-number stat-number" data-target="<?php echo $staff_count; ?>" data-suffix="+">0</div>
                    <div class="counter-label">Dedicated Staff</div>
                </div>
                <div style="position:absolute;right:0;top:50%;transform:translateY(-50%);width:1px;height:50px;background:rgba(255,255,255,0.06);"></div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="counter-item">
                    <div class="counter-icon"><i class="bi bi-award"></i></div>
                    <div class="counter-number stat-number" data-target="<?php echo $awards_count; ?>" data-suffix="+">0</div>
                    <div class="counter-label">Industry Awards</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Our Foundation</span>
            <h2 class="section-title">Mission, Vision &amp; Core Values</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">The principles that guide every aspect of our service and operations.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4" data-animate data-delay="0">
                <div class="mission-box mission-mission">
                    <div class="mission-icon"><i class="bi bi-bullseye"></i></div>
                    <h3 class="mission-title">Our Mission</h3>
                    <p class="mission-text">To provide unparalleled luxury hospitality experiences that exceed expectations, creating lasting memories for every guest through impeccable service, elegant accommodations, and genuine care.</p>
                </div>
            </div>
            <div class="col-lg-4" data-animate data-delay="100">
                <div class="mission-box mission-vision">
                    <div class="mission-icon"><i class="bi bi-eye"></i></div>
                    <h3 class="mission-title">Our Vision</h3>
                    <p class="mission-text">To be Africa's most celebrated hospitality brand, setting the global standard for luxury accommodation, fine dining, and wellness services while fostering sustainable tourism and community development.</p>
                </div>
            </div>
            <div class="col-lg-4" data-animate data-delay="200">
                <div class="mission-box mission-values">
                    <div class="mission-icon"><i class="bi bi-heart"></i></div>
                    <h3 class="mission-title">Our Values</h3>
                    <p class="mission-text">Excellence in every detail, integrity in all we do, warmth in every interaction, innovation that enhances experiences, and a deep respect for our guests, our people, and our environment.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-subtitle">Leadership</span>
            <h2 class="section-title">Meet Our Team</h2>
            <div class="gold-divider"></div>
            <p class="section-desc">The passionate individuals who bring the FFB Hotel experience to life every day.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6" data-animate data-delay="0">
                <div class="team-card">
                    <div class="team-avatar"><img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=200&h=200&fit=crop&crop=face" alt="Dr. Adewale FFB"></div>
                    <h4>Dr. Adewale FFB</h4>
                    <div class="team-role">Founder &amp; CEO</div>
                    <p style="color: var(--text-light); font-size: 0.85rem; margin-top: 10px; line-height: 1.6;">Visionary leader with over 25 years in luxury hospitality. Founded FFB Hotel to showcase African excellence.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-animate data-delay="80">
                <div class="team-card">
                    <div class="team-avatar"><img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&h=200&fit=crop&crop=face" alt="Chioma Eze"></div>
                    <h4>Chioma Eze</h4>
                    <div class="team-role">Chief Operations Officer</div>
                    <p style="color: var(--text-light); font-size: 0.85rem; margin-top: 10px; line-height: 1.6;">Operations expert ensuring seamless experiences across all properties with meticulous attention to quality.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-animate data-delay="160">
                <div class="team-card">
                    <div class="team-avatar"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&crop=face" alt="Emeka Obi"></div>
                    <h4>Emeka Obi</h4>
                    <div class="team-role">Executive Chef</div>
                    <p style="color: var(--text-light); font-size: 0.85rem; margin-top: 10px; line-height: 1.6;">World-renowned chef crafting extraordinary culinary experiences that celebrate local and international flavors.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-animate data-delay="240">
                <div class="team-card">
                    <div class="team-avatar"><img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&h=200&fit=crop&crop=face" alt="Amara Nwachukwu"></div>
                    <h4>Amara Nwachukwu</h4>
                    <div class="team-role">Director of Guest Experience</div>
                    <p style="color: var(--text-light); font-size: 0.85rem; margin-top: 10px; line-height: 1.6;">Dedicated to creating personalized journeys that make every stay at FFB Hotel truly unforgettable.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background: linear-gradient(135deg, #f8f7f4, #ffffff);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6" data-animate>
                <span class="section-subtitle" style="color: var(--gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 4px; font-weight: 600;">Why Choose Us</span>
                <h2 style="font-family: var(--font-heading); font-size: 2.2rem; color: var(--charcoal); margin: 12px 0 24px;">The FFB Hotel Difference</h2>
                <div class="d-flex flex-column gap-3">
                    <div class="why-item">
                        <div class="why-icon gold-icon"><i class="bi bi-award"></i></div>
                        <div>
                            <h5>Award-Winning Service</h5>
                            <p>Recognized globally for excellence in hospitality and guest satisfaction.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon blue-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h5>Premium Standards</h5>
                            <p>Every property adheres to the highest standards of luxury and comfort.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon green-icon"><i class="bi bi-person-hearts"></i></div>
                        <div>
                            <h5>Personalized Touch</h5>
                            <p>Tailored experiences catering to your unique preferences and desires.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon purple-icon"><i class="bi bi-globe2"></i></div>
                        <div>
                            <h5>Global Reach, Local Heart</h5>
                            <p>International standards infused with authentic local hospitality and culture.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-animate data-delay="100">
                <div class="about-image-wrap" style="height:420px;">
                    <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&h=600&fit=crop" alt="FFB Hotel Service" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background: linear-gradient(135deg, var(--navy), var(--navy-dark));">
    <div class="container text-center" data-animate>
        <span class="section-subtitle" style="color: var(--gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 4px; font-weight: 600;">Experience Luxury</span>
        <h2 style="font-family: var(--font-heading); font-size: 2.5rem; color: var(--white); margin: 12px 0 16px;">Begin Your Journey Today</h2>
        <p style="color: rgba(255,255,255,0.5); max-width: 560px; margin: 0 auto 28px; font-size: 1rem; line-height: 1.7;">From the moment you arrive, every detail is crafted to make your stay unforgettable. Book direct for the best rates and exclusive benefits.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg"><i class="bi bi-calendar-check me-2"></i>Book Your Stay</a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-gold btn-lg"><i class="bi bi-send me-2"></i>Get in Touch</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
<script>
(function() {
    var track = document.getElementById('aboutSlider');
    var slides = track.querySelectorAll('.about-slide');
    var dots = document.querySelectorAll('#aboutSliderDots .about-slider-dot');
    var prevBtn = document.getElementById('aboutSliderPrev');
    var nextBtn = document.getElementById('aboutSliderNext');
    if (!slides.length) return;
    var current = 0;
    var total = slides.length;
    var interval;

    function show(idx) {
        slides.forEach(function(s) { s.classList.remove('active'); });
        dots.forEach(function(d) { d.classList.remove('active'); });
        slides[idx].classList.add('active');
        dots[idx].classList.add('active');
        current = idx;
    }

    function next() { show((current + 1) % total); }
    function prev() { show((current - 1 + total) % total); }

    function startAuto() { interval = setInterval(next, 4000); }
    function stopAuto() { clearInterval(interval); }

    dots.forEach(function(d) {
        d.addEventListener('click', function() {
            stopAuto();
            show(parseInt(this.dataset.index));
            startAuto();
        });
    });

    if (prevBtn) prevBtn.addEventListener('click', function() { stopAuto(); prev(); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', function() { stopAuto(); next(); startAuto(); });

    track.addEventListener('mouseenter', stopAuto);
    track.addEventListener('mouseleave', startAuto);

    startAuto();
})();
</script>
