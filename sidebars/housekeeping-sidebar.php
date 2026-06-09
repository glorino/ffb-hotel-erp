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
        <li class="<?php echo $current_page == 'rooms-to-clean.php' ? 'active' : ''; ?>">
            <a href="rooms-to-clean.php"><i class="fas fa-broom"></i> <span>Rooms to Clean</span></a>
        </li>
        <li class="<?php echo $current_page == 'cleaned-rooms.php' ? 'active' : ''; ?>">
            <a href="cleaned-rooms.php"><i class="fas fa-check-double"></i> <span>Cleaned Rooms</span></a>
        </li>
        <li class="<?php echo $current_page == 'occupied-rooms.php' ? 'active' : ''; ?>">
            <a href="occupied-rooms.php"><i class="fas fa-user"></i> <span>Occupied Rooms</span></a>
        </li>
        <li class="<?php echo $current_page == 'maintenance-requests.php' ? 'active' : ''; ?>">
            <a href="maintenance-requests.php"><i class="fas fa-tools"></i> <span>Maintenance Requests</span></a>
        </li>
        <li class="<?php echo $current_page == 'room-supplies.php' ? 'active' : ''; ?>">
            <a href="room-supplies.php"><i class="fas fa-soap"></i> <span>Room Supplies</span></a>
        </li>
        <li class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Reports</span></a>
        </li>
    </ul>
</aside>
