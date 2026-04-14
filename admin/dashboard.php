<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if 2FA is required and not verified
if (isset($_SESSION['2fa_pending']) && $_SESSION['2fa_pending']) {
    header("Location: 2fa.php");
    exit();
}

// Check if 2FA is enabled for this admin
require_once '../backend/dbconn.php';
$stmt = $conn->prepare("SELECT two_factor_enabled FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    // Admin not found in database
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

if ($admin['two_factor_enabled'] == 1 && !isset($_SESSION['2fa_verified'])) {
    header("Location: 2fa.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/dashboard-charts.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>
    <script src="js/active-page.js"></script>
    <style>
        :root {
            --primary-color: #6C5CE7;
            --secondary-color: #A8A4FF;
            --success-color: #00B894;
            --warning-color: #FDCB6E;
            --danger-color: #FF7675;
            --dark-color: #2D3436;
            --light-color: #F5F6FA;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #B9A58C;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            padding: 2rem;
            margin-left: 250px;
            transition: var(--transition);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin: 0;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-container {
            display: flex;
            gap: 0.5rem;
            background: var(--light-color);
            padding: 0.5rem;
            border-radius: 10px;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: var(--dark-color);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .filter-btn:hover {
            background: rgba(108, 92, 231, 0.1);
            color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .print-btn {
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .print-btn:hover {
            background: #5B4BC4;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stat-card .icon.sales { background: rgba(108, 92, 231, 0.1); color: var(--primary-color); }
        .stat-card .icon.orders { background: rgba(0, 184, 148, 0.1); color: var(--success-color); }
        .stat-card .icon.products { background: rgba(253, 203, 110, 0.1); color: var(--warning-color); }
        .stat-card .icon.customers { background: rgba(255, 118, 117, 0.1); color: var(--danger-color); }

        .stat-card h3 {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin: 0 0 0.5rem 0;
            font-weight: 500;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            height: 400px;
        }

        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .chart-title {
            font-size: 1.2rem;
            color: var(--dark-color);
            margin: 0;
            font-weight: 600;
        }

        .calendar-container {
            grid-column: 1 / -1;
            height: 700px;
            min-height: 700px;
        }

        .calendar-container #peakDaysCalendar {
            height: 100% !important;
        }

        @media (max-width: 1024px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        @media print {
            html, body {
                width: 100%;
                height: 100%;
                background: #fff !important;
                color: #000 !important;
                font-size: 11px !important;
            }
            .main-content, .dashboard-container {
                margin: 0 !important;
                padding: 5px !important;
                width: 100% !important;
                box-sizing: border-box;
            }
            .dashboard-header, .chart-header {
                margin-bottom: 5px !important;
                padding-bottom: 2px !important;
            }
            .dashboard-header h1, .chart-title {
                font-size: 16px !important;
                margin-bottom: 5px !important;
            }
            .chart-container {
                min-height: 180px !important;
                max-height: 220px !important;
                height: 200px !important;
                margin-bottom: 10px !important;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                overflow: visible !important;
                page-break-inside: avoid !important;
            }
            .chart-container canvas {
                width: 100% !important;
                height: 160px !important;
                max-height: 180px !important;
            }
            .chart-grid {
                gap: 8px !important;
                page-break-before: auto !important;
            }
            /* Hide the calendar and its container when printing */
            .calendar-container {
                display: none !important;
            }
            /* Hide unnecessary elements */
            .navbar, .sidebar, .filter-container, .print-btn, .sidebar-toggle {
                display: none !important;
            }
            /* Remove browser print header/footer (URL, date, page number) */
            @page {
                margin: 10mm;
                size: A4 portrait;
            }
        }
    </style>
</head>
<body>
    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    <?php include("idle.php");?>
    
    <div class="main-content">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Dashboard Overview</h1>
                <div class="header-actions">
                    <div class="filter-container">
                        <button class="filter-btn" data-period="all">All</button>
                        <button class="filter-btn active" data-period="daily">Daily</button>
                        <button class="filter-btn" data-period="monthly">Monthly</button>
                        <button class="filter-btn" data-period="yearly">Yearly</button>
                    </div>
                    <button class="print-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon sales">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Total Sales</h3>
                    <p class="value" id="totalSales">₱0.00</p>
                </div>
                <div class="stat-card">
                    <div class="icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Total Orders</h3>
                    <p class="value" id="totalOrders">0</p>
                </div>
                <div class="stat-card">
                    <div class="icon products">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h3>Active Products</h3>
                    <p class="value" id="activeProducts">0</p>
                </div>
                <div class="stat-card">
                    <div class="icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Total Customers</h3>
                    <p class="value" id="totalCustomers">0</p>
                </div>
            </div>
            
            <div class="chart-grid">
                <!-- Stock Level Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Stock Level</h2>
                    </div>
                    <canvas id="stockLevelChart"></canvas>
                </div>
                
                <!-- Best Selling Products Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Best Selling Products</h2>
                    </div>
                    <canvas id="bestSellingChart"></canvas>
                </div>
                
                <!-- Least Selling Products Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Least Selling Products</h2>
                    </div>
                    <canvas id="leastSellingChart"></canvas>
                </div>
                
                <!-- Sales Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Sales Overview</h2>
                    </div>
                    <canvas id="salesChart"></canvas>
                </div>
                
                <!-- Inventory Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Inventory Status</h2>
                    </div>
                    <canvas id="inventoryChart"></canvas>
                </div>
                
                <!-- Peak Days Calendar -->
                <div class="chart-container calendar-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Peak Days</h2>
                    </div>
                    <div id="peakDaysCalendar"></div>
                </div>
            </div>
        </div>
    </div>

<script>
// Add this function before the DOMContentLoaded event
function prepareForPrint() {
    // Force charts to update before printing
    const charts = [
        window.charts.stockLevel,
        window.charts.bestSelling,
        window.charts.leastSelling,
        window.charts.sales,
        window.charts.inventory
    ];
    
    // Update all charts
    charts.forEach(chart => {
        if (chart) {
            chart.update('none'); // Update without animation
        }
    });
    
    // Wait for charts to update before printing
    setTimeout(() => {
        window.print();
    }, 100);
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart objects
    window.charts = {
        stockLevel: null,
        bestSelling: null,
        leastSelling: null,
        sales: null,
        inventory: null
    };
    
    // Set the default period to 'daily'
    let currentPeriod = 'daily';
    
    // Initialize all charts and calendar once
    initializeCharts(currentPeriod);
    initializeCalendar();
    
    // Handle period filter buttons with debounce
    const filterButtons = document.querySelectorAll('.filter-btn');
    let debounceTimeout;
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // If already active, don't do anything
            if (this.classList.contains('active')) return;
            
            // Clear any pending debounce
            clearTimeout(debounceTimeout);
            
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the selected period
            const selectedPeriod = this.getAttribute('data-period');
            
            // Debounce the update to prevent rapid multiple requests
            debounceTimeout = setTimeout(() => {
                // Only update if period actually changed
                if (currentPeriod !== selectedPeriod) {
                    currentPeriod = selectedPeriod;
                    updateCharts(currentPeriod);
                }
            }, 300);
        });
    });

    // Handle print button click
    const printBtn = document.querySelector('.print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', prepareForPrint);
    }

    // Update stats cards
    updateStatsCards();
    
    // Update stats every 5 minutes
    setInterval(updateStatsCards, 300000);
});

// Function to initialize all charts
function initializeCharts(period) {
    // Show loading indicators
    showChartLoading();
    
    // Set the default period to 'daily' if not specified
    period = period || 'daily';
    
    // Fetch all chart data in parallel
    Promise.all([
        fetchData(`stockLevel`, period),
        fetchData(`bestSelling`, period),
        fetchData(`leastSelling`, period),
        fetchData(`sales`, period),
        fetchData(`inventory`, period)
    ])
    .then(([stockData, bestSellingData, leastSellingData, salesData, inventoryData]) => {
        renderStockLevelChart(stockData);
        renderBestSellingChart(bestSellingData);
        renderLeastSellingChart(leastSellingData);
        renderSalesChart(salesData);
        renderInventoryChart(inventoryData);
    })
    .catch(error => {
        console.error('Error initializing charts:', error);
        showChartError();
    });
}

// Helper function to show loading state
function showChartLoading() {
    document.querySelectorAll('.chart-container canvas').forEach(canvas => {
        canvas.style.opacity = '0.5';
    });
}

// Helper function to show error state
function showChartError() {
    document.querySelectorAll('.chart-container').forEach(container => {
        container.innerHTML += '<div class="chart-error">Failed to load chart data. Please try again.</div>';
    });
}

// Function to update all charts with new data without recreating them
function updateCharts(period) {
    // Show loading state
    showChartLoading();
    
    // Fetch and update each chart
    Promise.all([
        fetchData(`stockLevel`, period),
        fetchData(`bestSelling`, period),
        fetchData(`leastSelling`, period),
        fetchData(`sales`, period),
        fetchData(`inventory`, period)
    ])
    .then(([stockData, bestSellingData, leastSellingData, salesData, inventoryData]) => {
        updateStockLevelChart(stockData);
        updateBestSellingChart(bestSellingData);
        updateLeastSellingChart(leastSellingData);
        updateSalesChart(salesData);
        updateInventoryChart(inventoryData);
    })
    .catch(error => {
        console.error('Error updating charts:', error);
        Swal.fire({
            title: 'Error',
            text: 'Failed to update chart data. Please try again.',
            icon: 'error'
        });
    });
}

// Generic function to fetch data with caching
const dataCache = {};
function fetchData(chartType, period) {
    const cacheKey = `${chartType}_${period}`;
    
    // Check if data is in cache and not expired
    if (dataCache[cacheKey] && (Date.now() - dataCache[cacheKey].timestamp < 60000)) {
        return Promise.resolve(dataCache[cacheKey].data);
    }
    
    // If not in cache or expired, fetch from server
    return fetch(`../backend/fetch_chart_data.php?chart=${chartType}&period=${period}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Cache the data with timestamp
            dataCache[cacheKey] = {
                data: data,
                timestamp: Date.now()
            };
            return data;
        });
}

// Stock Level Chart
function renderStockLevelChart(data) {
    const ctx = document.getElementById('stockLevelChart').getContext('2d');
    
    window.charts.stockLevel = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Current Stock',
                data: data.values,
                backgroundColor: data.colors,
                borderColor: data.borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Stock Amount'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Ingredients'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.raw} ${data.units[context.dataIndex]}`;
                        }
                    }
                }
            }
        }
    });
}

function updateStockLevelChart(data) {
    if (window.charts.stockLevel) {
        window.charts.stockLevel.data.labels = data.labels;
        window.charts.stockLevel.data.datasets[0].data = data.values;
        window.charts.stockLevel.data.datasets[0].backgroundColor = data.colors;
        window.charts.stockLevel.data.datasets[0].borderColor = data.borderColors;
        window.charts.stockLevel.update();
        document.getElementById('stockLevelChart').style.opacity = '1';
    }
}

// Best Selling Products Chart
function renderBestSellingChart(data) {
    const ctx = document.getElementById('bestSellingChart').getContext('2d');
    
    window.charts.bestSelling = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#8AC249', '#EA5F89', '#2D5082', '#F29D38'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function updateBestSellingChart(data) {
    if (window.charts.bestSelling) {
        window.charts.bestSelling.data.labels = data.labels;
        window.charts.bestSelling.data.datasets[0].data = data.values;
        window.charts.bestSelling.update();
        document.getElementById('bestSellingChart').style.opacity = '1';
    }
}

// Least Selling Products Chart
function renderLeastSellingChart(data) {
    const ctx = document.getElementById('leastSellingChart').getContext('2d');
    
    window.charts.leastSelling = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#8AC249', '#EA5F89', '#2D5082', '#F29D38'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function updateLeastSellingChart(data) {
    if (window.charts.leastSelling) {
        window.charts.leastSelling.data.labels = data.labels;
        window.charts.leastSelling.data.datasets[0].data = data.values;
        window.charts.leastSelling.update();
        document.getElementById('leastSellingChart').style.opacity = '1';
    }
}

// Sales Chart
function renderSalesChart(data) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    window.charts.sales = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Sales (PHP)',
                data: data.values,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales Amount (PHP)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: data.xAxisTitle
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Sales: PHP ${context.raw.toFixed(2)}`;
                        }
                    }
                }
            }
        }
    });
}

function updateSalesChart(data) {
    if (window.charts.sales) {
        window.charts.sales.data.labels = data.labels;
        window.charts.sales.data.datasets[0].data = data.values;
        window.charts.sales.options.scales.x.title.text = data.xAxisTitle;
        window.charts.sales.update();
        document.getElementById('salesChart').style.opacity = '1';
    }
}

// Inventory Chart
function renderInventoryChart(data) {
    const ctx = document.getElementById('inventoryChart').getContext('2d');
    
    window.charts.inventory = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    '#28a745', // Good
                    '#ffc107', // Warning
                    '#dc3545'  // Critical
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            return `${label}: ${value} ingredients`;
                        }
                    }
                }
            }
        }
    });
}

function updateInventoryChart(data) {
    if (window.charts.inventory) {
        window.charts.inventory.data.labels = data.labels;
        window.charts.inventory.data.datasets[0].data = data.values;
        window.charts.inventory.update();
        document.getElementById('inventoryChart').style.opacity = '1';
    }
}

// Initialize Calendar only once
let calendar = null;
function initializeCalendar() {
    fetchData('peakDays', 'all')
        .then(data => {
            const calendarEl = document.getElementById('peakDaysCalendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                events: data.events,
                eventColor: '#36A2EB',
                height: 'auto',
                eventClick: function(info) {
                    Swal.fire({
                        title: info.event.title,
                        html: `
                            <p>Date: ${info.event.start.toLocaleDateString()}</p>
                            <p>Orders: ${info.event.extendedProps.orders}</p>
                            <p>Revenue: PHP ${info.event.extendedProps.revenue.toFixed(2)}</p>
                        `,
                        icon: 'info'
                    });
                }
            });
            calendar.render();
        })
        .catch(error => {
            console.error('Error initializing calendar:', error);
            document.getElementById('peakDaysCalendar').innerHTML = 
                '<div class="chart-error">Failed to load calendar data. Please refresh the page.</div>';
        });
}

// Add this function to update stats cards
function updateStatsCards() {
    fetch('../backend/fetch_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalSales').textContent = `₱${parseFloat(data.total_sales).toFixed(2)}`;
            document.getElementById('totalOrders').textContent = data.total_orders;
            document.getElementById('activeProducts').textContent = data.active_products;
            document.getElementById('totalCustomers').textContent = data.total_customers;
        })
        .catch(error => console.error('Error fetching stats:', error));
}
</script>
</body>
</html>