<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ../stock-items.php');
    exit;
}

require_once __DIR__ . '/../../includes/csrf.php';
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ../stock-items.php?error=invalid_token');
    exit;
}

$db = getDB();
$item_id = (int)($_POST['id'] ?? 0);

if (!$item_id) {
    header('Location: ../stock-items.php?error=invalid_id');
    exit;
}

$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$unit = $_POST['unit'] ?? 'pieces';
$quantity = (float)($_POST['quantity'] ?? 0);
$reorder_level = (float)($_POST['reorder_level'] ?? 10);
$price_per_unit = (float)($_POST['price_per_unit'] ?? 0);
$supplier_id = (int)($_POST['supplier_id'] ?? 0) ?: null;
$status = $_POST['status'] ?? 'active';

if (empty($name)) {
    header('Location: ../stock-items.php?error=name_required');
    exit;
}

if (!in_array($status, ['active', 'inactive'])) $status = 'active';

try {
    $stmt = $db->prepare("UPDATE inventory_items SET name = ?, category = ?, unit = ?, quantity = ?, reorder_level = ?, price_per_unit = ?, supplier_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$name, $category, $unit, $quantity, $reorder_level, $price_per_unit, $supplier_id, $status, $item_id]);
    header('Location: ../stock-items.php?success=updated');
} catch (Exception $e) {
    header('Location: ../stock-items.php?error=failed');
}
exit;
