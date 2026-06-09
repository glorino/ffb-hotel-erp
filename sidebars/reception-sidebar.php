<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-headset"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Front Desk</li>
    <li><a href="room-availability.php" class="sidebar-nav-item <?php echo $current_page == 'room-availability.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-calendar3"></i></span><span class="nav-label">Room Availability</span></a></li>
    <li><a href="walk-in-booking.php" class="sidebar-nav-item <?php echo $current_page == 'walk-in-booking.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-plus"></i></span><span class="nav-label">Walk-in Booking</span></a></li>
    <li><a href="online-bookings.php" class="sidebar-nav-item <?php echo $current_page == 'online-bookings.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-globe"></i></span><span class="nav-label">Online Bookings</span></a></li>
    <li><a href="check-in.php" class="sidebar-nav-item <?php echo $current_page == 'check-in.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-arrow-in-right"></i></span><span class="nav-label">Check In</span></a></li>
    <li><a href="check-out.php" class="sidebar-nav-item <?php echo $current_page == 'check-out.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span><span class="nav-label">Check Out</span></a></li>
    <li class="sidebar-section">Records</li>
    <li><a href="guest-records.php" class="sidebar-nav-item <?php echo $current_page == 'guest-records.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-journal-text"></i></span><span class="nav-label">Guest Records</span></a></li>
    <li><a href="payments.php" class="sidebar-nav-item <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="receipts.php" class="sidebar-nav-item <?php echo $current_page == 'receipts.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span class="nav-label">Receipts</span></a></li>
    <li><a href="coupons.php" class="sidebar-nav-item <?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tag"></i></span><span class="nav-label">Coupons</span></a></li>
    <li><a href="live-chat.php" class="sidebar-nav-item <?php echo $current_page == 'live-chat.php' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-chat-dots"></i></span><span class="nav-label">Live Chat</span></a></li>
</ul>
