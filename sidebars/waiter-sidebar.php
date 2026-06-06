<?php
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
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
        <li class="<?php echo $current_page == 'tables.php' ? 'active' : ''; ?>">
            <a href="tables.php"><i class="fas fa-chair"></i> <span>Tables</span></a>
        </li>
        <li class="<?php echo $current_page == 'new-order.php' ? 'active' : ''; ?>">
            <a href="new-order.php"><i class="fas fa-plus-circle"></i> <span>New Order</span></a>
        </li>
        <li class="<?php echo $current_page == 'active-orders.php' ? 'active' : ''; ?>">
            <a href="active-orders.php"><i class="fas fa-spinner"></i> <span>Active Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'kitchen-status.php' ? 'active' : ''; ?>">
            <a href="kitchen-status.php"><i class="fas fa-kitchen-set"></i> <span>Kitchen Status</span></a>
        </li>
        <li class="<?php echo $current_page == 'bills.php' ? 'active' : ''; ?>">
            <a href="bills.php"><i class="fas fa-file-invoice-dollar"></i> <span>Bills</span></a>
        </li>
        <li class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
        </li>
        <li class="<?php echo $current_page == 'customer-requests.php' ? 'active' : ''; ?>">
            <a href="customer-requests.php"><i class="fas fa-bell"></i> <span>Customer Requests</span></a>
        </li>
    </ul>
</aside>
