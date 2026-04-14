<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/reports.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <!-- Active Page Detection -->
    <script src="js/active-page.js"></script>
</head>
<body>
    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    
    <div class="main-content">
        <div class="reports-container">
            <div class="reports-header">
                <h1>Reports Dashboard</h1>
                <div class="reports-filter">
                    <select id="reportType">
                        <option value="sales">Sales Report</option>
                        <option value="products">Product Performance</option>
                        <option value="feedback">Customer Feedback</option>
                    </select>
                    <input type="month" id="reportDateFilter" placeholder="Select Month">
                </div>
            </div>
            
            <!-- Report Summary Cards -->
            <div class="report-summary" id="reportSummary">
                <!-- Summary cards will be loaded dynamically -->
            </div>
            
            <!-- Charts Section -->
            <div class="charts-container">
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 id="chartTitle">Sales Overview</h2>
                    </div>
                    <div class="chart-body">
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 id="secondaryChartTitle">Product Ratings</h2>
                    </div>
                    <div class="chart-body">
                        <canvas id="secondaryChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Report Data -->
            <div class="report-data-container">
                <div class="report-data-header">
                    <h2 id="reportTableTitle">Detailed Report</h2>
                    <button id="exportReport" class="export-btn">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                </div>
                <div class="report-table-container">
                    <table class="report-table">
                        <thead id="reportTableHead">
                            <!-- Table headers will be dynamically updated -->
                        </thead>
                        <tbody id="reportTableBody">
                            <!-- Report data will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables for charts
            let mainChart = null;
            let secondaryChart = null;
            
            // Get the current date and set as default filter
            const currentDate = new Date();
            const currentMonth = currentDate.toISOString().slice(0, 7); // Format: YYYY-MM
            document.getElementById('reportDateFilter').value = currentMonth;
            
            // Load initial report (Sales Report by default)
            loadReport('sales', currentMonth);
            
            // Report type change handler
            document.getElementById('reportType').addEventListener('change', function() {
                const reportType = this.value;
                const dateFilter = document.getElementById('reportDateFilter').value;
                loadReport(reportType, dateFilter);
            });
            
            // Date filter handler
            document.getElementById('reportDateFilter').addEventListener('change', function() {
                const reportType = document.getElementById('reportType').value;
                const dateFilter = this.value;
                loadReport(reportType, dateFilter);
            });
            
            // Export button handler
            document.getElementById('exportReport').addEventListener('click', function() {
                const reportType = document.getElementById('reportType').value;
                const dateFilter = document.getElementById('reportDateFilter').value;
                exportReport(reportType, dateFilter);
            });
            
            // Function to load report data
            function loadReport(reportType, dateFilter) {
                fetch('../backend/generate_reports.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_report_data&report_type=${reportType}&date=${dateFilter}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI based on report type
                        updateReportUI(reportType, data);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to load report data',
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading report data:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while loading report data',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                });
            }
            
            // Function to update UI based on report type
            function updateReportUI(reportType, data) {
                // Update summary cards
                updateSummaryCards(reportType, data.summary);
                
                // Update charts
                updateCharts(reportType, data.chartData);
                
                // Update table
                updateReportTable(reportType, data.tableData);
            }
            
            // Function to update summary cards
            function updateSummaryCards(reportType, summary) {
                const summaryContainer = document.getElementById('reportSummary');
                summaryContainer.innerHTML = '';
                
                if (reportType === 'sales') {
                    summaryContainer.innerHTML = `
                        <div class="summary-panel total-sales">
                            <div class="panel-icon"><i class="fas fa-dollar-sign"></i></div>
                            <div class="panel-info">
                                <h3>Total Sales</h3>
                                <p class="panel-count">₱${parseFloat(summary.totalSales).toFixed(2)}</p>
                            </div>
                        </div>
                        <div class="summary-panel total-orders">
                            <div class="panel-icon"><i class="fas fa-shopping-cart"></i></div>
                            <div class="panel-info">
                                <h3>Total Orders</h3>
                                <p class="panel-count">${summary.totalOrders}</p>
                            </div>
                        </div>
                        <div class="summary-panel avg-order">
                            <div class="panel-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="panel-info">
                                <h3>Average Order Value</h3>
                                <p class="panel-count">₱${parseFloat(summary.avgOrderValue).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                } else if (reportType === 'products') {
                    summaryContainer.innerHTML = `
                        <div class="summary-panel top-product">
                            <div class="panel-icon"><i class="fas fa-trophy"></i></div>
                            <div class="panel-info">
                                <h3>Top Product</h3>
                                <p class="panel-count">${summary.topProduct}</p>
                            </div>
                        </div>
                        <div class="summary-panel total-products">
                            <div class="panel-icon"><i class="fas fa-coffee"></i></div>
                            <div class="panel-info">
                                <h3>Products Sold</h3>
                                <p class="panel-count">${summary.totalProductsSold}</p>
                            </div>
                        </div>
                        <div class="summary-panel avg-rating">
                            <div class="panel-icon"><i class="fas fa-star"></i></div>
                            <div class="panel-info">
                                <h3>Average Rating</h3>
                                <p class="panel-count">${parseFloat(summary.avgRating).toFixed(1)}/5</p>
                            </div>
                        </div>
                    `;
                } else if (reportType === 'feedback') {
                    summaryContainer.innerHTML = `
                        <div class="summary-panel total-feedback">
                            <div class="panel-icon"><i class="fas fa-comments"></i></div>
                            <div class="panel-info">
                                <h3>Total Feedback</h3>
                                <p class="panel-count">${summary.totalFeedback}</p>
                            </div>
                        </div>
                        <div class="summary-panel avg-feedback">
                            <div class="panel-icon"><i class="fas fa-star"></i></div>
                            <div class="panel-info">
                                <h3>Average Rating</h3>
                                <p class="panel-count">${parseFloat(summary.avgRating).toFixed(1)}/5</p>
                            </div>
                        </div>
                        <div class="summary-panel top-rated">
                            <div class="panel-icon"><i class="fas fa-award"></i></div>
                            <div class="panel-info">
                                <h3>Top Rated Product</h3>
                                <p class="panel-count">${summary.topRatedProduct}</p>
                            </div>
                        </div>
                    `;
                }
            }
            
            // Function to update charts
            function updateCharts(reportType, chartData) {
                const chartContext = document.getElementById('mainChart').getContext('2d');
                const secondaryChartContext = document.getElementById('secondaryChart').getContext('2d');
                
                // Destroy existing charts if they exist
                if (mainChart) mainChart.destroy();
                if (secondaryChart) secondaryChart.destroy();
                
                // Update chart titles
                if (reportType === 'sales') {
                    document.getElementById('chartTitle').textContent = 'Sales Overview';
                    document.getElementById('secondaryChartTitle').textContent = 'Order Types';
                    
                    // Create main chart (Sales by day)
                    mainChart = new Chart(chartContext, {
                        type: 'line',
                        data: {
                            labels: chartData.main.labels,
                            datasets: [{
                                label: 'Sales Amount (₱)',
                                data: chartData.main.data,
                                backgroundColor: 'rgba(99, 72, 50, 0.1)',
                                borderColor: 'rgba(99, 72, 50, 1)',
                                borderWidth: 2,
                                tension: 0.1,
                                fill: true,
                                pointBackgroundColor: 'rgba(99, 72, 50, 1)',
                                pointBorderColor: '#fff',
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value;
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            }
                        }
                    });
                    
                    // Create secondary chart (Order types)
                    secondaryChart = new Chart(secondaryChartContext, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.secondary.labels,
                            datasets: [{
                                data: chartData.secondary.data,
                                backgroundColor: [
                                    'rgba(99, 72, 50, 0.8)',    // Dark brown
                                    'rgba(236, 224, 209, 0.8)', // Light brown
                                    'rgba(74, 53, 39, 0.8)',    // Darker brown
                                    'rgba(165, 142, 118, 0.8)', // Medium brown
                                    'rgba(210, 180, 140, 0.8)'  // Tan
                                ],
                                borderColor: [
                                    'rgba(99, 72, 50, 1)',
                                    'rgba(236, 224, 209, 1)',
                                    'rgba(74, 53, 39, 1)',
                                    'rgba(165, 142, 118, 1)',
                                    'rgba(210, 180, 140, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'right',
                                }
                            }
                        }
                    });
                    
                } else if (reportType === 'products') {
                    document.getElementById('chartTitle').textContent = 'Product Sales';
                    document.getElementById('secondaryChartTitle').textContent = 'Product Ratings';
                    
                    // Create main chart (Product sales)
                    mainChart = new Chart(chartContext, {
                        type: 'bar',
                        data: {
                            labels: chartData.main.labels,
                            datasets: [{
                                label: 'Units Sold',
                                data: chartData.main.data,
                                backgroundColor: [
                                    'rgba(54, 162, 235, 0.8)',  // Blue
                                    'rgba(255, 99, 132, 0.8)',  // Pink
                                    'rgba(75, 192, 192, 0.8)',  // Teal
                                    'rgba(255, 159, 64, 0.8)',  // Orange
                                    'rgba(153, 102, 255, 0.8)', // Purple
                                    'rgba(255, 205, 86, 0.8)',  // Yellow
                                    'rgba(201, 203, 207, 0.8)'  // Gray
                                ],
                                borderColor: [
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(255, 205, 86, 1)',
                                    'rgba(201, 203, 207, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            }
                        }
                    });
                    
                    // Create secondary chart (Product ratings)
                    secondaryChart = new Chart(secondaryChartContext, {
                        type: 'radar',
                        data: {
                            labels: chartData.secondary.labels,
                            datasets: [{
                                label: 'Average Rating',
                                data: chartData.secondary.data,
                                backgroundColor: 'rgba(99, 72, 50, 0.2)',
                                borderColor: 'rgba(99, 72, 50, 1)',
                                borderWidth: 2,
                                pointBackgroundColor: 'rgba(99, 72, 50, 1)',
                                pointBorderColor: '#fff',
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            scales: {
                                r: {
                                    beginAtZero: true,
                                    max: 5,
                                    ticks: {
                                        stepSize: 1,
                                        color: 'rgba(0, 0, 0, 0.5)'
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    angleLines: {
                                        color: 'rgba(0, 0, 0, 0.1)'
                                    },
                                    pointLabels: {
                                        color: 'rgba(0, 0, 0, 0.7)'
                                    }
                                }
                            }
                        }
                    });
                    
                } else if (reportType === 'feedback') {
                    document.getElementById('chartTitle').textContent = 'Rating Distribution';
                    document.getElementById('secondaryChartTitle').textContent = 'Sentiment Analysis';
                    
                    // Create main chart (Rating distribution)
                    mainChart = new Chart(chartContext, {
                        type: 'bar',
                        data: {
                            labels: chartData.main.labels,
                            datasets: [{
                                label: 'Number of Ratings',
                                data: chartData.main.data,
                                backgroundColor: [
                                    'rgba(244, 67, 54, 0.8)',   // 1 star - Red
                                    'rgba(255, 152, 0, 0.8)',   // 2 stars - Orange
                                    'rgba(255, 235, 59, 0.8)',  // 3 stars - Yellow
                                    'rgba(76, 175, 80, 0.8)',   // 4 stars - Green
                                    'rgba(33, 150, 243, 0.8)'   // 5 stars - Blue
                                ],
                                borderColor: [
                                    'rgba(244, 67, 54, 1)',
                                    'rgba(255, 152, 0, 1)',
                                    'rgba(255, 235, 59, 1)',
                                    'rgba(76, 175, 80, 1)',
                                    'rgba(33, 150, 243, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                    
                    // Create secondary chart (Sentiment analysis)
                    secondaryChart = new Chart(secondaryChartContext, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.secondary.labels,
                            datasets: [{
                                data: chartData.secondary.data,
                                backgroundColor: [
                                    'rgba(76, 175, 80, 0.8)',   // Positive - Green
                                    'rgba(244, 67, 54, 0.8)',   // Negative - Red
                                    'rgba(158, 158, 158, 0.8)'  // Neutral - Grey
                                ],
                                borderColor: [
                                    'rgba(76, 175, 80, 1)',
                                    'rgba(244, 67, 54, 1)',
                                    'rgba(158, 158, 158, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });
                }
            }
            
            // Function to update report table
            function updateReportTable(reportType, tableData) {
                const tableHead = document.getElementById('reportTableHead');
                const tableBody = document.getElementById('reportTableBody');
                
                // Update table title
                if (reportType === 'sales') {
                    document.getElementById('reportTableTitle').textContent = 'Sales Details';
                    tableHead.innerHTML = `
                        <tr>
                            <th>Date</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Order Type</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                        </tr>
                    `;
                } else if (reportType === 'products') {
                    document.getElementById('reportTableTitle').textContent = 'Product Performance';
                    tableHead.innerHTML = `
                        <tr>
                            <th>Product Name</th>
                            <th>Units Sold</th>
                            <th>Total Revenue</th>
                            <th>Average Rating</th>
                        </tr>
                    `;
                } else if (reportType === 'feedback') {
                    document.getElementById('reportTableTitle').textContent = 'Customer Feedback';
                    tableHead.innerHTML = `
                        <tr>
                            <th>Date</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Comment</th>
                            <th>Sentiment</th>
                        </tr>
                    `;
                }
                
                // Update table body
                tableBody.innerHTML = '';
                if (tableData && tableData.length > 0) {
                    tableData.forEach(row => {
                        const tr = document.createElement('tr');
                        
                        if (reportType === 'sales') {
                            tr.innerHTML = `
                                <td>${formatDate(row.created_at)}</td>
                                <td>#${row.order_id}</td>
                                <td>${row.customer_name}</td>
                                <td>${row.order_type}</td>
                                <td><span class="status-badge ${row.order_status}">${row.order_status}</span></td>
                                <td>₱${parseFloat(row.total_amount).toFixed(2)}</td>
                            `;
                        } else if (reportType === 'products') {
                            tr.innerHTML = `
                                <td>${row.product_name}</td>
                                <td>${row.units_sold}</td>
                                <td>₱${parseFloat(row.total_revenue).toFixed(2)}</td>
                                <td>${row.avg_rating ? row.avg_rating.toFixed(1) : 'N/A'}</td>
                            `;
                        } else if (reportType === 'feedback') {
                            // Get sentiment badge class based on sentiment
                            const sentimentClass = row.sentiment === 'positive' ? 'success' : 
                                                row.sentiment === 'negative' ? 'danger' : 'warning';
                            
                            tr.innerHTML = `
                                <td>${formatDate(row.date)}</td>
                                <td>#${row.order_id}</td>
                                <td>${row.customer_name}</td>
                                <td>${row.comment}</td>
                                <td><span class="status-badge ${sentimentClass}">${row.sentiment}</span></td>
                            `;
                        }
                        
                        tableBody.appendChild(tr);
                    });
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="${reportType === 'sales' ? 6 : reportType === 'products' ? 5 : 5}" class="no-data">No data available</td>
                        </tr>
                    `;
                }
            }
            
            // Function to export report as CSV
            function exportReport(reportType, dateFilter) {
                fetch('../backend/generate_reports.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=export_report&report_type=${reportType}&date=${dateFilter}`
                })
                .then(response => {
                    if (response.ok) {
                        return response.blob();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(blob => {
                    // Create a download link and trigger download
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `${reportType}_report_${dateFilter}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    
                    Swal.fire({
                        title: 'Success',
                        text: 'Report exported successfully',
                        icon: 'success',
                        confirmButtonColor: '#634832'
                    });
                })
                .catch(error => {
                    console.error('Error exporting report:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while exporting the report',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                });
            }
            
            // Helper function to format date
            function formatDate(dateString) {
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                return new Date(dateString).toLocaleDateString(undefined, options);
            }
            
            // Helper function to display rating stars
            function getRatingStars(rating) {
                const fullStar = '<i class="fas fa-star"></i>';
                const emptyStar = '<i class="far fa-star"></i>';
                let stars = '';
                
                for (let i = 1; i <= 5; i++) {
                    stars += i <= rating ? fullStar : emptyStar;
                }
                
                return stars;
            }
        });
    </script>
</body>
</html>
