<?php
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
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
        <li class="<?php echo $current_page == 'room-availability.php' ? 'active' : ''; ?>">
            <a href="room-availability.php"><i class="fas fa-bed"></i> <span>Room Availability</span></a>
        </li>
        <li class="<?php echo $current_page == 'walk-in-booking.php' ? 'active' : ''; ?>">
            <a href="walk-in-booking.php"><i class="fas fa-user-plus"></i> <span>Walk-in Booking</span></a>
        </li>
        <li class="<?php echo $current_page == 'online-bookings.php' ? 'active' : ''; ?>">
            <a href="online-bookings.php"><i class="fas fa-globe"></i> <span>Online Bookings</span></a>
        </li>
        <li class="<?php echo $current_page == 'check-in.php' ? 'active' : ''; ?>">
            <a href="check-in.php"><i class="fas fa-sign-in-alt"></i> <span>Check-in</span></a>
        </li>
        <li class="<?php echo $current_page == 'check-out.php' ? 'active' : ''; ?>">
            <a href="check-out.php"><i class="fas fa-sign-out-alt"></i> <span>Check-out</span></a>
        </li>
        <li class="<?php echo $current_page == 'guest-records.php' ? 'active' : ''; ?>">
            <a href="guest-records.php"><i class="fas fa-address-book"></i> <span>Guest Records</span></a>
        </li>
        <li class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a>
        </li>
        <li class="<?php echo $current_page == 'live-chat.php' ? 'active' : ''; ?>">
            <a href="live-chat.php"><i class="fas fa-comments"></i> <span>Live Chat</span></a>
        </li>
        <li class="<?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>">
            <a href="coupons.php"><i class="fas fa-tags"></i> <span>Coupons</span></a>
        </li>
        <li class="<?php echo $current_page == 'receipts.php' ? 'active' : ''; ?>">
            <a href="receipts.php"><i class="fas fa-receipt"></i> <span>Receipts</span></a>
        </li>
    </ul>
</aside>
