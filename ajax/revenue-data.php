<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$days = isset($_GET['days']) ? (int) $_GET['days'] : 7;
if ($days < 1) $days = 7;
if ($days > 365) $days = 365;

$labels = [];
$data = [];

try {
    $db = getDB();
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        if ($days <= 31) {
            $labels[] = date('M j', strtotime($day));
        } else {
            $labels[] = date('M j', strtotime($day));
        }
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND DATE(created_at) = ?");
        $stmt->execute([$day]);
        $data[] = (float) $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $labels = [];
    $data = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M j', strtotime($day));
        $data[] = 0;
    }
}

echo json_encode(['labels' => $labels, 'data' => $data]);
