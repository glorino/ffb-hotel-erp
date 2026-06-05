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

    $where  = "WHERE p.status = 'paid' AND DATE(p.created_at) BETWEEN ? AND ?";
    $params = [$start_date, $end_date];

    if ($branch_id) {
        $where .= " AND (b.branch_id = ? OR b.id IS NULL)";
        $params[] = $branch_id;
    }

    $totalStmt = $db->prepare("SELECT COALESCE(SUM(p.amount), 0) AS total_sales, COUNT(p.id) AS total_transactions FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id {$where}");
    $totalStmt->execute($params);
    $totals = $totalStmt->fetch();

    $byMethodStmt = $db->prepare("SELECT p.method, COALESCE(SUM(p.amount), 0) AS total, COUNT(p.id) AS count FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id {$where} GROUP BY p.method ORDER BY total DESC");
    $byMethodStmt->execute($params);
    $salesByMethod = $byMethodStmt->fetchAll();

    $byCategoryStmt = $db->prepare("
        SELECT 
            CASE 
                WHEN p.booking_id IS NOT NULL THEN 'accommodation'
                WHEN p.order_id IS NOT NULL THEN 'food'
                WHEN p.reservation_id IS NOT NULL THEN 'reservation'
                ELSE 'other'
            END AS category,
            COALESCE(SUM(p.amount), 0) AS total,
            COUNT(p.id) AS count
        FROM payments p 
        {$where} 
        GROUP BY category 
        ORDER BY total DESC
    ");
    $byCategoryStmt->execute($params);
    $salesByCategory = $byCategoryStmt->fetchAll();

    $byBranchStmt = $db->prepare("
        SELECT COALESCE(br.name, 'N/A') AS branch_name, COALESCE(SUM(p.amount), 0) AS total, COUNT(p.id) AS count
        FROM payments p
        LEFT JOIN bookings b ON p.booking_id = b.id
        LEFT JOIN branches br ON b.branch_id = br.id
        {$where}
        GROUP BY br.id, br.name
        ORDER BY total DESC
    ");
    $byBranchStmt->execute($params);
    $salesByBranch = $byBranchStmt->fetchAll();

    $dailyStmt = $db->prepare("
        SELECT DATE(p.created_at) AS date, COALESCE(SUM(p.amount), 0) AS total, COUNT(p.id) AS count
        FROM payments p
        LEFT JOIN bookings b ON p.booking_id = b.id
        {$where}
        GROUP BY DATE(p.created_at)
        ORDER BY date ASC
    ");
    $dailyStmt->execute($params);
    $dailyBreakdown = $dailyStmt->fetchAll();

    jsonResponse([
        'success'          => true,
        'start_date'       => $start_date,
        'end_date'         => $end_date,
        'total_sales'      => (float) $totals['total_sales'],
        'total_transactions'=> (int) $totals['total_transactions'],
        'sales_by_method'  => $salesByMethod,
        'sales_by_category'=> $salesByCategory,
        'sales_by_branch'  => $salesByBranch,
        'daily_breakdown'  => $dailyBreakdown,
    ]);
} catch (PDOException $e) {
    error_log('Sales report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Sales report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
