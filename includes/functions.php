<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/constants.php';

function getDB() {
    return Database::getInstance()->getConnection();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser($id = null) {
    $db = getDB();
    $id = $id ?? $_SESSION['user_id'] ?? 0;
    $stmt = $db->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, b.name as branch_name 
                          FROM users u 
                          JOIN roles r ON u.role_id = r.id 
                          LEFT JOIN branches b ON u.branch_id = b.id 
                          WHERE u.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function formatMoney($amount) {
    $symbol = mb_chr(0x20A6, 'UTF-8');
    if (!$symbol) $symbol = 'NGN';
    return $symbol . number_format($amount, 2);
}

function formatDate($date, $format = null) {
    if (!$date) return '';
    $format = $format ?: DATE_FORMAT;
    return date($format, strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '';
    return date(DATETIME_FORMAT, strtotime($datetime));
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs ago';
    return date('M j', $timestamp);
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

function generateReference($prefix = 'GLP') {
    return $prefix . strtoupper(uniqid()) . rand(1000, 9999);
}

function generateReceiptNumber() {
    return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function getBranchName($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? $row['name'] : 'N/A';
}

function getBranches() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM branches WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRoomTypes() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM room_types WHERE status = 'active' ORDER BY base_price");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRoomStatusBadge($status) {
    $badges = [
        'available' => 'success',
        'reserved' => 'info',
        'occupied' => 'warning',
        'cleaning' => 'secondary',
        'maintenance' => 'danger',
        'out_of_service' => 'dark'
    ];
    $color = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-{$color}'>" . htmlspecialchars($status) . "</span>";
}

function getBookingStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'checked_in' => 'success',
        'checked_out' => 'secondary',
        'cancelled' => 'danger',
        'no_show' => 'dark'
    ];
    $color = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-{$color}'>" . htmlspecialchars($status) . "</span>";
}

function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'paid' => 'success',
        'partially_paid' => 'info',
        'failed' => 'danger',
        'refunded' => 'secondary'
    ];
    $color = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-{$color}'>" . htmlspecialchars($status) . "</span>";
}

function log_audit($action, $entity_type, $entity_id, $old_values = null, $new_values = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? 0,
        $action,
        $entity_type,
        $entity_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);
}

function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function getTimeSlots($interval = 30) {
    $slots = [];
    $start = strtotime('00:00');
    $end = strtotime('23:30');
    for ($i = $start; $i <= $end; $i += $interval * 60) {
        $slots[] = date('H:i', $i);
    }
    return $slots;
}

function truncate($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function calculateNights($check_in, $check_out) {
    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    $interval = $start->diff($end);
    return max(1, $interval->days);
}
