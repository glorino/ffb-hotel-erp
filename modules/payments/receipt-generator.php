<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';

$receipt_id = (int) ($_GET['receipt_id'] ?? 0);
$booking_id = (int) ($_GET['booking_id'] ?? 0);
$payment_id = (int) ($_GET['payment_id'] ?? 0);

if (!$receipt_id && !$booking_id && !$payment_id) {
    http_response_code(400);
    echo 'Receipt ID or Booking ID is required';
    exit;
}

$db = getDB();

try {
    $receipt = null;

    if ($receipt_id > 0) {
        $stmt = $db->prepare("
            SELECT p.*, b.booking_reference, b.check_in_date, b.check_out_date, b.guests, b.nights,
                   b.total_amount, b.discount_amount, b.payable_amount, b.booking_status, b.source,
                   c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address,
                   r.room_number, rt.name AS room_type_name, rt.base_price,
                   br.name AS branch_name, br.address AS branch_address, br.phone AS branch_phone, br.email AS branch_email
            FROM payments p
            LEFT JOIN bookings b ON p.booking_id = b.id
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN rooms r ON b.room_id = r.id
            LEFT JOIN room_types rt ON r.room_type_id = rt.id
            LEFT JOIN branches br ON b.branch_id = br.id
            WHERE p.id = ?
        ");
        $stmt->execute([$receipt_id]);
        $receipt = $stmt->fetch();
    } elseif ($payment_id > 0) {
        $stmt = $db->prepare("
            SELECT p.*, b.booking_reference, b.check_in_date, b.check_out_date, b.guests, b.nights,
                   b.total_amount, b.discount_amount, b.payable_amount, b.booking_status, b.source,
                   c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address,
                   r.room_number, rt.name AS room_type_name, rt.base_price,
                   br.name AS branch_name, br.address AS branch_address, br.phone AS branch_phone, br.email AS branch_email
            FROM payments p
            LEFT JOIN bookings b ON p.booking_id = b.id
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN rooms r ON b.room_id = r.id
            LEFT JOIN room_types rt ON r.room_type_id = rt.id
            LEFT JOIN branches br ON b.branch_id = br.id
            WHERE p.id = ?
        ");
        $stmt->execute([$payment_id]);
        $receipt = $stmt->fetch();
    } elseif ($booking_id > 0) {
        $stmt = $db->prepare("
            SELECT p.*, b.booking_reference, b.check_in_date, b.check_out_date, b.guests, b.nights,
                   b.total_amount, b.discount_amount, b.payable_amount, b.booking_status, b.source,
                   c.full_name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address,
                   r.room_number, rt.name AS room_type_name, rt.base_price,
                   br.name AS branch_name, br.address AS branch_address, br.phone AS branch_phone, br.email AS branch_email
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN customers c ON b.customer_id = c.id
            LEFT JOIN rooms r ON b.room_id = r.id
            LEFT JOIN room_types rt ON r.room_type_id = rt.id
            LEFT JOIN branches br ON b.branch_id = br.id
            WHERE p.booking_id = ?
            ORDER BY p.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$booking_id]);
        $receipt = $stmt->fetch();
    }

    if (!$receipt) {
        http_response_code(404);
        echo 'Receipt not found';
        exit;
    }

    $hotel_logo = getSetting('site_logo', '');
    $hotel_name = getSetting('site_name', APP_NAME);
    $hotel_tagline = getSetting('site_tagline', 'Luxury Redefined');

    $payment_method_labels = [
        'flutterwave'   => 'Online Payment',
        'cash'          => 'Cash',
        'pos'           => 'POS Terminal',
        'bank_transfer' => 'Bank Transfer',
        'split_payment' => 'Split Payment',
    ];

    $payment_method_display = $payment_method_labels[$receipt['method']] ?? ucfirst(str_replace('_', ' ', $receipt['method']));
    $nights = (int) ($receipt['nights'] ?: calculateNights($receipt['check_in_date'], $receipt['check_out_date']));
    $price_per_night = (float) ($receipt['base_price'] ?? 0);
    $subtotal = $price_per_night * $nights;
    $discount = (float) ($receipt['discount_amount'] ?? 0);
    $total = (float) ($receipt['amount'] ?? $receipt['payable_amount'] ?? 0);
    $tax = 0;

    // Check if tax applies (example: 7.5% VAT)
    $tax_rate = (float) getSetting('tax_rate', '0');
    if ($tax_rate > 0) {
        $tax = round($subtotal * $tax_rate / 100, 2);
    }

    $created_at = date('d M Y, h:i A', strtotime($receipt['created_at']));
    $status_badge = match ($receipt['status']) {
        'paid'           => 'Paid',
        'partially_paid' => 'Partially Paid',
        'pending'        => 'Pending',
        'failed'         => 'Failed',
        'refunded'       => 'Refunded',
        default          => ucfirst($receipt['status']),
    };

    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($receipt['booking_reference'] ?? 'Payment'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f5f7;
            color: #1a1a2e;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }
        .receipt-wrapper { max-width: 820px; width: 100%; }
        .receipt {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 40px 48px;
            text-align: center;
            position: relative;
        }
        .receipt-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: #ffffff;
            border-radius: 50% 50% 0 0;
        }
        .receipt-logo {
            max-height: 70px;
            max-width: 200px;
            margin-bottom: 12px;
        }
        .receipt-header h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .receipt-header .tagline {
            font-size: 13px;
            opacity: 0.8;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .receipt-body { padding: 40px 48px 32px; }
        .receipt-title-area {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px dashed #e0e0e0;
        }
        .receipt-title-area h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .receipt-title-area .receipt-number {
            text-align: right;
        }
        .receipt-title-area .receipt-number .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
        }
        .receipt-title-area .receipt-number .value {
            font-size: 16px;
            font-weight: 700;
            color: #c9a84c;
        }
        .receipt-title-area .receipt-date {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-badge.paid { background: #d4edda; color: #155724; }
        .status-badge.partially_paid { background: #fff3cd; color: #856404; }
        .status-badge.pending { background: #f8f9fa; color: #6c757d; }
        .status-badge.failed { background: #f8d7da; color: #721c24; }
        .status-badge.refunded { background: #e2e3e5; color: #383d41; }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        .info-section h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 8px;
        }
        .info-section p {
            font-size: 14px;
            line-height: 1.7;
            color: #333;
        }
        .info-section p strong {
            color: #1a1a2e;
        }
        .divider { border: none; border-top: 1px solid #e8e8e8; margin: 24px 0; }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .items-table th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            padding: 8px 12px;
            border-bottom: 2px solid #e8e8e8;
        }
        .items-table th:last-child,
        .items-table td:last-child { text-align: right; }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #333;
        }
        .items-table .item-name { font-weight: 600; color: #1a1a2e; }
        .items-table .item-desc { font-size: 12px; color: #888; }
        .totals { margin-left: auto; width: 300px; }
        .totals .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            color: #555;
        }
        .totals .row.total {
            padding-top: 12px;
            margin-top: 8px;
            border-top: 2px solid #1a1a2e;
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .totals .row.discount { color: #28a745; }
        .totals .row .label { }
        .totals .row .value { font-weight: 600; }
        .payment-info-bar {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
        }
        .payment-info-bar .method { font-size: 14px; color: #555; }
        .payment-info-bar .method strong { color: #1a1a2e; }
        .thank-you {
            text-align: center;
            padding: 32px 0 0;
            font-size: 14px;
            color: #888;
            line-height: 1.8;
        }
        .thank-you .heart { color: #e74c3c; }
        .receipt-footer {
            background: #f8f9fa;
            padding: 24px 48px;
            text-align: center;
            font-size: 12px;
            color: #999;
            line-height: 1.8;
        }
        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding: 12px 32px;
            background: #1a1a2e;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .print-btn:hover { background: #c9a84c; }
        .print-btn svg { width: 18px; height: 18px; fill: currentColor; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; border-radius: 0; }
            .print-btn { display: none; }
            .receipt-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .status-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .payment-info-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 600px) {
            body { padding: 16px; }
            .receipt-header { padding: 24px; }
            .receipt-body { padding: 24px; }
            .receipt-title-area { flex-direction: column; gap: 8px; }
            .receipt-title-area .receipt-number { text-align: left; }
            .grid-2 { grid-template-columns: 1fr; }
            .totals { width: 100%; }
            .payment-info-bar { flex-direction: column; gap: 8px; text-align: center; }
            .receipt-footer { padding: 16px 24px; }
        }
    </style>
</head>
<body>
<div class="receipt-wrapper">
    <div class="receipt">
        <div class="receipt-header">
            <?php if ($hotel_logo): ?>
                <img src="<?php echo htmlspecialchars($hotel_logo); ?>" alt="<?php echo htmlspecialchars($hotel_name); ?>" class="receipt-logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($hotel_name); ?></h1>
            <div class="tagline"><?php echo htmlspecialchars($hotel_tagline); ?></div>
        </div>

        <div class="receipt-body">
            <div class="receipt-title-area">
                <div>
                    <h2>Payment Receipt</h2>
                    <div class="receipt-date">Issued: <?php echo $created_at; ?></div>
                </div>
                <div class="receipt-number">
                    <div class="label">Receipt No.</div>
                    <div class="value"><?php echo htmlspecialchars($receipt['receipt_number'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <div class="grid-2">
                <div class="info-section">
                    <h3>Customer Details</h3>
                    <p>
                        <strong><?php echo htmlspecialchars($receipt['customer_name'] ?? 'N/A'); ?></strong><br>
                        <?php if ($receipt['customer_email']): ?>
                            <?php echo htmlspecialchars($receipt['customer_email']); ?><br>
                        <?php endif; ?>
                        <?php if ($receipt['customer_phone']): ?>
                            <?php echo htmlspecialchars($receipt['customer_phone']); ?><br>
                        <?php endif; ?>
                        <?php if ($receipt['customer_address']): ?>
                            <?php echo htmlspecialchars($receipt['customer_address']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="info-section">
                    <h3>Branch Details</h3>
                    <p>
                        <strong><?php echo htmlspecialchars($receipt['branch_name'] ?? 'N/A'); ?></strong><br>
                        <?php if ($receipt['branch_address']): ?>
                            <?php echo htmlspecialchars($receipt['branch_address']); ?><br>
                        <?php endif; ?>
                        <?php if ($receipt['branch_phone']): ?>
                            <?php echo htmlspecialchars($receipt['branch_phone']); ?><br>
                        <?php endif; ?>
                        <?php if ($receipt['branch_email']): ?>
                            <?php echo htmlspecialchars($receipt['branch_email']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ($receipt['booking_reference']): ?>
            <hr class="divider">
            <div class="grid-2">
                <div class="info-section">
                    <h3>Booking Reference</h3>
                    <p><strong><?php echo htmlspecialchars($receipt['booking_reference']); ?></strong></p>
                </div>
                <div class="info-section" style="text-align:right;">
                    <span class="status-badge <?php echo $receipt['status']; ?>"><?php echo $status_badge; ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($receipt['booking_reference'] && $price_per_night > 0): ?>
            <hr class="divider">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="item-name"><?php echo htmlspecialchars($receipt['room_type_name'] ?? 'Room'); ?> - Room <?php echo htmlspecialchars($receipt['room_number'] ?? 'N/A'); ?></div>
                            <div class="item-desc">
                                <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                                (<?php echo formatDate($receipt['check_in_date'] ?? ''); ?> - <?php echo formatDate($receipt['check_out_date'] ?? ''); ?>)
                                | <?php echo $receipt['guests']; ?> guest<?php echo ($receipt['guests'] ?? 1) > 1 ? 's' : ''; ?>
                            </div>
                        </td>
                        <td><?php echo $nights; ?></td>
                        <td><?php echo formatMoney($price_per_night); ?></td>
                        <td><?php echo formatMoney($subtotal); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <div class="totals">
                <div class="row">
                    <span class="label">Subtotal</span>
                    <span class="value"><?php echo formatMoney($subtotal > 0 ? $subtotal : $total); ?></span>
                </div>
                <?php if ($tax > 0): ?>
                <div class="row">
                    <span class="label">VAT (<?php echo $tax_rate; ?>%)</span>
                    <span class="value"><?php echo formatMoney($tax); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($discount > 0): ?>
                <div class="row discount">
                    <span class="label">Discount</span>
                    <span class="value">-<?php echo formatMoney($discount); ?></span>
                </div>
                <?php endif; ?>
                <div class="row total">
                    <span class="label">Total Paid</span>
                    <span class="value"><?php echo formatMoney($total); ?></span>
                </div>
            </div>

            <div class="payment-info-bar">
                <div class="method">
                    <strong>Payment Method:</strong> <?php echo $payment_method_display; ?>
                </div>
                <div class="method">
                    <strong>Status:</strong>
                    <span class="status-badge <?php echo $receipt['status']; ?>"><?php echo $status_badge; ?></span>
                </div>
            </div>

            <div class="thank-you">
                Thank you for choosing <strong><?php echo htmlspecialchars($hotel_name); ?></strong>!<br>
                We appreciate your business and look forward to serving you again.
            </div>
        </div>

        <div class="receipt-footer">
            <?php echo htmlspecialchars($hotel_name); ?> &bull; <?php echo htmlspecialchars($receipt['branch_name'] ?? APP_NAME); ?><br>
            <?php if ($receipt['branch_address']): ?>
                <?php echo htmlspecialchars($receipt['branch_address']); ?> &bull;
            <?php endif; ?>
            <?php if ($receipt['branch_phone']): ?>
                <?php echo htmlspecialchars($receipt['branch_phone']); ?> &bull;
            <?php endif; ?>
            <?php echo htmlspecialchars($receipt['branch_email'] ?? ''); ?><br>
            This is a computer-generated receipt.
        </div>

        <div style="text-align:center; padding: 0 48px 32px;">
            <button class="print-btn" onclick="window.print()">
                <svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                Print Receipt
            </button>
        </div>
    </div>
</div>
</body>
</html>
<?php

} catch (Exception $e) {
    error_log('Receipt generation error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to generate receipt. Please try again.';
}
