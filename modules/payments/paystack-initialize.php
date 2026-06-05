<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/paystack.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$amount    = (float) ($_POST['amount'] ?? 0);
$email     = trim($_POST['email'] ?? '');
$booking_id = (int) ($_POST['booking_id'] ?? 0);
$order_id  = (int) ($_POST['order_id'] ?? 0);
$reference = trim($_POST['reference'] ?? generateReference('PAY'));

if ($amount <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid amount'], 400);
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Valid email is required'], 400);
}

if (!$booking_id && !$order_id) {
    jsonResponse(['success' => false, 'message' => 'Booking ID or Order ID is required'], 400);
}

$amount_in_kobo = (int) round($amount * 100);

$callback_url = rtrim(PAYSTACK_CALLBACK_URL, '?') . '?booking_id=' . $booking_id . '&order_id=' . $order_id . '&reference=' . urlencode($reference);

$post_data = json_encode([
    'email'         => $email,
    'amount'        => $amount_in_kobo,
    'reference'     => $reference,
    'callback_url'  => $callback_url,
    'currency'      => PAYSTACK_CURRENCY,
    'metadata'      => json_encode([
        'booking_id'  => $booking_id,
        'order_id'    => $order_id,
        'cancel_action' => rtrim(APP_URL, '/') . '/customer/my-bookings.php',
    ]),
]);

$ch = curl_init('https://api.paystack.co/transaction/initialize');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $post_data,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post_data),
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    error_log('Paystack cURL error: ' . $curl_error);
    jsonResponse(['success' => false, 'message' => 'Failed to connect to payment gateway'], 500);
}

$result = json_decode($response, true);

if (!$result || !isset($result['status'])) {
    error_log('Paystack invalid response: ' . $response);
    jsonResponse(['success' => false, 'message' => 'Invalid response from payment gateway'], 502);
}

if ($http_code !== 200 || !$result['status']) {
    $error_msg = $result['message'] ?? 'Payment initialization failed';
    error_log('Paystack init error: ' . ($result['message'] ?? 'Unknown') . ' | Response: ' . $response);
    jsonResponse(['success' => false, 'message' => $error_msg], 502);
}

$data = $result['data'];

// Store Paystack reference on the booking/order
$db = getDB();
try {
    if ($booking_id > 0) {
        $stmt = $db->prepare("UPDATE bookings SET paystack_reference = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reference, $booking_id]);
    } elseif ($order_id > 0) {
        $stmt = $db->prepare("UPDATE orders SET paystack_reference = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reference, $order_id]);
    }

    // Record pending payment
    $stmt = $db->prepare("
        INSERT INTO payments (booking_id, order_id, amount, method, status, reference, created_at)
        VALUES (?, ?, ?, 'paystack', 'pending', ?, NOW())
    ");
    $stmt->execute([$booking_id ?: null, $order_id ?: null, $amount, $reference]);

} catch (Exception $e) {
    error_log('Failed to store paystack reference: ' . $e->getMessage());
    // Non-critical - continue with payment flow
}

jsonResponse([
    'success'           => true,
    'message'           => 'Payment initialized successfully',
    'authorization_url' => $data['authorization_url'],
    'access_code'       => $data['access_code'] ?? '',
    'reference'         => $reference,
    'amount'            => $amount,
]);
