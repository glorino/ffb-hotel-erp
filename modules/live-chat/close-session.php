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

    if ($session_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Session ID is required']);
    }

    $sessionStmt = $db->prepare("SELECT id, status FROM live_chat_sessions WHERE id = ?");
    $sessionStmt->execute([$session_id]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        jsonResponse(['success' => false, 'message' => 'Chat session not found']);
    }

    if ($session['status'] === 'closed') {
        jsonResponse(['success' => false, 'message' => 'This session is already closed']);
    }

    $stmt = $db->prepare("UPDATE live_chat_sessions SET status = 'closed', closed_at = NOW() WHERE id = ?");
    $stmt->execute([$session_id]);

    $msgStmt = $db->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message) VALUES (?, 'system', ?)");
    $msgStmt->execute([$session_id, 'This chat session has been closed.']);

    log_audit('close_session', 'live_chat_session', $session_id, null, [
        'status' => 'closed',
        'closed_at' => date('Y-m-d H:i:s'),
    ]);

    jsonResponse([
        'success'    => true,
        'message'    => 'Chat session closed',
        'session_id' => $session_id,
        'closed_at'  => date('Y-m-d H:i:s'),
    ]);
} catch (PDOException $e) {
    error_log('Live chat close session error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Live chat close session error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
