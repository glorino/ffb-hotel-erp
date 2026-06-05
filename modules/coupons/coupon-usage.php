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

    $coupon_id       = (int) ($_POST['coupon_id'] ?? 0);
    $customer_id     = (int) ($_POST['customer_id'] ?? 0);
    $booking_id      = !empty($_POST['booking_id']) ? (int) $_POST['booking_id'] : null;
    $order_id        = !empty($_POST['order_id']) ? (int) $_POST['order_id'] : null;
    $discount_amount = (float) ($_POST['discount_amount'] ?? 0);

    if ($coupon_id <= 0 || $customer_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Coupon ID and Customer ID are required']);
    }

    if (!$booking_id && !$order_id) {
        jsonResponse(['success' => false, 'message' => 'Booking ID or Order ID is required']);
    }

    $check = $db->prepare("SELECT id, usage_limit FROM coupons WHERE id = ?");
    $check->execute([$coupon_id]);
    $coupon = $check->fetch();

    if (!$coupon) {
        jsonResponse(['success' => false, 'message' => 'Coupon not found']);
    }

    $usageStmt = $db->prepare("SELECT COUNT(*) as total FROM coupon_usages WHERE coupon_id = ?");
    $usageStmt->execute([$coupon_id]);
    $totalUsage = (int) $usageStmt->fetch()['total'];

    if ((int)$coupon['usage_limit'] > 0 && $totalUsage >= (int)$coupon['usage_limit']) {
        jsonResponse(['success' => false, 'message' => 'Coupon usage limit has been reached']);
    }

    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO coupon_usages (coupon_id, customer_id, booking_id, order_id, discount_amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$coupon_id, $customer_id, $booking_id, $order_id, $discount_amount]);

    $usageId = $db->lastInsertId();

    $db->commit();

    log_audit('coupon_usage', 'coupon_usage', $usageId, null, [
        'coupon_id'       => $coupon_id,
        'customer_id'     => $customer_id,
        'discount_amount' => $discount_amount,
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Coupon usage recorded',
        'usage_id' => $usageId
    ]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Coupon usage error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Coupon usage error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
