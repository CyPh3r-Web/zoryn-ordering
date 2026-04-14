<!-- admin-sidebar.php -->
<?php
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Function to check if a menu item is active
function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-menu">
        <ul>
            <li class="sidebar-item <?php echo isActive('dashboard.php'); ?>">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo isActive('products.php'); ?>">
                <a href="products.php">
                    <i class="fas fa-mug-hot"></i>
                    <span class="sidebar-text">Products</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo isActive('inventory.php'); ?>">
                <a href="inventory.php">
                    <i class="fas fa-box"></i>
                    <span class="sidebar-text">Inventory</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo isActive('reports.php'); ?>">
                <a href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo isActive('users.php'); ?>">
                <a href="users.php">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text">Users</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</div>

