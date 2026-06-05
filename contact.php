<?php
$page_title = 'Contact Us - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

$contact_email = getSetting('contact_email', 'info@ffbhotel.com');
$contact_phone = getSetting('contact_phone', '+1 234 567 8900');
$contact_address = getSetting('contact_address', '123 Luxury Avenue, Beverly Hills, CA 90210');
$social_whatsapp = getSetting('social_whatsapp', '#');

$branches = getBranches();
?>
<!-- Page Hero -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Contact</span>
        </nav>
        <h1 class="page-hero-title">Contact Us</h1>
        <p class="hero-subtitle">We would love to hear from you</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <!-- Contact Info -->
            <div class="col-lg-5">
                <span class="section-subtitle" style="color: var(--gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 4px; font-weight: 600;">Get in Touch</span>
                <h2 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--charcoal); margin: 12px 0 24px;">We're Here to Help</h2>
                <p style="color: var(--mid-gray); margin-bottom: 32px; line-height: 1.7;">
                    Whether you have a question about our services, need assistance with a booking, or want to plan a special event, our team is ready to assist you.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="contact-info-card">
                            <div class="contact-icon"><i class="bi bi-telephone"></i></div>
                            <div>
                                <div class="contact-label">Phone</div>
                                <div class="contact-value">
                                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>">
                                        <?php echo htmlspecialchars($contact_phone); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="contact-info-card">
                            <div class="contact-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <div class="contact-label">Email</div>
                                <div class="contact-value">
                                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>">
                                        <?php echo htmlspecialchars($contact_email); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="contact-info-card">
                            <div class="contact-icon"><i class="bi bi-whatsapp"></i></div>
                            <div>
                                <div class="contact-label">WhatsApp</div>
                                <div class="contact-value">
                                    <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $contact_phone)); ?>" target="_blank">
                                        Chat on WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="contact-info-card">
                            <div class="contact-icon"><i class="bi bi-geo-alt"></i></div>
                            <div>
                                <div class="contact-label">Address</div>
                                <div class="contact-value"><?php echo htmlspecialchars($contact_address); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Hours -->
                <div class="content-card mb-4">
                    <h4 style="font-family: var(--font-serif); color: var(--charcoal); margin-bottom: 16px;">
                        <i class="bi bi-clock me-2" style="color: var(--gold);"></i>Business Hours
                    </h4>
                    <table class="table table-borderless branch-hours-table" style="font-size: 0.9rem;">
                        <tbody>
                            <tr>
                                <td style="font-weight: 600; color: var(--charcoal); padding: 6px 0;">Monday - Friday</td>
                                <td style="text-align: right; color: var(--mid-gray); padding: 6px 0;">8:00 AM - 10:00 PM</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: var(--charcoal); padding: 6px 0;">Saturday</td>
                                <td style="text-align: right; color: var(--mid-gray); padding: 6px 0;">9:00 AM - 11:00 PM</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: var(--charcoal); padding: 6px 0;">Sunday</td>
                                <td style="text-align: right; color: var(--mid-gray); padding: 6px 0;">10:00 AM - 8:00 PM</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: var(--charcoal); padding: 6px 0;">24/7 Front Desk</td>
                                <td style="text-align: right; color: var(--charcoal); padding: 6px 0;"><span class="badge bg-success">Always Open</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Social Links -->
                <div>
                    <h5 style="font-family: var(--font-sans); font-weight: 600; color: var(--charcoal); margin-bottom: 12px;">Follow Us</h5>
                    <div class="d-flex gap-2">
                        <a href="<?php echo htmlspecialchars(getSetting('social_instagram', '#')); ?>" class="btn btn-outline-gold" style="width:44px;height:44px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;" target="_blank" rel="noopener" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars(getSetting('social_facebook', '#')); ?>" class="btn btn-outline-gold" style="width:44px;height:44px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;" target="_blank" rel="noopener" aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars(getSetting('social_twitter', '#')); ?>" class="btn btn-outline-gold" style="width:44px;height:44px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;" target="_blank" rel="noopener" aria-label="Twitter">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars(getSetting('social_linkedin', '#')); ?>" class="btn btn-outline-gold" style="width:44px;height:44px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;" target="_blank" rel="noopener" aria-label="LinkedIn">
                            <i class="bi bi-linkedin"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="content-card">
                    <h3 style="font-family: var(--font-serif); color: var(--charcoal); margin-bottom: 24px;">
                        <i class="bi bi-send me-2" style="color: var(--gold);"></i>Send Us a Message
                    </h3>

                    <?php flash(); ?>

                    <form action="<?php echo BASE_URL; ?>modules/contact/send.php" method="POST" class="row g-3" novalidate>
                        <?php echo csrf_field(); ?>

                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500; color: var(--charcoal);">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Your full name" required
                                   style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500; color: var(--charcoal);">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" placeholder="your@email.com" required
                                   style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500; color: var(--charcoal);">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" placeholder="+1 234 567 8900"
                                   style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500; color: var(--charcoal);">Subject <span class="text-danger">*</span></label>
                            <select class="form-select" name="subject" required
                                    style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
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
                            <label class="form-label" style="font-weight: 500; color: var(--charcoal);">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="message" rows="5" placeholder="How can we help you?" required
                                      style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px; resize: vertical;"></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-gold btn-lg">
                                <i class="bi bi-send me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="section-padding-sm" style="background: var(--off-white);">
    <div class="container">
        <div class="section-header mb-4">
            <span class="section-subtitle">Find Us</span>
            <h2 class="section-title" style="font-size: 2rem;">Our Location</h2>
        </div>
        <div class="map-placeholder">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3964.549508164789!2d3.4213336740995493!3d6.445940823955386!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103bf4c5a8f3b9c1%3A0x8b1a5a5f5c5f5c5f!2sVictoria%20Island%2C%20Lagos!5e0!3m2!1sen!2sng!4v1700000000000"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    title="FFB Hotel Location"></iframe>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Experience FFB Hotel?</h2>
        <p class="cta-desc">Book your stay today and let us take care of everything.</p>
        <div class="cta-actions">
            <a href="<?php echo BASE_URL; ?>booking.php" class="btn btn-gold btn-lg">
                <i class="bi bi-calendar-check me-2"></i>Book Now
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
