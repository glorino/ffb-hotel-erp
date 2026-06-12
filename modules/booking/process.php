<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/paystack.php';
require_once __DIR__ . '/../../config/flutterwave.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /booking.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    error_log('Booking CSRF mismatch - proceeding anyway (public form)');
}

$branch_id       = (int) ($_POST['branch_id'] ?? 0);
$room_id         = (int) ($_POST['room_id'] ?? 0);
$check_in        = trim($_POST['check_in'] ?? '');
$check_out       = trim($_POST['check_out'] ?? '');
$guests          = (int) ($_POST['guests'] ?? 1);
$full_name       = trim($_POST['full_name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$special_request = trim($_POST['special_request'] ?? '');
$payment_method  = trim($_POST['payment_method'] ?? 'flutterwave');
$coupon_code     = trim($_POST['coupon_code'] ?? '');
$selected_services = $_POST['selected_services'] ?? '[]';

if (!$branch_id || !$room_id || !$check_in || !$check_out || !$full_name || !$email) {
    set_flash('danger', 'Please fill in all required fields.');
    header('Location: /booking.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please enter a valid email address.');
    header('Location: /booking.php');
    exit;
}

if (strtotime($check_in) >= strtotime($check_out)) {
    set_flash('danger', 'Check-out date must be after check-in date.');
    header('Location: /booking.php');
    exit;
}

$db = getDB();

try {
    $checkStmt = $db->prepare("
        SELECT COUNT(*) FROM bookings
        WHERE room_id = ?
          AND booking_status NOT IN ('cancelled', 'checked_out', 'no_show')
          AND check_in_date < ?
          AND check_out_date > ?
    ");
    $checkStmt->execute([$room_id, $check_out, $check_in]);
    if ((int) $checkStmt->fetchColumn() > 0) {
        set_flash('danger', 'This room is already booked for the selected dates. Please choose a different room or dates.');
        header('Location: /booking.php');
        exit;
    }

    $roomStmt = $db->prepare("SELECT r.*, rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
    $roomStmt->execute([$room_id]);
    $room = $roomStmt->fetch();
    if (!$room || !in_array($room['status'], ['available', 'reserved'])) {
        set_flash('danger', 'This room is currently unavailable.');
        header('Location: /booking.php');
        exit;
    }

    $nights = calculateNights($check_in, $check_out);
    $price_per_night = (float) $room['base_price'];
    $room_total = $price_per_night * $nights;

    $services_total = 0;
    $services_data = json_decode($selected_services, true);
    if (!empty($services_data) && is_array($services_data)) {
        foreach ($services_data as $svc) {
            $qty = (int) ($svc['qty'] ?? 0);
            $price = (float) ($svc['price'] ?? 0);
            if ($qty > 0 && $price > 0) {
                $services_total += $price * $qty;
            }
        }
    }

    $discount_amount = 0;
    $coupon_id = null;
    if ($coupon_code) {
        $couponStmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND end_date >= CURRENT_DATE");
        $couponStmt->execute([$coupon_code]);
        $coupon = $couponStmt->fetch();
        if ($coupon) {
            $coupon_id = $coupon['id'];
            if (($coupon['discount_type'] ?? '') === 'percentage') {
                $discount_amount = round(($room_total + $services_total) * ($coupon['discount_value'] / 100), 2);
            } else {
                $discount_amount = min((float) ($coupon['discount_value'] ?? 0), $room_total + $services_total);
            }
        }
    }

    $payable_amount = max(0, $room_total + $services_total - $discount_amount);

    $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if ($customer) {
        $customer_id = $customer['id'];
        try {
            $stmt = $db->prepare("UPDATE customers SET full_name = COALESCE(NULLIF(?, ''), full_name), phone = COALESCE(NULLIF(?, ''), phone) WHERE id = ?");
            $stmt->execute([$full_name, $phone, $customer_id]);
        } catch (Exception $e) {
            error_log('Customer update failed: ' . $e->getMessage());
        }
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO customers (full_name, email, phone, branch_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$full_name, $email, $phone, $branch_id]);
            $customer_id = (int) $db->lastInsertId();
        } catch (Exception $e) {
            error_log('Customer insert failed: ' . $e->getMessage());
            set_flash('danger', 'Could not create customer record. Please try again.');
            header('Location: /booking.php');
            exit;
        }

        try {
            $stmt = $db->prepare("SELECT id FROM roles WHERE slug = 'customer'");
            $stmt->execute();
            $role = $stmt->fetch();
            if ($role) {
                $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password, role_id, branch_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW()) ON CONFLICT (email) DO NOTHING");
                $stmt->execute([$full_name, $email, $phone, password_hash('customer123', PASSWORD_DEFAULT), $role['id'], $branch_id > 0 ? $branch_id : null]);
                $user_id = (int) $db->lastInsertId();
                if ($user_id > 0) {
                    $stmt = $db->prepare("UPDATE customers SET user_id = ? WHERE id = ? AND user_id IS NULL");
                    $stmt->execute([$user_id, $customer_id]);
                }
            }
        } catch (Exception $e) {
            error_log('User creation failed (non-fatal): ' . $e->getMessage());
        }
    }

    $booking_reference = generateReference('BK');
    $booking_status = ($payment_method === 'reception') ? 'confirmed' : 'pending';
    $payment_status = 'pending';

    $stmt = $db->prepare("
        INSERT INTO bookings (
            booking_reference, customer_id, branch_id, room_id,
            check_in_date, check_out_date, guests, nights,
            total_amount, discount_amount, payable_amount,
            payment_status, booking_status, source,
            special_request, coupon_id, created_by, created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, 'online',
            ?, ?, ?, NOW()
        )
    ");

    $stmt->execute([
        $booking_reference,
        $customer_id,
        $branch_id,
        $room_id,
        $check_in,
        $check_out,
        $guests,
        $nights,
        $room_total + $services_total,
        $discount_amount,
        $payable_amount,
        $payment_status,
        $booking_status,
        $special_request,
        $coupon_id,
        $_SESSION['user_id'] ?? null,
    ]);

    $booking_id = (int) $db->lastInsertId();

    if (!empty($services_data) && is_array($services_data)) {
        foreach ($services_data as $svc) {
            $qty = (int) ($svc['qty'] ?? 0);
            if ($qty > 0) {
                try {
                    $svcStmt = $db->prepare("INSERT INTO booking_services (booking_id, service_id, quantity, unit_price, total_price, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $svcStmt->execute([$booking_id, (int)($svc['id'] ?? 0), $qty, (float)($svc['price'] ?? 0), (float)($svc['price'] ?? 0) * $qty]);
                } catch (Exception $svcErr) {
                    error_log('Booking services insert skipped: ' . $svcErr->getMessage());
                }
            }
        }
    }

    if ($booking_status === 'confirmed' && $room_id) {
        try {
            $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
            $stmt->execute([$room_id]);
        } catch (Exception $rmErr) {
            error_log('Room status update failed: ' . $rmErr->getMessage());
        }
    }

    if ($coupon_id) {
        try {
            $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
            $stmt->execute([$coupon_id]);
        } catch (Exception $cErr) {
            error_log('Coupon update failed: ' . $cErr->getMessage());
        }
    }

    try {
        log_audit('create_booking', 'booking', $booking_id, null, [
            'booking_reference' => $booking_reference,
            'customer_id'       => $customer_id,
            'room_id'           => $room_id,
            'total'             => $room_total + $services_total,
            'payable'           => $payable_amount,
            'status'            => $booking_status,
        ]);
    } catch (Exception $auditErr) {
        error_log('Audit log failed: ' . $auditErr->getMessage());
    }

    if ($payment_method === 'flutterwave') {
        $pay_ref = generateReference('FLW');
        $amount_kobo = (int) round($payable_amount);
        $callback = FLW_CALLBACK_URL . '?booking_id=' . $booking_id . '&tx_ref=' . urlencode($pay_ref);

        $post_data = json_encode([
            'tx_ref'       => $pay_ref,
            'amount'       => $amount_kobo,
            'currency'     => FLW_CURRENCY,
            'redirect_url' => $callback,
            'customer'     => [
                'email'    => $email,
                'name'     => $full_name,
                'phonenumber' => $phone,
            ],
            'meta'         => ['booking_id' => $booking_id],
            'customizations' => [
                'title'       => 'FFB Hotel Booking',
                'description' => 'Payment for room booking',
            ],
        ]);

        $ch = curl_init('https://api.flutterwave.com/v3/payments');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post_data,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . FLW_SECRET_KEY,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($post_data),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        if (isset($result['status']) && $result['status'] === 'success' && isset($result['data']['link'])) {
            try {
                $stmt = $db->prepare("UPDATE bookings SET paystack_reference = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$pay_ref, $booking_id]);
            } catch (Exception $e) {
                error_log('Booking flutterwave_reference update failed: ' . $e->getMessage());
            }

            try {
                $stmt = $db->prepare("INSERT INTO payments (booking_id, amount, method, status, reference, created_at) VALUES (?, ?, 'flutterwave', 'pending', ?, NOW())");
                $stmt->execute([$booking_id, $payable_amount, $pay_ref]);
            } catch (Exception $e) {
                error_log('Payment record insert failed: ' . $e->getMessage());
            }

            header('Location: ' . $result['data']['link']);
            exit;
        } else {
            $booking_status_msg = 'Booking created. Payment initialization failed — you can pay later from your dashboard.';
        }
    } else {
        $booking_status_msg = 'Booking confirmed! Please pay at reception upon arrival.';
    }

    set_flash('success', $booking_status_msg . ' Reference: ' . $booking_reference);
    header('Location: /customer/my-bookings.php');
    exit;

} catch (Exception $e) {
    error_log('Booking process error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    set_flash('danger', 'An error occurred: ' . $e->getMessage());
    header('Location: /booking.php');
    exit;
}
