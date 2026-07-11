<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/paystack.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../config/termii.php';
require_once __DIR__ . '/../../includes/functions.php';

$trxref   = $_GET['trxref'] ?? '';
$reference = $_GET['reference'] ?? $_GET['trxref'] ?? '';
$booking_id = (int) ($_GET['booking_id'] ?? 0);
$order_id  = (int) ($_GET['order_id'] ?? 0);

if (empty($reference)) {
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=missing_reference');
    exit;
}

// Verify transaction
$ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=verification_failed');
    exit;
}

$result = json_decode($response, true);

if (!$result || !$result['status'] || $result['data']['status'] !== 'success') {
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=payment_not_successful');
    exit;
}

$data = $result['data'];
$flw_amount = $data['amount'] / 100;
$flw_currency = $data['currency'] ?? 'NGN';
$flw_reference = $data['reference'] ?? $reference;

// If booking_id was not passed in URL, try to look it up from pending payment
$db = getDB();

if (!$booking_id && !$order_id) {
    $stmt = $db->prepare("SELECT booking_id, order_id FROM payments WHERE reference = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$reference]);
    $lookup = $stmt->fetch();
    if ($lookup) {
        $booking_id = (int) ($lookup['booking_id'] ?? 0);
        $order_id = (int) ($lookup['order_id'] ?? 0);
    }
}

try {
    $db->beginTransaction();

    // Update payment record
    $stmt = $db->prepare("
        UPDATE payments
        SET status = 'paid',
            verified_at = NOW(),
            gateway_response = ?
        WHERE reference = ? AND status = 'pending'
    ");
    $stmt->execute([json_encode($data), $flw_reference]);

    // If no pending payment record found, insert one
    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, order_id, amount, method, status, reference, gateway_response, created_at, verified_at)
            VALUES (?, ?, ?, 'flutterwave', 'paid', ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $booking_id ?: null,
            $order_id ?: null,
            $flw_amount,
            $flw_reference,
            json_encode($data),
        ]);
    }

    $receipt_number = generateReceiptNumber();

    // Generate receipt record
    $stmt = $db->prepare("
        UPDATE payments SET receipt_number = ? WHERE reference = ? AND receipt_number IS NULL
    ");
    $stmt->execute([$receipt_number, $flw_reference]);

    if ($booking_id > 0) {
        // Update booking payment and status
        $stmt = $db->prepare("
            UPDATE bookings
            SET payment_status = 'paid',
                booking_status = CASE WHEN booking_status = 'pending' THEN 'confirmed' ELSE booking_status END,
                paystack_reference = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$flw_reference, $booking_id]);

        // Reserve the room
        $stmt = $db->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $bk = $stmt->fetch();
        if (!empty($bk['room_id'])) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
            $stmt->execute([$bk['room_id']]);
        }

        // Fetch customer details for notification
        $stmt = $db->prepare("
            SELECT b.*, c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, br.name AS branch_name
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            LEFT JOIN branches br ON b.branch_id = br.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if ($booking) {
            // Send payment confirmation email
            try {
                $subject = 'Payment Confirmed - ' . $booking['booking_reference'];
                $message = "Dear {$booking['customer_name']},\n\n"
                         . "Your payment of " . formatMoney($flw_amount) . " has been confirmed.\n\n"
                         . "Booking Reference: {$booking['booking_reference']}\n"
                         . "Branch: {$booking['branch_name']}\n"
                         . "Check-In: " . formatDate($booking['check_in_date']) . "\n"
                         . "Check-Out: " . formatDate($booking['check_out_date']) . "\n"
                         . "Receipt: {$receipt_number}\n\n"
                         . "Your booking is now confirmed.\n\n"
                         . APP_NAME;

                $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n"
                         . 'Reply-To: ' . SMTP_FROM_EMAIL . "\r\n"
                         . 'X-Mailer: PHP/' . phpversion();

                mail($booking['customer_email'], $subject, $message, $headers);
            } catch (Exception $e) {
                error_log('Payment confirmation email failed: ' . $e->getMessage());
            }

            // Send SMS
            try {
                $sms_text = "Payment confirmed for booking {$booking['booking_reference']}. Amount: " . formatMoney($flw_amount) . ". Receipt: {$receipt_number}. Thank you for choosing " . APP_NAME . ".";

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
                error_log('Payment confirmation SMS failed: ' . $e->getMessage());
            }

            log_audit('payment_success', 'booking', $booking_id,
                ['payment_status' => 'pending'],
                ['payment_status' => 'paid', 'amount' => $flw_amount, 'reference' => $flw_reference]
            );
        }
    }

    if ($order_id > 0) {
        $stmt = $db->prepare("UPDATE food_orders SET payment_status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);

        log_audit('payment_success', 'order', $order_id,
            ['payment_status' => 'pending'],
            ['payment_status' => 'paid', 'amount' => $flw_amount, 'reference' => $flw_reference]
        );
    }

    $db->commit();

    // Redirect to success page
    $params = [
        'success'  => 'payment_successful',
        'ref'      => $flw_reference,
        'receipt'  => $receipt_number,
    ];
    if ($booking_id) {
        $params['booking_id'] = $booking_id;
        $redirect = APP_URL . '/customer/my-bookings.php?' . http_build_query($params);
    } elseif ($order_id) {
        $params['order_id'] = $order_id;
        $redirect = APP_URL . '/customer/my-orders.php?' . http_build_query($params);
    } else {
        $redirect = APP_URL . '/customer/dashboard.php?' . http_build_query($params);
    }

    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    error_log('Flutterwave callback error: ' . $e->getMessage());
    header('Location: ' . APP_URL . '/customer/my-bookings.php?error=callback_processing_failed');
    exit;
}
