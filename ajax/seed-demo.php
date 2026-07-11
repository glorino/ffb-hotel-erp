<?php
/**
 * FFB Hotel - Demo Users Seed (web-accessible)
 * Access: /ajax/seed-demo.php
 * Creates demo login accounts for all roles with password: demo1234
 * Only run once - skips existing users
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDB();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$demo_password = password_hash('demo1234', PASSWORD_DEFAULT);

$demo_users = [
    ['full_name' => 'Emeka Okonkwo', 'email' => 'owner@ffbhotel.com', 'phone' => '+2348012345678', 'role_slug' => 'business_owner', 'branch_id' => null],
    ['full_name' => 'Aisha Bello', 'email' => 'admin@ffbhotel.com', 'phone' => '+2348023456789', 'role_slug' => 'admin', 'branch_id' => null],
    ['full_name' => 'Tunde Adewale', 'email' => 'manager@ffbhotel.com', 'phone' => '+2348034567890', 'role_slug' => 'branch_manager', 'branch_id' => 1],
    ['full_name' => 'Ngozi Okafor', 'email' => 'reception@ffbhotel.com', 'phone' => '+2348045678901', 'role_slug' => 'receptionist', 'branch_id' => 1],
    ['full_name' => 'Chioma Eze', 'email' => 'customer@ffbhotel.com', 'phone' => '+2348056789012', 'role_slug' => 'customer', 'branch_id' => null],
    ['full_name' => 'Yusuf Abdullahi', 'email' => 'kitchen@ffbhotel.com', 'phone' => '+2348067890123', 'role_slug' => 'kitchen_chef', 'branch_id' => 1],
    ['full_name' => 'Blessing Okoro', 'email' => 'waiter@ffbhotel.com', 'phone' => '+2348078901234', 'role_slug' => 'waiter', 'branch_id' => 1],
    ['full_name' => 'Segun Akindele', 'email' => 'inventory@ffbhotel.com', 'phone' => '+2348089012345', 'role_slug' => 'inventory_manager', 'branch_id' => 1],
    ['full_name' => 'Funke Adeyemi', 'email' => 'housekeeping@ffbhotel.com', 'phone' => '+2348090123456', 'role_slug' => 'housekeeping', 'branch_id' => 1],
    ['full_name' => 'Kemi Oladipo', 'email' => 'accountant@ffbhotel.com', 'phone' => '+2348001234567', 'role_slug' => 'accountant', 'branch_id' => 1],
];

$results = [];

foreach ($demo_users as $user) {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$user['email']]);
    if ($stmt->fetch()) {
        $results[] = ['email' => $user['email'], 'status' => 'exists'];
        continue;
    }

    $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ?");
    $stmt->execute([$user['role_slug']]);
    $role = $stmt->fetch();
    if (!$role) {
        $results[] = ['email' => $user['email'], 'status' => 'role_not_found', 'role' => $user['role_slug']];
        continue;
    }

    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, phone, password, role_id, branch_id, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
    ");
    $stmt->execute([
        $user['full_name'], $user['email'], $user['phone'],
        $demo_password, $role['id'], $user['branch_id'],
    ]);
    $userId = (int) $db->lastInsertId();

    if ($user['role_slug'] === 'customer') {
        $nameParts = explode(' ', $user['full_name'], 2);
        $stmt = $db->prepare("INSERT INTO customers (user_id, full_name, email, phone, branch_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $user['full_name'], $user['email'], $user['phone'], $user['branch_id']]);
    }

    $results[] = ['email' => $user['email'], 'status' => 'created', 'role' => $user['role_slug']];
}

echo json_encode([
    'success' => true,
    'message' => 'Demo users seeded',
    'password' => 'demo1234',
    'results' => $results,
]);
