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

    $branchesStmt = $db->query("SELECT id, name, city FROM branches WHERE status = 'active' ORDER BY name");
    $branches = $branchesStmt->fetchAll();

    $comparison = [];

    foreach ($branches as $branch) {
        $branchId = (int) $branch['id'];

        $revenueStmt = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0) AS revenue, COUNT(p.id) AS transactions
            FROM payments p
            LEFT JOIN bookings b ON p.booking_id = b.id
            WHERE p.status = 'paid' AND b.branch_id = ? AND DATE(p.created_at) BETWEEN ? AND ?
        ");
        $revenueStmt->execute([$branchId, $start_date, $end_date]);
        $revenueData = $revenueStmt->fetch();

        $bookingStmt = $db->prepare("
            SELECT COUNT(id) AS bookings, COALESCE(SUM(payable_amount), 0) AS revenue
            FROM bookings
            WHERE branch_id = ? AND DATE(created_at) BETWEEN ? AND ?
        ");
        $bookingStmt->execute([$branchId, $start_date, $end_date]);
        $bookingData = $bookingStmt->fetch();

        $occupancyStmt = $db->prepare("
            SELECT COUNT(r.id) AS total_rooms,
                   (SELECT COUNT(DISTINCT b2.room_id) FROM bookings b2 WHERE b2.branch_id = ? AND b2.booking_status IN ('checked_in', 'confirmed') AND b2.check_in_date <= ? AND b2.check_out_date >= ?) AS occupied
            FROM rooms r
            WHERE r.branch_id = ? AND r.status = 'available'
        ");
        $occupancyStmt->execute([$branchId, $end_date, $start_date, $branchId]);
        $occupancyData = $occupancyStmt->fetch();

        $totalRooms    = (int) $occupancyData['total_rooms'];
        $occupiedRooms = (int) $occupancyData['occupied'];
        $occRate       = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        $ordersStmt = $db->prepare("
            SELECT COUNT(id) AS total_orders, COALESCE(SUM(payable_amount), 0) AS revenue
            FROM food_orders
            WHERE branch_id = ? AND DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'
        ");
        $ordersStmt->execute([$branchId, $start_date, $end_date]);
        $ordersData = $ordersStmt->fetch();

        $comparison[] = [
            'branch_id'    => $branchId,
            'branch_name'  => $branch['name'],
            'city'         => $branch['city'],
            'revenue'      => [
                'total'        => (float) $revenueData['revenue'],
                'transactions' => (int) $revenueData['transactions'],
            ],
            'bookings'     => [
                'count'    => (int) $bookingData['bookings'],
                'revenue'  => (float) $bookingData['revenue'],
            ],
            'occupancy'    => [
                'total_rooms' => $totalRooms,
                'occupied'    => $occupiedRooms,
                'rate'        => $occRate,
            ],
            'orders'       => [
                'count'   => (int) $ordersData['total_orders'],
                'revenue' => (float) $ordersData['revenue'],
            ],
        ];
    }

    $totalRevenue = array_sum(array_column(array_column($comparison, 'revenue'), 'total'));
    $totalBookings = array_sum(array_column(array_column($comparison, 'bookings'), 'count'));
    $totalOccupied = array_sum(array_column(array_column($comparison, 'occupancy'), 'occupied'));
    $totalRoomsAll = array_sum(array_column(array_column($comparison, 'occupancy'), 'total_rooms'));
    $totalOrders   = array_sum(array_column(array_column($comparison, 'orders'), 'count'));
    $totalOrderRev = array_sum(array_column(array_column($comparison, 'orders'), 'revenue'));

    jsonResponse([
        'success'     => true,
        'start_date'  => $start_date,
        'end_date'    => $end_date,
        'branches'    => $comparison,
        'totals'      => [
            'revenue'      => $totalRevenue,
            'bookings'     => $totalBookings,
            'occupancy_rate' => $totalRoomsAll > 0 ? round(($totalOccupied / $totalRoomsAll) * 100, 2) : 0,
            'orders'       => $totalOrders,
            'order_revenue' => $totalOrderRev,
        ],
    ]);
} catch (PDOException $e) {
    error_log('Branch report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Branch report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
