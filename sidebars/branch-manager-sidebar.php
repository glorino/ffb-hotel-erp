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
        <li class="<?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> <span>Branch Bookings</span></a>
        </li>
        <li class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
            <a href="orders.php"><i class="fas fa-clipboard-list"></i> <span>Branch Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'rooms.php' ? 'active' : ''; ?>">
            <a href="rooms.php"><i class="fas fa-door-open"></i> <span>Branch Rooms</span></a>
        </li>
        <li class="<?php echo $current_page == 'staff-on-duty.php' ? 'active' : ''; ?>">
            <a href="staff-on-duty.php"><i class="fas fa-user-clock"></i> <span>Staff on Duty</span></a>
        </li>
        <li class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
            <a href="inventory.php"><i class="fas fa-boxes"></i> <span>Inventory</span></a>
        </li>
        <li class="<?php echo $current_page == 'daily-sales.php' ? 'active' : ''; ?>">
            <a href="daily-sales.php"><i class="fas fa-chart-bar"></i> <span>Daily Sales</span></a>
        </li>
        <li class="<?php echo $current_page == 'customer-issues.php' ? 'active' : ''; ?>">
            <a href="customer-issues.php"><i class="fas fa-exclamation-triangle"></i> <span>Customer Issues</span></a>
        </li>
        <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Reports</span></a>
        </li>
    </ul>
</aside>
