<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="../dashboard.php" class="sidebar-brand">
            <span class="brand-text">FFB HOTEL</span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle">&times;</button>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        <li class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
        </li>
        <li class="<?php echo $current_page == 'invoices.php' ? 'active' : ''; ?>">
            <a href="invoices.php"><i class="fas fa-file-invoice"></i> <span>Invoices</span></a>
        </li>
        <li class="<?php echo $current_page == 'expenses.php' ? 'active' : ''; ?>">
            <a href="expenses.php"><i class="fas fa-money-bill-wave"></i> <span>Expenses</span></a>
        </li>
        <li class="<?php echo $current_page == 'revenue.php' ? 'active' : ''; ?>">
            <a href="revenue.php"><i class="fas fa-chart-line"></i> <span>Revenue</span></a>
        </li>
        <li class="<?php echo $current_page == 'paystack-transactions.php' ? 'active' : ''; ?>">
            <a href="paystack-transactions.php"><i class="fas fa-exchange-alt"></i> <span>Paystack Transactions</span></a>
        </li>
        <li class="<?php echo $current_page == 'offline-payments.php' ? 'active' : ''; ?>">
            <a href="offline-payments.php"><i class="fas fa-hand-holding-usd"></i> <span>Offline Payments</span></a>
        </li>
        <li class="<?php echo $current_page == 'refunds.php' ? 'active' : ''; ?>">
            <a href="refunds.php"><i class="fas fa-undo-alt"></i> <span>Refunds</span></a>
        </li>
        <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Financial Reports</span></a>
        </li>
    </ul>
</aside>
