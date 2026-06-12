<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-building"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Management</li>
    <li><a href="branches.php" class="sidebar-nav-item <?php echo $current_page == 'branches.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-building"></i></span><span class="nav-label">Branches</span></a></li>
    <li><a href="revenue.php" class="sidebar-nav-item <?php echo $current_page == 'revenue.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-graph-up-arrow"></i></span><span class="nav-label">Revenue Analytics</span></a></li>
    <li><a href="bookings.php" class="sidebar-nav-item <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">Bookings</span></a></li>
    <li><a href="occupancy.php" class="sidebar-nav-item <?php echo $current_page == 'occupancy.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-door-open"></i></span><span class="nav-label">Room Occupancy</span></a></li>
    <li><a href="restaurant-sales.php" class="sidebar-nav-item <?php echo $current_page == 'restaurant-sales.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-cup-hot"></i></span><span class="nav-label">Restaurant Sales</span></a></li>
    <li class="sidebar-section">Operations</li>
    <li><a href="customers.php" class="sidebar-nav-item <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-people"></i></span><span class="nav-label">Customers</span></a></li>
    <li><a href="staff.php" class="sidebar-nav-item <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-badge"></i></span><span class="nav-label">Staff</span></a></li>
    <li><a href="inventory-overview.php" class="sidebar-nav-item <?php echo $current_page == 'inventory-overview.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-seam"></i></span><span class="nav-label">Inventory Overview</span></a></li>
    <li><a href="coupons.php" class="sidebar-nav-item <?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tag"></i></span><span class="nav-label">Coupons & Promotions</span></a></li>
    <li class="sidebar-section">Finance</li>
    <li><a href="payments.php" class="sidebar-nav-item <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="reports.php" class="sidebar-nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="settings.php" class="sidebar-nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-gear"></i></span><span class="nav-label">Settings</span></a></li>
    <li><a href="notifications.php" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
