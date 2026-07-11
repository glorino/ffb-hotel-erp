<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-fire"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Kitchen</li>
    <li><a href="incoming-orders" class="sidebar-nav-item <?php echo $current_page == 'incoming-orders.php' || $current_page == 'incoming-orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-inbox"></i></span><span class="nav-label">Incoming Orders</span></a></li>
    <li><a href="preparing-orders" class="sidebar-nav-item <?php echo $current_page == 'preparing-orders.php' || $current_page == 'preparing-orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-fire"></i></span><span class="nav-label">Preparing</span></a></li>
    <li><a href="ready-orders" class="sidebar-nav-item <?php echo $current_page == 'ready-orders.php' || $current_page == 'ready-orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-check-circle"></i></span><span class="nav-label">Ready Orders</span></a></li>
    <li><a href="completed-orders" class="sidebar-nav-item <?php echo $current_page == 'completed-orders.php' || $current_page == 'completed-orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-check2-all"></i></span><span class="nav-label">Completed</span></a></li>
    <li><a href="order-history" class="sidebar-nav-item <?php echo $current_page == 'order-history.php' || $current_page == 'order-history' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-clock-history"></i></span><span class="nav-label">Order History</span></a></li>
    <li class="sidebar-section">Stock</li>
    <li><a href="inventory-requests" class="sidebar-nav-item <?php echo $current_page == 'inventory-requests.php' || $current_page == 'inventory-requests' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-seam"></i></span><span class="nav-label">Inventory Requests</span></a></li>
    <li><a href="unavailable-items" class="sidebar-nav-item <?php echo $current_page == 'unavailable-items.php' || $current_page == 'unavailable-items' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-exclamation-octagon"></i></span><span class="nav-label">Unavailable Items</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
