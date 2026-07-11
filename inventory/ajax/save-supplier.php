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
$branch_id = $_SESSION['branch_id'] ?? 0;

if (!$branch_id) {
    header('Location: ../suppliers.php?error=no_branch');
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
    $stmt = $db->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, branch_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $contact_person, $phone, $email, $address, $branch_id]);
    header('Location: ../suppliers.php?success=added');
} catch (Exception $e) {
    header('Location: ../suppliers.php?error=failed');
}
exit;
