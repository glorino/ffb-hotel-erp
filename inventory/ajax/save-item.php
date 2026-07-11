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
$branch_id = $_SESSION['branch_id'] ?? 0;

if (!$branch_id) {
    header('Location: ../stock-items.php?error=no_branch');
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
    $stmt = $db->prepare("INSERT INTO inventory_items (branch_id, name, category, unit, quantity, reorder_level, price_per_unit, supplier_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$branch_id, $name, $category, $unit, $quantity, $reorder_level, $price_per_unit, $supplier_id, $status]);
    header('Location: ../stock-items.php?success=added');
} catch (Exception $e) {
    header('Location: ../stock-items.php?error=failed');
}
exit;
