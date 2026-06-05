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

    $branch_id = !empty($_GET['branch_id']) ? (int) $_GET['branch_id'] : null;
    $category  = $_GET['category'] ?? '';

    $where  = "WHERE i.status = 'active'";
    $params = [];

    if ($branch_id) {
        $where .= " AND i.branch_id = ?";
        $params[] = $branch_id;
    }

    if (!empty($category)) {
        $where .= " AND i.category = ?";
        $params[] = $category;
    }

    $summaryStmt = $db->prepare("SELECT COUNT(i.id) AS total_items, COALESCE(SUM(i.quantity * i.price_per_unit), 0) AS total_value FROM inventory_items i {$where}");
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();

    $lowStockStmt = $db->prepare("SELECT COUNT(i.id) AS low_stock_count FROM inventory_items i {$where} AND i.quantity <= i.reorder_level");
    $lowStockStmt->execute($params);
    $lowStock = (int) $lowStockStmt->fetch()['low_stock_count'];

    $byCategoryStmt = $db->prepare("
        SELECT i.category, COUNT(i.id) AS count, COALESCE(SUM(i.quantity), 0) AS total_quantity, COALESCE(SUM(i.quantity * i.price_per_unit), 0) AS total_value
        FROM inventory_items i
        {$where}
        GROUP BY i.category
        ORDER BY total_value DESC
    ");
    $byCategoryStmt->execute($params);
    $byCategory = $byCategoryStmt->fetchAll();

    $movementsWhere  = "WHERE sm.created_at >= NOW() - INTERVAL '30 days'";
    $movementsParams = [];

    if ($branch_id) {
        $movementsWhere .= " AND sm.branch_id = ?";
        $movementsParams[] = $branch_id;
    }

    if (!empty($category)) {
        $movementsWhere .= " AND i.category = ?";
        $movementsParams[] = $category;
    }

    $movementsStmt = $db->prepare("
        SELECT sm.type, sm.quantity, sm.notes, sm.created_at, i.name AS item_name
        FROM stock_movements sm
        JOIN inventory_items i ON sm.item_id = i.id
        {$movementsWhere}
        ORDER BY sm.created_at DESC
        LIMIT 100
    ");
    $movementsStmt->execute($movementsParams);
    $movements = $movementsStmt->fetchAll();

    jsonResponse([
        'success'         => true,
        'total_items'     => (int) $summary['total_items'],
        'total_value'     => (float) $summary['total_value'],
        'low_stock_count' => $lowStock,
        'by_category'     => $byCategory,
        'stock_movements' => $movements,
    ]);
} catch (PDOException $e) {
    error_log('Inventory report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Inventory report error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}
