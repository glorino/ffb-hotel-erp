<?php
$page_title = 'Book a Room - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

// Get branches for dropdown
$branches = getBranches();

// Get room types
$room_types = getRoomTypes();

// Get selected room from URL
$selected_room_id = $_GET['room_id'] ?? '';
$selected_room_type = $_GET['room_type'] ?? '';

// Pre-fill if logged in
$user_name = '';
$user_email = '';
$user_phone = '';
if (isset($_SESSION['user_id'])) {
    $user = getUser();
    $user_name = $user['full_name'] ?? '';
    $user_email = $user['email'] ?? '';
    $user_phone = $user['phone'] ?? '';
}
?>
<!-- Page Hero -->
<section class="page-hero" style="min-height: 35vh;">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Book a Room</span>
        </nav>
        <h1 class="page-hero-title">Book Your Stay</h1>
        <p class="hero-subtitle">Secure your luxury experience at FFB Hotel</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="booking-form-wrapper">
                    <?php flash(); ?>

                    <form action="<?php echo BASE_URL; ?>modules/booking/process.php" method="POST" id="bookingForm" novalidate>
                        <?php echo csrf_field(); ?>

                        <!-- Step 1: Room Selection -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="step-number">1</span>
                                Select Your Room
                            </h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Branch <span class="text-danger">*</span></label>
                                    <select class="form-select" name="branch_id" id="branchSelect" required
                                            style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                        <option value="">Select a branch</option>
                                        <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Room Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="room_type_id" id="roomTypeSelect" required
                                            style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                        <option value="">Select room type</option>
                                        <?php foreach ($room_types as $rt): ?>
                                        <option value="<?php echo $rt['id']; ?>" <?php echo $selected_room_type == $rt['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rt['name']); ?> - <?php echo formatMoney($rt['base_price']); ?>/night
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Select Room <span class="text-danger">*</span></label>
                                    <select class="form-select" name="room_id" id="roomSelect" required
                                            style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                        <option value="">Select branch and type first</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Number of Guests <span class="text-danger">*</span></label>
                                    <select class="form-select" name="guests" id="guestsSelect" required
                                            style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Dates -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="step-number">2</span>
                                Choose Your Dates
                            </h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Check-In Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="check_in" id="checkInDate"
                                           min="<?php echo date('Y-m-d'); ?>" required
                                           style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Check-Out Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="check_out" id="checkOutDate"
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required
                                           style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Personal Info -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="step-number">3</span>
                                Your Information
                            </h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user_name); ?>"
                                           placeholder="John Doe" required
                                           style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user_email); ?>"
                                           placeholder="john@example.com" required
                                           style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user_phone); ?>"
                                           placeholder="+1 234 567 8900" required
                                           style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Special Requests & Coupon -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="step-number">4</span>
                                Special Requests &amp; Coupon
                            </h3>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" style="font-weight: 500;">Special Requests</label>
                                    <textarea class="form-control" name="special_request" rows="3"
                                              placeholder="Any special requests? (e.g., extra pillows, late check-in, celebrations)"
                                              style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px; resize: vertical;"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight: 500;">Coupon Code</label>
                                    <div class="coupon-input-group">
                                        <input type="text" class="form-control" name="coupon_code" id="couponCode"
                                               placeholder="Enter coupon code"
                                               style="border-radius: var(--radius-sm); border: 1.5px solid var(--light-gray); padding: 12px 16px;">
                                        <button type="button" class="btn btn-outline-gold btn-apply" id="applyCoupon">
                                            Apply
                                        </button>
                                    </div>
                                    <div id="couponMessage" class="mt-2" style="font-size: 0.85rem;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Payment -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="step-number">5</span>
                                Payment Method
                            </h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="payment-option selected" id="payOnline">
                                        <div class="payment-radio"></div>
                                        <div class="payment-info">
                                            <div class="payment-name">Paystack Online</div>
                                            <div class="payment-desc">Pay securely with card, bank transfer, or USSD</div>
                                        </div>
                                        <input type="radio" name="payment_method" value="paystack" checked hidden>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="payment-option" id="payAtReception">
                                        <div class="payment-radio"></div>
                                        <div class="payment-info">
                                            <div class="payment-name">Pay at Reception</div>
                                            <div class="payment-desc">Pay when you arrive at the hotel</div>
                                        </div>
                                        <input type="radio" name="payment_method" value="reception" hidden>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold btn-lg w-100 mt-4">
                            <i class="bi bi-check-circle me-2"></i>Book Now
                        </button>
                    </form>
                </div>
            </div>

            <!-- Price Summary Sidebar -->
            <div class="col-lg-4">
                <div class="cart-sidebar">
                    <h3 class="cart-title" style="font-family: var(--font-serif);">
                        <i class="bi bi-receipt me-2" style="color: var(--gold);"></i>Price Summary
                    </h3>

                    <div class="price-summary" id="priceSummary">
                        <div class="price-row">
                            <span>Room Type</span>
                            <span id="summaryRoomType">Not selected</span>
                        </div>
                        <div class="price-row">
                            <span>Check-In</span>
                            <span id="summaryCheckIn">--</span>
                        </div>
                        <div class="price-row">
                            <span>Check-Out</span>
                            <span id="summaryCheckOut">--</span>
                        </div>
                        <div class="price-row">
                            <span>Nights</span>
                            <span id="summaryNights">0</span>
                        </div>
                        <div class="price-row">
                            <span>Guests</span>
                            <span id="summaryGuests">0</span>
                        </div>
                        <div class="price-row">
                            <span>Price per Night</span>
                            <span id="summaryPricePerNight"><?php echo CURRENCY_SYMBOL; ?>0</span>
                        </div>
                        <div class="price-row">
                            <span>Subtotal</span>
                            <span id="summarySubtotal"><?php echo CURRENCY_SYMBOL; ?>0</span>
                        </div>
                        <div class="price-row discount" id="discountRow" style="display:none;">
                            <span>Discount (<span id="discountLabel">0%</span>)</span>
                            <span>-<?php echo CURRENCY_SYMBOL; ?><span id="discountAmount">0</span></span>
                        </div>
                        <div class="price-row total">
                            <span>Total Payable</span>
                            <span id="summaryTotal"><?php echo CURRENCY_SYMBOL; ?>0</span>
                        </div>
                    </div>

                    <div class="mt-3 p-3" style="background: rgba(212,175,55,0.08); border-radius: var(--radius-sm); border: 1px solid rgba(212,175,55,0.2);">
                        <p style="font-size: 0.82rem; color: var(--mid-gray); margin: 0;">
                            <i class="bi bi-shield-check me-1" style="color: var(--gold);"></i>
                            Your booking is secure. You can cancel within 24 hours of booking for a full refund.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    'use strict';

    // Room loading based on branch/type
    var branchSelect = document.getElementById('branchSelect');
    var roomTypeSelect = document.getElementById('roomTypeSelect');
    var roomSelect = document.getElementById('roomSelect');
    var checkIn = document.getElementById('checkInDate');
    var checkOut = document.getElementById('checkOutDate');
    var guestsSelect = document.getElementById('guestsSelect');

    function loadRooms() {
        var branchId = branchSelect ? branchSelect.value : '';
        var typeId = roomTypeSelect ? roomTypeSelect.value : '';

        if (!branchId || !typeId) {
            if (roomSelect) {
                roomSelect.innerHTML = '<option value="">Select branch and type first</option>';
            }
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo BASE_URL; ?>modules/booking/get-rooms.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (roomSelect) {
                        var html = '<option value="">Select a room</option>';
                        if (data.rooms && data.rooms.length) {
                            data.rooms.forEach(function(room) {
                                html += '<option value="' + room.id + '" data-price="' + room.price_per_night + '"';
                                if (room.id == '<?php echo $selected_room_id; ?>') html += ' selected';
                                html += '>Room ' + room.room_number + ' - ' + room.type_name + ' (' + room.status + ')</option>';
                            });
                        }
                        roomSelect.innerHTML = html;
                    }
                } catch(e) {}
            }
        };
        xhr.send('branch_id=' + encodeURIComponent(branchId) + '&room_type_id=' + encodeURIComponent(typeId));
    }

    if (branchSelect) branchSelect.addEventListener('change', loadRooms);
    if (roomTypeSelect) roomTypeSelect.addEventListener('change', loadRooms);

    // Auto-load if coming from suites page
    <?php if ($selected_room_id || $selected_room_type): ?>
    if (branchSelect && branchSelect.value) loadRooms();
    <?php endif; ?>

    // Sync dates
    if (checkIn && checkOut) {
        checkIn.addEventListener('change', function() {
            var minOut = new Date(this.value);
            minOut.setDate(minOut.getDate() + 1);
            checkOut.min = minOut.toISOString().split('T')[0];
            if (checkOut.value && checkOut.value <= this.value) {
                checkOut.value = '';
            }
            updateSummary();
        });
        checkOut.addEventListener('change', updateSummary);
    }

    // Update summary on room/guests change
    if (roomSelect) roomSelect.addEventListener('change', updateSummary);
    if (guestsSelect) guestsSelect.addEventListener('change', updateSummary);
    if (roomTypeSelect) roomTypeSelect.addEventListener('change', updateSummary);

    // Payment option selection
    var payOnline = document.getElementById('payOnline');
    var payReception = document.getElementById('payAtReception');
    if (payOnline) {
        payOnline.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(function(el) { el.classList.remove('selected'); });
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    }
    if (payReception) {
        payReception.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(function(el) { el.classList.remove('selected'); });
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    }

    function updateSummary() {
        var roomTypeEl = roomTypeSelect ? roomTypeSelect.options[roomTypeSelect.selectedIndex] : null;
        var roomEl = roomSelect ? roomSelect.options[roomSelect.selectedIndex] : null;

        var roomTypeName = roomTypeEl ? roomTypeEl.text.split(' - ')[0] : 'Not selected';
        var pricePerNight = roomEl ? parseFloat(roomEl.getAttribute('data-price') || 0) : 0;
        var checkInVal = checkIn ? checkIn.value : '';
        var checkOutVal = checkOut ? checkOut.value : '';
        var guestsVal = guestsSelect ? guestsSelect.value : 0;

        // Calculate nights
        var nights = 0;
        if (checkInVal && checkOutVal) {
            var d1 = new Date(checkInVal);
            var d2 = new Date(checkOutVal);
            var diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24));
            nights = diff > 0 ? diff : 0;
        }

        var subtotal = pricePerNight * nights;

        document.getElementById('summaryRoomType').textContent = roomTypeName;
        document.getElementById('summaryCheckIn').textContent = checkInVal || '--';
        document.getElementById('summaryCheckOut').textContent = checkOutVal || '--';
        document.getElementById('summaryNights').textContent = nights;
        document.getElementById('summaryGuests').textContent = guestsVal;
        document.getElementById('summaryPricePerNight').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + pricePerNight.toLocaleString();
        document.getElementById('summarySubtotal').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + subtotal.toLocaleString();
        document.getElementById('summaryTotal').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + subtotal.toLocaleString();
    }

    // Initial update
    updateSummary();

    // Coupon apply
    var applyBtn = document.getElementById('applyCoupon');
    var couponInput = document.getElementById('couponCode');
    var couponMsg = document.getElementById('couponMessage');

    if (applyBtn && couponInput) {
        applyBtn.addEventListener('click', function() {
            var code = couponInput.value.trim();
            if (!code) {
                couponMsg.innerHTML = '<span style="color:#dc3545;">Please enter a coupon code.</span>';
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo BASE_URL; ?>modules/booking/validate-coupon.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            couponMsg.innerHTML = '<span style="color:#28a745;"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
                            var subtotal = parseFloat(document.getElementById('summarySubtotal').textContent.replace(/[^0-9.]/g, '')) || 0;
                            var discount = 0;
                            if (data.discount_type === 'percentage') {
                                discount = subtotal * (data.discount_value / 100);
                            } else {
                                discount = data.discount_value;
                            }
                            var total = subtotal - discount;
                            document.getElementById('discountRow').style.display = 'flex';
                            document.getElementById('discountLabel').textContent = data.discount_type === 'percentage' ? data.discount_value + '%' : 'Fixed';
                            document.getElementById('discountAmount').textContent = discount.toLocaleString();
                            document.getElementById('summaryTotal').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + Math.max(0, total).toLocaleString();
                        } else {
                            couponMsg.innerHTML = '<span style="color:#dc3545;">' + data.message + '</span>';
                        }
                    } catch(e) {
                        couponMsg.innerHTML = '<span style="color:#dc3545;">Error validating coupon.</span>';
                    }
                }
            };
            xhr.send('code=' + encodeURIComponent(code) + '&amount=' + encodeURIComponent(document.getElementById('summarySubtotal').textContent.replace(/[^0-9.]/g, '')));
        });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
