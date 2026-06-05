<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$redirect_map = [
    'business_owner' => '../owner/dashboard.php',
    'admin'          => '../admin/dashboard.php',
    'branch_manager' => '../branch-manager/dashboard.php',
    'receptionist'   => '../reception/dashboard.php',
    'kitchen_chef'   => '../kitchen/dashboard.php',
    'waiter'         => '../waiter/dashboard.php',
    'inventory_manager' => '../inventory/dashboard.php',
    'housekeeping'   => '../housekeeping/dashboard.php',
    'accountant'     => '../accountant/dashboard.php',
    'customer'       => '../customer/dashboard.php',
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token. Please try again.');
    header('Location: ../login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);

if ($email === '' || $password === '') {
    set_flash('danger', 'Please enter your email and password.');
    header('Location: ../login.php');
    exit;
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("
        SELECT u.id, u.password, u.full_name, u.email, u.role_id, u.branch_id, u.status,
               r.slug AS role_slug
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['status'] !== 'active') {
        set_flash('danger', 'Invalid email or password.');
        header('Location: ../login.php');
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        set_flash('danger', 'Invalid email or password.');
        header('Location: ../login.php');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['role_id']    = (int) $user['role_id'];
    $_SESSION['role_slug']  = $user['role_slug'];
    $_SESSION['branch_id']  = $user['branch_id'] ? (int) $user['branch_id'] : null;
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['email']      = $user['email'];

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $stmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
        $stmt->execute([':token' => $token, ':id' => $user['id']]);

        setcookie(
            'remember_token',
            $token,
            [
                'expires'  => strtotime('+30 days'),
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);

    log_audit('login', 'user', $user['id'], null, ['email' => $user['email'], 'role' => $user['role_slug']]);

    $redirect = $redirect_map[$user['role_slug']] ?? '../index.php';
    header('Location: ' . $redirect);
    exit;

} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred. Please try again.');
    header('Location: ../login.php');
    exit;
}
