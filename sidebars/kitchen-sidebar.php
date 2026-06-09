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
        <li class="<?php echo $current_page == 'incoming-orders.php' ? 'active' : ''; ?>">
            <a href="incoming-orders.php"><i class="fas fa-arrow-down"></i> <span>Incoming Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'preparing-orders.php' ? 'active' : ''; ?>">
            <a href="preparing-orders.php"><i class="fas fa-fire"></i> <span>Preparing Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'ready-orders.php' ? 'active' : ''; ?>">
            <a href="ready-orders.php"><i class="fas fa-check-circle"></i> <span>Ready Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'completed-orders.php' ? 'active' : ''; ?>">
            <a href="completed-orders.php"><i class="fas fa-clipboard-check"></i> <span>Completed Orders</span></a>
        </li>
        <li class="<?php echo $current_page == 'unavailable-items.php' ? 'active' : ''; ?>">
            <a href="unavailable-items.php"><i class="fas fa-ban"></i> <span>Unavailable Items</span></a>
        </li>
        <li class="<?php echo $current_page == 'inventory-requests.php' ? 'active' : ''; ?>">
            <a href="inventory-requests.php"><i class="fas fa-box-open"></i> <span>Kitchen Inventory Requests</span></a>
        </li>
        <li class="<?php echo $current_page == 'order-history.php' ? 'active' : ''; ?>">
            <a href="order-history.php"><i class="fas fa-history"></i> <span>Order History</span></a>
        </li>
    </ul>
</aside>
