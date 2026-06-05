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

    $session_id      = (int) ($_POST['session_id'] ?? 0);
    $receptionist_id = (int) ($_POST['receptionist_id'] ?? 0);

    if ($session_id <= 0 || $receptionist_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Session ID and Receptionist ID are required']);
    }

    $sessionStmt = $db->prepare("SELECT id, assigned_to, status FROM live_chat_sessions WHERE id = ?");
    $sessionStmt->execute([$session_id]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        jsonResponse(['success' => false, 'message' => 'Chat session not found']);
    }

    $userStmt = $db->prepare("SELECT u.id, r.slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.status = 'active'");
    $userStmt->execute([$receptionist_id]);
    $user = $userStmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Receptionist not found or inactive']);
    }

    if ($user['slug'] !== 'receptionist') {
        jsonResponse(['success' => false, 'message' => 'Selected user is not a receptionist']);
    }

    $oldAssignedTo = $session['assigned_to'];

    $stmt = $db->prepare("UPDATE live_chat_sessions SET assigned_to = ? WHERE id = ?");
    $stmt->execute([$receptionist_id, $session_id]);

    $msgStmt = $db->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message) VALUES (?, 'system', ?)");
    $msgStmt->execute([$session_id, 'A receptionist has been assigned to this chat.']);

    log_audit('assign_receptionist', 'live_chat_session', $session_id, 
        $oldAssignedTo ? ['assigned_to' => $oldAssignedTo] : null,
        ['assigned_to' => $receptionist_id]
    );

    jsonResponse([
        'success'         => true,
        'message'         => 'Receptionist assigned successfully',
        'session_id'      => $session_id,
        'receptionist_id' => $receptionist_id,
    ]);
} catch (PDOException $e) {
    error_log('Live chat assign error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Live chat assign error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
