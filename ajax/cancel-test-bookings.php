<?php
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

try {
    $stmt = $db->prepare("UPDATE bookings SET booking_status = 'cancelled', updated_at = NOW() WHERE booking_status IN ('pending', 'confirmed')");
    $stmt->execute();
    $count = $stmt->rowCount();

    $roomStmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE status IN ('reserved', 'occupied')");
    $roomStmt->execute();
    $roomCount = $roomStmt->rowCount();

    echo "OK: Cancelled {$count} bookings, freed {$roomCount} rooms.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
