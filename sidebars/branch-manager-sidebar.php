<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-speedometer2"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Operations</li>
    <li><a href="bookings.php" class="sidebar-nav-item <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">Bookings</span></a></li>
    <li><a href="orders.php" class="sidebar-nav-item <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bag"></i></span><span class="nav-label">Orders</span></a></li>
    <li><a href="rooms.php" class="sidebar-nav-item <?php echo $current_page == 'rooms.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-door-open"></i></span><span class="nav-label">Rooms</span></a></li>
    <li><a href="staff-on-duty.php" class="sidebar-nav-item <?php echo $current_page == 'staff-on-duty.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-badge"></i></span><span class="nav-label">Staff On Duty</span></a></li>
    <li><a href="inventory.php" class="sidebar-nav-item <?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-seam"></i></span><span class="nav-label">Inventory</span></a></li>
    <li class="sidebar-section">Analytics</li>
    <li><a href="daily-sales.php" class="sidebar-nav-item <?php echo $current_page == 'daily-sales.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-cash-stack"></i></span><span class="nav-label">Daily Sales</span></a></li>
    <li><a href="customer-issues.php" class="sidebar-nav-item <?php echo $current_page == 'customer-issues.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-exclamation-triangle"></i></span><span class="nav-label">Customer Issues</span></a></li>
    <li><a href="reports.php" class="sidebar-nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
</ul>
