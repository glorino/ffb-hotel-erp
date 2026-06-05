<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$branch_id      = (int) ($_REQUEST['branch_id'] ?? 0);
$room_type_id   = (int) ($_REQUEST['room_type_id'] ?? 0);
$check_in       = $_REQUEST['check_in'] ?? '';
$check_out      = $_REQUEST['check_out'] ?? '';
$exclude_booking_id = (int) ($_REQUEST['exclude_booking_id'] ?? 0);

if (!$branch_id || !$check_in || !$check_out) {
    jsonResponse(['success' => false, 'message' => 'Branch, check-in and check-out dates are required'], 400);
}

if (!validateDate($check_in) || !validateDate($check_out)) {
    jsonResponse(['success' => false, 'message' => 'Invalid date format'], 400);
}

if (strtotime($check_in) >= strtotime($check_out)) {
    jsonResponse(['success' => false, 'message' => 'Check-out must be after check-in'], 400);
}

$db = getDB();

try {
    $params = [':branch_id' => $branch_id, ':check_in' => $check_in, ':check_out' => $check_out];
    $sql = "
        SELECT r.*, rt.name AS room_type_name, rt.base_price, rt.max_guests, rt.description AS room_type_description
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.branch_id = :branch_id
          AND r.status = 'available'
          AND r.id NOT IN (
              SELECT b.room_id FROM bookings b
              WHERE b.room_id IS NOT NULL
                AND b.booking_status IN ('pending', 'confirmed', 'checked_in')
                AND b.check_in_date < :check_out
                AND b.check_out_date > :check_in
    ";

    if ($exclude_booking_id > 0) {
        $sql .= " AND b.id != :exclude_booking_id";
        $params[':exclude_booking_id'] = $exclude_booking_id;
    }

    $sql .= ")";

    if ($room_type_id > 0) {
        $sql .= " AND r.room_type_id = :room_type_id";
        $params[':room_type_id'] = $room_type_id;
    }

    $sql .= " ORDER BY rt.base_price, r.room_number";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();

    foreach ($rooms as &$room) {
        $room['price_per_night'] = (float) $room['base_price'];
    }
    unset($room);

    $total_available = count($rooms);
    $total_rooms = 0;
    $occupied = 0;

    $countSql = "SELECT COUNT(*) as total FROM rooms WHERE branch_id = ? AND status = 'available'";
    $countParams = [$branch_id];
    if ($room_type_id > 0) {
        $countSql = "SELECT COUNT(*) as total FROM rooms WHERE branch_id = ? AND status = 'available' AND room_type_id = ?";
        $countParams = [$branch_id, $room_type_id];
    }
    $stmt = $db->prepare($countSql);
    $stmt->execute($countParams);
    $total_rooms = (int) $stmt->fetchColumn();

    // Get total capacity reference
    $totalRefSql = "SELECT COUNT(*) FROM rooms WHERE branch_id = ?";
    $refParams = [$branch_id];
    if ($room_type_id > 0) {
        $totalRefSql .= " AND room_type_id = ?";
        $refParams[] = $room_type_id;
    }
    $stmt = $db->prepare($totalRefSql);
    $stmt->execute($refParams);
    $capacity = (int) $stmt->fetchColumn();

    // Suggest alternatives if no rooms available
    $suggestions = [];
    if ($total_available === 0) {
        $suggestSql = "
            SELECT rt.id, rt.name, rt.base_price, COUNT(r.id) as available_count
            FROM room_types rt
            JOIN rooms r ON r.room_type_id = rt.id
            WHERE r.branch_id = ?
              AND r.status = 'available'
              AND r.id NOT IN (
                  SELECT b.room_id FROM bookings b
                  WHERE b.room_id IS NOT NULL
                    AND b.booking_status IN ('pending', 'confirmed', 'checked_in')
                    AND b.check_in_date < ?
                    AND b.check_out_date > ?
              )
            GROUP BY rt.id, rt.name, rt.base_price
            HAVING available_count > 0
            ORDER BY rt.base_price
        ";
        $suggestParams = [$branch_id, $check_out, $check_in];
        if ($room_type_id > 0) {
            $suggestSql .= " AND rt.id != ?";
            $suggestParams[] = $room_type_id;
        }
        $stmt = $db->prepare($suggestSql);
        $stmt->execute($suggestParams);
        $suggestions = $stmt->fetchAll();
    }

    jsonResponse([
        'success'         => true,
        'count'           => $total_available,
        'rooms'           => $rooms,
        'capacity'        => $capacity,
        'occupied'        => $capacity - $total_available,
        'suggestions'     => $suggestions,
        'check_in'        => $check_in,
        'check_out'       => $check_out,
        'branch_id'       => $branch_id,
        'room_type_id'    => $room_type_id,
    ]);

} catch (Exception $e) {
    error_log('Check availability error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to check availability'], 500);
}
