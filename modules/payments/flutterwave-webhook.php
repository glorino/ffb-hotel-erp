<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/flutterwave.php';
require_once __DIR__ . '/../../includes/functions.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['event'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook payload']);
    exit;
}

if ($data['event'] !== 'charge.completed') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
    exit;
}

$payload = $data['data'] ?? [];
$tx_ref = $payload['tx_ref'] ?? '';
$flw_id = $payload['id'] ?? '';
$status = $payload['status'] ?? '';

if ($status !== 'successful') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored_non_successful']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    $flw_amount = (float) ($payload['amount'] ?? 0);
    $flw_reference = $tx_ref;

    $stmt = $db->prepare("UPDATE payments SET status = 'paid', verified_at = COALESCE(verified_at, NOW()), gateway_response = ? WHERE reference = ? AND status = 'pending'");
    $stmt->execute([json_encode($payload), $flw_reference]);

    if ($stmt->rowCount() === 0) {
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        $db->rollBack();
        exit;
    }

    $stmt = $db->prepare("SELECT booking_id, order_id FROM payments WHERE reference = ? LIMIT 1");
    $stmt->execute([$flw_reference]);
    $payment = $stmt->fetch();
    $booking_id = (int) ($payment['booking_id'] ?? 0);
    $order_id = (int) ($payment['order_id'] ?? 0);

    if ($booking_id > 0) {
        $stmt = $db->prepare("UPDATE bookings SET payment_status = 'paid', booking_status = CASE WHEN booking_status = 'pending' THEN 'confirmed' ELSE booking_status END, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$booking_id]);

        $stmt = $db->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $bk = $stmt->fetch();
        if (!empty($bk['room_id'])) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
            $stmt->execute([$bk['room_id']]);
        }

        log_audit('payment_webhook', 'booking', $booking_id, null, ['status' => 'paid', 'amount' => $flw_amount]);
    }

    if ($order_id > 0) {
        $stmt = $db->prepare("UPDATE food_orders SET payment_status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
        log_audit('payment_webhook', 'order', $order_id, null, ['status' => 'paid', 'amount' => $flw_amount]);
    }

    $db->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Flutterwave webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
