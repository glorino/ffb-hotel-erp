<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();

    $session_id     = (int) ($_GET['session_id'] ?? 0);
    $last_message_id = (int) ($_GET['last_message_id'] ?? 0);

    if ($session_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Session ID is required']);
    }

    $sessionStmt = $db->prepare("SELECT id, status FROM live_chat_sessions WHERE id = ?");
    $sessionStmt->execute([$session_id]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        jsonResponse(['success' => false, 'message' => 'Chat session not found']);
    }

    $sql = "SELECT id, sender_type, sender_id, message, is_read, created_at FROM live_chat_messages WHERE session_id = ?";
    $params = [$session_id];

    if ($last_message_id > 0) {
        $sql .= " AND id > ?";
        $params[] = $last_message_id;
    }

    $sql .= " ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    $formatted = [];
    foreach ($messages as $msg) {
        $formatted[] = [
            'id'          => (int) $msg['id'],
            'sender_type' => $msg['sender_type'],
            'sender_id'   => $msg['sender_id'] ? (int) $msg['sender_id'] : null,
            'message'     => $msg['message'],
            'is_read'     => (bool) $msg['is_read'],
            'created_at'  => $msg['created_at'],
            'time_ago'    => timeAgo($msg['created_at']),
        ];
    }

    $updateStmt = $db->prepare("UPDATE live_chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type IN ('customer', 'visitor') AND is_read = 0");
    $updateStmt->execute([$session_id]);

    $unreadStmt = $db->prepare("SELECT COUNT(*) as unread FROM live_chat_messages WHERE session_id = ? AND is_read = 0");
    $unreadStmt->execute([$session_id]);

    jsonResponse([
        'success'            => true,
        'session_id'         => $session_id,
        'session_status'     => $session['status'],
        'messages'           => $formatted,
        'unread_count'       => (int) $unreadStmt->fetch()['unread'],
        'total_messages'     => count($formatted),
    ]);
} catch (PDOException $e) {
    error_log('Live chat fetch messages error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Live chat fetch messages error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
