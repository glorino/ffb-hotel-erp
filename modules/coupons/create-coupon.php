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

    $code            = strtoupper(trim($_POST['code'] ?? ''));
    $title           = trim($_POST['title'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $discount_type   = $_POST['discount_type'] ?? 'percentage';
    $discount_value  = (float) ($_POST['discount_value'] ?? 0);
    $start_date      = $_POST['start_date'] ?? '';
    $end_date        = $_POST['end_date'] ?? '';
    $branch_id       = !empty($_POST['branch_id']) ? (int) $_POST['branch_id'] : null;
    $applicable_to   = $_POST['applicable_to'] ?? 'all';
    $minimum_spend   = (float) ($_POST['minimum_spend'] ?? 0);
    $usage_limit     = (int) ($_POST['usage_limit'] ?? 0);
    $usage_per_customer = (int) ($_POST['usage_per_customer'] ?? 0);

    if (empty($code) || empty($title) || empty($start_date) || empty($end_date)) {
        jsonResponse(['success' => false, 'message' => 'Code, title, start date, and end date are required']);
    }

    if (!in_array($discount_type, ['percentage', 'fixed'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid discount type']);
    }

    if ($discount_value <= 0) {
        jsonResponse(['success' => false, 'message' => 'Discount value must be greater than zero']);
    }

    if ($discount_type === 'percentage' && $discount_value > 100) {
        jsonResponse(['success' => false, 'message' => 'Percentage discount cannot exceed 100%']);
    }

    if (!in_array($applicable_to, ['rooms', 'food', 'services', 'all'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid applicable_to value']);
    }

    $check = $db->prepare("SELECT id FROM coupons WHERE code = ?");
    $check->execute([$code]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Coupon code already exists']);
    }

    $stmt = $db->prepare("INSERT INTO coupons (code, title, description, discount_type, discount_value, start_date, end_date, branch_id, applicable_to, minimum_spend, usage_limit, usage_per_customer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$code, $title, $description, $discount_type, $discount_value, $start_date, $end_date, $branch_id, $applicable_to, $minimum_spend, $usage_limit, $usage_per_customer]);

    $couponId = $db->lastInsertId();

    log_audit('create', 'coupon', $couponId, null, [
        'code'           => $code,
        'discount_type'  => $discount_type,
        'discount_value' => $discount_value,
        'branch_id'      => $branch_id,
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Coupon created successfully',
        'coupon_id' => $couponId
    ]);
} catch (PDOException $e) {
    error_log('Coupon create error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Coupon create error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
