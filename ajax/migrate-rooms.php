<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$room_updates = [
    ['id' => 1,  'room_number' => 'Coral',             'floor' => '1', 'price' => 120000.00],
    ['id' => 2,  'room_number' => 'Jade',              'floor' => '1', 'price' => 120000.00],
    ['id' => 3,  'room_number' => 'Ivory',             'floor' => '1', 'price' => 120000.00],
    ['id' => 4,  'room_number' => 'Pearl',             'floor' => '1', 'price' => 120000.00],
    ['id' => 5,  'room_number' => 'Amber',             'floor' => '1', 'price' => 120000.00],
    ['id' => 6,  'room_number' => 'Obsidian',          'floor' => '2', 'price' => 180000.00],
    ['id' => 7,  'room_number' => 'Amethyst',          'floor' => '2', 'price' => 180000.00],
    ['id' => 8,  'room_number' => 'Garnet',            'floor' => '2', 'price' => 180000.00],
    ['id' => 9,  'room_number' => 'Jasper',            'floor' => '2', 'price' => 180000.00],
    ['id' => 10, 'room_number' => 'Quartz',            'floor' => '2', 'price' => 180000.00],
    ['id' => 11, 'room_number' => 'The Palm Suite',    'floor' => '3', 'price' => 350000.00],
    ['id' => 12, 'room_number' => 'The Lotus Suite',   'floor' => '3', 'price' => 350000.00],
    ['id' => 13, 'room_number' => 'The Orchid Suite',  'floor' => '3', 'price' => 350000.00],
    ['id' => 14, 'room_number' => 'The Presidency',    'floor' => '4', 'price' => 600000.00],
    ['id' => 15, 'room_number' => 'The Crown Penthouse','floor' => '5', 'price' => 950000.00],
];

try {
    $db = getDB();

    $existing = $db->query("SELECT COUNT(*) as cnt FROM rooms")->fetch();
    $count = (int)$existing['cnt'];

    if ($count === 0) {
        $stmt = $db->prepare("INSERT INTO rooms (branch_id, room_type_id, room_number, floor, price_per_night, status) VALUES (1, ?, ?, ?, ?, 'available')");
        $typeMap = [1=>1,2=>2,3=>3,4=>4,5=>5,6=>2,7=>2,8=>2,9=>2,10=>2,11=>3,12=>3,13=>3,14=>4,15=>5];
        foreach ($room_updates as $r) {
            $stmt->execute([$typeMap[$r['id']], $r['room_number'], $r['floor'], $r['price']]);
        }
        echo json_encode(['ok' => true, 'action' => 'seeded', 'rooms' => count($room_updates)]);
    } else {
        $updated = 0;
        $stmt = $db->prepare("UPDATE rooms SET room_number = ?, floor = ?, price_per_night = ? WHERE id = ?");
        foreach ($room_updates as $r) {
            $stmt->execute([$r['room_number'], $r['floor'], $r['price'], $r['id']]);
            $updated += $stmt->rowCount();
        }
        echo json_encode(['ok' => true, 'action' => 'updated', 'changed' => $updated, 'total' => $count]);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
