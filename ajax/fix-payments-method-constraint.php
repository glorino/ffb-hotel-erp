<?php
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

try {
    $db->exec("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_method_check");
    $db->exec("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('flutterwave','cash','pos','bank_transfer','split_payment'))");
    echo "OK: payments.method CHECK constraint updated to include 'flutterwave'";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
