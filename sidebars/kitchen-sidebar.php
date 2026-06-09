<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-fire"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Kitchen</li>
    <li><a href="incoming-orders.php" class="sidebar-nav-item <?php echo $current_page == 'incoming-orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-inbox"></i></span><span class="nav-label">Incoming Orders</span></a></li>
    <li><a href="preparing-orders.php" class="sidebar-nav-item <?php echo $current_page == 'preparing-orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-fire"></i></span><span class="nav-label">Preparing</span></a></li>
    <li><a href="ready-orders.php" class="sidebar-nav-item <?php echo $current_page == 'ready-orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-check-circle"></i></span><span class="nav-label">Ready Orders</span></a></li>
    <li><a href="completed-orders.php" class="sidebar-nav-item <?php echo $current_page == 'completed-orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-check2-all"></i></span><span class="nav-label">Completed</span></a></li>
    <li><a href="order-history.php" class="sidebar-nav-item <?php echo $current_page == 'order-history.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-clock-history"></i></span><span class="nav-label">Order History</span></a></li>
    <li class="sidebar-section">Stock</li>
    <li><a href="inventory-requests.php" class="sidebar-nav-item <?php echo $current_page == 'inventory-requests.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-seam"></i></span><span class="nav-label">Inventory Requests</span></a></li>
    <li><a href="unavailable-items.php" class="sidebar-nav-item <?php echo $current_page == 'unavailable-items.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-exclamation-octagon"></i></span><span class="nav-label">Unavailable Items</span></a></li>
</ul>
