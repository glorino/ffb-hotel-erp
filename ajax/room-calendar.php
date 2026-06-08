<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$month = (int) ($_GET['month'] ?? date('m'));
$year  = (int) ($_GET['year']  ?? date('Y'));

if ($month < 1 || $month > 12) $month = (int) date('m');
if ($year < 2020 || $year > 2030) $year = (int) date('Y');

try {
    $db = getDB();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

try {
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));
    $days_in_month = (int) date('t', strtotime($start_date));

    $stmt = $db->prepare("
        SELECT r.id, r.room_number, r.status AS room_status, r.price_per_night,
               rt.name AS type_name, rt.base_price
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE rt.status = 'active'
        ORDER BY rt.base_price, r.room_number
    ");
    $stmt->execute();
    $rooms = $stmt->fetchAll();

    $room_ids = array_column($rooms, 'id');
    $bookings = [];

    if (!empty($room_ids)) {
        $placeholders = implode(',', array_fill(0, count($room_ids), '?'));
        $bsql = "SELECT room_id, check_in_date, check_out_date, booking_status
                 FROM bookings
                 WHERE room_id IN ({$placeholders})
                   AND booking_status NOT IN ('cancelled', 'checked_out', 'no_show')
                   AND check_in_date <= ?
                   AND check_out_date >= ?";
        $bparams = array_merge($room_ids, [$end_date, $start_date]);
        $stmt = $db->prepare($bsql);
        $stmt->execute($bparams);
        $bookings = $stmt->fetchAll();
    }

    $today = date('Y-m-d');
    $month_summary = [];

    $calendar = [];
    foreach ($rooms as $room) {
        $rid = (int) $room['id'];
        $room_statuses = [];

        $cal_room = [
            'id' => $rid,
            'room_number' => $room['room_number'],
            'type_name' => $room['type_name'],
            'price' => (float) ($room['price_per_night'] ?: $room['base_price']),
            'status' => $room['room_status'],
            'days' => [],
        ];

        for ($d = 1; $d <= $days_in_month; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $day_status = 'available';

            if (in_array($room['room_status'], ['maintenance', 'out_of_service'])) {
                $day_status = 'unavailable';
            }

            foreach ($bookings as $b) {
                if ((int) $b['room_id'] !== $rid) continue;
                if ($date >= $b['check_in_date'] && $date < $b['check_out_date']) {
                    if ($b['booking_status'] === 'checked_in') {
                        $day_status = 'occupied';
                    } else {
                        $day_status = 'reserved';
                    }
                }
            }

            if ($date < $today && $day_status === 'available') {
                $day_status = 'past';
            }

            $cal_room['days'][$d] = $day_status;
            if ($day_status !== 'past') {
                $room_statuses[] = $day_status;
            }
        }

        $priority = ['unavailable' => 4, 'occupied' => 3, 'reserved' => 2, 'available' => 1];
        $worst = 'available';
        foreach ($room_statuses as $rs) {
            if (($priority[$rs] ?? 0) > ($priority[$worst] ?? 0)) {
                $worst = $rs;
            }
        }
        $month_summary[$worst] = ($month_summary[$worst] ?? 0) + 1;

        $calendar[] = $cal_room;
    }

    echo json_encode([
        'success'    => true,
        'year'       => $year,
        'month'      => $month,
        'days'       => $days_in_month,
        'month_name' => date('F', strtotime($start_date)),
        'rooms'      => $calendar,
        'summary'    => array_merge(['available' => 0, 'reserved' => 0, 'occupied' => 0, 'unavailable' => 0], $month_summary),
    ]);

} catch (Exception $e) {
    error_log('Calendar data error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load calendar data']);
}
