<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();

    $coupon_id  = (int) ($_POST['coupon_id'] ?? 0);
    $booking_id = !empty($_POST['booking_id']) ? (int) $_POST['booking_id'] : null;
    $order_id   = !empty($_POST['order_id']) ? (int) $_POST['order_id'] : null;
    $amount     = (float) ($_POST['amount'] ?? 0);

    if ($coupon_id <= 0 || $amount <= 0) {
        jsonResponse(['success' => false, 'message' => 'Coupon ID and amount are required']);
    }

    if (!$booking_id && !$order_id) {
        jsonResponse(['success' => false, 'message' => 'Booking ID or Order ID is required']);
    }

    $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ? AND status = 'active'");
    $stmt->execute([$coupon_id]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        jsonResponse(['success' => false, 'message' => 'Coupon not found or inactive']);
    }

    $now = date('Y-m-d');
    if ($now < $coupon['start_date'] || $now > $coupon['end_date']) {
        jsonResponse(['success' => false, 'message' => 'Coupon is not valid at this time']);
    }

    if ($coupon['discount_type'] === 'percentage') {
        $discount_amount = $amount * ((float)$coupon['discount_value'] / 100);
    } else {
        $discount_amount = (float)$coupon['discount_value'];
    }

    $discount_amount = min($discount_amount, $amount);
    $payable_amount  = $amount - $discount_amount;

    jsonResponse([
        'success'         => true,
        'message'         => 'Coupon applied successfully',
        'coupon_code'     => $coupon['code'],
        'discount_type'   => $coupon['discount_type'],
        'discount_value'  => (float) $coupon['discount_value'],
        'original_amount' => $amount,
        'discount_amount' => round($discount_amount, 2),
        'payable_amount'  => round($payable_amount, 2),
    ]);
} catch (PDOException $e) {
    error_log('Coupon apply error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Coupon apply error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
