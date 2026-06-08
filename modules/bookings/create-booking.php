<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$customer_id     = (int) ($_POST['customer_id'] ?? 0);
$branch_id       = (int) ($_POST['branch_id'] ?? 0);
$room_id         = (int) ($_POST['room_id'] ?? 0);
$check_in_date   = $_POST['check_in_date'] ?? '';
$check_out_date  = $_POST['check_out_date'] ?? '';
$guests          = (int) ($_POST['guests'] ?? 1);
$total_amount    = (float) ($_POST['total_amount'] ?? 0);
$discount_amount = (float) ($_POST['discount_amount'] ?? 0);
$payable_amount  = (float) ($_POST['payable_amount'] ?? 0);
$payment_status  = $_POST['payment_status'] ?? 'pending';
$booking_status  = $_POST['booking_status'] ?? '';
$source          = $_POST['source'] ?? 'online';
$special_request = $_POST['special_request'] ?? '';
$coupon_id       = !empty($_POST['coupon_id']) ? (int) $_POST['coupon_id'] : null;
$created_by      = (int) ($_POST['created_by'] ?? $_SESSION['user_id'] ?? 0);

if (!$customer_id || !$branch_id || !$room_id || !$check_in_date || !$check_out_date) {
    jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
}

if (!validateDate($check_in_date) || !validateDate($check_out_date)) {
    jsonResponse(['success' => false, 'message' => 'Invalid date format'], 400);
}

if (strtotime($check_in_date) >= strtotime($check_out_date)) {
    jsonResponse(['success' => false, 'message' => 'Check-out must be after check-in'], 400);
}

if ($guests < 1) {
    jsonResponse(['success' => false, 'message' => 'At least one guest required'], 400);
}

$db = getDB();

try {
    $db->beginTransaction();

    if ($room_id) {
        $checkStmt = $db->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE room_id = ?
              AND booking_status NOT IN ('cancelled', 'checked_out', 'no_show')
              AND check_in_date < ?
              AND check_out_date > ?
        ");
        $checkStmt->execute([$room_id, $check_out_date, $check_in_date]);
        if ((int) $checkStmt->fetchColumn() > 0) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'This room is already booked for the selected dates. Please choose different dates or another room.'], 409);
        }

        $roomStmt = $db->prepare("SELECT status FROM rooms WHERE id = ?");
        $roomStmt->execute([$room_id]);
        $roomRow = $roomStmt->fetch();
        if ($roomRow && !in_array($roomRow['status'], ['available', 'reserved'])) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'This room is currently ' . ucfirst($roomRow['status']) . ' and cannot be booked.'], 409);
        }
    }

    $booking_reference = generateReference('BK');

    if (empty($booking_status)) {
        $booking_status = ($source === 'online') ? 'pending' : 'confirmed';
    }

    $nights = calculateNights($check_in_date, $check_out_date);

    $stmt = $db->prepare("
        INSERT INTO bookings (
            booking_reference, customer_id, branch_id, room_id,
            check_in_date, check_out_date, guests, nights,
            total_amount, discount_amount, payable_amount,
            payment_status, booking_status, source,
            special_request, coupon_id, created_by, created_at
        ) VALUES (
            :booking_reference, :customer_id, :branch_id, :room_id,
            :check_in_date, :check_out_date, :guests, :nights,
            :total_amount, :discount_amount, :payable_amount,
            :payment_status, :booking_status, :source,
            :special_request, :coupon_id, :created_by, NOW()
        )
    ");

    $stmt->execute([
        ':booking_reference' => $booking_reference,
        ':customer_id'       => $customer_id,
        ':branch_id'         => $branch_id,
        ':room_id'           => $room_id,
        ':check_in_date'     => $check_in_date,
        ':check_out_date'    => $check_out_date,
        ':guests'            => $guests,
        ':nights'            => $nights,
        ':total_amount'      => $total_amount,
        ':discount_amount'   => $discount_amount,
        ':payable_amount'    => $payable_amount,
        ':payment_status'    => $payment_status,
        ':booking_status'    => $booking_status,
        ':source'            => $source,
        ':special_request'   => $special_request,
        ':coupon_id'         => $coupon_id,
        ':created_by'        => $created_by,
    ]);

    $booking_id = (int) $db->lastInsertId();

    if ($booking_status === 'confirmed' && $room_id) {
        $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
        $stmt->execute([$room_id]);
    }

    if ($coupon_id) {
        $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$coupon_id]);
    }

    $db->commit();

    log_audit('create_booking', 'booking', $booking_id, null, [
        'booking_reference' => $booking_reference,
        'customer_id'       => $customer_id,
        'room_id'           => $room_id,
        'check_in'          => $check_in_date,
        'check_out'         => $check_out_date,
        'total'             => $total_amount,
        'payable'           => $payable_amount,
        'status'            => $booking_status,
        'source'            => $source,
    ]);

    jsonResponse([
        'success'    => true,
        'message'    => 'Booking created successfully',
        'booking_id' => $booking_id,
        'reference'  => $booking_reference,
        'status'     => $booking_status,
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Create booking error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to create booking. Please try again.'], 500);
}
