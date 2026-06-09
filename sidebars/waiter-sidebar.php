<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-cup-straw"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Service</li>
    <li><a href="tables.php" class="sidebar-nav-item <?php echo $current_page == 'tables.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-3x3"></i></span><span class="nav-label">Tables</span></a></li>
    <li><a href="new-order.php" class="sidebar-nav-item <?php echo $current_page == 'new-order.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-plus-circle"></i></span><span class="nav-label">New Order</span></a></li>
    <li><a href="active-orders.php" class="sidebar-nav-item <?php echo $current_page == 'active-orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-hourglass-split"></i></span><span class="nav-label">Active Orders</span></a></li>
    <li><a href="kitchen-status.php" class="sidebar-nav-item <?php echo $current_page == 'kitchen-status.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-fire"></i></span><span class="nav-label">Kitchen Status</span></a></li>
    <li class="sidebar-section">Billing</li>
    <li><a href="bills.php" class="sidebar-nav-item <?php echo $current_page == 'bills.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span class="nav-label">Bills</span></a></li>
    <li><a href="payments.php" class="sidebar-nav-item <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="customer-requests.php" class="sidebar-nav-item <?php echo $current_page == 'customer-requests.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Customer Requests</span></a></li>
</ul>
