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

if (!$booking_id) {
    jsonResponse(['success' => false, 'message' => 'Booking ID is required'], 400);
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.id as room_id,
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

    if ($booking['booking_status'] !== 'pending') {
        jsonResponse(['success' => false, 'message' => 'Booking cannot be confirmed. Current status: ' . $booking['booking_status']], 400);
    }

    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE bookings SET booking_status = 'confirmed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$booking_id]);

    if (!empty($booking['room_id'])) {
        $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
        $stmt->execute([$booking['room_id']]);
    }

    $db->commit();

    log_audit('confirm_booking', 'booking', $booking_id,
        ['booking_status' => 'pending'],
        ['booking_status' => 'confirmed']
    );

    // Send email notification
    try {
        $subject = 'Booking Confirmed - ' . $booking['booking_reference'];
        $message = "Dear {$booking['customer_name']},\n\n"
                 . "Your booking ({$booking['booking_reference']}) at {$booking['branch_name']} has been confirmed.\n\n"
                 . "Check-in: " . formatDate($booking['check_in_date']) . "\n"
                 . "Check-out: " . formatDate($booking['check_out_date']) . "\n"
                 . "Room: {$booking['room_number']}\n"
                 . "Total Paid: " . formatMoney($booking['payable_amount']) . "\n\n"
                 . "Thank you for choosing " . APP_NAME . ".";

        $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n"
                 . 'Reply-To: ' . SMTP_FROM_EMAIL . "\r\n"
                 . 'X-Mailer: PHP/' . phpversion();

        mail($booking['customer_email'], $subject, $message, $headers);
    } catch (Exception $e) {
        error_log('Confirmation email failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    // Send SMS notification
    try {
        $sms_text = "Dear {$booking['customer_name']}, your booking {$booking['booking_reference']} at {$booking['branch_name']} is CONFIRMED. Check-in: " . formatDate($booking['check_in_date']) . ". Thank you for choosing " . APP_NAME . ".";

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
        error_log('Confirmation SMS failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    jsonResponse([
        'success' => true,
        'message' => 'Booking confirmed successfully',
        'booking' => [
            'id'                => $booking_id,
            'reference'         => $booking['booking_reference'],
            'customer_name'     => $booking['customer_name'],
            'customer_email'    => $booking['customer_email'],
            'customer_phone'    => $booking['customer_phone'],
            'branch_name'       => $booking['branch_name'],
            'room_number'       => $booking['room_number'],
            'check_in_date'     => $booking['check_in_date'],
            'check_out_date'    => $booking['check_out_date'],
            'payable_amount'    => (float) $booking['payable_amount'],
        ],
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Confirm booking error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to confirm booking'], 500);
}
