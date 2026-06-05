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
$checked_in_by = (int) ($_POST['checked_in_by'] ?? $_SESSION['user_id'] ?? 0);
$id_document = $_POST['id_document'] ?? '';
$id_number = $_POST['id_number'] ?? '';
$vehicle_info = $_POST['vehicle_info'] ?? '';

if (!$booking_id) {
    jsonResponse(['success' => false, 'message' => 'Booking ID is required'], 400);
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT b.*, r.id as room_id, r.room_number, r.status as room_status,
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

    if ($booking['booking_status'] !== 'confirmed') {
        jsonResponse(['success' => false, 'message' => 'Booking must be confirmed before check-in. Current status: ' . $booking['booking_status']], 400);
    }

    if (empty($booking['room_id'])) {
        jsonResponse(['success' => false, 'message' => 'No room assigned to this booking'], 400);
    }

    $db->beginTransaction();

    $stmt = $db->prepare("
        UPDATE bookings
        SET booking_status = 'checked_in',
            actual_check_in = NOW(),
            checked_in_by = ?,
            id_document = ?,
            id_number = ?,
            vehicle_info = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$checked_in_by, $id_document, $id_number, $vehicle_info, $booking_id]);

    $stmt = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
    $stmt->execute([$booking['room_id']]);

    $db->commit();

    log_audit('check_in', 'booking', $booking_id,
        ['booking_status' => 'confirmed', 'room_status' => $booking['room_status']],
        ['booking_status' => 'checked_in', 'room_status' => 'occupied']
    );

    // Send check-in email notification
    try {
        $subject = 'Check-In Successful - ' . $booking['booking_reference'];
        $message = "Dear {$booking['customer_name']},\n\n"
                 . "You have successfully checked in at {$booking['branch_name']}.\n\n"
                 . "Room: {$booking['room_number']}\n"
                 . "Check-In: " . date(DATETIME_FORMAT) . "\n"
                 . "Check-Out: " . formatDate($booking['check_out_date']) . "\n\n"
                 . "Wishing you a pleasant stay!\n\n"
                 . APP_NAME;

        $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n"
                 . 'Reply-To: ' . SMTP_FROM_EMAIL . "\r\n"
                 . 'X-Mailer: PHP/' . phpversion();

        mail($booking['customer_email'], $subject, $message, $headers);
    } catch (Exception $e) {
        error_log('Check-in email failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    // Send check-in SMS notification
    try {
        $sms_text = "Welcome to {$booking['branch_name']}, {$booking['customer_name']}! You have checked in to Room {$booking['room_number']}. Check-out: " . formatDate($booking['check_out_date']) . ". Enjoy your stay!";

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
        error_log('Check-in SMS failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    jsonResponse([
        'success' => true,
        'message' => 'Check-in processed successfully',
        'booking' => [
            'id'            => $booking_id,
            'reference'     => $booking['booking_reference'],
            'customer_name' => $booking['customer_name'],
            'room_number'   => $booking['room_number'],
            'check_in'      => date(DATETIME_FORMAT),
            'check_out'     => $booking['check_out_date'],
        ],
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Check-in error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to process check-in'], 500);
}
