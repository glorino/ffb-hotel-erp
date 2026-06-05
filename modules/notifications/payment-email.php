<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/mail.php';
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
        SELECT p.*, c.full_name AS customer_name, c.email AS customer_email,
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

    if (empty($payment['customer_email'])) {
        jsonResponse(['sent' => false, 'message' => 'Customer has no email address']);
    }

    $amount  = formatMoney($payment['amount']);
    $status  = ucfirst(str_replace('_', ' ', $payment['status']));
    $method  = ucfirst(str_replace('_', ' ', $payment['method']));
    $receipt = $payment['receipt_number'] ?? 'N/A';
    $date    = formatDateTime($payment['created_at']);

    $messageHtml = <<<HTML
<p>Your payment has been processed successfully.</p>

<table style="width:100%;border-collapse:collapse;margin:20px 0;">
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Receipt No.</td><td style="padding:10px;border:1px solid #ddd;">{$receipt}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Payment Reference</td><td style="padding:10px;border:1px solid #ddd;">{$payment['payment_reference']}</td></tr>
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Booking Reference</td><td style="padding:10px;border:1px solid #ddd;">{$payment['booking_reference']}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Amount</td><td style="padding:10px;border:1px solid #ddd;">{$amount}</td></tr>
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Payment Method</td><td style="padding:10px;border:1px solid #ddd;">{$method}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Status</td><td style="padding:10px;border:1px solid #ddd;">{$status}</td></tr>
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Date</td><td style="padding:10px;border:1px solid #ddd;">{$date}</td></tr>
</table>

<p>Thank you for your patronage.</p>
HTML;

    $_POST['to_email'] = $payment['customer_email'];
    $_POST['to_name']  = $payment['customer_name'];
    $_POST['subject']  = 'Payment Confirmation - ' . ($payment['receipt_number'] ?? $payment['payment_reference']);
    $_POST['message']  = $messageHtml;

    require __DIR__ . '/send-email.php';
} catch (PDOException $e) {
    error_log('Payment email error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Payment email error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'An unexpected error occurred'], 500);
}
