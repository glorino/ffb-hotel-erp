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

    $booking_id = (int) ($_POST['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        jsonResponse(['sent' => false, 'message' => 'Booking ID is required']);
    }

    $stmt = $db->prepare("
        SELECT b.*, c.full_name AS customer_name, c.email AS customer_email, c.phone,
               r.room_number, rt.name AS room_type_name, br.name AS branch_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        LEFT JOIN branches br ON b.branch_id = br.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        jsonResponse(['sent' => false, 'message' => 'Booking not found']);
    }

    if (empty($booking['customer_email'])) {
        jsonResponse(['sent' => false, 'message' => 'Customer has no email address']);
    }

    $nights    = calculateNights($booking['check_in_date'], $booking['check_out_date']);
    $checkIn   = formatDate($booking['check_in_date']);
    $checkOut  = formatDate($booking['check_out_date']);
    $total     = formatMoney($booking['total_amount']);
    $discount  = formatMoney($booking['discount_amount']);
    $payable   = formatMoney($booking['payable_amount']);

    $messageHtml = <<<HTML
<p>Thank you for choosing <strong>{$booking['branch_name']}</strong>. Your booking has been confirmed.</p>

<table style="width:100%;border-collapse:collapse;margin:20px 0;">
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Reference</td><td style="padding:10px;border:1px solid #ddd;">{$booking['booking_reference']}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Room</td><td style="padding:10px;border:1px solid #ddd;">{$booking['room_type_name']} ({$booking['room_number']})</td></tr>
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Check-In</td><td style="padding:10px;border:1px solid #ddd;">{$checkIn}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Check-Out</td><td style="padding:10px;border:1px solid #ddd;">{$checkOut}</td></tr>
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Guests</td><td style="padding:10px;border:1px solid #ddd;">{$booking['guests']}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Nights</td><td style="padding:10px;border:1px solid #ddd;">{$nights}</td></tr>
<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Total Amount</td><td style="padding:10px;border:1px solid #ddd;">{$total}</td></tr>
<tr><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Discount</td><td style="padding:10px;border:1px solid #ddd;">{$discount}</td></tr>
<tr style="background:#e8f5e9;"><td style="padding:10px;border:1px solid #ddd;font-weight:600;">Payable Amount</td><td style="padding:10px;border:1px solid #ddd;">{$payable}</td></tr>
</table>

<p>If you have any questions, please contact us.</p>
HTML;

    $_POST['to_email'] = $booking['customer_email'];
    $_POST['to_name']  = $booking['customer_name'];
    $_POST['subject']  = 'Booking Confirmation - ' . $booking['booking_reference'];
    $_POST['message']  = $messageHtml;

    require __DIR__ . '/send-email.php';
} catch (PDOException $e) {
    error_log('Booking email error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Booking email error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'An unexpected error occurred'], 500);
}
