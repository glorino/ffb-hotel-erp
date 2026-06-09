<?php
$page_title = 'Contact Us - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

$contact_email = getSetting('contact_email', 'info@ffbhotel.com');
$contact_phone = '+2349059980991';
$contact_address = '14 Adeola Odeku Street, Victoria Island, Lagos, Nigeria';
$social_whatsapp = '2349059980991';

$branches = getBranches();
?>

<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Contact</span>
        </nav>
        <h1 class="page-hero-title">Contact Us</h1>
        <p class="hero-subtitle">We'd love to hear from you</p>
        <div class="page-hero-decoration"></div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5" data-animate>
            <span class="section-subtitle" style="color:var(--gold);font-size:0.8rem;text-transform:uppercase;letter-spacing:4px;font-weight:600;">Get in Touch</span>
            <h2 style="font-family:var(--font-heading);font-size:2.4rem;color:var(--charcoal);margin:12px 0 16px;">We're Here to Help</h2>
            <p style="color:var(--mid-gray);max-width:600px;margin:0 auto;line-height:1.7;">Whether you have a question about our services, need assistance with a booking, or want to plan a special event, our team is ready to assist you.</p>
            <div class="gold-divider" style="margin:24px auto 0;"></div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6" data-animate data-delay="0">
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>" class="text-decoration-none">
                    <div class="contact-feature-card text-center h-100">
                        <div class="contact-feature-icon bg-gold-subtle">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <h5>Call Us</h5>
                        <p class="mb-2"><?php echo htmlspecialchars($contact_phone); ?></p>
                        <small class="text-muted">Tap to call directly</small>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="100">
                <a href="https://wa.me/<?php echo $social_whatsapp; ?>" target="_blank" class="text-decoration-none">
                    <div class="contact-feature-card text-center h-100">
                        <div class="contact-feature-icon bg-success-subtle">
                            <i class="bi bi-whatsapp"></i>
                        </div>
                        <h5>WhatsApp</h5>
                        <p class="mb-2">Chat with us instantly</p>
                        <small class="text-muted">Tap to open WhatsApp</small>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6" data-animate data-delay="200">
                <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="text-decoration-none">
                    <div class="contact-feature-card text-center h-100">
                        <div class="contact-feature-icon bg-primary-subtle">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <h5>Email Us</h5>
                        <p class="mb-2"><?php echo htmlspecialchars($contact_email); ?></p>
                        <small class="text-muted">We reply within 24 hours</small>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-5 align-items-start">
            <div class="col-lg-5" data-animate>
                <div class="contact-info-panel">
                    <h3 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--charcoal);margin-bottom:20px;">Visit Our Hotel</h3>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="contact-detail-label">Address</div>
                            <span><?php echo htmlspecialchars($contact_address); ?></span>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon"><i class="bi bi-telephone"></i></div>
                        <div>
                            <div class="contact-detail-label">Phone</div>
                            <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>"><?php echo htmlspecialchars($contact_phone); ?></a>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="contact-detail-label">Email</div>
                            <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon"><i class="bi bi-whatsapp"></i></div>
                        <div>
                            <div class="contact-detail-label">WhatsApp</div>
                            <a href="https://wa.me/<?php echo $social_whatsapp; ?>" target="_blank">+234 905 998 0991</a>
                        </div>
                    </div>

                    <hr style="border-color:rgba(0,0,0,0.06);margin:20px 0;">

                    <h6 style="font-weight:700;color:var(--charcoal);margin-bottom:12px;font-size:0.85rem;text-transform:uppercase;letter-spacing:1px;"><i class="bi bi-clock me-1" style="color:var(--gold);"></i> Business Hours</h6>
                    <div class="hours-table">
                        <div class="hours-row">
                            <span>Monday - Friday</span>
                            <span>8:00 AM - 10:00 PM</span>
                        </div>
                        <div class="hours-row">
                            <span>Saturday</span>
                            <span>9:00 AM - 11:00 PM</span>
                        </div>
                        <div class="hours-row">
                            <span>Sunday</span>
                            <span>10:00 AM - 8:00 PM</span>
                        </div>
                        <div class="hours-row hours-row-highlight">
                            <span>24/7 Front Desk</span>
                            <span class="badge-gold">Always Open</span>
                        </div>
                    </div>

                    <hr style="border-color:rgba(0,0,0,0.06);margin:20px 0;">

                    <h6 style="font-weight:700;color:var(--charcoal);margin-bottom:12px;font-size:0.85rem;text-transform:uppercase;letter-spacing:1px;">Follow Us</h6>
                    <div class="d-flex gap-2">
                        <a href="<?php echo htmlspecialchars(getSetting('social_instagram', '#')); ?>" class="social-icon-link" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="<?php echo htmlspecialchars(getSetting('social_facebook', '#')); ?>" class="social-icon-link" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="<?php echo htmlspecialchars(getSetting('social_twitter', '#')); ?>" class="social-icon-link" target="_blank" rel="noopener" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="<?php echo htmlspecialchars(getSetting('social_linkedin', '#')); ?>" class="social-icon-link" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-7" data-animate data-delay="100">
                <div class="contact-form-panel">
                    <h3 style="font-family:var(--font-heading);font-size:1.6rem;color:var(--charcoal);margin-bottom:6px;">Send Us a Message</h3>
                    <p style="color:var(--mid-gray);font-size:0.9rem;margin-bottom:24px;">Fill out the form below and we'll get back to you promptly.</p>

                    <?php flash(); ?>

                    <form action="<?php echo BASE_URL; ?>modules/contact/send.php" method="POST" novalidate>
                        <?php echo csrf_field(); ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Your full name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" placeholder="your@email.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" placeholder="+234 ...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <select class="form-select" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="booking">Booking Inquiry</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="complaint">Complaint</option>
                                    <option value="feedback">Feedback</option>
                                    <option value="events">Events &amp; Conferences</option>
                                    <option value="careers">Careers</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="message" rows="5" placeholder="How can we help you?" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-gold btn-lg w-100" style="padding:14px 0;">
                                    <i class="bi bi-send me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background:var(--off-white);">
    <div class="container">
        <div class="section-header mb-4" data-animate>
            <span class="section-subtitle">Find Us</span>
            <h2 class="section-title" style="font-size:2rem;">Our Location</h2>
            <div class="gold-divider"></div>
        </div>
        <div data-animate>
            <iframe src="https://maps.google.com/maps?q=14+Adeola+Odeku+Street,+Victoria+Island,+Lagos,+Nigeria&t=&z=16&ie=UTF8&iwloc=&output=embed"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    title="FFB Hotel Location"
                    style="width:100%;height:420px;border:0;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);"></iframe>
        </div>
    </div>
</section>

<section class="section-padding" style="background:linear-gradient(135deg,var(--navy),var(--navy-dark));">
    <div class="container text-center" data-animate>
        <span class="section-subtitle" style="color:var(--gold);font-size:0.8rem;text-transform:uppercase;letter-spacing:4px;font-weight:600;">Ready to Begin?</span>
        <h2 style="font-family:var(--font-heading);font-size:2.5rem;color:var(--white);margin:12px 0 16px;">Ready to Experience FFB Hotel?</h2>
        <p style="color:rgba(255,255,255,0.5);max-width:520px;margin:0 auto 28px;font-size:1rem;line-height:1.7;">Book your stay today and let us take care of everything.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg"><i class="bi bi-calendar-check me-2"></i>Book Now</a>
            <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>" class="btn btn-outline-gold btn-lg"><i class="bi bi-telephone me-2"></i>Call Now</a>
        </div>
    </div>
</section>

<style>
.contact-feature-card {
    background: var(--white);
    border-radius: 16px;
    padding: 32px 24px;
    border: 1px solid rgba(0,0,0,0.04);
    transition: all 0.3s ease;
    box-shadow: 0 2px 12px rgba(0,0,0,0.03);
}
.contact-feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(15,26,46,0.1);
    border-color: rgba(201,168,76,0.2);
}
.contact-feature-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 1.3rem;
}
.contact-feature-icon i {
    font-size: 1.3rem;
}
.bg-gold-subtle { background: rgba(201,168,76,0.1); color: var(--gold); }
.bg-success-subtle { background: rgba(16,185,129,0.1); color: #10b981; }
.bg-primary-subtle { background: rgba(13,110,253,0.1); color: #0d6efd; }
.contact-feature-card h5 {
    font-family: var(--font-heading);
    font-size: 1.1rem;
    color: var(--charcoal);
    margin-bottom: 6px;
}
.contact-feature-card p {
    color: var(--charcoal);
    font-weight: 500;
    font-size: 0.9rem;
}
.contact-info-panel {
    background: var(--white);
    border-radius: 16px;
    padding: 32px;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 2px 12px rgba(0,0,0,0.03);
    border-top: 4px solid var(--gold);
}
.contact-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 16px;
}
.contact-detail-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(201,168,76,0.08);
    border: 1px solid rgba(201,168,76,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}
.contact-detail-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--text-light);
    margin-bottom: 2px;
    font-weight: 600;
}
.contact-detail-item span,
.contact-detail-item a {
    font-size: 0.9rem;
    color: var(--charcoal);
    text-decoration: none;
    font-weight: 500;
    line-height: 1.5;
}
.contact-detail-item a:hover {
    color: var(--gold);
}
.hours-table {
    font-size: 0.88rem;
}
.hours-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.04);
}
.hours-row:last-child { border-bottom: none; }
.hours-row span:first-child { font-weight: 600; color: var(--charcoal); }
.hours-row span:last-child { color: var(--mid-gray); }
.hours-row-highlight { background: rgba(201,168,76,0.04); margin: 0 -12px; padding: 10px 12px !important; border-radius: 8px; }
.badge-gold {
    background: linear-gradient(135deg, #c9a84c, #b8943d);
    color: white;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.social-icon-link {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 1.5px solid rgba(201,168,76,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1rem;
}
.social-icon-link:hover {
    background: var(--gold);
    color: var(--white);
    border-color: var(--gold);
    transform: translateY(-2px);
}
.contact-form-panel {
    background: var(--white);
    border-radius: 16px;
    padding: 32px;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 2px 12px rgba(0,0,0,0.03);
    border-top: 4px solid var(--gold);
}
.contact-form-panel .form-label {
    font-weight: 600;
    color: var(--charcoal);
    font-size: 0.82rem;
    margin-bottom: 6px;
}
.contact-form-panel .form-control,
.contact-form-panel .form-select {
    border-radius: 10px;
    border: 1.5px solid rgba(0,0,0,0.08);
    padding: 12px 16px;
    font-size: 0.9rem;
    background: var(--off-white);
    transition: border-color 0.2s;
}
.contact-form-panel .form-control:focus,
.contact-form-panel .form-select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
}
</style>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
