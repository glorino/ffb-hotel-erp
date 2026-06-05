<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$booking_id  = (int) ($_POST['booking_id'] ?? 0);
$order_id    = (int) ($_POST['order_id'] ?? 0);
$amount      = (float) ($_POST['amount'] ?? 0);
$method      = $_POST['method'] ?? '';
$notes       = $_POST['notes'] ?? '';
$recorded_by = (int) ($_POST['recorded_by'] ?? $_SESSION['user_id'] ?? 0);

if (!$booking_id && !$order_id) {
    jsonResponse(['success' => false, 'message' => 'Booking ID or Order ID is required'], 400);
}

if ($amount <= 0) {
    jsonResponse(['success' => false, 'message' => 'Amount must be greater than zero'], 400);
}

$valid_methods = ['cash', 'pos', 'bank_transfer', 'split_payment'];
if (!in_array($method, $valid_methods)) {
    jsonResponse(['success' => false, 'message' => 'Invalid payment method. Accepted: ' . implode(', ', $valid_methods)], 400);
}

$db = getDB();

try {
    $db->beginTransaction();

    // Handle split payment - additional methods sent as JSON array
    $split_methods = [];
    if ($method === 'split_payment') {
        $split_data = $_POST['split_methods'] ?? '';
        if (!empty($split_data)) {
            $split_methods = is_string($split_data) ? json_decode($split_data, true) : $split_data;
            if (!is_array($split_methods) || empty($split_methods)) {
                $db->rollBack();
                jsonResponse(['success' => false, 'message' => 'Split payment requires split_methods array'], 400);
            }
        }
    }

    $receipt_number = generateReceiptNumber();

    // Determine payment status
    $total_due = 0;
    if ($booking_id > 0) {
        $stmt = $db->prepare("SELECT payable_amount, payment_status FROM bookings WHERE id = ? FOR UPDATE");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        if (!$booking) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Booking not found'], 404);
        }
        $total_due = (float) $booking['payable_amount'];

        // Sum existing payments
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE booking_id = ? AND status IN ('paid', 'partially_paid')");
        $stmt->execute([$booking_id]);
        $existing_paid = (float) $stmt->fetchColumn();
    } elseif ($order_id > 0) {
        $stmt = $db->prepare("SELECT total_amount as payable_amount, payment_status FROM orders WHERE id = ? FOR UPDATE");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        if (!$order) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
        }
        $total_due = (float) $order['payable_amount'];

        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE order_id = ? AND status IN ('paid', 'partially_paid')");
        $stmt->execute([$order_id]);
        $existing_paid = (float) $stmt->fetchColumn();
    }

    $new_total_paid = $existing_paid + $amount;
    $payment_status = ($new_total_paid >= $total_due) ? 'paid' : 'partially_paid';

    // Record the payment(s)
    if ($method === 'split_payment' && !empty($split_methods)) {
        // Record each split as a separate payment
        foreach ($split_methods as $split) {
            $split_amount = (float) ($split['amount'] ?? 0);
            $split_method = $split['method'] ?? '';
            $split_notes  = $split['notes'] ?? '';

            if ($split_amount <= 0 || !in_array($split_method, $valid_methods)) {
                continue;
            }

            $split_ref = generateReference('SPLIT');
            $stmt = $db->prepare("
                INSERT INTO payments (booking_id, order_id, amount, method, status, reference, receipt_number, notes, recorded_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $booking_id ?: null,
                $order_id ?: null,
                $split_amount,
                $split_method,
                $payment_status,
                $split_ref,
                $receipt_number,
                $split_notes ?: 'Split payment: ' . $notes,
                $recorded_by,
            ]);
        }
    } else {
        $payment_ref = generateReference('OFF');
        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, order_id, amount, method, status, reference, receipt_number, notes, recorded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $booking_id ?: null,
            $order_id ?: null,
            $amount,
            $method,
            $payment_status,
            $payment_ref,
            $receipt_number,
            $notes,
            $recorded_by,
        ]);
        $payment_ref_saved = $payment_ref;
    }

    // Update booking/order payment status
    if ($booking_id > 0) {
        $new_booking_status = ($payment_status === 'paid') ? 'confirmed' : $booking['payment_status'];
        $stmt = $db->prepare("UPDATE bookings SET payment_status = ?, booking_status = CASE WHEN ? = 'paid' AND booking_status = 'pending' THEN 'confirmed' ELSE booking_status END, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$payment_status, $payment_status, $booking_id]);

        // Reserve room if fully paid
        if ($payment_status === 'paid') {
            $stmt = $db->prepare("SELECT room_id FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $bk = $stmt->fetch();
            if (!empty($bk['room_id'])) {
                $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
                $stmt->execute([$bk['room_id']]);
            }
        }

        log_audit('offline_payment', 'booking', $booking_id,
            ['payment_status' => $booking['payment_status']],
            ['payment_status' => $payment_status, 'amount' => $amount, 'method' => $method, 'receipt' => $receipt_number]
        );
    }

    if ($order_id > 0) {
        $stmt = $db->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$payment_status, $order_id]);

        log_audit('offline_payment', 'order', $order_id,
            ['payment_status' => $order['payment_status']],
            ['payment_status' => $payment_status, 'amount' => $amount, 'method' => $method, 'receipt' => $receipt_number]
        );
    }

    $db->commit();

    jsonResponse([
        'success'         => true,
        'message'         => 'Payment recorded successfully',
        'receipt_number'  => $receipt_number,
        'amount'          => $amount,
        'method'          => $method,
        'payment_status'  => $payment_status,
        'total_paid'      => $new_total_paid,
        'total_due'       => $total_due,
        'balance'         => max(0, $total_due - $new_total_paid),
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Offline payment error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to record payment'], 500);
}
