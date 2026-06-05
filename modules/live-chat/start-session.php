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

    $visitor_name  = trim($_POST['visitor_name'] ?? '');
    $visitor_email = trim($_POST['visitor_email'] ?? '');
    $visitor_phone = trim($_POST['visitor_phone'] ?? '');
    $customer_id   = !empty($_POST['customer_id']) ? (int) $_POST['customer_id'] : null;

    if (empty($visitor_name)) {
        jsonResponse(['success' => false, 'message' => 'Visitor name is required']);
    }

    $assigned_to = null;

    $receptionistStmt = $db->prepare("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = 'receptionist' AND u.status = 'active' ORDER BY u.last_login ASC LIMIT 1");
    $receptionistStmt->execute();
    $receptionist = $receptionistStmt->fetch();

    if ($receptionist) {
        $busyStmt = $db->prepare("SELECT COUNT(*) as active_sessions FROM live_chat_sessions WHERE assigned_to = ? AND status = 'active'");
        $busyStmt->execute([$receptionist['id']]);
        if ((int)$busyStmt->fetch()['active_sessions'] < 5) {
            $assigned_to = (int) $receptionist['id'];
        }
    }

    $stmt = $db->prepare("INSERT INTO live_chat_sessions (customer_id, visitor_name, visitor_email, visitor_phone, assigned_to, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$customer_id, $visitor_name, $visitor_email, $visitor_phone, $assigned_to]);

    $sessionId = $db->lastInsertId();

    if ($assigned_to) {
        $msgStmt = $db->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message) VALUES (?, 'system', ?)");
        $msgStmt->execute([$sessionId, 'A receptionist has been assigned to assist you.']);
    }

    jsonResponse([
        'success'    => true,
        'message'    => 'Chat session started',
        'session_id' => (int) $sessionId,
        'status'     => 'active',
        'assigned_to' => $assigned_to,
    ]);
} catch (PDOException $e) {
    error_log('Live chat start session error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Live chat start session error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
