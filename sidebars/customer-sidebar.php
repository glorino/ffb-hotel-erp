<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="../dashboard.php" class="sidebar-brand">
            <span class="brand-text">FFB HOTEL</span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle">&times;</button>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        <li class="<?php echo $current_page == 'my-bookings.php' ? 'active' : ''; ?>">
            <a href="my-bookings.php"><i class="fas fa-calendar-check"></i> <span>My Bookings</span></a>
        </li>
        <li class="<?php echo $current_page == 'my-orders.php' ? 'active' : ''; ?>">
            <a href="my-orders.php"><i class="fas fa-clipboard-list"></i> <span>My Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'my-reservations.php' ? 'active' : ''; ?>">
            <a href="my-reservations.php"><i class="fas fa-clock"></i> <span>My Reservations</span></a>
        </li>
        <li class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
        </li>
        <li class="<?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>">
            <a href="coupons.php"><i class="fas fa-tags"></i> <span>Coupons</span></a>
        </li>
        <li class="<?php echo $current_page == 'live-chat.php' ? 'active' : ''; ?>">
            <a href="live-chat.php"><i class="fas fa-comments"></i> <span>Live Chat</span></a>
        </li>
        <li class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <a href="profile.php"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
        </li>
    </ul>
</aside>
