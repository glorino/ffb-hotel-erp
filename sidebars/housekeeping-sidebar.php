<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-stars"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Housekeeping</li>
    <li><a href="rooms-to-clean" class="sidebar-nav-item <?php echo $current_page == 'rooms-to-clean.php' || $current_page == 'rooms-to-clean' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-brush"></i></span><span class="nav-label">Rooms to Clean</span></a></li>
    <li><a href="cleaned-rooms" class="sidebar-nav-item <?php echo $current_page == 'cleaned-rooms.php' || $current_page == 'cleaned-rooms' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-check-circle"></i></span><span class="nav-label">Cleaned Rooms</span></a></li>
    <li><a href="occupied-rooms" class="sidebar-nav-item <?php echo $current_page == 'occupied-rooms.php' || $current_page == 'occupied-rooms' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-person-fill"></i></span><span class="nav-label">Occupied Rooms</span></a></li>
    <li class="sidebar-section">Maintenance</li>
    <li><a href="maintenance-requests" class="sidebar-nav-item <?php echo $current_page == 'maintenance-requests.php' || $current_page == 'maintenance-requests' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-tools"></i></span><span class="nav-label">Maintenance Requests</span></a></li>
    <li><a href="room-supplies" class="sidebar-nav-item <?php echo $current_page == 'room-supplies.php' || $current_page == 'room-supplies' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-box-seam"></i></span><span class="nav-label">Room Supplies</span></a></li>
    <li><a href="reports" class="sidebar-nav-item <?php echo $current_page == 'reports.php' || $current_page == 'reports' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
