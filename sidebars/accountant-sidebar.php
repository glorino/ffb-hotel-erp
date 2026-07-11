<?php $current_page = $current_page ?? basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard" class="sidebar-brand">
    <span class="brand-icon"><i class="bi bi-calculator"></i></span>
    <span class="brand-text">FFB Hotel</span>
</a>
<ul class="sidebar-menu">
    <li><a href="dashboard" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' || $current_page == 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-grid-1x2"></i></span><span class="nav-label">Dashboard</span></a></li>
    <li class="sidebar-section">Finance</li>
    <li><a href="payments" class="sidebar-nav-item <?php echo $current_page == 'payments.php' || $current_page == 'payments' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span class="nav-label">Payments</span></a></li>
    <li><a href="invoices" class="sidebar-nav-item <?php echo $current_page == 'invoices.php' || $current_page == 'invoices' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-text"></i></span><span class="nav-label">Invoices</span></a></li>
    <li><a href="expenses" class="sidebar-nav-item <?php echo $current_page == 'expenses.php' || $current_page == 'expenses' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-wallet2"></i></span><span class="nav-label">Expenses</span></a></li>
    <li><a href="revenue" class="sidebar-nav-item <?php echo $current_page == 'revenue.php' || $current_page == 'revenue' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-graph-up-arrow"></i></span><span class="nav-label">Revenue</span></a></li>
    <li class="sidebar-section">Transactions</li>
    <li><a href="flutterwave-transactions" class="sidebar-nav-item <?php echo $current_page == 'flutterwave-transactions.php' || $current_page == 'flutterwave-transactions' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-shield-lock"></i></span><span class="nav-label">Online Transactions</span></a></li>
    <li><a href="offline-payments" class="sidebar-nav-item <?php echo $current_page == 'offline-payments.php' || $current_page == 'offline-payments' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bank"></i></span><span class="nav-label">Offline Payments</span></a></li>
    <li><a href="refunds" class="sidebar-nav-item <?php echo $current_page == 'refunds.php' || $current_page == 'refunds' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-arrow-counterclockwise"></i></span><span class="nav-label">Refunds</span></a></li>
    <li><a href="reports" class="sidebar-nav-item <?php echo $current_page == 'reports.php' || $current_page == 'reports' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span class="nav-label">Reports</span></a></li>
    <li><a href="notifications" class="sidebar-nav-item <?php echo $current_page == 'notifications.php' || $current_page == 'notifications' ? 'active' : ''; ?>"><span class="nav-icon"><i class="bi bi-bell"></i></span><span class="nav-label">Notifications</span></a></li>
    <li style="margin-top:auto; padding:12px 20px 16px; border-top:1px solid rgba(255,255,255,0.06);">
        <a href="../logout.php" class="sidebar-nav-item" style="color:rgba(239,68,68,0.8);">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            <span class="nav-label">Sign Out</span>
        </a>
    </li>
</ul>
