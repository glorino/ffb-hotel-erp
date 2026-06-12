<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../includes/csrf.php';
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$db = getDB();
$supplier_id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? 'active';

if (!$supplier_id || !in_array($status, ['active', 'inactive'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE suppliers SET status = ? WHERE id = ?");
    $stmt->execute([$status, $supplier_id]);
    echo json_encode(['success' => true, 'status' => $status]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update supplier']);
}
