<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-box-seam"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Inventory</li>
    <li><a href="stock-items" class="sidebar-nav-item <?php echo $current_page == 'stock-items.php' || $current_page == 'stock-items' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box"></i></span><span class="nav-label">Stock Items</span></a></li>
    <li><a href="low-stock-alerts" class="sidebar-nav-item <?php echo $current_page == 'low-stock-alerts.php' || $current_page == 'low-stock-alerts' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-exclamation-triangle"></i></span><span class="nav-label">Low Stock Alerts</span></a></li>
    <li><a href="stock-in" class="sidebar-nav-item <?php echo $current_page == 'stock-in.php' || $current_page == 'stock-in' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-arrow-down-circle"></i></span><span class="nav-label">Stock In</span></a></li>
    <li><a href="stock-out" class="sidebar-nav-item <?php echo $current_page == 'stock-out.php' || $current_page == 'stock-out' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-arrow-up-circle"></i></span><span class="nav-label">Stock Out</span></a></li>
    <li><a href="suppliers" class="sidebar-nav-item <?php echo $current_page == 'suppliers.php' || $current_page == 'suppliers' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-truck"></i></span><span class="nav-label">Suppliers</span></a></li>
    <li class="sidebar-section">Transfers</li>
    <li><a href="branch-transfers" class="sidebar-nav-item <?php echo $current_page == 'branch-transfers.php' || $current_page == 'branch-transfers' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-arrow-left-right"></i></span><span class="nav-label">Branch Transfers</span></a></li>
    <li><a href="kitchen-requests" class="sidebar-nav-item <?php echo $current_page == 'kitchen-requests.php' || $current_page == 'kitchen-requests' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-fire"></i></span><span class="nav-label">Kitchen Requests</span></a></li>
    <li><a href="room-supplies" class="sidebar-nav-item <?php echo $current_page == 'room-supplies.php' || $current_page == 'room-supplies' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-door-open"></i></span><span class="nav-label">Room Supplies</span></a></li>
    <li><a href="reports" class="sidebar-nav-item <?php echo $current_page == 'reports.php' || $current_page == 'reports' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
