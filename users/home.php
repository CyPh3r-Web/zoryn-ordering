<?php
session_start();
require_once '../navigation/navbar.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <style>
        :root {
            --primary-bg: #634832;
            --nav-bg: #ece0d1;
            --text-dark: #634832;
            --active-tab: #4A3527;
            --inactive-tab: #F5EBE0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--primary-bg);
        }

        /* Main content styles */
        .main-content {
            min-height: 100vh;
            padding: 20px;
            width: 100%;
        }

        /* Sidebar styles - only applied for cashier users */
        .cashier-layout .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }

        .cashier-layout .main-content.expanded {
            margin-left: 0;
            width: 100%;
        }

        /* Sidebar toggle button styles */
        .sidebar-toggle {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
            z-index: 1001;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
        }

        .sidebar-toggle i {
            filter: brightness(0) invert(1);
        }

        .menu-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 2rem 0;
        }

        .tab-btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background-color: var(--active-tab);
            color: white;
        }

        .tab-btn:not(.active) {
            background-color: var(--inactive-tab);
            color: var(--text-dark);
        }

        .slider-container {
            position: relative;
            margin: 0 auto;
            min-height: 350px;
            overflow: hidden;
        }

        .bg-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../assets/zoryn/torn_bg.png');
            background-position: center;
            background-repeat: no-repeat;
            background-size: 100% 850px;
            z-index: 0;
        }

        .products-container {
            display: flex;
            transition: transform 0.5s ease;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .products-section {
            flex: 0 0 100%; 
            width: 100%;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 2rem;
        }

        .product-category {
            text-align: center;
            width: 250px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .product-category:hover {
            transform: scale(1.05);
        }

        .product-items {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            width: 100%;
            padding: 1rem;
        }

        .milky-images img, .choco-images img, .rookie-images img, .product-image img {
            width: 100%;
            height: 250px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .milky-images img {
            width: 100%;
            max-width: 350px;
        }

        .choco-images img {
            width: 100%;
            max-width: 300px;
        }

        .rookie-images img {
            width: 100%;
            max-width: 300px;
        }

        .product-items h3 {
            color: #E5890A;
            font-size: 1.2rem;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
        }

        .navigation-dots {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            position: relative;
            z-index: 2;
        }

        .dot {
            height: 10px;
            width: 10px;
            margin: 0 5px;
            border-radius: 50%;
            background-color: var(--inactive-tab);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .dot.active {
            background-color: var(--active-tab);
        }
    </style>
</head>
<body>
    <?php include("idle.php");?>
    <?php 
    $isCashier = isset($_SESSION['role']) && $_SESSION['role'] === 'cashier';
    if ($isCashier) {
        include '../navigation/cashier-sidebar.php';
    }
    ?>

    <div class="main-content <?php echo $isCashier ? 'cashier-layout' : ''; ?>">
        <div class="menu-tabs">
            <button class="tab-btn active" data-tab="non-coffee">Non-Coffee</button>
            <button class="tab-btn" data-tab="coffee">Coffee Series</button>
        </div>

        <div class="slider-container">
            <div class="bg-container"></div>
            <div class="products-container">
                <!-- Non-Coffee Section -->
                <div class="products-section active" id="non-coffee-section">
                    <div class="product-category" onclick="window.location.href='milky-series.php'" style="text-decoration: none;">
                        <div class="product-items">
                            <div class="milky-images">
                                <img src="../assets/zoryn/3cups.png" alt="Milky Series">
                            </div>
                            <h3>Milky Series</h3>
                        </div>
                    </div>

                      <div class="product-category" onclick="window.location.href='rookie-series.php'">
                        <div class="product-items">
                            <div class="rookie-images">
                                <img src="../assets/zoryn/rookie.png" alt="Rookie Series">         
                            </div>
                            <h3>Rookie Series</h3>
                        </div>
                    </div>

                      <div class="product-category" onclick="window.location.href='choco-ey.php'">
                        <div class="product-items">
                            <div class="choco-images">
                                <img src="../assets/zoryn/choco-ey.png" alt="Choco-ey Series">
                            </div>
                            <h3>Choco-ey Series</h3>
                        </div>
                    </div>
                </div>

                <!-- Coffee Series Section -->
                <div class="products-section" id="coffee-section">
                      <div class="product-category" onclick="window.location.href='coffee-series.php'">
                        <div class="product-items">
                            <div class="product-image">
                                <img src="../assets/zoryn/coffee/iced_pureblack.png" alt="Espresso Series">
                            </div>
                            <h3>Iced Pure Black</h3>
                        </div>
                    </div>

                    <div class="product-category" onclick="window.location.href='coffee-series.php'">
                        <div class="product-items">
                            <div class="product-image">
                            <img src="../assets/zoryn/coffee/carame_macchiato.png" alt="Latte Series">         
                            </div>
                            <h3>Caramel Macchiato</h3>
                        </div>
                    </div>

                    <div class="product-category" onclick="window.location.href='coffee-series.php'">
                        <div class="product-items">
                            <div class="product-image">
                            <img src="../assets/zoryn/coffee/spanish_latte.png" alt="Cappuccino Series">
                            </div>
                            <h3>Spanish Latte</h3>
                        </div>
                    </div>

                    <div class="product-category" onclick="window.location.href='coffee-series.php'">
                        <div class="product-items">
                            <div class="product-image">
                            <img src="../assets/zoryn/coffee/coffee_latte.png" alt="Americano Series">         
                            </div>
                            <h3>Coffee Latte</h3>
                        </div>
                    </div>

                    <div class="product-category" onclick="window.location.href='gold-series.php'">
                        <div class="product-items">
                            <div class="product-image">
                            <img src="../assets/zoryn/coffee/gold_seris/goldseries.png" alt="Americano Series">         
                            </div>
                            <h3>Gold Series</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="navigation-dots">
                <span class="dot active" data-tab="non-coffee"></span>
                <span class="dot" data-tab="coffee"></span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const productsContainer = document.querySelector('.products-container');
            const productSections = document.querySelectorAll('.products-section');
            const dots = document.querySelectorAll('.dot');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            // Only run sidebar code if elements exist
            if (sidebar && mainContent && sidebarToggle) {
                // Check if sidebar state is stored in localStorage
                const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                
                // Set initial state based on localStorage
                if (sidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                    sidebarToggle.classList.add('active');
                }

                // Add click event to sidebar toggle button
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    sidebarToggle.classList.toggle('active');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
            }
            
            // Function to switch tabs
            function switchTab(tabId) {
                // Update active tab button
                tabButtons.forEach(button => {
                    if (button.dataset.tab === tabId) {
                        button.classList.add('active');
                    } else {
                        button.classList.remove('active');
                    }
                });
                
                // Update active dot
                dots.forEach(dot => {
                    if (dot.dataset.tab === tabId) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
                
                // Slide to appropriate section
                if (tabId === 'coffee') {
                    productsContainer.style.transform = 'translateX(-100%)';
                } else {
                    productsContainer.style.transform = 'translateX(0)';
                }
            }
            
            // Add click event to tab buttons
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    switchTab(tabId);
                });
            });
            
            // Add click event to dots
            dots.forEach(dot => {
                dot.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    switchTab(tabId);
                });
            });
        });
    </script>
</body>
</html>