<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../config/termii.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$booking_id = (int) ($_POST['booking_id'] ?? 0);
$cancel_reason = $_POST['cancel_reason'] ?? '';
$process_refund = isset($_POST['process_refund']) ? (bool) $_POST['process_refund'] : true;

if (!$booking_id) {
    jsonResponse(['success' => false, 'message' => 'Booking ID is required'], 400);
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.id as room_id, r.status as room_status,
               c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
               br.name AS branch_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN branches br ON b.branch_id = br.id
        WHERE b.id = ? FOR UPDATE
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        jsonResponse(['success' => false, 'message' => 'Booking not found'], 404);
    }

    $allowed_statuses = ['pending', 'confirmed', 'checked_in'];
    if (!in_array($booking['booking_status'], $allowed_statuses)) {
        jsonResponse(['success' => false, 'message' => 'Booking cannot be cancelled. Current status: ' . $booking['booking_status']], 400);
    }

    $db->beginTransaction();

    $old_values = [
        'booking_status' => $booking['booking_status'],
        'payment_status' => $booking['payment_status'],
    ];

    $stmt = $db->prepare("UPDATE bookings SET booking_status = 'cancelled', cancel_reason = ?, cancelled_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$cancel_reason, $booking_id]);

    // Free up the room
    if (!empty($booking['room_id']) && in_array($booking['room_status'], ['reserved', 'occupied'])) {
        $new_room_status = ($booking['room_status'] === 'occupied') ? 'cleaning' : 'available';
        $stmt = $db->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $stmt->execute([$new_room_status, $booking['room_id']]);
    }

    // Handle refund if payment was made
    $refund_amount = 0;
    $refund_note = '';
    if ($process_refund && in_array($booking['payment_status'], ['paid', 'partially_paid'])) {
        $stmt = $db->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE booking_id = ? AND status = 'paid'");
        $stmt->execute([$booking_id]);
        $payment_row = $stmt->fetch();
        $total_paid = (float) ($payment_row['total_paid'] ?? 0);

        if ($total_paid > 0) {
            $refund_amount = $total_paid;

            $refund_ref = 'RFND-' . strtoupper(uniqid());
            $stmt = $db->prepare("
                INSERT INTO payments (booking_id, amount, method, status, reference, notes, created_at)
                VALUES (?, ?, 'bank_transfer', 'refunded', ?, ?, NOW())
            ");
            $stmt->execute([$booking_id, -$refund_amount, $refund_ref, 'Auto-refund on cancellation']);

            // Update payment status on booking
            $stmt = $db->prepare("UPDATE bookings SET payment_status = 'refunded', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$booking_id]);

            $refund_note = " Refund of " . formatMoney($refund_amount) . " has been processed.";
        }
    }

    $db->commit();

    log_audit('cancel_booking', 'booking', $booking_id, $old_values, [
        'booking_status' => 'cancelled',
        'cancel_reason'  => $cancel_reason,
        'refund_amount'  => $refund_amount,
    ]);

    // Send cancellation email
    try {
        $subject = 'Booking Cancelled - ' . $booking['booking_reference'];
        $message = "Dear {$booking['customer_name']},\n\n"
                 . "Your booking ({$booking['booking_reference']}) at {$booking['branch_name']} has been cancelled.\n"
                 . "Reason: " . ($cancel_reason ?: 'Requested by customer') . "\n"
                 . ($refund_note ? "\n{$refund_note}\n" : "\nNo payment was processed for this booking.\n")
                 . "\nWe hope to serve you again in the future.\n\n"
                 . APP_NAME;

        $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n"
                 . 'Reply-To: ' . SMTP_FROM_EMAIL . "\r\n"
                 . 'X-Mailer: PHP/' . phpversion();

        mail($booking['customer_email'], $subject, $message, $headers);
    } catch (Exception $e) {
        error_log('Cancellation email failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    // Send cancellation SMS
    try {
        $sms_text = "Dear {$booking['customer_name']}, your booking {$booking['booking_reference']} has been CANCELLED." . ($refund_note ? " Refund of " . formatMoney($refund_amount) . " will be processed." : "") . " - " . APP_NAME;

        $sms_payload = json_encode([
            'api_key' => TERMII_API_KEY,
            'to'      => $booking['customer_phone'],
            'from'    => TERMII_SENDER_ID,
            'sms'     => $sms_text,
            'type'    => 'plain',
            'channel' => 'generic',
        ]);

        $ch = curl_init(TERMII_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $sms_payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Content-Length: ' . strlen($sms_payload)],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    } catch (Exception $e) {
        error_log('Cancellation SMS failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    jsonResponse([
        'success'       => true,
        'message'       => 'Booking cancelled successfully.' . $refund_note,
        'refund_amount' => $refund_amount,
        'refund_note'   => $refund_note,
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Cancel booking error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to cancel booking'], 500);
}
