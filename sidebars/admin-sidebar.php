<?php
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <span class="brand-text">FFB HOTEL</span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle">&times;</button>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        <li class="<?php echo $current_page == 'branches.php' ? 'active' : ''; ?>">
            <a href="branches.php"><i class="fas fa-building"></i> <span>Manage Branches</span></a>
        </li>
        <li class="<?php echo $current_page == 'rooms.php' ? 'active' : ''; ?>">
            <a href="rooms.php"><i class="fas fa-door-open"></i> <span>Manage Rooms/Suites</span></a>
        </li>
        <li class="<?php echo $current_page == 'services.php' ? 'active' : ''; ?>">
            <a href="services.php"><i class="fas fa-concierge-bell"></i> <span>Manage Services</span></a>
        </li>
        <li class="<?php echo $current_page == 'food-menu.php' ? 'active' : ''; ?>">
            <a href="food-menu.php"><i class="fas fa-utensils"></i> <span>Manage Food Menu</span></a>
        </li>
        <li class="<?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
            <a href="staff.php"><i class="fas fa-user-tie"></i> <span>Manage Staff</span></a>
        </li>
        <li class="<?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> <span>Bookings</span></a>
        </li>
        <li class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
            <a href="orders.php"><i class="fas fa-clipboard-list"></i> <span>Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
            <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
        </li>
        <li class="<?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>">
            <a href="coupons.php"><i class="fas fa-tags"></i> <span>Coupons</span></a>
        </li>
        <li class="<?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
            <a href="gallery.php"><i class="fas fa-images"></i> <span>Gallery</span></a>
        </li>
        <li class="<?php echo $current_page == 'website-content.php' ? 'active' : ''; ?>">
            <a href="website-content.php"><i class="fas fa-globe"></i> <span>Website Content</span></a>
        </li>
        <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Reports</span></a>
        </li>
        <li class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </li>
    </ul>
</aside>
