<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-cup-straw"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Service</li>
    <li><a href="tables" class="sidebar-nav-item <?php echo $current_page == 'tables.php' || $current_page == 'tables' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-3x3"></i></span><span class="nav-label">Tables</span></a></li>
    <li><a href="new-order" class="sidebar-nav-item <?php echo $current_page == 'new-order.php' || $current_page == 'new-order' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-plus-circle"></i></span><span class="nav-label">New Order</span></a></li>
    <li><a href="active-orders" class="sidebar-nav-item <?php echo $current_page == 'active-orders.php' || $current_page == 'active-orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-hourglass-split"></i></span><span class="nav-label">Active Orders</span></a></li>
    <li><a href="kitchen-status" class="sidebar-nav-item <?php echo $current_page == 'kitchen-status.php' || $current_page == 'kitchen-status' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-fire"></i></span><span class="nav-label">Kitchen Status</span></a></li>
    <li class="sidebar-section">Billing</li>
    <li><a href="bills" class="sidebar-nav-item <?php echo $current_page == 'bills.php' || $current_page == 'bills' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span class="nav-label">Bills</span></a></li>
    <li><a href="payments" class="sidebar-nav-item <?php echo $current_page == 'payments.php' || $current_page == 'payments' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="customer-requests" class="sidebar-nav-item <?php echo $current_page == 'customer-requests.php' || $current_page == 'customer-requests' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Customer Requests</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
