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

    $to      = trim($_POST['to'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($to) || empty($message)) {
        jsonResponse(['sent' => false, 'message' => 'Phone number and message are required']);
    }

    if (empty(TERMII_API_KEY) || TERMII_API_KEY === 'your_termii_api_key_here') {
        jsonResponse(['sent' => false, 'message' => 'Termii API key is not configured']);
    }

    $payload = [
        'api_key' => TERMII_API_KEY,
        'to'      => $to,
        'from'    => TERMII_SENDER_ID,
        'sms'     => $message,
        'type'    => 'plain',
        'channel' => 'generic',
    ];

    $ch = curl_init(TERMII_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('Termii cURL error: ' . $curlError);
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_type, reference_id) VALUES (?, ?, ?, 'sms', ?, ?)");
        $notifStmt->execute([$_SESSION['user_id'] ?? 0, 'SMS Failed', $message, 'sms_send', 0]);

        jsonResponse(['sent' => false, 'message' => 'SMS service unreachable: ' . $curlError]);
    }

    $result = json_decode($response, true);
    $sent    = $httpCode === 200 && isset($result['message_id']);

    $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_type, reference_id) VALUES (?, ?, ?, 'sms', ?, ?)");
    $notifStmt->execute([
        $_SESSION['user_id'] ?? 0,
        $sent ? 'SMS Sent' : 'SMS Failed',
        $message,
        'sms_send',
        0,
    ]);

    if ($sent) {
        jsonResponse(['sent' => true, 'message' => 'SMS sent successfully', 'message_id' => $result['message_id'] ?? null]);
    } else {
        $errMsg = $result['message'] ?? 'Failed to send SMS';
        jsonResponse(['sent' => false, 'message' => $errMsg]);
    }
} catch (PDOException $e) {
    error_log('Send SMS error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Send SMS error: ' . $e->getMessage());
    jsonResponse(['sent' => false, 'message' => 'An unexpected error occurred'], 500);
}
