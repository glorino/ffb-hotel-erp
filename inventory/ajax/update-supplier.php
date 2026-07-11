<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/role-check.php';
checkRole(['inventory_manager', 'admin', 'branch_manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ../suppliers.php');
    exit;
}

require_once __DIR__ . '/../../includes/csrf.php';
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ../suppliers.php?error=invalid_token');
    exit;
}

$db = getDB();
$supplier_id = (int)($_POST['id'] ?? 0);

if (!$supplier_id) {
    header('Location: ../suppliers.php?error=invalid_id');
    exit;
}

$name = trim($_POST['name'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($name)) {
    header('Location: ../suppliers.php?error=name_required');
    exit;
}

try {
    $stmt = $db->prepare("UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?");
    $stmt->execute([$name, $contact_person, $phone, $email, $address, $supplier_id]);
    header('Location: ../suppliers.php?success=updated');
} catch (Exception $e) {
    header('Location: ../suppliers.php?error=failed');
}
exit;
