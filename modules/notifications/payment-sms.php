<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/termii.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['sent' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();

    $payment_id = (int) ($_POST['payment_id'] ?? 0);

    if ($payment_id <= 0) {
        jsonResponse(['sent' => false, 'message' => 'Payment ID is required']);
    }

    $stmt = $db->prepare("
        SELECT p.*, c.full_name AS customer_name, c.phone,
               b.booking_reference, br.name AS branch_name
        FROM payments p
        JOIN customers c ON p.customer_id = c.id
        LEFT JOIN bookings b ON p.booking_id = b.id
        LEFT JOIN branches br ON b.branch_id = br.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        jsonResponse(['sent' => false, 'message' => 'Payment not found']);
    }

    if (empty($payment['phone'])) {
        jsonResponse(['sent' => false, 'message' => 'Customer has no phone number']);
    }

    $amount = formatMoney($payment['amount']);
    $ref    = $payment['payment_reference'];
    $status = ucfirst(str_replace('_', ' ', $payment['status']));

    $message = "Payment {$status}! Ref: {$ref}. Amount: {$amount}. "
             . "Receipt: {$payment['receipt_number']}. "
             . "Thank you for choosing {$payment['branch_name']}.";

    $_POST['to']      = $payment['phone'];
    $_POST['message'] = $message;

    require __DIR__ . '/send-sms.php';
} catch (PDOException $e) {
    error_log('Payment SMS error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Payment SMS error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'An unexpected error occurred'], 500);
}
