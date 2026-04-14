<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Function to check if a menu item is active
function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}

// Only show sidebar if user is a cashier
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cashier'):
?>

<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h3>Cashier Menu</h3>
    </div>
    <div class="sidebar-content">
        <ul class="sidebar-menu">
            <li class="<?php echo isActive('order-details.php'); ?>">
                <a href="order-details.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Orders</span>
                </a>
            </li>
            <li class="<?php echo isActive('orders.php'); ?>">
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
/* Base sidebar styles */
.sidebar {
    width: 250px;
    height: 100vh;
    background-color: #fff;
    position: fixed;
    left: 0;
    top: 0;
    padding-top: 80px; /* Account for navbar height */
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease-in-out;
    z-index: 999;
    overflow-x: hidden; /* Prevent content from showing outside */
}

/* Collapsed state */
.sidebar.collapsed {
    transform: translateX(-250px);
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.sidebar-header h3 {
    margin: 0;
    color: #3c2415;
    font-size: 1.2rem;
    white-space: nowrap;
    overflow: hidden;
}

.sidebar-content {
    padding: 20px 0;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 5px;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
    gap: 15px; /* Add consistent spacing between icon and text */
}

.sidebar-menu li a:hover {
    background-color: #f8f9fa;
    color: #3c2415;
}

.sidebar-menu li.active a {
    background-color: #f0f0f0;
    color: #3c2415;
    border-left: 4px solid #3c2415;
}

.sidebar-menu li a i {
    width: 20px;
    font-size: 1.1rem;
    color: #3c2415;
    text-align: center; /* Center the icon */
}

.sidebar-menu li a span {
    white-space: nowrap;
    overflow: hidden;
    font-size: 0.95rem;
    line-height: 1.2; /* Improve text line height */
}

/* Main content adjustment */
.main-content {
    margin-left: 250px;
    padding: 20px;
    transition: all 0.3s ease-in-out;
    width: calc(100% - 250px);
}

.main-content.expanded {
    margin-left: 0;
    width: 100%;
}

/* Toggle button styles */
.sidebar-toggle-btn {
    position: fixed;
    left: 250px;
    top: 80px;
    background-color: #fff;
    border: none;
    border-radius: 0 4px 4px 0;
    padding: 10px;
    cursor: pointer;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    transition: all 0.3s ease-in-out;
}

.sidebar-toggle-btn.active {
    left: 0;
}

.sidebar-toggle-btn.active i {
    transform: rotate(180deg);
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
    .sidebar {
        width: 250px;
        transform: translateX(-100%);
        padding-top: 60px;
    }
    
    .sidebar.collapsed {
        transform: translateX(-250px);
    }
    
    .sidebar-toggle-btn {
        left: auto;
        right: 10px;
        top: 10px;
    }
    
    .sidebar-toggle-btn.active {
        left: auto;
        right: 10px;
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    
    // Check localStorage for sidebar state
    const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    // Initialize sidebar state
    if (isSidebarCollapsed) {
        sidebar.classList.add('collapsed');
        if (mainContent) mainContent.classList.add('expanded');
        if (sidebarToggle) sidebarToggle.classList.add('active');
    }
    
    // Toggle sidebar when clicking the button
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
            sidebarToggle.classList.toggle('active');
            
            // Store sidebar state in localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // Close sidebar automatically on smaller screens when clicking menu items
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                if (mainContent) mainContent.classList.add('expanded');
                if (sidebarToggle) sidebarToggle.classList.add('active');
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        });
    });
});
</script>

<?php endif; ?>