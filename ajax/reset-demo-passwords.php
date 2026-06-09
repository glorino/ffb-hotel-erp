<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $demo_hash = password_hash('demo1234', PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?");
    $stmt->execute([$demo_hash]);
    echo json_encode(['ok' => true, 'updated' => $stmt->rowCount()]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
