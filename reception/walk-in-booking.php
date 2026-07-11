<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Walk-in Booking';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 7) $step = 1;

$check_in = $_GET['check_in'] ?? $_POST['check_in'] ?? date('Y-m-d');
$check_out = $_GET['check_out'] ?? $_POST['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$room_type_id = $_POST['room_type_id'] ?? $_GET['room_type_id'] ?? '';
$room_id = $_GET['room_id'] ?? $_POST['room_id'] ?? '';
$nights = $_GET['nights'] ?? $_POST['nights'] ?? 1;
$guest_first_name = $_POST['guest_first_name'] ?? '';
$guest_last_name = $_POST['guest_last_name'] ?? '';
$guest_email = $_POST['guest_email'] ?? '';
$guest_phone = $_POST['guest_phone'] ?? '';
$guest_id_type = $_POST['guest_id_type'] ?? '';
$guest_id_number = $_POST['guest_id_number'] ?? '';
$coupon_code = $_POST['coupon_code'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';
$payment_amount = $_POST['payment_amount'] ?? '';

$total_amount = 0;
$discount = 0;
$room_price = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT id, full_name FROM customers WHERE (email = ? OR phone = ?) AND branch_id = ? LIMIT 1");
        $stmt->execute([$guest_email, $guest_phone, $branch_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $customer_id = $existing['id'];
            $stmt = $db->prepare("UPDATE customers SET full_name = ?, email = ?, phone = ?, id_type = ?, id_number = ? WHERE id = ? AND branch_id = ?");
            $stmt->execute([$guest_first_name . ' ' . $guest_last_name, $guest_email, $guest_phone, $guest_id_type, $guest_id_number, $customer_id, $branch_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO customers (full_name, email, phone, id_type, id_number, branch_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$guest_first_name . ' ' . $guest_last_name, $guest_email, $guest_phone, $guest_id_type, $guest_id_number, $branch_id]);
            $customer_id = $db->lastInsertId();
        }

        $stmt = $db->prepare("SELECT base_price FROM rooms WHERE id = ? AND branch_id = ?");
        $stmt->execute([$room_id, $branch_id]);
        $room_price = (float)$stmt->fetchColumn();

        $total_amount = $room_price * $nights;
        $discount = 0;

        if (!empty($coupon_code)) {
            $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND valid_from <= NOW() AND valid_to >= NOW() AND (max_uses = 0 OR used_count < max_uses) AND (branch_id = ? OR branch_id IS NULL)");
            $stmt->execute([$coupon_code, $branch_id]);
            $coupon = $stmt->fetch();
            if ($coupon) {
                if ($coupon['discount_type'] === 'percentage') {
                    $discount = $total_amount * ($coupon['discount_value'] / 100);
                } else {
                    $discount = min($coupon['discount_value'], $total_amount);
                }
                $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                $stmt->execute([$coupon['id']]);
            }
        }

        $final_amount = max(0, $total_amount - $discount);
        $reference = generateReference('WKB');

        $stmt = $db->prepare("INSERT INTO bookings (branch_id, customer_id, room_id, booking_reference, source, check_in_date, check_out_date, total_amount, discount_amount, payable_amount, booking_status, created_at) VALUES (?, ?, ?, ?, 'walk_in', ?, ?, ?, ?, ?, 'confirmed', NOW())");
        $stmt->execute([$branch_id, $customer_id, $room_id, $reference, $check_in, $check_out, $total_amount, $discount, $final_amount]);
        $booking_id = $db->lastInsertId();

        $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND branch_id = ?");
        $stmt->execute([$room_id, $branch_id]);

        if ($payment_method && $payment_amount > 0) {
            $pay_ref = generateReference('PAY');
            $stmt = $db->prepare("INSERT INTO payments (branch_id, booking_id, customer_id, payment_reference, amount, payment_method, payment_category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'room', 'paid', NOW())");
            $stmt->execute([$branch_id, $booking_id, $customer_id, $pay_ref, $payment_amount, $payment_method]);
        }

        $receipt_no = generateReceiptNumber();
        $stmt = $db->prepare("INSERT INTO receipts (booking_id, receipt_number, amount, generated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$booking_id, $receipt_no, $final_amount]);

        log_audit('walk_in_booking', 'booking', $booking_id, null, ['reference' => $reference, 'customer' => $guest_first_name . ' ' . $guest_last_name]);

        $db->commit();

        set_flash('success', "Booking confirmed! Reference: {$reference}. Receipt: {$receipt_no}");
        header("Location: receipts.php?ref={$receipt_no}");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        set_flash('danger', 'Booking failed: ' . $e->getMessage());
        header('Location: walk-in-booking.php?step=7');
        exit;
    }
}

if ($room_id) {
    $stmt = $db->prepare("SELECT r.*, rt.name as type_name, rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ? AND r.branch_id = ?");
    $stmt->execute([$room_id, $branch_id]);
    $room = $stmt->fetch();
    if ($room) {
        $room_price = (float)$room['base_price'];
        $total_amount = $room_price * max(1, (int)$nights);
    }
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Walk-in Booking</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between steps-indicator">
                <?php $steps = ['Branch & Dates', 'Select Room', 'Guest Details', 'Coupon', 'Payment Method', 'Payment', 'Review']; ?>
                <?php foreach ($steps as $i => $s): ?>
                <div class="step-item text-center flex-fill <?php echo $step > $i + 1 ? 'completed' : ($step == $i + 1 ? 'active' : ''); ?>">
                    <div class="step-circle d-inline-flex align-items-center justify-content-center rounded-circle bg-<?php echo $step > $i + 1 ? 'success' : ($step == $i + 1 ? 'primary' : 'secondary'); ?> text-white" style="width:36px;height:36px;">
                        <?php echo $step > $i + 1 ? '<i class="bi bi-check"></i>' : $i + 1; ?>
                    </div>
                    <small class="d-block mt-1"><?php echo $s; ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($step === 1): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Step 1: Select Branch, Dates & Room Type</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="step" value="2">
                <div class="col-md-6">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select branch</option>
                        <?php foreach (getBranches() as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo $b['id'] == $branch_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Check In</label>
                    <input type="date" name="check_in" class="form-control" value="<?php echo $check_in; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Check Out</label>
                    <input type="date" name="check_out" class="form-control" value="<?php echo $check_out; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Room Type</label>
                    <select name="room_type_id" class="form-select">
                        <option value="">Any Room Type</option>
                        <?php foreach (getRoomTypes() as $rt): ?>
                        <option value="<?php echo $rt['id']; ?>"><?php echo htmlspecialchars($rt['name']); ?> — <?php echo formatMoney($rt['base_price']); ?>/night</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-2"></i>Check Availability</button>
                </div>
            </form>
        </div>
    </div>

    <?php elseif ($step === 2): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Step 2: Select Available Room</h5></div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-auto"><span class="badge bg-success px-3 py-2">Available</span></div>
                <div class="col-auto"><span class="badge bg-danger px-3 py-2">Unavailable</span></div>
            </div>
            <?php
            $booked_ids = [];
            $stmt = $db->prepare("SELECT DISTINCT room_id FROM bookings WHERE branch_id = ? AND booking_status IN ('confirmed','checked_in','pending') AND ((check_in_date <= ? AND check_out_date > ?) OR (check_in_date < ? AND check_out_date >= ?))");
            $stmt->execute([$branch_id, $check_out, $check_in, $check_out, $check_in]);
            while ($row = $stmt->fetch()) $booked_ids[] = $row['room_id'];

            $room_sql = "SELECT r.*, rt.name as type_name, rt.base_price, rt.max_guests FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.branch_id = ? AND r.status NOT IN ('maintenance','out_of_service')";
            $room_params = [$branch_id];
            if ($room_type_id) { $room_sql .= " AND r.room_type_id = ?"; $room_params[] = $room_type_id; }
            $room_sql .= " ORDER BY r.floor, r.room_number";
            $rooms = $db->prepare($room_sql);
            $rooms->execute($room_params);
            $all_rooms = $rooms->fetchAll();
            ?>
            <div class="row g-2">
                <?php foreach ($all_rooms as $r):
                    $is_booked = in_array($r['id'], $booked_ids) || $r['status'] === 'occupied';
                ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="card <?php echo $is_booked ? 'bg-light' : 'bg-success text-white'; ?> border-0 room-select-card" style="cursor:<?php echo $is_booked ? 'not-allowed' : 'pointer'; ?>;" onclick="<?php echo $is_booked ? '' : "window.location.href='?step=3&check_in={$check_in}&check_out={$check_out}&room_id={$r['id']}&nights={$nights}'"; ?>">
                        <div class="card-body text-center py-3">
                            <h4 class="mb-0"><?php echo htmlspecialchars($r['room_number']); ?></h4>
                            <small><?php echo htmlspecialchars($r['type_name']); ?> (Floor <?php echo $r['floor']; ?>)</small>
                            <div class="mt-2"><strong><?php echo formatMoney($r['base_price']); ?></strong><small>/night</small></div>
                            <?php if ($is_booked): ?>
                            <span class="badge bg-danger mt-2">Unavailable</span>
                            <?php else: ?>
                            <span class="badge bg-white text-success mt-2">Available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php elseif ($step === 3): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Step 3: Guest Details</h5></div>
        <div class="card-body">
            <form method="POST" action="?step=4" class="row g-3">
                <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <input type="hidden" name="nights" value="<?php echo $nights; ?>">
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="guest_first_name" class="form-control" value="<?php echo htmlspecialchars($guest_first_name); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="guest_last_name" class="form-control" value="<?php echo htmlspecialchars($guest_last_name); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="guest_email" class="form-control" value="<?php echo htmlspecialchars($guest_email); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="guest_phone" class="form-control" value="<?php echo htmlspecialchars($guest_phone); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ID Type</label>
                    <select name="guest_id_type" class="form-select">
                        <option value="">Select</option>
                        <option value="national_id" <?php echo $guest_id_type === 'national_id' ? 'selected' : ''; ?>>National ID</option>
                        <option value="passport" <?php echo $guest_id_type === 'passport' ? 'selected' : ''; ?>>Passport</option>
                        <option value="drivers_license" <?php echo $guest_id_type === 'drivers_license' ? 'selected' : ''; ?>>Driver's License</option>
                        <option value="voters_card" <?php echo $guest_id_type === 'voters_card' ? 'selected' : ''; ?>>Voter's Card</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ID Number</label>
                    <input type="text" name="guest_id_number" class="form-control" value="<?php echo htmlspecialchars($guest_id_number); ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-2"></i>Continue to Coupon</button>
                    <a href="?step=2" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>

    <?php elseif ($step === 4): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Step 4: Apply Coupon</h5></div>
        <div class="card-body">
            <form method="POST" action="?step=5" class="row g-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <input type="hidden" name="nights" value="<?php echo $nights; ?>">
                <input type="hidden" name="guest_first_name" value="<?php echo htmlspecialchars($guest_first_name); ?>">
                <input type="hidden" name="guest_last_name" value="<?php echo htmlspecialchars($guest_last_name); ?>">
                <input type="hidden" name="guest_email" value="<?php echo htmlspecialchars($guest_email); ?>">
                <input type="hidden" name="guest_phone" value="<?php echo htmlspecialchars($guest_phone); ?>">
                <input type="hidden" name="guest_id_type" value="<?php echo htmlspecialchars($guest_id_type); ?>">
                <input type="hidden" name="guest_id_number" value="<?php echo htmlspecialchars($guest_id_number); ?>">
                <div class="col-md-6">
                    <label class="form-label">Coupon Code</label>
                    <div class="input-group">
                        <input type="text" name="coupon_code" id="couponCode" class="form-control" placeholder="Enter coupon code" value="<?php echo htmlspecialchars($coupon_code); ?>">
                        <button type="button" class="btn btn-outline-primary" id="validateCouponBtn"><i class="bi bi-check-lg"></i> Validate</button>
                    </div>
                    <div id="couponStatus" class="mt-2"></div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-2"></i>Skip / Continue</button>
                    <a href="?step=3" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('validateCouponBtn')?.addEventListener('click', function() {
        const code = document.getElementById('couponCode').value.trim();
        const status = document.getElementById('couponStatus');
        if (!code) { status.innerHTML = '<span class="text-warning">Enter a coupon code</span>'; return; }
        fetch('../ajax/validate-coupon.php?code=' + encodeURIComponent(code))
            .then(r => r.json())
            .then(d => {
                if (d.valid) status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + d.message + '</span>';
                else status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + d.message + '</span>';
            })
            .catch(() => status.innerHTML = '<span class="text-danger">Validation error</span>');
    });
    </script>

    <?php elseif ($step === 5): ?>
    <?php
    $total_amount = $room_price * $nights;
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND valid_from <= NOW() AND valid_to >= NOW()");
    $stmt->execute([$coupon_code]);
    $coupon = $stmt->fetch();
    $discount = 0;
    if ($coupon && $coupon['max_uses'] > $coupon['used_count']) {
        if ($coupon['discount_type'] === 'percentage') $discount = $total_amount * ($coupon['discount_value'] / 100);
        else $discount = min($coupon['discount_value'], $total_amount);
    }
    $final_amount = max(0, $total_amount - $discount);
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Step 5: Select Payment Method</h5></div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Booking Summary</h6>
                        <div class="d-flex justify-content-between"><span>Room charge (<?php echo $nights; ?> nights)</span><span><?php echo formatMoney($total_amount); ?></span></div>
                        <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between text-success"><span>Discount (-<?php echo htmlspecialchars($coupon_code); ?>)</span><span>-<?php echo formatMoney($discount); ?></span></div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?php echo formatMoney($final_amount); ?></span></div>
                    </div>
                </div>
            </div>
            <form method="POST" action="?step=6" class="row g-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <input type="hidden" name="nights" value="<?php echo $nights; ?>">
                <input type="hidden" name="guest_first_name" value="<?php echo htmlspecialchars($guest_first_name); ?>">
                <input type="hidden" name="guest_last_name" value="<?php echo htmlspecialchars($guest_last_name); ?>">
                <input type="hidden" name="guest_email" value="<?php echo htmlspecialchars($guest_email); ?>">
                <input type="hidden" name="guest_phone" value="<?php echo htmlspecialchars($guest_phone); ?>">
                <input type="hidden" name="guest_id_type" value="<?php echo htmlspecialchars($guest_id_type); ?>">
                <input type="hidden" name="guest_id_number" value="<?php echo htmlspecialchars($guest_id_number); ?>">
                <input type="hidden" name="coupon_code" value="<?php echo htmlspecialchars($coupon_code); ?>">
                <input type="hidden" name="total_amount" value="<?php echo $final_amount; ?>">
                <div class="col-12">
                    <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                </div>
                <?php
                $payment_methods = [
                    'cash' => ['Cash', 'bi-cash', 'success'],
                    'pos' => ['POS Terminal', 'bi-credit-card', 'primary'],
                    'bank_transfer' => ['Bank Transfer', 'bi-bank', 'info'],
                    'split_payment' => ['Split Payment', 'bi-arrows', 'warning'],
                ];
                foreach ($payment_methods as $key => $pm):
                ?>
                <div class="col-md-3 col-6">
                    <div class="payment-method-card card border-1 text-center p-3" style="cursor:pointer;" onclick="selectPayment(this, '<?php echo $key; ?>')">
                        <div class="card-body py-3">
                            <i class="bi <?php echo $pm[1]; ?> fs-2 d-block mb-2"></i>
                            <h6 class="mb-0"><?php echo $pm[0]; ?></h6>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="">
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary" id="continuePaymentBtn" disabled><i class="bi bi-arrow-right me-2"></i>Continue</button>
                    <a href="?step=4" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function selectPayment(el, method) {
        document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('border-primary', 'bg-primary-subtle'));
        el.classList.add('border-primary', 'bg-primary-subtle');
        document.getElementById('selectedPaymentMethod').value = method;
        document.getElementById('continuePaymentBtn').disabled = false;
    }
    </script>

    <?php elseif ($step === 6): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Step 6: Record Payment</h5></div>
        <div class="card-body">
            <form method="POST" action="?step=7" class="row g-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <input type="hidden" name="nights" value="<?php echo $nights; ?>">
                <input type="hidden" name="guest_first_name" value="<?php echo htmlspecialchars($guest_first_name); ?>">
                <input type="hidden" name="guest_last_name" value="<?php echo htmlspecialchars($guest_last_name); ?>">
                <input type="hidden" name="guest_email" value="<?php echo htmlspecialchars($guest_email); ?>">
                <input type="hidden" name="guest_phone" value="<?php echo htmlspecialchars($guest_phone); ?>">
                <input type="hidden" name="guest_id_type" value="<?php echo htmlspecialchars($guest_id_type); ?>">
                <input type="hidden" name="guest_id_number" value="<?php echo htmlspecialchars($guest_id_number); ?>">
                <input type="hidden" name="coupon_code" value="<?php echo htmlspecialchars($coupon_code); ?>">
                <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($payment_method); ?>">
                <div class="col-md-6">
                    <label class="form-label">Amount to Pay (<?php echo formatMoney($final_amount ?? 0); ?> total)</label>
                    <div class="input-group">
                        <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                        <input type="number" name="payment_amount" class="form-control form-control-lg" step="0.01" min="0" max="<?php echo $final_amount ?? 0; ?>" value="<?php echo $final_amount ?? 0; ?>" required>
                    </div>
                    <small class="text-muted">Partial payment allowed. Enter the amount being collected now.</small>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-arrow-right me-2"></i>Review & Confirm</button>
                    <a href="?step=5" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>

    <?php elseif ($step === 7): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-semibold">Step 7: Review & Confirm Booking</h5></div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Guest Details</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($guest_first_name . ' ' . $guest_last_name); ?></strong></p>
                        <p class="mb-1">Email: <?php echo htmlspecialchars($guest_email ?: 'N/A'); ?></p>
                        <p class="mb-1">Phone: <?php echo htmlspecialchars($guest_phone); ?></p>
                        <p class="mb-0">ID: <?php echo htmlspecialchars($guest_id_type ?: 'N/A'); ?> — <?php echo htmlspecialchars($guest_id_number ?: 'N/A'); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Booking Details</h6>
                        <p class="mb-1">Check In: <strong><?php echo formatDate($check_in); ?></strong></p>
                        <p class="mb-1">Check Out: <strong><?php echo formatDate($check_out); ?></strong></p>
                        <p class="mb-1">Nights: <strong><?php echo $nights; ?></strong></p>
                        <p class="mb-0">Room: <strong><?php echo $room_id ? htmlspecialchars($room['room_number'] ?? '') : '—'; ?></strong></p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="bg-light rounded-3 p-3">
                        <h6>Payment Summary</h6>
                        <div class="d-flex justify-content-between"><span>Room Charges (<?php echo $nights; ?> x <?php echo formatMoney($room_price); ?>)</span><span><?php echo formatMoney($total_amount); ?></span></div>
                        <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between text-success"><span>Coupon Discount (<?php echo htmlspecialchars($coupon_code); ?>)</span><span>-<?php echo formatMoney($discount); ?></span></div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span><?php echo formatMoney(max(0, $total_amount - $discount)); ?></span></div>
                        <div class="d-flex justify-content-between mt-2"><span>Payment Method</span><span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment_method))); ?></span></div>
                        <div class="d-flex justify-content-between mt-1"><span>Amount Paid</span><span><?php echo formatMoney($payment_amount ?: 0); ?></span></div>
                        <?php $balance = max(0, ($total_amount - $discount) - ($payment_amount ?: 0)); ?>
                        <?php if ($balance > 0): ?>
                        <div class="d-flex justify-content-between text-danger"><span>Balance Due</span><span><?php echo formatMoney($balance); ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                        <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                        <input type="hidden" name="nights" value="<?php echo $nights; ?>">
                        <input type="hidden" name="guest_first_name" value="<?php echo htmlspecialchars($guest_first_name); ?>">
                        <input type="hidden" name="guest_last_name" value="<?php echo htmlspecialchars($guest_last_name); ?>">
                        <input type="hidden" name="guest_email" value="<?php echo htmlspecialchars($guest_email); ?>">
                        <input type="hidden" name="guest_phone" value="<?php echo htmlspecialchars($guest_phone); ?>">
                        <input type="hidden" name="guest_id_type" value="<?php echo htmlspecialchars($guest_id_type); ?>">
                        <input type="hidden" name="guest_id_number" value="<?php echo htmlspecialchars($guest_id_number); ?>">
                        <input type="hidden" name="coupon_code" value="<?php echo htmlspecialchars($coupon_code); ?>">
                        <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($payment_method); ?>">
                        <input type="hidden" name="payment_amount" value="<?php echo $payment_amount; ?>">
                        <button type="submit" name="confirm_booking" class="btn btn-success btn-lg px-5"><i class="bi bi-check-circle me-2"></i>Confirm Booking</button>
                        <a href="?step=6" class="btn btn-outline-secondary btn-lg">Back</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.steps-indicator .step-item { position: relative; }
.steps-indicator .step-item:not(:last-child)::after {
    content: ''; position: absolute; top: 18px; left: 50%; width: 100%; height: 2px;
    background: #dee2e6; z-index: 0;
}
.steps-indicator .step-item.completed:not(:last-child)::after { background: #198754; }
.steps-indicator .step-item.active:not(:last-child)::after { background: #0d6efd; }
.step-circle { position: relative; z-index: 1; font-weight: 600; font-size: 14px; }
.room-select-card { transition: transform .15s; }
.room-select-card:hover:not(.bg-light) { transform: scale(1.03); }
.payment-method-card { transition: all .15s; }
.payment-method-card:hover { border-color: #0d6efd; }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
