<?php
/**
 * FFB Hotel - Demo Users Seed Script
 * Run: php database/seed-demo-users.php
 * Creates demo login accounts for all roles with password: demo1234
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$demo_password = password_hash('demo1234', PASSWORD_DEFAULT);

$demo_users = [
    // Business Owner
    [
        'full_name' => 'Emeka Okonkwo',
        'email' => 'owner@ffbhotel.com',
        'phone' => '+2348012345678',
        'role_slug' => 'business_owner',
        'branch_id' => null,
    ],
    // Admin
    [
        'full_name' => 'Aisha Bello',
        'email' => 'admin@ffbhotel.com',
        'phone' => '+2348023456789',
        'role_slug' => 'admin',
        'branch_id' => null,
    ],
    // Branch Manager
    [
        'full_name' => 'Tunde Adewale',
        'email' => 'manager@ffbhotel.com',
        'phone' => '+2348034567890',
        'role_slug' => 'branch_manager',
        'branch_id' => 1,
    ],
    // Receptionist
    [
        'full_name' => 'Ngozi Okafor',
        'email' => 'reception@ffbhotel.com',
        'phone' => '+2348045678901',
        'role_slug' => 'receptionist',
        'branch_id' => 1,
    ],
    // Customer
    [
        'full_name' => 'Chioma Eze',
        'email' => 'customer@ffbhotel.com',
        'phone' => '+2348056789012',
        'role_slug' => 'customer',
        'branch_id' => null,
    ],
    // Kitchen Chef
    [
        'full_name' => 'Yusuf Abdullahi',
        'email' => 'kitchen@ffbhotel.com',
        'phone' => '+2348067890123',
        'role_slug' => 'kitchen_chef',
        'branch_id' => 1,
    ],
    // Waiter
    [
        'full_name' => 'Blessing Okoro',
        'email' => 'waiter@ffbhotel.com',
        'phone' => '+2348078901234',
        'role_slug' => 'waiter',
        'branch_id' => 1,
    ],
    // Inventory Manager
    [
        'full_name' => 'Segun Akindele',
        'email' => 'inventory@ffbhotel.com',
        'phone' => '+2348089012345',
        'role_slug' => 'inventory_manager',
        'branch_id' => 1,
    ],
    // Housekeeping
    [
        'full_name' => 'Funke Adeyemi',
        'email' => 'housekeeping@ffbhotel.com',
        'phone' => '+2348090123456',
        'role_slug' => 'housekeeping',
        'branch_id' => 1,
    ],
    // Accountant
    [
        'full_name' => 'Kemi Oladipo',
        'email' => 'accountant@ffbhotel.com',
        'phone' => '+2348001234567',
        'role_slug' => 'accountant',
        'branch_id' => 1,
    ],
];

try {
    $created = 0;
    $skipped = 0;

    foreach ($demo_users as $user) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);
        if ($stmt->fetch()) {
            echo "  SKIP: {$user['email']} (already exists)\n";
            $skipped++;
            continue;
        }

        $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ?");
        $stmt->execute([$user['role_slug']]);
        $role = $stmt->fetch();
        if (!$role) {
            echo "  SKIP: {$user['email']} (role '{$user['role_slug']}' not found)\n";
            $skipped++;
            continue;
        }

        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, phone, password, role_id, branch_id, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $user['full_name'],
            $user['email'],
            $user['phone'],
            $demo_password,
            $role['id'],
            $user['branch_id'],
        ]);

        // If customer role, also create a customers record
        if ($user['role_slug'] === 'customer') {
            $userId = (int) $db->lastInsertId();
            $stmt = $db->prepare("
                INSERT INTO customers (user_id, first_name, last_name, email, phone, branch_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $nameParts = explode(' ', $user['full_name'], 2);
            $stmt->execute([
                $userId,
                $nameParts[0] ?? '',
                $nameParts[1] ?? '',
                $user['email'],
                $user['phone'],
                $user['branch_id'],
            ]);
            echo "  OK: {$user['email']} (user + customer record created)\n";
        } else {
            echo "  OK: {$user['email']} (role: {$user['role_slug']})\n";
        }
        $created++;
    }

    echo "\n========================================\n";
    echo "  Demo users: {$created} created, {$skipped} skipped\n";
    echo "========================================\n";
    echo "\n  All passwords: demo1234\n";
    echo "\n  Login at: /login.php\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
