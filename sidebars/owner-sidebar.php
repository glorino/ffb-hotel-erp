<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-building"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Management</li>
    <li><a href="branches" class="sidebar-nav-item <?php echo $current_page == 'branches.php' || $current_page == 'branches' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-building"></i></span><span class="nav-label">Branches</span></a></li>
    <li><a href="revenue" class="sidebar-nav-item <?php echo $current_page == 'revenue.php' || $current_page == 'revenue' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-graph-up-arrow"></i></span><span class="nav-label">Revenue Analytics</span></a></li>
    <li><a href="bookings" class="sidebar-nav-item <?php echo $current_page == 'bookings.php' || $current_page == 'bookings' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">Bookings</span></a></li>
    <li><a href="occupancy" class="sidebar-nav-item <?php echo $current_page == 'occupancy.php' || $current_page == 'occupancy' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-door-open"></i></span><span class="nav-label">Room Occupancy</span></a></li>
    <li><a href="restaurant-sales" class="sidebar-nav-item <?php echo $current_page == 'restaurant-sales.php' || $current_page == 'restaurant-sales' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-cup-hot"></i></span><span class="nav-label">Restaurant Sales</span></a></li>
    <li class="sidebar-section">Operations</li>
    <li><a href="customers" class="sidebar-nav-item <?php echo $current_page == 'customers.php' || $current_page == 'customers' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-people"></i></span><span class="nav-label">Customers</span></a></li>
    <li><a href="staff" class="sidebar-nav-item <?php echo $current_page == 'staff.php' || $current_page == 'staff' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-badge"></i></span><span class="nav-label">Staff</span></a></li>
    <li><a href="inventory-overview" class="sidebar-nav-item <?php echo $current_page == 'inventory-overview.php' || $current_page == 'inventory-overview' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-seam"></i></span><span class="nav-label">Inventory Overview</span></a></li>
    <li><a href="coupons" class="sidebar-nav-item <?php echo $current_page == 'coupons.php' || $current_page == 'coupons' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tag"></i></span><span class="nav-label">Coupons & Promotions</span></a></li>
    <li class="sidebar-section">Finance</li>
    <li><a href="payments" class="sidebar-nav-item <?php echo $current_page == 'payments.php' || $current_page == 'payments' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="reports" class="sidebar-nav-item <?php echo $current_page == 'reports.php' || $current_page == 'reports' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="settings" class="sidebar-nav-item <?php echo $current_page == 'settings.php' || $current_page == 'settings' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-gear"></i></span><span class="nav-label">Settings</span></a></li>
    <li><a href="send-notification" class="sidebar-nav-item <?php echo $current_page == 'send-notification.php' || $current_page == 'send-notification' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-megaphone"></i></span><span class="nav-label">Send Notification</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
