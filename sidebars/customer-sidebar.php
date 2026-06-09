<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-person-heart"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">My Bookings</li>
    <li><a href="my-bookings.php" class="sidebar-nav-item <?php echo $current_page == 'my-bookings.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">My Bookings</span></a></li>
    <li><a href="my-orders.php" class="sidebar-nav-item <?php echo $current_page == 'my-orders.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bag"></i></span><span class="nav-label">My Orders</span></a></li>
    <li><a href="my-reservations.php" class="sidebar-nav-item <?php echo $current_page == 'my-reservations.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bookmark-check"></i></span><span class="nav-label">Reservations</span></a></li>
    <li class="sidebar-section">Account</li>
    <li><a href="payments.php" class="sidebar-nav-item <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="coupons.php" class="sidebar-nav-item <?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tag"></i></span><span class="nav-label">Coupons</span></a></li>
    <li><a href="live-chat.php" class="sidebar-nav-item <?php echo $current_page == 'live-chat.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-chat-dots"></i></span><span class="nav-label">Live Chat</span></a></li>
    <li><a href="profile.php" class="sidebar-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-gear"></i></span><span class="nav-label">My Profile</span></a></li>
</ul>
