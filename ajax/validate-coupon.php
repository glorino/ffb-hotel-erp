<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$code = $_POST['code'] ?? $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['valid' => false, 'message' => 'Please enter a coupon code']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND (valid_from IS NULL OR valid_from <= CURRENT_DATE) AND (valid_to IS NULL OR valid_to >= CURRENT_DATE)");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Invalid or expired coupon code']);
        exit;
    }

    if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
        echo json_encode(['valid' => false, 'message' => 'Coupon usage limit has been reached']);
        exit;
    }

    $discount_text = $coupon['discount_type'] === 'percentage' ? $coupon['discount_value'] . '%' : CURRENCY_SYMBOL . number_format($coupon['discount_value'], 2);
    echo json_encode([
        'valid' => true,
        'message' => "Coupon applied! You save {$discount_text}",
        'coupon' => [
            'code' => $coupon['code'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => (float)$coupon['discount_value']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['valid' => false, 'message' => 'Error validating coupon']);
}
