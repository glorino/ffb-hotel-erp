<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$branch_id    = (int) ($_POST['branch_id'] ?? 0);
$room_type_id = (int) ($_POST['room_type_id'] ?? 0);

if (!$branch_id) {
    echo json_encode(['success' => false, 'rooms' => []]);
    exit;
}

try {
    $db = getDB();

    $sql = "SELECT r.id, r.room_number, r.status, rt.name AS type_name, rt.base_price AS price_per_night
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.branch_id = ? AND r.status IN ('available', 'reserved')";

    $params = [$branch_id];

    if ($room_type_id > 0) {
        $sql .= " AND r.room_type_id = ?";
        $params[] = $room_type_id;
    }

    $sql .= " ORDER BY rt.base_price, r.room_number";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rooms' => $rooms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'rooms' => []]);
}
