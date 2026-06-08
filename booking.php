<?php
$page_title = 'Book a Room - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

$branches = getBranches();
$room_types = getRoomTypes();

$selected_room_id = $_GET['room_id'] ?? '';
$selected_room_type = $_GET['room_type'] ?? '';

$user_name = '';
$user_email = '';
$user_phone = '';
if (isset($_SESSION['user_id'])) {
    $user = getUser();
    $user_name = $user['full_name'] ?? '';
    $user_email = $user['email'] ?? '';
    $user_phone = $user['phone'] ?? '';
}

$cur = mb_chr(0x20A6, 'UTF-8');
?>

<section class="page-hero" style="min-height:32vh;">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Book a Room</span>
        </nav>
        <h1 class="page-hero-title">Book Your Stay</h1>
        <p class="hero-subtitle">Reserve your luxury experience at FFB Hotel</p>
        <div class="page-hero-decoration"></div>
    </div>
</section>

<section class="section-padding" style="padding-top:40px;">
    <div class="container">
        <div class="booking-steps-bar mb-5" data-animate>
            <div class="booking-step active" data-step="1">
                <div class="step-circle"><span>1</span></div>
                <div class="step-label">Room</div>
            </div>
            <div class="step-line"></div>
            <div class="booking-step" data-step="2">
                <div class="step-circle"><span>2</span></div>
                <div class="step-label">Dates</div>
            </div>
            <div class="step-line"></div>
            <div class="booking-step" data-step="3">
                <div class="step-circle"><span>3</span></div>
                <div class="step-label">Services</div>
            </div>
            <div class="step-line"></div>
            <div class="booking-step" data-step="4">
                <div class="step-circle"><span>4</span></div>
                <div class="step-label">Details</div>
            </div>
            <div class="step-line"></div>
            <div class="booking-step" data-step="5">
                <div class="step-circle"><span>5</span></div>
                <div class="step-label">Payment</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="booking-form-card">
                    <?php flash(); ?>

                    <form action="<?php echo BASE_URL; ?>modules/booking/process" method="POST" id="bookingForm" novalidate>
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="selected_services" id="selectedServicesInput" value="">

                        <div class="booking-section" data-section="1">
                            <div class="booking-section-header">
                                <div class="section-icon"><i class="bi bi-door-open"></i></div>
                                <div>
                                    <h3>Select Your Room</h3>
                                    <p>Choose your preferred branch, room type, and specific room</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-geo-alt"></i>
                                        <select class="form-select" name="branch_id" id="branchSelect" required>
                                            <option value="">Select a branch</option>
                                            <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-bookmark"></i>
                                        <select class="form-select" name="room_type_id" id="roomTypeSelect" required>
                                            <option value="">Select room type</option>
                                            <?php foreach ($room_types as $rt): ?>
                                            <option value="<?php echo $rt['id']; ?>" <?php echo $selected_room_type == $rt['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($rt['name']); ?> &mdash; <?php echo formatMoney($rt['base_price']); ?>/night
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Select Room <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-key"></i>
                                        <select class="form-select" name="room_id" id="roomSelect" required>
                                            <option value="">Select branch and type first</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Number of Guests <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-people"></i>
                                        <select class="form-select" name="guests" id="guestsSelect" required>
                                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-section" data-section="2">
                            <div class="booking-section-header">
                                <div class="section-icon"><i class="bi bi-calendar3"></i></div>
                                <div>
                                    <h3>Choose Your Dates</h3>
                                    <p>Select your check-in and check-out dates</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Check-In Date <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-calendar-event"></i>
                                        <input type="date" class="form-control" name="check_in" id="checkInDate"
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Check-Out Date <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-calendar-check"></i>
                                        <input type="date" class="form-control" name="check_out" id="checkOutDate"
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-section" data-section="3">
                            <div class="booking-section-header">
                                <div class="section-icon"><i class="bi bi-concierge-bell"></i></div>
                                <div>
                                    <h3>Additional Services</h3>
                                    <p>Enhance your stay with optional extras</p>
                                </div>
                            </div>
                            <div id="servicesContainer">
                                <div class="text-center py-4 text-muted" id="servicesLoading" style="display:none;">
                                    <div class="spinner-border text-primary mb-2" role="status" style="width:1.5rem;height:1.5rem;border-width:2px;"></div>
                                    <div style="font-size:0.85rem;">Loading available services...</div>
                                </div>
                                <div id="servicesEmpty" class="text-center py-4" style="display:none;">
                                    <i class="bi bi-info-circle text-muted me-1"></i>
                                    <span class="text-muted" style="font-size:0.85rem;">Select a branch first to view available services</span>
                                </div>
                                <div id="servicesList" class="row g-3"></div>
                                <div id="servicesNone" class="text-center py-4" style="display:none;">
                                    <i class="bi bi-check-circle text-success me-1"></i>
                                    <span class="text-muted" style="font-size:0.85rem;">No additional services available for this branch</span>
                                </div>
                            </div>
                            <div id="selectedServicesSummary" class="mt-3" style="display:none;">
                                <div class="selected-services-header">
                                    <i class="bi bi-check2-square"></i>
                                    <span>Selected Services</span>
                                </div>
                                <div id="selectedServicesList"></div>
                            </div>
                        </div>

                        <div class="booking-section" data-section="4">
                            <div class="booking-section-header">
                                <div class="section-icon"><i class="bi bi-person"></i></div>
                                <div>
                                    <h3>Your Information</h3>
                                    <p>Provide your contact details for the reservation</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-person-circle"></i>
                                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user_name); ?>"
                                               placeholder="e.g. Adewale Ahmed" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-envelope"></i>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user_email); ?>"
                                               placeholder="you@example.com" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-icon-wrap">
                                        <i class="bi bi-telephone"></i>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user_phone); ?>"
                                               placeholder="+234 ..." required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Coupon Code</label>
                                    <div class="coupon-input-group">
                                        <div class="input-icon-wrap" style="flex:1;">
                                            <i class="bi bi-tag"></i>
                                            <input type="text" class="form-control" name="coupon_code" id="couponCode" placeholder="Enter code">
                                        </div>
                                        <button type="button" class="btn btn-gold btn-coupon" id="applyCoupon">Apply</button>
                                    </div>
                                    <div id="couponMessage" class="mt-2" style="font-size:0.82rem;"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Special Requests</label>
                                    <div class="input-icon-wrap textarea-wrap">
                                        <i class="bi bi-chat-left-text"></i>
                                        <textarea class="form-control" name="special_request" rows="3"
                                                  placeholder="Extra pillows, late check-in, birthday celebration..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-section" data-section="5">
                            <div class="booking-section-header">
                                <div class="section-icon"><i class="bi bi-credit-card"></i></div>
                                <div>
                                    <h3>Payment Method</h3>
                                    <p>Choose how you'd like to pay for your stay</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="payment-option selected" id="payOnline">
                                        <div class="payment-radio"></div>
                                        <div class="payment-icon"><i class="bi bi-shield-lock"></i></div>
                                        <div class="payment-info">
                                            <div class="payment-name">Pay Online</div>
                                            <div class="payment-desc">Pay securely via Paystack &mdash; card, bank, USSD</div>
                                        </div>
                                        <input type="radio" name="payment_method" value="paystack" checked hidden>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="payment-option" id="payAtReception">
                                        <div class="payment-radio"></div>
                                        <div class="payment-icon"><i class="bi bi-building"></i></div>
                                        <div class="payment-info">
                                            <div class="payment-name">Pay at Reception</div>
                                            <div class="payment-desc">Pay when you arrive at the hotel</div>
                                        </div>
                                        <input type="radio" name="payment_method" value="reception" hidden>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold btn-lg w-100 booking-submit-btn mt-3">
                            <i class="bi bi-check-circle me-2"></i>Complete Reservation
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="booking-summary-card" data-animate data-delay="100">
                    <div class="summary-header">
                        <div class="summary-icon"><i class="bi bi-receipt"></i></div>
                        <h3>Price Summary</h3>
                    </div>

                    <div class="summary-body" id="priceSummary">
                        <div class="summary-row">
                            <span class="summary-label"><i class="bi bi-bookmark me-1"></i> Room Type</span>
                            <span class="summary-value" id="summaryRoomType">Not selected</span>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="bi bi-calendar-event me-1"></i> Check-In</span>
                            <span class="summary-value" id="summaryCheckIn">&mdash;</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="bi bi-calendar-check me-1"></i> Check-Out</span>
                            <span class="summary-value" id="summaryCheckOut">&mdash;</span>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="bi bi-moon me-1"></i> Nights</span>
                            <span class="summary-value fw-bold" id="summaryNights">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label"><i class="bi bi-people me-1"></i> Guests</span>
                            <span class="summary-value" id="summaryGuests">1</span>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-row">
                            <span class="summary-label">Room Rate/Night</span>
                            <span class="summary-value" id="summaryPricePerNight"><?php echo $cur; ?>0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Room Subtotal</span>
                            <span class="summary-value" id="summarySubtotal"><?php echo $cur; ?>0</span>
                        </div>
                        <div class="summary-divider" id="servicesDivider" style="display:none;"></div>
                        <div class="summary-row" id="servicesFeeRow" style="display:none;">
                            <span class="summary-label"><i class="bi bi-concierge-bell me-1"></i> Services</span>
                            <span class="summary-value" id="summaryServices"><?php echo $cur; ?>0</span>
                        </div>
                        <div class="summary-row discount" id="discountRow" style="display:none;">
                            <span class="summary-label text-success">Discount (<span id="discountLabel">0%</span>)</span>
                            <span class="summary-value text-success">-<?php echo $cur; ?><span id="discountAmount">0</span></span>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-row summary-total">
                            <span class="summary-label">Total Payable</span>
                            <span class="summary-value" id="summaryTotal"><?php echo $cur; ?>0</span>
                        </div>
                    </div>

                    <div class="summary-trust">
                        <i class="bi bi-shield-check"></i>
                        <span>Your booking is secure. Free cancellation within 24 hours.</span>
                    </div>

                    <div class="summary-help">
                        <i class="bi bi-headset"></i>
                        <span>Need help? Call us at <a href="tel:+2349059980991">+234 905 998 0991</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.booking-steps-bar { display:flex; align-items:center; justify-content:center; gap:0; padding:0 20px; }
.booking-step { display:flex; flex-direction:column; align-items:center; gap:8px; }
.step-circle { width:42px; height:42px; border-radius:50%; background:var(--off-white); border:2px solid var(--light-gray); display:flex; align-items:center; justify-content:center; transition:all 0.3s ease; }
.step-circle span { font-size:0.85rem; font-weight:700; color:var(--mid-gray); transition:all 0.3s ease; }
.step-label { font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:1px; color:var(--mid-gray); transition:all 0.3s ease; }
.booking-step.active .step-circle { background:var(--navy); border-color:var(--navy); }
.booking-step.active .step-circle span { color:white; }
.booking-step.active .step-label { color:var(--navy); }
.booking-step.done .step-circle { background:var(--gold); border-color:var(--gold); }
.booking-step.done .step-circle span { color:white; }
.step-line { flex:1; height:2px; background:var(--light-gray); min-width:30px; max-width:80px; margin-bottom:22px; transition:background 0.3s ease; }
.step-line.filled { background:var(--gold); }

.booking-form-card { background:var(--white); border-radius:20px; border:1px solid rgba(0,0,0,0.04); box-shadow:0 4px 24px rgba(0,0,0,0.04); overflow:hidden; }
.booking-section { padding:32px; border-bottom:1px solid rgba(0,0,0,0.04); }
.booking-section:last-of-type { border-bottom:none; }
.booking-section-header { display:flex; align-items:flex-start; gap:16px; margin-bottom:24px; }
.section-icon { width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,rgba(15,26,46,0.06),rgba(15,26,46,0.02)); border:1px solid rgba(15,26,46,0.08); display:flex; align-items:center; justify-content:center; color:var(--navy); font-size:1.1rem; flex-shrink:0; }
.booking-section-header h3 { font-family:var(--font-heading); font-size:1.15rem; color:var(--charcoal); margin-bottom:2px; }
.booking-section-header p { font-size:0.82rem; color:var(--mid-gray); margin:0; }
.booking-section .form-label { font-weight:600; color:var(--charcoal); font-size:0.82rem; margin-bottom:6px; }
.input-icon-wrap { position:relative; }
.input-icon-wrap > i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--mid-gray); font-size:0.9rem; z-index:2; pointer-events:none; }
.input-icon-wrap .form-control, .input-icon-wrap .form-select { padding-left:40px; }
.textarea-wrap > i { top:16px; transform:none; }
.form-control, .form-select { border-radius:10px; border:1.5px solid rgba(0,0,0,0.08); padding:12px 16px; font-size:0.88rem; background:var(--off-white); color:var(--charcoal); transition:all 0.2s ease; }
.form-control:focus, .form-select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,168,76,0.1); background:var(--white); }
.form-control::placeholder { color:#adb5bd; }
.coupon-input-group { display:flex; gap:8px; align-items:stretch; }
.btn-coupon { border-radius:10px; padding:0 20px; font-weight:600; font-size:0.85rem; white-space:nowrap; }

.svc-card { background:var(--off-white); border:1.5px solid rgba(0,0,0,0.06); border-radius:14px; padding:16px; cursor:pointer; transition:all 0.2s ease; position:relative; }
.svc-card:hover { border-color:rgba(201,168,76,0.3); transform:translateY(-1px); }
.svc-card.selected { border-color:var(--gold); background:rgba(201,168,76,0.04); box-shadow:0 0 0 3px rgba(201,168,76,0.1); }
.svc-card-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
.svc-card-name { font-weight:700; font-size:0.9rem; color:var(--charcoal); }
.svc-card-price { font-weight:800; font-size:0.9rem; color:var(--gold); white-space:nowrap; }
.svc-card-desc { font-size:0.78rem; color:var(--mid-gray); margin-bottom:10px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.svc-card-bottom { display:flex; justify-content:space-between; align-items:center; }
.svc-card-category { font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-light); font-weight:600; }
.svc-qty-control { display:flex; align-items:center; gap:0; border:1.5px solid rgba(0,0,0,0.1); border-radius:8px; overflow:hidden; background:white; }
.svc-qty-btn { width:32px; height:32px; border:none; background:transparent; display:flex; align-items:center; justify-content:center; font-size:1rem; color:var(--charcoal); cursor:pointer; transition:background 0.15s; }
.svc-qty-btn:hover { background:rgba(0,0,0,0.05); }
.svc-qty-btn:disabled { color:#ccc; cursor:not-allowed; }
.svc-qty-val { width:36px; text-align:center; font-weight:700; font-size:0.85rem; border-left:1px solid rgba(0,0,0,0.08); border-right:1px solid rgba(0,0,0,0.08); padding:4px 0; }
.svc-card .svc-check { position:absolute; top:12px; right:12px; width:22px; height:22px; border-radius:50%; border:2px solid rgba(0,0,0,0.12); display:flex; align-items:center; justify-content:center; transition:all 0.2s; font-size:0.7rem; color:transparent; }
.svc-card.selected .svc-check { background:var(--gold); border-color:var(--gold); color:white; }

.selected-services-header { display:flex; align-items:center; gap:8px; padding:10px 14px; background:rgba(15,26,46,0.03); border-radius:10px; margin-bottom:12px; font-size:0.82rem; font-weight:700; color:var(--charcoal); text-transform:uppercase; letter-spacing:0.5px; }
.selected-services-header i { color:var(--gold); }
.selected-svc-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(0,0,0,0.04); font-size:0.85rem; }
.selected-svc-row:last-child { border-bottom:none; }
.selected-svc-name { color:var(--charcoal); }
.selected-svc-qty { color:var(--mid-gray); font-size:0.8rem; }
.selected-svc-price { font-weight:700; color:var(--charcoal); }

.payment-option { display:flex; align-items:center; gap:14px; padding:18px 20px; border-radius:14px; border:2px solid rgba(0,0,0,0.06); background:var(--off-white); cursor:pointer; transition:all 0.2s ease; }
.payment-option:hover { border-color:rgba(201,168,76,0.3); }
.payment-option.selected { border-color:var(--gold); background:rgba(201,168,76,0.04); }
.payment-radio { width:20px; height:20px; border-radius:50%; border:2px solid var(--light-gray); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.2s ease; }
.payment-option.selected .payment-radio { border-color:var(--gold); }
.payment-option.selected .payment-radio::after { content:''; width:10px; height:10px; border-radius:50%; background:var(--gold); }
.payment-icon { width:40px; height:40px; border-radius:10px; background:rgba(201,168,76,0.08); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:1.1rem; flex-shrink:0; }
.payment-name { font-weight:700; font-size:0.9rem; color:var(--charcoal); }
.payment-desc { font-size:0.78rem; color:var(--mid-gray); margin-top:1px; }
.booking-submit-btn { margin:0 32px 32px; width:calc(100% - 64px) !important; padding:16px; font-size:1rem; font-weight:700; border-radius:12px; letter-spacing:0.5px; }

.booking-summary-card { background:var(--white); border-radius:20px; border:1px solid rgba(0,0,0,0.04); box-shadow:0 4px 24px rgba(0,0,0,0.04); overflow:hidden; position:sticky; top:100px; }
.summary-header { background:var(--navy); padding:24px 28px; display:flex; align-items:center; gap:14px; }
.summary-icon { width:44px; height:44px; border-radius:12px; background:rgba(201,168,76,0.15); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:1.2rem; }
.summary-header h3 { font-family:var(--font-heading); color:white; font-size:1.15rem; margin:0; }
.summary-body { padding:24px 28px; }
.summary-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; }
.summary-label { font-size:0.83rem; color:var(--mid-gray); }
.summary-value { font-size:0.85rem; font-weight:600; color:var(--charcoal); text-align:right; }
.summary-divider { height:1px; background:rgba(0,0,0,0.05); margin:6px 0; }
.summary-total { padding-top:12px; }
.summary-total .summary-label { font-weight:700; color:var(--charcoal); font-size:0.9rem; }
.summary-total .summary-value { font-size:1.2rem; font-weight:800; color:var(--gold); font-family:var(--font-heading); }
.summary-trust { margin:0 28px; padding:14px 16px; background:rgba(16,185,129,0.05); border-radius:10px; border:1px solid rgba(16,185,129,0.12); display:flex; align-items:flex-start; gap:10px; font-size:0.78rem; color:var(--mid-gray); line-height:1.5; }
.summary-trust i { color:#10b981; font-size:1rem; margin-top:1px; flex-shrink:0; }
.summary-help { margin:16px 28px 24px; padding:12px 16px; background:rgba(15,26,46,0.03); border-radius:10px; display:flex; align-items:center; gap:10px; font-size:0.78rem; color:var(--mid-gray); }
.summary-help i { color:var(--navy); font-size:1rem; flex-shrink:0; }
.summary-help a { color:var(--gold); text-decoration:none; font-weight:700; }
.summary-help a:hover { text-decoration:underline; }

@media (max-width:768px) {
    .booking-steps-bar { padding:0; }
    .step-circle { width:34px; height:34px; }
    .step-circle span { font-size:0.75rem; }
    .step-label { font-size:0.6rem; letter-spacing:0.5px; }
    .step-line { min-width:16px; margin-bottom:18px; }
    .booking-section { padding:20px; }
    .booking-submit-btn { margin:0 20px 20px; width:calc(100% - 40px) !important; }
    .summary-body, .summary-trust, .summary-help { margin-left:20px; margin-right:20px; }
}
</style>

<script>
(function() {
    'use strict';

    var CUR = '<?php echo $cur; ?>';
    var branchSelect = document.getElementById('branchSelect');
    var roomTypeSelect = document.getElementById('roomTypeSelect');
    var roomSelect = document.getElementById('roomSelect');
    var checkIn = document.getElementById('checkInDate');
    var checkOut = document.getElementById('checkOutDate');
    var guestsSelect = document.getElementById('guestsSelect');
    var servicesList = document.getElementById('servicesList');
    var servicesLoading = document.getElementById('servicesLoading');
    var servicesEmpty = document.getElementById('servicesEmpty');
    var servicesNone = document.getElementById('servicesNone');
    var selectedServicesInput = document.getElementById('selectedServicesInput');
    var servicesFeeRow = document.getElementById('servicesFeeRow');
    var servicesDivider = document.getElementById('servicesDivider');
    var selectedSummaryWrap = document.getElementById('selectedServicesSummary');
    var selectedSummaryList = document.getElementById('selectedServicesList');
    var allServices = {};
    var servicesQty = {};

    function loadRooms() {
        var branchId = branchSelect ? branchSelect.value : '';
        var typeId = roomTypeSelect ? roomTypeSelect.value : '';
        if (!branchId) {
            if (roomSelect) roomSelect.innerHTML = '<option value="">Select branch and type first</option>';
            return;
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo BASE_URL; ?>modules/booking/get-rooms', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);

                    if (data.type_counts && data.type_counts.length) {
                        for (var i = 1; i < roomTypeSelect.options.length; i++) {
                            var opt = roomTypeSelect.options[i];
                            if (!opt.value) continue;
                            if (!opt.getAttribute('data-orig')) {
                                opt.setAttribute('data-orig', opt.text);
                            }
                        }
                        var foundType = false;
                        data.type_counts.forEach(function(tc) {
                            foundType = true;
                            for (var i = 0; i < roomTypeSelect.options.length; i++) {
                                var opt = roomTypeSelect.options[i];
                                if (opt.value == tc.type_id) {
                                    var base = opt.getAttribute('data-orig') || opt.text;
                                    var clean = base.replace(/\s*\(.*$/, '').trim();
                                    if (tc.available_count > 0) {
                                        opt.innerHTML = clean + ' <span style="color:#10b981;font-weight:700;font-size:0.8em;">(' + tc.available_count + ' available)</span>';
                                        opt.disabled = false;
                                    } else {
                                        opt.innerHTML = clean + ' <span style="color:#dc3545;font-weight:700;font-size:0.8em;">(Full)</span>';
                                        opt.disabled = true;
                                    }
                                }
                            }
                        });
                    }

                    var html = '<option value="">Select a room</option>';
                    if (data.rooms && data.rooms.length) {
                        data.rooms.forEach(function(room) {
                            html += '<option value="' + room.id + '" data-price="' + room.price_per_night + '"';
                            if (room.id == '<?php echo $selected_room_id; ?>') html += ' selected';
                            html += '>' + room.room_number + '</option>';
                        });
                    } else if (branchId) {
                        html = '<option value="">No available rooms for this selection</option>';
                    }
                    roomSelect.innerHTML = html;
                } catch(e) {}
            }
        };
        xhr.send('branch_id=' + encodeURIComponent(branchId) + '&room_type_id=' + encodeURIComponent(typeId));
    }

    function loadServices() {
        var branchId = branchSelect ? branchSelect.value : '';
        if (!branchId) {
            servicesList.innerHTML = '';
            servicesLoading.style.display = 'none';
            servicesEmpty.style.display = 'block';
            servicesNone.style.display = 'none';
            return;
        }
        servicesLoading.style.display = 'block';
        servicesEmpty.style.display = 'none';
        servicesNone.style.display = 'none';
        servicesList.innerHTML = '';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo BASE_URL; ?>modules/booking/get-services', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                servicesLoading.style.display = 'none';
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.services && data.services.length > 0) {
                        renderServices(data.services);
                    } else {
                        servicesNone.style.display = 'block';
                    }
                } catch(e) {
                    servicesNone.style.display = 'block';
                }
            }
        };
        xhr.send('branch_id=' + encodeURIComponent(branchId));
    }

    function renderServices(services) {
        allServices = {};
        servicesQty = {};
        var html = '';
        services.forEach(function(s) {
            allServices[s.id] = s;
            servicesQty[s.id] = 0;
            var desc = s.description || 'Available for your stay';
            var category = s.category || 'service';
            var catLabel = category.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            html += '<div class="col-md-6 col-lg-4">';
            html += '<div class="svc-card" data-id="' + s.id + '" onclick="toggleService(' + s.id + ')">';
            html += '<div class="svc-check"><i class="bi bi-check-lg"></i></div>';
            html += '<div class="svc-card-top"><div class="svc-card-name">' + escHtml(s.name) + '</div>';
            html += '<div class="svc-card-price">' + CUR + parseFloat(s.price).toLocaleString() + '</div></div>';
            html += '<div class="svc-card-desc">' + escHtml(desc) + '</div>';
            html += '<div class="svc-card-bottom">';
            html += '<span class="svc-card-category">' + escHtml(catLabel) + '</span>';
            html += '<div class="svc-qty-control">';
            html += '<button type="button" class="svc-qty-btn" onclick="event.stopPropagation();changeQty(' + s.id + ',-1)" disabled>-</button>';
            html += '<span class="svc-qty-val" id="svcQty' + s.id + '">0</span>';
            html += '<button type="button" class="svc-qty-btn" onclick="event.stopPropagation();changeQty(' + s.id + ',1)">+</button>';
            html += '</div></div></div></div>';
        });
        servicesList.innerHTML = html;
    }

    window.toggleService = function(id) {
        changeQty(id, 1);
    };

    window.changeQty = function(id, delta) {
        var svc = allServices[id];
        if (!svc) return;
        var cur = servicesQty[id] || 0;
        var next = Math.max(0, Math.min(10, cur + delta));
        servicesQty[id] = next;
        var qtyEl = document.getElementById('svcQty' + id);
        if (qtyEl) qtyEl.textContent = next;
        var card = document.querySelector('.svc-card[data-id="' + id + '"]');
        if (card) {
            card.classList.toggle('selected', next > 0);
            var btns = card.querySelectorAll('.svc-qty-btn');
            if (btns[0]) btns[0].disabled = next <= 0;
            if (btns[1]) btns[1].disabled = next >= 10;
        }
        updateSummary();
    };

    function escHtml(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    if (branchSelect) branchSelect.addEventListener('change', function() { loadRooms(); loadServices(); });
    if (roomTypeSelect) roomTypeSelect.addEventListener('change', loadRooms);

    <?php if ($selected_room_id || $selected_room_type): ?>
    if (branchSelect && branchSelect.value) { loadRooms(); loadServices(); }
    <?php endif; ?>

    if (checkIn && checkOut) {
        checkIn.addEventListener('change', function() {
            var minOut = new Date(this.value);
            minOut.setDate(minOut.getDate() + 1);
            checkOut.min = minOut.toISOString().split('T')[0];
            if (checkOut.value && checkOut.value <= this.value) checkOut.value = '';
            updateSummary();
            updateSteps();
        });
        checkOut.addEventListener('change', function() { updateSummary(); updateSteps(); });
    }

    if (roomSelect) roomSelect.addEventListener('change', function() { updateSummary(); updateSteps(); });
    if (guestsSelect) guestsSelect.addEventListener('change', updateSummary);
    if (roomTypeSelect) roomTypeSelect.addEventListener('change', function() { updateSummary(); updateSteps(); });
    if (branchSelect) branchSelect.addEventListener('change', updateSteps);

    var payOnline = document.getElementById('payOnline');
    var payReception = document.getElementById('payAtReception');
    function setupPayment(el) {
        el.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(function(p) { p.classList.remove('selected'); });
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    }
    if (payOnline) setupPayment(payOnline);
    if (payReception) setupPayment(payReception);

    function updateSteps() {
        var steps = document.querySelectorAll('.booking-step');
        var lines = document.querySelectorAll('.step-line');
        var filled = 0;
        if (branchSelect && branchSelect.value && roomTypeSelect && roomTypeSelect.value && roomSelect && roomSelect.value) filled = 1;
        if (filled >= 1 && checkIn && checkIn.value && checkOut && checkOut.value) filled = 2;
        if (filled >= 2) filled = 3;
        var nameInput = document.querySelector('input[name="full_name"]');
        var emailInput = document.querySelector('input[name="email"]');
        var phoneInput = document.querySelector('input[name="phone"]');
        if (filled >= 3 && nameInput && nameInput.value.trim() && emailInput && emailInput.value.trim() && phoneInput && phoneInput.value.trim()) filled = 4;

        steps.forEach(function(s, i) {
            s.classList.remove('active', 'done');
            if (i < filled) s.classList.add('done');
            else if (i === filled) s.classList.add('active');
            else if (filled >= steps.length) s.classList.add('done');
        });
        lines.forEach(function(l, i) {
            l.classList.toggle('filled', i < filled);
        });
    }

    document.querySelectorAll('#bookingForm input, #bookingForm select').forEach(function(el) {
        el.addEventListener('change', updateSteps);
        el.addEventListener('input', updateSteps);
    });

    function updateSummary() {
        var roomTypeEl = roomTypeSelect ? roomTypeSelect.options[roomTypeSelect.selectedIndex] : null;
        var roomEl = roomSelect ? roomSelect.options[roomSelect.selectedIndex] : null;
        var roomTypeName = roomTypeEl && roomTypeEl.value ? roomTypeEl.text.split(' - ')[0].split(' \u2014 ')[0] : 'Not selected';
        var pricePerNight = roomEl && roomEl.value ? parseFloat(roomEl.getAttribute('data-price') || 0) : 0;
        var checkInVal = checkIn ? checkIn.value : '';
        var checkOutVal = checkOut ? checkOut.value : '';
        var guestsVal = guestsSelect ? guestsSelect.value : 1;
        var nights = 0;
        if (checkInVal && checkOutVal) {
            var d1 = new Date(checkInVal), d2 = new Date(checkOutVal);
            nights = Math.floor((d2 - d1) / 86400000);
            if (nights < 0) nights = 0;
        }
        var roomSubtotal = pricePerNight * nights;
        var servicesTotal = 0;
        var svcHtml = '';
        for (var id in servicesQty) {
            var qty = servicesQty[id];
            if (qty > 0 && allServices[id]) {
                var lineTotal = allServices[id].price * qty;
                servicesTotal += lineTotal;
                svcHtml += '<div class="selected-svc-row"><div><span class="selected-svc-name">' + escHtml(allServices[id].name) + '</span> <span class="selected-svc-qty">&times; ' + qty + '</span></div><span class="selected-svc-price">' + CUR + lineTotal.toLocaleString() + '</span></div>';
            }
        }
        var grandTotal = roomSubtotal + servicesTotal;

        document.getElementById('summaryRoomType').textContent = roomTypeName;
        document.getElementById('summaryCheckIn').textContent = checkInVal || '\u2014';
        document.getElementById('summaryCheckOut').textContent = checkOutVal || '\u2014';
        document.getElementById('summaryNights').textContent = nights;
        document.getElementById('summaryGuests').textContent = guestsVal;
        document.getElementById('summaryPricePerNight').textContent = CUR + pricePerNight.toLocaleString();
        document.getElementById('summarySubtotal').textContent = CUR + roomSubtotal.toLocaleString();

        if (servicesTotal > 0) {
            servicesFeeRow.style.display = 'flex';
            servicesDivider.style.display = 'block';
            document.getElementById('summaryServices').textContent = CUR + servicesTotal.toLocaleString();
            selectedSummaryWrap.style.display = 'block';
            selectedSummaryList.innerHTML = svcHtml;
        } else {
            servicesFeeRow.style.display = 'none';
            servicesDivider.style.display = 'none';
            selectedSummaryWrap.style.display = 'none';
            selectedSummaryList.innerHTML = '';
        }

        var discountAmt = 0;
        if (document.getElementById('discountRow').style.display === 'flex') {
            discountAmt = parseFloat(document.getElementById('discountAmount').textContent.replace(/[^0-9.]/g, '')) || 0;
        }
        var total = Math.max(0, grandTotal - discountAmt);
        document.getElementById('summaryTotal').textContent = CUR + total.toLocaleString();

        var svcData = [];
        for (var sid in servicesQty) {
            if (servicesQty[sid] > 0) svcData.push({id: parseInt(sid), qty: servicesQty[sid], name: allServices[sid].name, price: allServices[sid].price});
        }
        selectedServicesInput.value = JSON.stringify(svcData);
    }

    updateSummary();
    updateSteps();

    var applyBtn = document.getElementById('applyCoupon');
    var couponInput = document.getElementById('couponCode');
    var couponMsg = document.getElementById('couponMessage');

    if (applyBtn && couponInput) {
        applyBtn.addEventListener('click', function() {
            var code = couponInput.value.trim();
            if (!code) { couponMsg.innerHTML = '<span style="color:#dc3545;">Please enter a coupon code.</span>'; return; }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo BASE_URL; ?>ajax/validate-coupon.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.valid) {
                            couponMsg.innerHTML = '<span style="color:#10b981;"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
                            var sub = parseFloat(document.getElementById('summarySubtotal').textContent.replace(/[^0-9.]/g, '')) || 0;
                            var svcAmt = 0;
                            for (var id in servicesQty) { if (servicesQty[id] > 0 && allServices[id]) svcAmt += allServices[id].price * servicesQty[id]; }
                            var base = sub + svcAmt;
                            var discType = data.coupon ? data.coupon.discount_type : 'fixed';
                            var discVal = data.coupon ? data.coupon.discount_value : 0;
                            var disc = discType === 'percentage' ? base * (discVal / 100) : discVal;
                            var total = base - disc;
                            document.getElementById('discountRow').style.display = 'flex';
                            document.getElementById('discountLabel').textContent = discType === 'percentage' ? discVal + '%' : 'Fixed';
                            document.getElementById('discountAmount').textContent = disc.toLocaleString();
                            document.getElementById('summaryTotal').textContent = CUR + Math.max(0, total).toLocaleString();
                        } else {
                            couponMsg.innerHTML = '<span style="color:#dc3545;">' + data.message + '</span>';
                        }
                    } catch(e) { couponMsg.innerHTML = '<span style="color:#dc3545;">Error validating coupon.</span>'; }
                }
            };
            var subVal = parseFloat(document.getElementById('summarySubtotal').textContent.replace(/[^0-9.]/g, '')) || 0;
            var svcVal = 0;
            for (var id in servicesQty) { if (servicesQty[id] > 0 && allServices[id]) svcVal += allServices[id].price * servicesQty[id]; }
            xhr.send('code=' + encodeURIComponent(code) + '&amount=' + encodeURIComponent(subVal + svcVal));
        });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
