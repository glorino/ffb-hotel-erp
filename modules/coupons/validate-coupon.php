<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['valid' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();

    $code           = strtoupper(trim($_POST['code'] ?? ''));
    $customer_id    = (int) ($_POST['customer_id'] ?? 0);
    $branch_id      = !empty($_POST['branch_id']) ? (int) $_POST['branch_id'] : null;
    $applicable_to  = $_POST['applicable_to'] ?? 'all';
    $order_amount   = (float) ($_POST['order_amount'] ?? 0);

    if (empty($code)) {
        jsonResponse(['valid' => false, 'message' => 'Coupon code is required']);
    }

    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ?");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        jsonResponse(['valid' => false, 'message' => 'Invalid coupon code']);
    }

    if ($coupon['status'] !== 'active') {
        jsonResponse(['valid' => false, 'message' => 'This coupon is not active']);
    }

    $now = date('Y-m-d');
    if ($now < $coupon['start_date']) {
        jsonResponse(['valid' => false, 'message' => 'This coupon is not yet valid']);
    }
    if ($now > $coupon['end_date']) {
        jsonResponse(['valid' => false, 'message' => 'This coupon has expired']);
    }

    if ($branch_id && $coupon['branch_id'] !== null && (int)$coupon['branch_id'] !== $branch_id) {
        jsonResponse(['valid' => false, 'message' => 'This coupon is not valid at this branch']);
    }

    if ($applicable_to !== 'all' && $coupon['applicable_to'] !== 'all' && $coupon['applicable_to'] !== $applicable_to) {
        jsonResponse(['valid' => false, 'message' => 'This coupon is not applicable to this service']);
    }

    if ($order_amount > 0 && $coupon['minimum_spend'] > 0 && $order_amount < (float)$coupon['minimum_spend']) {
        $min = CURRENCY_SYMBOL . number_format($coupon['minimum_spend'], 2);
        jsonResponse(['valid' => false, 'message' => "Minimum spend of {$min} required for this coupon"]);
    }

    if ((int)$coupon['usage_limit'] > 0) {
        $usageStmt = $db->prepare("SELECT COUNT(*) as total FROM coupon_usages WHERE coupon_id = ?");
        $usageStmt->execute([$coupon['id']]);
        $usageCount = (int) $usageStmt->fetch()['total'];
        if ($usageCount >= (int)$coupon['usage_limit']) {
            jsonResponse(['valid' => false, 'message' => 'This coupon has reached its usage limit']);
        }
    }

    if ((int)$coupon['usage_per_customer'] > 0 && $customer_id > 0) {
        $custStmt = $db->prepare("SELECT COUNT(*) as total FROM coupon_usages WHERE coupon_id = ? AND customer_id = ?");
        $custStmt->execute([$coupon['id'], $customer_id]);
        $custCount = (int) $custStmt->fetch()['total'];
        if ($custCount >= (int)$coupon['usage_per_customer']) {
            jsonResponse(['valid' => false, 'message' => 'You have reached the maximum usage for this coupon']);
        }
    }

    jsonResponse([
        'valid'  => true,
        'coupon' => [
            'id'             => (int) $coupon['id'],
            'code'           => $coupon['code'],
            'discount_type'  => $coupon['discount_type'],
            'discount_value' => (float) $coupon['discount_value'],
            'title'          => $coupon['title'],
        ]
    ]);
} catch (PDOException $e) {
    error_log('Coupon validate error: ' . $e->getMessage());
    jsonResponse(['valid' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Coupon validate error: ' . $e->getMessage());
    jsonResponse(['valid' => false, 'message' => 'An unexpected error occurred'], 500);
}
