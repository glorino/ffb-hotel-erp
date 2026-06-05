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
    $status     = $_GET['status'] ?? '';

    $where  = "WHERE DATE(b.created_at) BETWEEN ? AND ?";
    $params = [$start_date, $end_date];

    if ($branch_id) {
        $where .= " AND b.branch_id = ?";
        $params[] = $branch_id;
    }

    if (!empty($status)) {
        $where .= " AND b.booking_status = ?";
        $params[] = $status;
    }

    $totalStmt = $db->prepare("SELECT COUNT(b.id) AS total_bookings, COALESCE(SUM(b.payable_amount), 0) AS total_revenue FROM bookings b {$where}");
    $totalStmt->execute($params);
    $totals = $totalStmt->fetch();

    $byStatusStmt = $db->prepare("SELECT b.booking_status AS status, COUNT(b.id) AS count, COALESCE(SUM(b.payable_amount), 0) AS revenue FROM bookings b {$where} GROUP BY b.booking_status ORDER BY count DESC");
    $byStatusStmt->execute($params);
    $byStatus = $byStatusStmt->fetchAll();

    $bySourceStmt = $db->prepare("SELECT b.source, COUNT(b.id) AS count, COALESCE(SUM(b.payable_amount), 0) AS revenue FROM bookings b {$where} GROUP BY b.source ORDER BY count DESC");
    $bySourceStmt->execute($params);
    $bySource = $bySourceStmt->fetchAll();

    $byRoomTypeStmt = $db->prepare("
        SELECT rt.name AS room_type, COUNT(b.id) AS count, COALESCE(SUM(b.payable_amount), 0) AS revenue
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        {$where}
        GROUP BY rt.id, rt.name
        ORDER BY count DESC
    ");
    $byRoomTypeStmt->execute($params);
    $byRoomType = $byRoomTypeStmt->fetchAll();

    jsonResponse([
        'success'        => true,
        'start_date'     => $start_date,
        'end_date'       => $end_date,
        'total_bookings' => (int) $totals['total_bookings'],
        'total_revenue'  => (float) $totals['total_revenue'],
        'by_status'      => $byStatus,
        'by_source'      => $bySource,
        'by_room_type'   => $byRoomType,
    ]);
} catch (PDOException $e) {
    error_log('Booking report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Booking report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
