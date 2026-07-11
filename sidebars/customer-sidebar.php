<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-person-heart"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">My Bookings</li>
    <li><a href="my-bookings" class="sidebar-nav-item <?php echo $current_page == 'my-bookings.php' || $current_page == 'my-bookings' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar-check"></i></span><span class="nav-label">My Bookings</span></a></li>
    <li><a href="my-orders" class="sidebar-nav-item <?php echo $current_page == 'my-orders.php' || $current_page == 'my-orders' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bag"></i></span><span class="nav-label">My Orders</span></a></li>
    <li><a href="my-reservations" class="sidebar-nav-item <?php echo $current_page == 'my-reservations.php' || $current_page == 'my-reservations' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bookmark-check"></i></span><span class="nav-label">Reservations</span></a></li>
    <li class="sidebar-section">Account</li>
    <li><a href="payments" class="sidebar-nav-item <?php echo $current_page == 'payments.php' || $current_page == 'payments' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="coupons" class="sidebar-nav-item <?php echo $current_page == 'coupons.php' || $current_page == 'coupons' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tag"></i></span><span class="nav-label">Coupons</span></a></li>
    <li><a href="live-chat" class="sidebar-nav-item <?php echo $current_page == 'live-chat.php' || $current_page == 'live-chat' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-chat-dots"></i></span><span class="nav-label">Live Chat</span></a></li>
    <li><a href="profile" class="sidebar-nav-item <?php echo $current_page == 'profile.php' || $current_page == 'profile' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-gear"></i></span><span class="nav-label">My Profile</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
