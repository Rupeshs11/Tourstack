<?php
// Determine current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-hotel"></i>
        <span>TOUR STACK</span>
    </div>
    <div class="sidebar-nav">
        <div class="sidebar-nav-title">Main</div>
        <a href="admin/admin_dashboard.php" class="sidebar-nav-item <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin/admin_customers.php" class="sidebar-nav-item <?php echo ($current_page == 'admin_customers.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
        <a href="admin/admin_bookings.php" class="sidebar-nav-item <?php echo ($current_page == 'admin_bookings.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>Bookings</span>
        </a>
        <a href="admin/admin_new_booking.php" class="sidebar-nav-item <?php echo ($current_page == 'admin_new_booking.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-plus"></i>
            <span>New Booking</span>
        </a>
        
        <div class="sidebar-divider"></div>
        <div class="sidebar-nav-title">Management</div>
        <a href="admin/simple_tours.php" class="sidebar-nav-item <?php echo ($current_page == 'simple_tours.php') ? 'active' : ''; ?>">
            <i class="fas fa-route"></i>
            <span>Tours</span>
        </a>
        <a href="admin/simple_rooms.php" class="sidebar-nav-item <?php echo ($current_page == 'simple_rooms.php') ? 'active' : ''; ?>">
            <i class="fas fa-bed"></i>
            <span>Rooms</span>
        </a>
        <a href="admin/simple_packages.php" class="sidebar-nav-item <?php echo ($current_page == 'simple_packages.php') ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Packages</span>
        </a>
        <a href="admin/admin_users.php" class="sidebar-nav-item <?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i>
            <span>Users</span>
        </a>
    </div>
</aside> 