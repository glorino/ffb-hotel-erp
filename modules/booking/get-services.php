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

    if (empty($services)) {
        $services = [
            ['id' => -1, 'name' => 'Airport Transfer', 'description' => 'Round-trip airport pickup and drop-off in a luxury sedan', 'price' => 15000, 'category' => 'transport', 'image' => null],
            ['id' => -2, 'name' => 'Room Service (Breakfast)', 'description' => 'Continental or Full English breakfast served to your room', 'price' => 5000, 'category' => 'dining', 'image' => null],
            ['id' => -3, 'name' => 'Room Service (Lunch/Dinner)', 'description' => 'Three-course meal from our à la carte menu delivered to your room', 'price' => 12000, 'category' => 'dining', 'image' => null],
            ['id' => -4, 'name' => 'Mini Bar Restock', 'description' => 'Fully restocked mini bar with premium beverages and snacks', 'price' => 8000, 'category' => 'dining', 'image' => null],
            ['id' => -5, 'name' => 'Spa & Massage (60 min)', 'description' => 'Full body relaxation massage with aromatherapy oils', 'price' => 25000, 'category' => 'wellness', 'image' => null],
            ['id' => -6, 'name' => 'Spa & Massage (90 min)', 'description' => 'Deep tissue massage with hot stone therapy', 'price' => 35000, 'category' => 'wellness', 'image' => null],
            ['id' => -7, 'name' => 'Laundry & Dry Cleaning', 'description' => 'Same-day laundry and dry cleaning service', 'price' => 3000, 'category' => 'comfort', 'image' => null],
            ['id' => -8, 'name' => 'Extra Bed / Crib', 'description' => 'Additional single bed or baby crib set up in your room', 'price' => 5000, 'category' => 'comfort', 'image' => null],
            ['id' => -9, 'name' => 'Late Check-Out (2 PM)', 'description' => 'Extended check-out until 2:00 PM (subject to availability)', 'price' => 7000, 'category' => 'convenience', 'image' => null],
            ['id' => -10, 'name' => 'Early Check-In (10 AM)', 'description' => 'Early room access from 10:00 AM (subject to availability)', 'price' => 7000, 'category' => 'convenience', 'image' => null],
            ['id' => -11, 'name' => 'Welcome Champagne', 'description' => 'Bottle of premium champagne waiting in your room on arrival', 'price' => 18000, 'category' => 'special', 'image' => null],
            ['id' => -12, 'name' => 'Flowers & Room Decoration', 'description' => 'Romantic room setup with fresh flowers and candlelight', 'price' => 15000, 'category' => 'special', 'image' => null],
            ['id' => -13, 'name' => 'Private Dining (2 Guests)', 'description' => 'Exclusive candlelit dinner set up on your balcony or in the garden', 'price' => 40000, 'category' => 'dining', 'image' => null],
            ['id' => -14, 'name' => 'Gym Day Pass', 'description' => 'Full day access to our state-of-the-art fitness centre', 'price' => 3000, 'category' => 'wellness', 'image' => null],
            ['id' => -15, 'name' => 'Swimming Pool Access', 'description' => 'Premium poolside cabana with towel and refreshment service', 'price' => 4000, 'category' => 'wellness', 'image' => null],
        ];
    }

    echo json_encode(['success' => true, 'services' => $services]);
} catch (Exception $e) {
    $services = [
        ['id' => -1, 'name' => 'Airport Transfer', 'description' => 'Round-trip airport pickup and drop-off in a luxury sedan', 'price' => 10000, 'category' => 'transport', 'image' => null],
        ['id' => -2, 'name' => 'Room Service (Breakfast)', 'description' => 'Continental or Full English breakfast served to your room', 'price' => 5000, 'category' => 'dining', 'image' => null],
        ['id' => -3, 'name' => 'Spa & Massage', 'description' => 'Full body relaxation massage with aromatherapy oils', 'price' => 15000, 'category' => 'wellness', 'image' => null],
        ['id' => -4, 'name' => 'Laundry Service', 'description' => 'Same-day laundry and dry cleaning service', 'price' => 3000, 'category' => 'comfort', 'image' => null],
        ['id' => -5, 'name' => 'Late Check-Out', 'description' => 'Extended check-out until 2:00 PM', 'price' => 5000, 'category' => 'convenience', 'image' => null],
    ];
    echo json_encode(['success' => true, 'services' => $services]);
}
