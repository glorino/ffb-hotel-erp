<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();

    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date   = $_GET['end_date'] ?? date('Y-m-t');
    $branch_id  = !empty($_GET['branch_id']) ? (int) $_GET['branch_id'] : null;

    $roomWhere  = "WHERE r.status = 'available'";
    $roomParams = [];

    if ($branch_id) {
        $roomWhere .= " AND r.branch_id = ?";
        $roomParams[] = $branch_id;
    }

    $totalStmt = $db->prepare("SELECT COUNT(r.id) AS total_rooms FROM rooms r {$roomWhere}");
    $totalStmt->execute($roomParams);
    $totalRooms = (int) $totalStmt->fetch()['total_rooms'];

    $bookingWhere  = "WHERE b.booking_status IN ('checked_in', 'confirmed') AND b.check_in_date <= ? AND b.check_out_date >= ?";
    $bookingParams = [$end_date, $start_date];

    if ($branch_id) {
        $bookingWhere .= " AND b.branch_id = ?";
        $bookingParams[] = $branch_id;
    }

    $occupiedStmt = $db->prepare("SELECT COUNT(DISTINCT b.room_id) AS occupied_rooms FROM bookings b {$bookingWhere}");
    $occupiedStmt->execute($bookingParams);
    $occupiedRooms = (int) $occupiedStmt->fetch()['occupied_rooms'];

    $availableRooms = max(0, $totalRooms - $occupiedRooms);
    $occupancyRate  = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

    $byRoomTypeStmt = $db->prepare("
        SELECT rt.name AS room_type, COUNT(DISTINCT r.id) AS total, COUNT(DISTINCT b.room_id) AS occupied
        FROM room_types rt
        JOIN rooms r ON r.room_type_id = rt.id
        LEFT JOIN bookings b ON b.room_id = r.id AND b.booking_status IN ('checked_in', 'confirmed') AND b.check_in_date <= ? AND b.check_out_date >= ?
        WHERE r.status = 'available'
        GROUP BY rt.id, rt.name
        ORDER BY rt.name
    ");
    $byRoomTypeParams = [$end_date, $start_date];

    if ($branch_id) {
        $byRoomTypeStmt = $db->prepare("
            SELECT rt.name AS room_type, COUNT(DISTINCT r.id) AS total, COUNT(DISTINCT b.room_id) AS occupied
            FROM room_types rt
            JOIN rooms r ON r.room_type_id = rt.id AND r.branch_id = ?
            LEFT JOIN bookings b ON b.room_id = r.id AND b.booking_status IN ('checked_in', 'confirmed') AND b.check_in_date <= ? AND b.check_out_date >= ?
            WHERE r.status = 'available'
            GROUP BY rt.id, rt.name
            ORDER BY rt.name
        ");
        $byRoomTypeParams = [$branch_id, $end_date, $start_date];
    }

    $byRoomTypeStmt->execute($byRoomTypeParams);
    $byRoomType = $byRoomTypeStmt->fetchAll();

    foreach ($byRoomType as &$rt) {
        $rt['total']     = (int) $rt['total'];
        $rt['occupied']  = (int) $rt['occupied'];
        $rt['available'] = max(0, $rt['total'] - $rt['occupied']);
        $rt['rate']      = $rt['total'] > 0 ? round(($rt['occupied'] / $rt['total']) * 100, 2) : 0;
    }
    unset($rt);

    $monthlyStmt = $db->prepare("
        SELECT TO_CHAR(b.check_in_date, 'YYYY-MM') AS month,
               COUNT(DISTINCT b.room_id) AS occupied_rooms
        FROM bookings b
        WHERE b.booking_status IN ('checked_in', 'confirmed')
          AND b.check_in_date <= ? AND b.check_out_date >= ?
        GROUP BY month
        ORDER BY month ASC
    ");
    $monthlyParams = [$end_date, $start_date];

    if ($branch_id) {
        $monthlyStmt .= " AND b.branch_id = ?";
        $monthlyParams[] = $branch_id;
    }

    // Rebuild for monthly with branch
    if ($branch_id) {
        $monthlyStmt = $db->prepare("
            SELECT TO_CHAR(b.check_in_date, 'YYYY-MM') AS month,
                   COUNT(DISTINCT b.room_id) AS occupied_rooms
            FROM bookings b
            WHERE b.booking_status IN ('checked_in', 'confirmed')
              AND b.check_in_date <= ? AND b.check_out_date >= ?
              AND b.branch_id = ?
            GROUP BY month
            ORDER BY month ASC
        ");
        $monthlyParams = [$end_date, $start_date, $branch_id];
    } else {
        $monthlyStmt = $db->prepare("
            SELECT TO_CHAR(b.check_in_date, 'YYYY-MM') AS month,
                   COUNT(DISTINCT b.room_id) AS occupied_rooms
            FROM bookings b
            WHERE b.booking_status IN ('checked_in', 'confirmed')
              AND b.check_in_date <= ? AND b.check_out_date >= ?
            GROUP BY month
            ORDER BY month ASC
        ");
        $monthlyParams = [$end_date, $start_date];
    }
    $monthlyStmt->execute($monthlyParams);
    $monthly = $monthlyStmt->fetchAll();

    foreach ($monthly as &$m) {
        $m['occupied'] = (int) $m['occupied_rooms'];
        $m['available'] = $availableRooms;
        $m['rate']      = $totalRooms > 0 ? round(($m['occupied'] / $totalRooms) * 100, 2) : 0;
    }
    unset($m);

    jsonResponse([
        'success'         => true,
        'start_date'      => $start_date,
        'end_date'        => $end_date,
        'total_rooms'     => $totalRooms,
        'occupied_rooms'  => $occupiedRooms,
        'available_rooms' => $availableRooms,
        'occupancy_rate'  => $occupancyRate,
        'by_room_type'    => $byRoomType,
        'monthly'         => $monthly,
    ]);
} catch (PDOException $e) {
    error_log('Occupancy report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Occupancy report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
