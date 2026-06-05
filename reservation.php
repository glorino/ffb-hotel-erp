<?php
$page_title = 'Table Reservation - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();
$branches = getBranches();
?>
<!-- Page Hero -->
<section class="page-hero" style="min-height: 35vh;">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Table Reservation</span>
        </nav>
        <h1 class="page-hero-title">Reserve a Table</h1>
        <p class="hero-subtitle">Book your dining experience at FFB Hotel</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="reservation-card">
                    <?php flash(); ?>

                    <form action="<?php echo BASE_URL; ?>modules/reservation/process.php" method="POST" class="reservation-form" novalidate>
                        <?php echo csrf_field(); ?>

                        <div class="row g-4">
                            <div class="col-12">
                                <h4 style="font-family: var(--font-serif); color: var(--charcoal); margin-bottom: 4px;">
                                    <i class="bi bi-calendar-check me-2" style="color: var(--gold);"></i>Reservation Details
                                </h4>
                                <p style="color: var(--mid-gray); font-size: 0.9rem;">Fill in your preferred date, time, and party size.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Select Branch <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="branch_id" required>
                                    <option value="">Choose a branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Reservation Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="reservation_date"
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Reservation Time <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="reservation_time" required>
                                    <option value="">Select time</option>
                                    <option value="08:00">8:00 AM</option>
                                    <option value="09:00">9:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">1:00 PM</option>
                                    <option value="14:00">2:00 PM</option>
                                    <option value="15:00">3:00 PM</option>
                                    <option value="16:00">4:00 PM</option>
                                    <option value="17:00">5:00 PM</option>
                                    <option value="18:00">6:00 PM</option>
                                    <option value="19:00">7:00 PM</option>
                                    <option value="20:00">8:00 PM</option>
                                    <option value="21:00">9:00 PM</option>
                                    <option value="22:00">10:00 PM</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Number of Guests <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="guests" required>
                                    <option value="">Select guests</option>
                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i === 1 ? 'Guest' : 'Guests'; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Special Requests
                                </label>
                                <textarea class="form-control" name="special_request" rows="3"
                                          placeholder="Any dietary restrictions, allergies, or special occasions? (e.g., birthday, anniversary, business meeting)"
                                          style="resize: vertical;"></textarea>
                            </div>
                        </div>

                        <hr style="border-color: var(--off-white); margin: 24px 0;">

                        <div class="row g-4">
                            <div class="col-12">
                                <h4 style="font-family: var(--font-serif); color: var(--charcoal); margin-bottom: 4px;">
                                    <i class="bi bi-person me-2" style="color: var(--gold);"></i>Contact Information
                                </h4>
                                <p style="color: var(--mid-gray); font-size: 0.9rem;">We'll send you a confirmation.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="full_name" placeholder="Your full name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" name="email" placeholder="your@email.com" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500; color: var(--charcoal);">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control" name="phone" placeholder="+1 234 567 8900" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold btn-lg w-100 mt-4">
                            <i class="bi bi-check-circle me-2"></i>Confirm Reservation
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Sidebar -->
            <div class="col-lg-5">
                <div class="content-card mb-4">
                    <h4 style="font-family: var(--font-serif); color: var(--charcoal); margin-bottom: 16px;">
                        <i class="bi bi-info-circle me-2" style="color: var(--gold);"></i>Dining Information
                    </h4>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div>
                                <h6 style="font-weight: 600; color: var(--charcoal); margin-bottom: 2px;">Operating Hours</h6>
                                <p style="font-size: 0.85rem; color: var(--mid-gray); margin: 0;">
                                    Breakfast: 7:00 AM - 10:30 AM<br>
                                    Lunch: 12:00 PM - 3:00 PM<br>
                                    Dinner: 6:00 PM - 10:30 PM
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <h6 style="font-weight: 600; color: var(--charcoal); margin-bottom: 2px;">Reservation Duration</h6>
                                <p style="font-size: 0.85rem; color: var(--mid-gray); margin: 0;">
                                    Standard dining reservations are for 2 hours. Need more time? Let us know in your special requests.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;">
                                <i class="bi bi-bell"></i>
                            </div>
                            <div>
                                <h6 style="font-weight: 600; color: var(--charcoal); margin-bottom: 2px;">Cancellation Policy</h6>
                                <p style="font-size: 0.85rem; color: var(--mid-gray); margin: 0;">
                                    Free cancellation up to 2 hours before your reservation. Late cancellations may incur a fee.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h6 style="font-weight: 600; color: var(--charcoal); margin-bottom: 2px;">Dress Code</h6>
                                <p style="font-size: 0.85rem; color: var(--mid-gray); margin: 0;">
                                    Smart casual attire required. Jackets recommended for evening dining.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card" style="background: linear-gradient(135deg, var(--charcoal), var(--charcoal-2)); color: var(--white);">
                    <h4 style="font-family: var(--font-serif); color: var(--gold); margin-bottom: 12px;">
                        <i class="bi bi-cup-straw me-2"></i>Looking for Takeaway?
                    </h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 20px;">
                        Prefer to enjoy our cuisine in the comfort of your room? Browse our full menu and order online for takeaway or delivery.
                    </p>
                    <a href="<?php echo BASE_URL; ?>order.php" class="btn btn-gold w-100">
                        <i class="bi bi-bag me-2"></i>Order Food Online
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Can't Wait to See You</h2>
        <p class="cta-desc">Experience the finest dining at FFB Hotel.</p>
        <div class="cta-actions">
            <a href="<?php echo BASE_URL; ?>order.php" class="btn btn-gold btn-lg">
                <i class="bi bi-cup-hot me-2"></i>View Full Menu
            </a>
        </div>
    </div>
</section>

<script>
(function() {
    var dateInput = document.querySelector('input[name="reservation_date"]');
    if (dateInput) {
        var today = new Date();
        dateInput.min = today.toISOString().split('T')[0];
    }
})();
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
