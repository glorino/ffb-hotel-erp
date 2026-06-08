<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$branch_id = isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : 0;

if (!$branch_id) {
    echo json_encode(['success' => false, 'services' => []]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, name, description, price, category, image
        FROM services
        WHERE branch_id = ? AND status = 'active'
        ORDER BY category, name
    ");
    $stmt->execute([$branch_id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'services' => $services]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'services' => []]);
}
