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

    $booking_id = (int) ($_POST['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        jsonResponse(['sent' => false, 'message' => 'Booking ID is required']);
    }

    $stmt = $db->prepare("
        SELECT b.*, c.full_name AS customer_name, c.phone,
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

    if (empty($booking['phone'])) {
        jsonResponse(['sent' => false, 'message' => 'Customer has no phone number']);
    }

    $checkIn  = formatDate($booking['check_in_date']);
    $checkOut = formatDate($booking['check_out_date']);
    $payable  = formatMoney($booking['payable_amount']);

    $message = "Booking Confirmed! Ref: {$booking['booking_reference']}. "
             . "Room: {$booking['room_type_name']} ({$booking['room_number']}). "
             . "Check-in: {$checkIn}, Check-out: {$checkOut}. "
             . "Amount: {$payable}. "
             . "Thank you for choosing {$booking['branch_name']}.";

    $_POST['to']      = $booking['phone'];
    $_POST['message'] = $message;

    require __DIR__ . '/send-sms.php';
} catch (PDOException $e) {
    error_log('Booking SMS error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Booking SMS error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'An unexpected error occurred'], 500);
}
