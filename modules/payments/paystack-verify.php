<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/paystack.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$reference = $_REQUEST['reference'] ?? '';

if (empty($reference)) {
    jsonResponse(['success' => false, 'message' => 'Transaction reference is required'], 400);
}

$ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
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
    error_log('Flutterwave verify cURL error: ' . $curl_error);
    jsonResponse([
        'verified' => false,
        'success'  => false,
        'message'  => 'Failed to connect to payment gateway for verification',
    ], 502);
}

if ($http_code !== 200) {
    $error_msg = 'Flutterwave verification failed with HTTP ' . $http_code;
    if ($http_code === 404) {
        $error_msg = 'Transaction reference not found';
    }
    error_log('Flutterwave verify HTTP error: ' . $http_code . ' | Ref: ' . $reference);
    jsonResponse([
        'verified' => false,
        'success'  => false,
        'message'  => $error_msg,
    ], 502);
}

$result = json_decode($response, true);

if (!$result || !isset($result['status'])) {
    error_log('Flutterwave verify invalid response: ' . $response);
    jsonResponse([
        'verified' => false,
        'success'  => false,
        'message'  => 'Invalid response from payment gateway',
    ], 502);
}

if (!$result['status']) {
    jsonResponse([
        'verified' => false,
        'success'  => false,
        'message'  => $result['message'] ?? 'Transaction verification failed',
        'gateway_message' => $result['message'] ?? '',
    ]);
}

$data = $result['data'];
$transaction_status = $data['status'] ?? 'unknown';
$transaction_amount = $data['amount'] ?? 0;
$transaction_currency = $data['currency'] ?? '';
$transaction_fees = $data['fees'] ?? 0;
$paid_at = $data['paid_at'] ?? '';
$channel = $data['channel'] ?? '';
$card_details = $data['authorization'] ?? [];
$customer = $data['customer'] ?? [];

// Verify the amount if expected amount is provided
$expected_amount = $_REQUEST['expected_amount'] ?? null;
$amount_mismatch = false;
if ($expected_amount !== null) {
    $expected_kobo = (int) round((float) $expected_amount * 100);
    if ((int) $transaction_amount !== $expected_kobo) {
        $amount_mismatch = true;
    }
}

// Verify currency matches NGN
$currency_mismatch = false;
if ($transaction_currency !== PAYSTACK_CURRENCY) {
    $currency_mismatch = true;
}

if ($transaction_status === 'success' && !$amount_mismatch && !$currency_mismatch) {
    jsonResponse([
        'verified'          => true,
        'success'           => true,
        'message'           => 'Transaction verified successfully',
        'transaction'       => [
            'reference'         => $data['reference'] ?? $reference,
            'status'            => $transaction_status,
            'amount'            => (float) ($transaction_amount / 100),
            'amount_kobo'       => (int) $transaction_amount,
            'currency'          => $transaction_currency,
            'fees'              => (float) ($transaction_fees / 100),
            'paid_at'           => $paid_at,
            'channel'           => $channel,
            'ip_address'        => $data['ip_address'] ?? '',
            'card_type'         => $card_details['card_type'] ?? '',
            'last_four'         => $card_details['last4'] ?? '',
            'bank'              => $card_details['bank'] ?? '',
            'customer_email'    => $customer['email'] ?? '',
            'customer_code'     => $customer['customer_code'] ?? '',
            'gateway_response'  => $data['gateway_response'] ?? '',
        ],
    ]);
} else {
    $errors = [];
    if ($transaction_status !== 'success') {
        $errors[] = "Transaction status is '{$transaction_status}', expected 'success'";
    }
    if ($amount_mismatch) {
        $errors[] = 'Amount mismatch';
    }
    if ($currency_mismatch) {
        $errors[] = "Currency mismatch: expected " . PAYSTACK_CURRENCY . ", got {$transaction_currency}";
    }

    jsonResponse([
        'verified'          => false,
        'success'           => false,
        'message'           => implode('; ', $errors),
        'transaction_status' => $transaction_status,
        'amount_mismatch'   => $amount_mismatch,
        'currency_mismatch' => $currency_mismatch,
        'transaction'       => [
            'reference'  => $data['reference'] ?? $reference,
            'status'     => $transaction_status,
            'amount'     => (float) ($transaction_amount / 100),
            'currency'   => $transaction_currency,
        ],
    ]);
}
