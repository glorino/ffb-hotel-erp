<?php
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <span class="brand-text">FFB HOTEL</span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle">&times;</button>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        <li class="<?php echo $current_page == 'stock-items.php' ? 'active' : ''; ?>">
            <a href="stock-items.php"><i class="fas fa-cubes"></i> <span>Stock Items</span></a>
        </li>
        <li class="<?php echo $current_page == 'low-stock-alerts.php' ? 'active' : ''; ?>">
            <a href="low-stock-alerts.php"><i class="fas fa-exclamation-circle"></i> <span>Low Stock Alerts</span></a>
        </li>
        <li class="<?php echo $current_page == 'stock-in.php' ? 'active' : ''; ?>">
            <a href="stock-in.php"><i class="fas fa-arrow-down"></i> <span>Stock In</span></a>
        </li>
        <li class="<?php echo $current_page == 'stock-out.php' ? 'active' : ''; ?>">
            <a href="stock-out.php"><i class="fas fa-arrow-up"></i> <span>Stock Out</span></a>
        </li>
        <li class="<?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?>">
            <a href="suppliers.php"><i class="fas fa-truck"></i> <span>Supplier Management</span></a>
        </li>
        <li class="<?php echo $current_page == 'branch-transfers.php' ? 'active' : ''; ?>">
            <a href="branch-transfers.php"><i class="fas fa-exchange-alt"></i> <span>Branch Transfers</span></a>
        </li>
        <li class="<?php echo $current_page == 'kitchen-requests.php' ? 'active' : ''; ?>">
            <a href="kitchen-requests.php"><i class="fas fa-utensils"></i> <span>Kitchen Requests</span></a>
        </li>
        <li class="<?php echo $current_page == 'room-supplies.php' ? 'active' : ''; ?>">
            <a href="room-supplies.php"><i class="fas fa-soap"></i> <span>Room Supplies</span></a>
        </li>
        <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Inventory Reports</span></a>
        </li>
    </ul>
</aside>
