<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-shield-lock"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Management</li>
    <li><a href="branches" class="sidebar-nav-item <?php echo $current_page == 'branches.php' || $current_page == 'branches' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-building"></i></span><span class="nav-label">Branches</span></a></li>
    <li><a href="rooms" class="sidebar-nav-item <?php echo $current_page == 'rooms.php' || $current_page == 'rooms' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-door-open"></i></span><span class="nav-label">Rooms</span></a></li>
    <li><a href="services" class="sidebar-nav-item <?php echo $current_page == 'services.php' || $current_page == 'services' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-concierge-bell"></i></span><span class="nav-label">Services</span></a></li>
    <li><a href="food-menu" class="sidebar-nav-item <?php echo $current_page == 'food-menu.php' || $current_page == 'food-menu' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-cup-hot"></i></span><span class="nav-label">Food Menu</span></a></li>
    <li><a href="staff" class="sidebar-nav-item <?php echo $current_page == 'staff.php' || $current_page == 'staff' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-people"></i></span><span class="nav-label">Staff</span></a></li>
    <li class="sidebar-section">Operations</li>
    <li><a href="bookings" class="sidebar-nav-item <?php echo $current_page == 'bookings.php' || $current_page == 'bookings' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">Bookings</span></a></li>
    <li><a href="orders" class="sidebar-nav-item <?php echo $current_page == 'orders.php' || $current_page == 'orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bag"></i></span><span class="nav-label">Orders</span></a></li>
    <li><a href="customers" class="sidebar-nav-item <?php echo $current_page == 'customers.php' || $current_page == 'customers' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-check"></i></span><span class="nav-label">Customers</span></a></li>
    <li><a href="coupons" class="sidebar-nav-item <?php echo $current_page == 'coupons.php' || $current_page == 'coupons' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tag"></i></span><span class="nav-label">Coupons</span></a></li>
    <li><a href="gallery" class="sidebar-nav-item <?php echo $current_page == 'gallery.php' || $current_page == 'gallery' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-images"></i></span><span class="nav-label">Gallery</span></a></li>
    <li class="sidebar-section">Settings</li>
    <li><a href="website-content" class="sidebar-nav-item <?php echo $current_page == 'website-content.php' || $current_page == 'website-content' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-globe"></i></span><span class="nav-label">Website Content</span></a></li>
    <li><a href="reports" class="sidebar-nav-item <?php echo $current_page == 'reports.php' || $current_page == 'reports' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="settings" class="sidebar-nav-item <?php echo $current_page == 'settings.php' || $current_page == 'settings' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-gear"></i></span><span class="nav-label">Settings</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
