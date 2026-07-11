<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-speedometer2"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Operations</li>
    <li><a href="bookings" class="sidebar-nav-item <?php echo $current_page == 'bookings.php' || $current_page == 'bookings' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">Bookings</span></a></li>
    <li><a href="orders" class="sidebar-nav-item <?php echo $current_page == 'orders.php' || $current_page == 'orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bag"></i></span><span class="nav-label">Orders</span></a></li>
    <li><a href="rooms" class="sidebar-nav-item <?php echo $current_page == 'rooms.php' || $current_page == 'rooms' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-door-open"></i></span><span class="nav-label">Rooms</span></a></li>
    <li><a href="staff-on-duty" class="sidebar-nav-item <?php echo $current_page == 'staff-on-duty.php' || $current_page == 'staff-on-duty' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-badge"></i></span><span class="nav-label">Staff On Duty</span></a></li>
    <li><a href="inventory" class="sidebar-nav-item <?php echo $current_page == 'inventory.php' || $current_page == 'inventory' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-seam"></i></span><span class="nav-label">Inventory</span></a></li>
    <li class="sidebar-section">Analytics</li>
    <li><a href="daily-sales" class="sidebar-nav-item <?php echo $current_page == 'daily-sales.php' || $current_page == 'daily-sales' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-cash-stack"></i></span><span class="nav-label">Daily Sales</span></a></li>
    <li><a href="customer-issues" class="sidebar-nav-item <?php echo $current_page == 'customer-issues.php' || $current_page == 'customer-issues' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-exclamation-triangle"></i></span><span class="nav-label">Customer Issues</span></a></li>
    <li><a href="reports" class="sidebar-nav-item <?php echo $current_page == 'reports.php' || $current_page == 'reports' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
