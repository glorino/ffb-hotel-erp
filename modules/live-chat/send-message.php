<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();

    $session_id = (int) ($_POST['session_id'] ?? 0);
    $sender_type = $_POST['sender_type'] ?? 'customer';
    $sender_id   = !empty($_POST['sender_id']) ? (int) $_POST['sender_id'] : null;
    $message     = trim($_POST['message'] ?? '');

    if ($session_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Session ID is required']);
    }

    if (empty($message)) {
        jsonResponse(['success' => false, 'message' => 'Message cannot be empty']);
    }

    if (!in_array($sender_type, ['customer', 'visitor', 'receptionist', 'system'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid sender type']);
    }

    $sessionStmt = $db->prepare("SELECT id, status FROM live_chat_sessions WHERE id = ?");
    $sessionStmt->execute([$session_id]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        jsonResponse(['success' => false, 'message' => 'Chat session not found']);
    }

    if ($session['status'] !== 'active') {
        jsonResponse(['success' => false, 'message' => 'This chat session is closed']);
    }

    $stmt = $db->prepare("INSERT INTO live_chat_messages (session_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$session_id, $sender_type, $sender_id, $message]);

    $messageId = $db->lastInsertId();

    $createdAt = date('Y-m-d H:i:s');

    jsonResponse([
        'success'    => true,
        'message'    => 'Message sent',
        'message_id' => (int) $messageId,
        'created_at' => $createdAt,
    ]);
} catch (PDOException $e) {
    error_log('Live chat send message error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Live chat send message error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
