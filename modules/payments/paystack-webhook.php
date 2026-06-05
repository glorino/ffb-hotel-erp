<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/paystack.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../config/termii.php';
require_once __DIR__ . '/../../includes/functions.php';

// Do not output any content before webhook processing
$input = file_get_contents('php://input');

if (empty($input)) {
    http_response_code(400);
    exit('Empty payload');
}

// Verify webhook signature
$paystack_signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (empty($paystack_signature)) {
    http_response_code(401);
    exit('Missing signature');
}

$expected_signature = hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY);

if (!hash_equals($expected_signature, $paystack_signature)) {
    error_log('Paystack webhook: Invalid signature. Expected: ' . $expected_signature . ', Got: ' . $paystack_signature);
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($input, true);

if (!$event || !isset($event['event']) || !isset($event['data'])) {
    error_log('Paystack webhook: Invalid event structure');
    http_response_code(400);
    exit('Invalid event');
}

$event_type = $event['event'];
$event_data = $event['data'];
$log_data = [
    'event'       => $event_type,
    'reference'   => $event_data['reference'] ?? '',
    'status'      => $event_data['status'] ?? '',
    'amount'      => $event_data['amount'] ?? 0,
    'currency'    => $event_data['currency'] ?? '',
    'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
    'timestamp'   => date('Y-m-d H:i:s'),
];

error_log('Paystack webhook received: ' . json_encode($log_data));

$db = getDB();

try {
    switch ($event_type) {
        case 'charge.success':
            handleChargeSuccess($db, $event_data);
            break;

        case 'charge.failed':
            handleChargeFailed($db, $event_data);
            break;

        case 'transfer.success':
            // Handle transfer success if applicable
            error_log('Paystack webhook: Transfer successful - ' . ($event_data['reference'] ?? ''));
            break;

        case 'transfer.failed':
            error_log('Paystack webhook: Transfer failed - ' . ($event_data['reference'] ?? ''));
            break;

        default:
            error_log('Paystack webhook: Unhandled event type - ' . $event_type);
    }

    http_response_code(200);
    echo 'OK';

} catch (Exception $e) {
    error_log('Paystack webhook processing error: ' . $e->getMessage());
    http_response_code(500);
    exit('Processing error');
}

function handleChargeSuccess(PDO $db, array $data): void
{
    $reference   = $data['reference'] ?? '';
    $amount_kobo = (int) ($data['amount'] ?? 0);
    $amount      = $amount_kobo / 100;
    $currency    = $data['currency'] ?? 'NGN';
    $paid_at     = $data['paid_at'] ?? date('Y-m-d H:i:s');
    $channel     = $data['channel'] ?? '';
    $gateway_response = $data['gateway_response'] ?? '';

    if (empty($reference)) {
        error_log('Paystack webhook: charge.success with empty reference');
        return;
    }

    // Find pending payment by reference
    $stmt = $db->prepare("SELECT * FROM payments WHERE reference = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$reference]);
    $payment = $stmt->fetch();

    $booking_id = 0;
    $order_id = 0;

    if ($payment) {
        $booking_id = (int) ($payment['booking_id'] ?? 0);
        $order_id   = (int) ($payment['order_id'] ?? 0);
    } else {
        // Check if reference is stored on booking/order
        $stmt = $db->prepare("SELECT id FROM bookings WHERE paystack_reference = ? LIMIT 1");
        $stmt->execute([$reference]);
        $bk = $stmt->fetch();
        if ($bk) {
            $booking_id = (int) $bk['id'];
        } else {
            $stmt = $db->prepare("SELECT id FROM orders WHERE paystack_reference = ? LIMIT 1");
            $stmt->execute([$reference]);
            $ord = $stmt->fetch();
            if ($ord) {
                $order_id = (int) $ord['id'];
            }
        }
    }

    $db->beginTransaction();

    // Update or insert payment record
    if ($payment) {
        $stmt = $db->prepare("
            UPDATE payments
            SET status = 'paid',
                verified_at = NOW(),
                gateway_response = ?,
                receipt_number = COALESCE(receipt_number, ?)
            WHERE id = ?
        ");
        $stmt->execute([json_encode($data), generateReceiptNumber(), $payment['id']]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, order_id, amount, method, status, reference, gateway_response, receipt_number, created_at, verified_at)
            VALUES (?, ?, ?, 'paystack', 'paid', ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $booking_id ?: null,
            $order_id ?: null,
            $amount,
            $reference,
            json_encode($data),
            generateReceiptNumber(),
        ]);
    }

    if ($booking_id > 0) {
        $stmt = $db->prepare("
            UPDATE bookings
            SET payment_status = 'paid',
                booking_status = CASE WHEN booking_status = 'pending' THEN 'confirmed' ELSE booking_status END,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$booking_id]);

        // Reserve room
        $stmt = $db->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $bk = $stmt->fetch();
        if (!empty($bk['room_id'])) {
            $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'");
            $stmt->execute([$bk['room_id']]);
        }

        // Send notifications
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
            sendPaymentNotifications($booking, $amount, $reference);
            log_audit('webhook_payment_success', 'booking', $booking_id,
                ['payment_status' => 'pending'],
                ['payment_status' => 'paid', 'amount' => $amount, 'reference' => $reference, 'channel' => $channel]
            );
        }
    }

    if ($order_id > 0) {
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);

        log_audit('webhook_payment_success', 'order', $order_id,
            ['payment_status' => 'pending'],
            ['payment_status' => 'paid', 'amount' => $amount, 'reference' => $reference, 'channel' => $channel]
        );
    }

    $db->commit();
    error_log("Paystack webhook: Payment successful for reference {$reference}, amount {$amount}, booking {$booking_id}, order {$order_id}");
}

function handleChargeFailed(PDO $db, array $data): void
{
    $reference = $data['reference'] ?? '';
    $amount_kobo = (int) ($data['amount'] ?? 0);
    $amount = $amount_kobo / 100;
    $failure_message = $data['failure_message'] ?? ($data['gateway_response'] ?? 'Unknown error');

    if (empty($reference)) {
        return;
    }

    $stmt = $db->prepare("SELECT * FROM payments WHERE reference = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$reference]);
    $payment = $stmt->fetch();

    if ($payment) {
        $stmt = $db->prepare("
            UPDATE payments
            SET status = 'failed',
                gateway_response = ?,
                failure_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([json_encode($data), $failure_message, $payment['id']]);

        $booking_id = (int) ($payment['booking_id'] ?? 0);
        $order_id   = (int) ($payment['order_id'] ?? 0);

        if ($booking_id > 0) {
            $stmt = $db->prepare("UPDATE bookings SET payment_status = 'failed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$booking_id]);

            log_audit('webhook_payment_failed', 'booking', $booking_id,
                ['payment_status' => 'pending'],
                ['payment_status' => 'failed', 'reference' => $reference, 'reason' => $failure_message]
            );
        }

        if ($order_id > 0) {
            $stmt = $db->prepare("UPDATE orders SET payment_status = 'failed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$order_id]);

            log_audit('webhook_payment_failed', 'order', $order_id,
                ['payment_status' => 'pending'],
                ['payment_status' => 'failed', 'reference' => $reference, 'reason' => $failure_message]
            );
        }
    }

    error_log("Paystack webhook: Payment failed for reference {$reference}, reason: {$failure_message}");
}

function sendPaymentNotifications(array $booking, float $amount, string $reference): void
{
    $receipt_number = generateReceiptNumber();

    try {
        $subject = 'Payment Confirmed - ' . $booking['booking_reference'];
        $message = "Dear {$booking['customer_name']},\n\n"
                 . "Your payment of " . formatMoney($amount) . " has been confirmed via Paystack.\n\n"
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
        error_log('Webhook payment email failed: ' . $e->getMessage());
    }

    try {
        $sms_text = "Payment confirmed! Booking {$booking['booking_reference']}. Amount: " . formatMoney($amount) . ". Receipt: {$receipt_number}. Thank you - " . APP_NAME;

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
        error_log('Webhook payment SMS failed: ' . $e->getMessage());
    }
}
