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
        <li class="<?php echo $current_page == 'branches.php' ? 'active' : ''; ?>">
            <a href="branches.php"><i class="fas fa-building"></i> <span>Branches</span></a>
        </li>
        <li class="<?php echo $current_page == 'revenue.php' ? 'active' : ''; ?>">
            <a href="revenue.php"><i class="fas fa-chart-line"></i> <span>Revenue Analytics</span></a>
        </li>
        <li class="<?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> <span>Bookings</span></a>
        </li>
        <li class="<?php echo $current_page == 'occupancy.php' ? 'active' : ''; ?>">
            <a href="occupancy.php"><i class="fas fa-bed"></i> <span>Room Occupancy</span></a>
        </li>
        <li class="<?php echo $current_page == 'restaurant-sales.php' ? 'active' : ''; ?>">
            <a href="restaurant-sales.php"><i class="fas fa-utensils"></i> <span>Restaurant Sales</span></a>
        </li>
        <li class="<?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
            <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
        </li>
        <li class="<?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
            <a href="staff.php"><i class="fas fa-user-tie"></i> <span>Staff</span></a>
        </li>
        <li class="<?php echo $current_page == 'inventory-overview.php' ? 'active' : ''; ?>">
            <a href="inventory-overview.php"><i class="fas fa-boxes"></i> <span>Inventory Overview</span></a>
        </li>
        <li class="<?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>">
            <a href="coupons.php"><i class="fas fa-tags"></i> <span>Coupons & Promotions</span></a>
        </li>
        <li class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
        </li>
        <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Reports</span></a>
        </li>
        <li class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </li>
    </ul>
</aside>
