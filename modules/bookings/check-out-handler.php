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
$checked_out_by = (int) ($_POST['checked_out_by'] ?? $_SESSION['user_id'] ?? 0);
$extra_charges = (float) ($_POST['extra_charges'] ?? 0);
$extra_charges_note = $_POST['extra_charges_note'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';

if (!$booking_id) {
    jsonResponse(['success' => false, 'message' => 'Booking ID is required'], 400);
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT b.*, r.id as room_id, r.room_number, r.status as room_status,
               rt.base_price, rt.name AS room_type_name,
               c.id AS customer_id, c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
               br.name AS branch_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        LEFT JOIN branches br ON b.branch_id = br.id
        WHERE b.id = ? FOR UPDATE
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        jsonResponse(['success' => false, 'message' => 'Booking not found'], 404);
    }

    if ($booking['booking_status'] !== 'checked_in') {
        jsonResponse(['success' => false, 'message' => 'Booking is not checked in. Current status: ' . $booking['booking_status']], 400);
    }

    $actual_check_in = $booking['actual_check_in'];
    $scheduled_check_in = $booking['check_in_date'];
    $scheduled_check_out = $booking['check_out_date'];
    $actual_check_out = date('Y-m-d H:i:s');

    // Calculate actual nights stayed
    $check_in_dt = $actual_check_in ? new DateTime($actual_check_in) : new DateTime($scheduled_check_in);
    $check_out_dt = new DateTime();
    $actual_nights = max(1, $check_in_dt->diff($check_out_dt)->days);

    // If checked out early/late, recalculate
    $scheduled_nights = calculateNights($scheduled_check_in, $scheduled_check_out);
    $base_price_per_night = (float) ($booking['base_price'] ?? 0);

    // Total room charges based on actual nights
    $total_room_charges = $actual_nights * $base_price_per_night;
    $original_payable = (float) $booking['payable_amount'];
    $discount_amount = (float) $booking['discount_amount'];

    // If actual stay differs, recalculate
    if ($actual_nights !== $scheduled_nights) {
        $new_total = $actual_nights * $base_price_per_night;
        $discount_amount = ($discount_amount > 0 && $original_payable > 0)
            ? round(($discount_amount / ($scheduled_nights * $base_price_per_night)) * $new_total, 2)
            : 0;
        $total_room_charges = $new_total;
    }

    $final_total = $total_room_charges - $discount_amount + $extra_charges;

    $db->beginTransaction();

    $stmt = $db->prepare("
        UPDATE bookings
        SET booking_status = 'checked_out',
            actual_check_out = ?,
            checked_out_by = ?,
            extra_charges = ?,
            extra_charges_note = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$actual_check_out, $checked_out_by, $extra_charges, $extra_charges_note, $booking_id]);

    $stmt = $db->prepare("UPDATE rooms SET status = 'cleaning' WHERE id = ?");
    $stmt->execute([$booking['room_id']]);

    // Record extra charges payment if any
    if ($extra_charges > 0 && !empty($payment_method)) {
        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, amount, method, status, reference, notes, created_at)
            VALUES (?, ?, ?, 'paid', ?, ?, NOW())
        ");
        $stmt->execute([
            $booking_id,
            $extra_charges,
            $payment_method,
            generateReference('EXTRA'),
            'Extra charges: ' . $extra_charges_note,
        ]);
    }

    $db->commit();

    log_audit('check_out', 'booking', $booking_id,
        ['booking_status' => 'checked_in', 'room_status' => 'occupied'],
        [
            'booking_status'    => 'checked_out',
            'room_status'       => 'cleaning',
            'actual_nights'     => $actual_nights,
            'extra_charges'     => $extra_charges,
            'final_total'       => $final_total,
        ]
    );

    // Send thank you email
    try {
        $subject = 'Thank You for Staying - ' . $booking['booking_reference'];
        $message = "Dear {$booking['customer_name']},\n\n"
                 . "Thank you for staying at {$booking['branch_name']}.\n\n"
                 . "Stay Summary:\n"
                 . "----------------\n"
                 . "Room: {$booking['room_number']} ({$booking['room_type_name']})\n"
                 . "Check-In: " . formatDateTime($actual_check_in ?: $scheduled_check_in) . "\n"
                 . "Check-Out: " . formatDateTime($actual_check_out) . "\n"
                 . "Nights Stayed: {$actual_nights}\n"
                 . "Room Charges: " . formatMoney($total_room_charges) . "\n"
                 . "Discount: " . formatMoney($discount_amount) . "\n"
                 . ($extra_charges > 0 ? "Extra Charges: " . formatMoney($extra_charges) . "\n" : "")
                 . "----------------\n"
                 . "Total Charged: " . formatMoney($final_total) . "\n"
                 . "Payment Status: " . ucfirst(str_replace('_', ' ', $booking['payment_status'])) . "\n\n"
                 . "We look forward to welcoming you again!\n\n"
                 . APP_NAME;

        $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n"
                 . 'Reply-To: ' . SMTP_FROM_EMAIL . "\r\n"
                 . 'X-Mailer: PHP/' . phpversion();

        mail($booking['customer_email'], $subject, $message, $headers);
    } catch (Exception $e) {
        error_log('Check-out email failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    // Send thank you SMS
    try {
        $sms_text = "Thank you for staying at {$booking['branch_name']}, {$booking['customer_name']}! We hope you enjoyed your stay. We look forward to welcoming you again. - " . APP_NAME;

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
        error_log('Check-out SMS failed for booking ' . $booking_id . ': ' . $e->getMessage());
    }

    jsonResponse([
        'success' => true,
        'message' => 'Check-out processed successfully',
        'summary' => [
            'booking_reference'  => $booking['booking_reference'],
            'customer_name'      => $booking['customer_name'],
            'room_number'        => $booking['room_number'],
            'room_type'          => $booking['room_type_name'],
            'branch_name'        => $booking['branch_name'],
            'actual_check_in'    => $actual_check_in ?: $scheduled_check_in,
            'actual_check_out'   => $actual_check_out,
            'total_nights'       => $actual_nights,
            'scheduled_nights'   => $scheduled_nights,
            'base_price_per_night' => $base_price_per_night,
            'total_room_charges' => round($total_room_charges, 2),
            'discount_amount'    => round($discount_amount, 2),
            'extra_charges'      => $extra_charges,
            'final_total'        => round($final_total, 2),
            'payment_status'     => $booking['payment_status'],
        ],
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Check-out error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to process check-out'], 500);
}
