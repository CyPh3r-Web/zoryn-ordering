<?php
session_start();
?>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="index.php">Zoryn</a>
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="milky-series.php">Milky Series</a>
            <a href="choco-ey.php">Choco-ey Series</a>
            <a href="order-details.php">My Orders</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
    .navbar {
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1rem 0;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 1rem;
    }

    .nav-brand a {
        font-size: 1.5rem;
        font-weight: 700;
        color: #333;
        text-decoration: none;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .nav-links a {
        color: #333;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }

    .nav-links a:hover {
        color: #4CAF50;
    }

    .login-btn {
        background-color: #4CAF50;
        color: white !important;
        padding: 0.5rem 1rem;
        border-radius: 5px;
    }

    .login-btn:hover {
        background-color: #45a049;
    }

    .user-dropdown {
        position: relative;
        display: inline-block;
    }

    .user-btn {
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        color: #333;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #fff;
        min-width: 160px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 5px;
        z-index: 1;
    }

    .user-dropdown:hover .dropdown-content {
        display: block;
    }

    .dropdown-content a {
        color: #333;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background-color: #f5f5f5;
    }
</style> 