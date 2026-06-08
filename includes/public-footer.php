<?php
require_once __DIR__ . '/functions.php';
$social_instagram = getSetting('social_instagram', '#');
$social_facebook = getSetting('social_facebook', '#');
$social_twitter = getSetting('social_twitter', '#');
$social_whatsapp = getSetting('social_whatsapp', '#');
$social_linkedin = getSetting('social_linkedin', '#');
$contact_email = getSetting('contact_email', 'info@ffbhotel.com');
$contact_phone = getSetting('contact_phone', '+2349059980991');
$contact_address = getSetting('contact_address', '14 Adeola Odeku Street, Victoria Island, Lagos, Nigeria');
?>
</main>

<footer class="site-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1440 60" preserveAspectRatio="none">
            <path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#0b1320"/>
        </svg>
    </div>

    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col footer-brand-col">
                    <h4 class="footer-brand">FFB HOTEL</h4>
                    <p class="footer-desc">Experience unparalleled luxury and world-class hospitality at FFB Hotel. Every stay is crafted to perfection, blending timeless elegance with modern comfort for an unforgettable experience.</p>
                    <div class="footer-social">
                        <a href="<?php echo htmlspecialchars($social_instagram); ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="<?php echo htmlspecialchars($social_facebook); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="<?php echo htmlspecialchars($social_twitter); ?>" target="_blank" rel="noopener" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="<?php echo htmlspecialchars($social_whatsapp); ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <a href="<?php echo htmlspecialchars($social_linkedin); ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>rooms.php">Rooms &amp; Suites</a></li>
                        <li><a href="<?php echo BASE_URL; ?>gallery.php">Gallery</a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact.php">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h5>Services</h5>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>services.php">Luxury Accommodation</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php">Fine Dining</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php">Spa &amp; Wellness</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php">Event Planning</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php">Concierge Service</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h5>Contact</h5>
                    <ul>
                        <li>
                            <div class="footer-contact-item">
                                <i class="bi bi-geo-alt"></i>
                                <span><?php echo htmlspecialchars($contact_address); ?></span>
                            </div>
                        </li>
                        <li>
                            <div class="footer-contact-item">
                                <i class="bi bi-telephone"></i>
                                <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>"><?php echo htmlspecialchars($contact_phone); ?></a>
                            </div>
                        </li>
                        <li>
                            <div class="footer-contact-item">
                                <i class="bi bi-envelope"></i>
                                <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h5>Newsletter</h5>
                    <div class="footer-newsletter">
                        <p>Subscribe for exclusive offers and updates.</p>
                        <form action="<?php echo BASE_URL; ?>subscribe.php" method="POST" class="newsletter-form" id="footerNewsletterForm">
                            <?php echo csrf_field(); ?>
                            <input type="email" name="email" placeholder="Your email" required>
                            <button type="submit"><i class="bi bi-send"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> FFB Hotel. All rights reserved.</p>
            <p>Powered by <a href="https://ffbhotel.com" target="_blank" rel="noopener">FFB Hotel</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js?v=2.0"></script>
<script src="<?php echo BASE_URL; ?>assets/js/live-chat.js?v=2.0"></script>
<?php require_once __DIR__ . '/live-chat-widget.php'; ?>
</body>
</html>
