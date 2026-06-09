<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-shield-lock"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Management</li>
    <li><a href="branches.php" class="sidebar-nav-item <?php echo $current_page == 'branches.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-building"></i></span><span class="nav-label">Branches</span></a></li>
    <li><a href="rooms.php" class="sidebar-nav-item <?php echo $current_page == 'rooms.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-door-open"></i></span><span class="nav-label">Rooms</span></a></li>
    <li><a href="services.php" class="sidebar-nav-item <?php echo $current_page == 'services.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-concierge-bell"></i></span><span class="nav-label">Services</span></a></li>
    <li><a href="food-menu.php" class="sidebar-nav-item <?php echo $current_page == 'food-menu.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-cup-hot"></i></span><span class="nav-label">Food Menu</span></a></li>
    <li><a href="staff.php" class="sidebar-nav-item <?php echo $current_page == 'staff.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-people"></i></span><span class="nav-label">Staff</span></a></li>
    <li class="sidebar-section">Operations</li>
    <li><a href="bookings.php" class="sidebar-nav-item <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">Bookings</span></a></li>
    <li><a href="orders.php" class="sidebar-nav-item <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bag"></i></span><span class="nav-label">Orders</span></a></li>
    <li><a href="customers.php" class="sidebar-nav-item <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-check"></i></span><span class="nav-label">Customers</span></a></li>
    <li><a href="coupons.php" class="sidebar-nav-item <?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tag"></i></span><span class="nav-label">Coupons</span></a></li>
    <li><a href="gallery.php" class="sidebar-nav-item <?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-images"></i></span><span class="nav-label">Gallery</span></a></li>
    <li class="sidebar-section">Settings</li>
    <li><a href="website-content.php" class="sidebar-nav-item <?php echo $current_page == 'website-content.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-globe"></i></span><span class="nav-label">Website Content</span></a></li>
    <li><a href="reports.php" class="sidebar-nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="settings.php" class="sidebar-nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-gear"></i></span><span class="nav-label">Settings</span></a></li>
</ul>
