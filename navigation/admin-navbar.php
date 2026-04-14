<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
?>

<nav class="navbar">
    <div class="nav-left">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <img src="../assets/zoryn/logo.png" alt="Zoryn Logo" class="logo">
    </div>
    <div class="nav-right">
        <div class="nav-icons dropdown">
            <img src="../assets/zoryn/bell.png" alt="Notifications" class="icon" id="notificationIcon">
            <div class="dropdown-content notification-dropdown">
                <h3>Notifications</h3>
                <div class="notification-item">
                    <p>New order received</p>
                    <span>5 minutes ago</span>
                </div>
                <div class="notification-item">
                    <p>Inventory low alert</p>
                    <span>30 minutes ago</span>
                </div>
                <a href="notifications.php" class="view-all">View All Notifications</a>
            </div>
        </div>
        <div class="user-profile dropdown">
            <div class="profile-trigger">
                <div class="profile-pic"></div>
                <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-content user-dropdown">
                <a href="profile.php">My Profile</a>
                <a href="settings-2fa.php">Settings</a>
                <a href="#" id="logoutLink">Logout</a>
            </div>
        </div>
    </div>
</nav>

<style>
/* Dropdown styles */
.dropdown {
    position: relative;
    cursor: pointer;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 200px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 1000;
    border-radius: 4px;
    overflow: hidden;
}

.dropdown:hover .dropdown-content {
    display: block;
}

/* User dropdown styles */
.user-dropdown {
    top: 100%;
    padding: 10px 0;
}

.user-dropdown a {
    color: #333;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
    transition: background-color 0.2s;
}

.user-dropdown a:hover {
    background-color: #f5f5f5;
}

/* Notification dropdown styles */
.notification-dropdown {
    top: 100%;
    right: -50px;
    width: 300px;
    padding: 10px 0;
}

.notification-dropdown h3 {
    padding: 10px 15px;
    margin: 0;
    border-bottom: 1px solid #eee;
    font-size: 16px;
}

.notification-item {
    padding: 10px 15px;
    border-bottom: 1px solid #f0f0f0;
}

.notification-item p {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.notification-item span {
    color: #888;
    font-size: 12px;
}

.view-all {
    display: block;
    text-align: center;
    padding: 10px;
    color: #0066cc;
    text-decoration: none;
    font-size: 14px;
}

.view-all:hover {
    background-color: #f5f5f5;
}

/* Profile trigger styling */
.profile-trigger {
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-trigger i {
    font-size: 12px;
    margin-left: 4px;
}

/* Existing navbar styling enhancements */
.nav-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');
        const logoutLink = document.getElementById('logoutLink');
        
        // Check if sidebar state is stored in localStorage
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        // Set initial state based on localStorage
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            sidebarToggle.querySelector('i').classList.remove('fa-bars');
            sidebarToggle.querySelector('i').classList.add('fa-bars');
        }
        
        // Logout confirmation
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of your account",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        });
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Store state in localStorage
            if (sidebar.classList.contains('collapsed')) {
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        });
        
        // Optional: Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('.dropdown-content');
            dropdowns.forEach(dropdown => {
                if (!event.target.closest('.dropdown')) {
                    dropdown.style.display = 'none';
                }
            });
        });
        
        // Re-enable hover functionality after click outside
        const dropdownContainers = document.querySelectorAll('.dropdown');
        dropdownContainers.forEach(container => {
            container.addEventListener('mouseleave', function() {
                setTimeout(() => {
                    const dropdownContent = this.querySelector('.dropdown-content');
                    if (dropdownContent) {
                        dropdownContent.style.display = 'none';
                    }
                }, 100);
            });
            
            container.addEventListener('mouseenter', function() {
                const dropdownContent = this.querySelector('.dropdown-content');
                if (dropdownContent) {
                    dropdownContent.style.display = 'block';
                }
            });
        });
    });
</script>