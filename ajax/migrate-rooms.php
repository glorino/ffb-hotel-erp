<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

header('Content-Type: application/json');

$room_updates = [
    // Floor 1 - Deluxe
    ['branch_id' => 1, 'room_type_id' => 1, 'room_number' => 'Coral',       'floor' => '1', 'price' => 120000.00],
    ['branch_id' => 1, 'room_type_id' => 1, 'room_number' => 'Jade',        'floor' => '1', 'price' => 120000.00],
    ['branch_id' => 1, 'room_type_id' => 1, 'room_number' => 'Ivory',       'floor' => '1', 'price' => 120000.00],
    ['branch_id' => 1, 'room_type_id' => 1, 'room_number' => 'Pearl',       'floor' => '1', 'price' => 120000.00],
    ['branch_id' => 1, 'room_type_id' => 1, 'room_number' => 'Amber',       'floor' => '1', 'price' => 120000.00],
    // Floor 2 - Executive
    ['branch_id' => 1, 'room_type_id' => 2, 'room_number' => 'Obsidian',    'floor' => '2', 'price' => 180000.00],
    ['branch_id' => 1, 'room_type_id' => 2, 'room_number' => 'Amethyst',    'floor' => '2', 'price' => 180000.00],
    ['branch_id' => 1, 'room_type_id' => 2, 'room_number' => 'Garnet',      'floor' => '2', 'price' => 180000.00],
    ['branch_id' => 1, 'room_type_id' => 2, 'room_number' => 'Jasper',      'floor' => '2', 'price' => 180000.00],
    ['branch_id' => 1, 'room_type_id' => 2, 'room_number' => 'Quartz',      'floor' => '2', 'price' => 180000.00],
    // Floor 3 - Luxury Suite
    ['branch_id' => 1, 'room_type_id' => 3, 'room_number' => 'The Palm Suite',    'floor' => '3', 'price' => 350000.00],
    ['branch_id' => 1, 'room_type_id' => 3, 'room_number' => 'The Lotus Suite',   'floor' => '3', 'price' => 350000.00],
    ['branch_id' => 1, 'room_type_id' => 3, 'room_number' => 'The Orchid Suite',  'floor' => '3', 'price' => 350000.00],
    // Floor 4 - Presidential
    ['branch_id' => 1, 'room_type_id' => 4, 'room_number' => 'The Presidency',    'floor' => '4', 'price' => 600000.00],
    // Floor 5 - Penthouse
    ['branch_id' => 1, 'room_type_id' => 5, 'room_number' => 'The Crown Penthouse','floor' => '5', 'price' => 950000.00],
];

try {
    $db = getDB();

    $existing = $db->query("SELECT COUNT(*) as cnt FROM rooms WHERE branch_id = 1")->fetch();
    $count = (int)$existing['cnt'];

    if ($count < 15) {
        $db->exec("DELETE FROM rooms WHERE branch_id = 1");
        $stmt = $db->prepare("INSERT INTO rooms (branch_id, room_type_id, room_number, floor, price_per_night, status) VALUES (?, ?, ?, ?, ?, 'available')");
        foreach ($room_updates as $r) {
            $stmt->execute([$r['branch_id'], $r['room_type_id'], $r['room_number'], $r['floor'], $r['price']]);
        }
        echo json_encode(['ok' => true, 'action' => 'seeded', 'rooms' => count($room_updates)]);
    } else {
        $updated = 0;
        $stmt = $db->prepare("UPDATE rooms SET room_number = ?, floor = ?, price_per_night = ? WHERE branch_id = ? AND room_type_id = ? AND price_per_night != ?");
        foreach ($room_updates as $r) {
            $stmt->execute([$r['room_number'], $r['floor'], $r['price'], $r['branch_id'], $r['room_type_id'], $r['price']]);
            $updated += $stmt->rowCount();
        }
        echo json_encode(['ok' => true, 'action' => 'updated', 'changed' => $updated]);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
