<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/flutterwave.php';
require_once __DIR__ . '/../../includes/functions.php';

$tx_ref = $_GET['tx_ref'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';
$booking_id = (int) ($_GET['booking_id'] ?? 0);
$order_id = (int) ($_GET['order_id'] ?? 0);

if (empty($tx_ref) && empty($transaction_id)) {
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=missing_reference');
    exit;
}

$db = getDB();

if (!$booking_id && !$order_id && $tx_ref) {
    $stmt = $db->prepare("SELECT booking_id, order_id FROM payments WHERE reference = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$tx_ref]);
    $lookup = $stmt->fetch();
    if ($lookup) {
        $booking_id = (int) ($lookup['booking_id'] ?? 0);
        $order_id = (int) ($lookup['order_id'] ?? 0);
    }
}

$verify_url = 'https://api.flutterwave.com/v3/transactions/verify/' . urlencode($tx_ref);
$ch = curl_init($verify_url);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . FLW_SECRET_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=verification_failed');
    exit;
}

$result = json_decode($response, true);

if (!$result || $result['status'] !== 'success' || $result['data']['status'] !== 'successful') {
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=payment_not_successful');
    exit;
}

$data = $result['data'];
$flw_amount = (float) $data['amount'];
$flw_currency = $data['currency'] ?? 'NGN';
$flw_reference = $data['tx_ref'] ?? $tx_ref;

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        UPDATE payments
        SET status = 'paid',
            verified_at = NOW(),
            gateway_response = ?
        WHERE reference = ? AND status = 'pending'
    ");
    $stmt->execute([json_encode($data), $flw_reference]);

    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, order_id, amount, method, status, reference, gateway_response, created_at, verified_at)
            VALUES (?, ?, ?, 'flutterwave', 'paid', ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $booking_id ?: null,
            $order_id ?: null,
            $flw_amount,
            $flw_reference,
            json_encode($data),
        ]);
    }

    $receipt_number = 'RCT-' . strtoupper(uniqid());

    $stmt = $db->prepare("UPDATE payments SET receipt_number = ? WHERE reference = ? AND receipt_number IS NULL");
    $stmt->execute([$receipt_number, $flw_reference]);

    if ($booking_id > 0) {
        $stmt = $db->prepare("
            UPDATE bookings
            SET payment_status = 'paid',
                booking_status = CASE WHEN booking_status = 'pending' THEN 'confirmed' ELSE booking_status END,
                flw_reference = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$flw_reference, $booking_id]);

        $stmt = $db->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $bk = $stmt->fetch();
        if (!empty($bk['room_id'])) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
            $stmt->execute([$bk['room_id']]);
        }

        log_audit('payment_success', 'booking', $booking_id,
            ['payment_status' => 'pending'],
            ['payment_status' => 'paid', 'amount' => $flw_amount, 'reference' => $flw_reference]
        );
    }

    if ($order_id > 0) {
        $stmt = $db->prepare("UPDATE food_orders SET payment_status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);

        log_audit('payment_success', 'order', $order_id,
            ['payment_status' => 'pending'],
            ['payment_status' => 'paid', 'amount' => $flw_amount, 'reference' => $flw_reference]
        );
    }

    $db->commit();

    $params = [
        'success' => 'payment_successful',
        'ref'     => $flw_reference,
        'receipt' => $receipt_number,
    ];
    if ($booking_id) {
        $params['booking_id'] = $booking_id;
        $redirect = APP_URL . '/customer/my-bookings.php?' . http_build_query($params);
    } elseif ($order_id) {
        $params['order_id'] = $order_id;
        $redirect = APP_URL . '/customer/my-orders.php?' . http_build_query($params);
    } else {
        $redirect = APP_URL . '/customer/dashboard.php?' . http_build_query($params);
    }

    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Flutterwave callback error: ' . $e->getMessage());
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=callback_processing_failed');
    exit;
}
