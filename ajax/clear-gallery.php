<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM gallery_items WHERE id <= 6");
    $stmt->execute();
    echo json_encode(['ok' => true, 'deleted' => $stmt->rowCount()]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
