<?php
// Include database connection
require_once('dbconn.php');

// Set header to return JSON
header('Content-Type: application/json');

// Get chart type and period from request
$chart = isset($_GET['chart']) ? $_GET['chart'] : '';
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';

// Handle different chart data requests
switch ($chart) {
    case 'stockLevel':
        getStockLevelData($conn);
        break;
    case 'bestSelling':
        getBestSellingData($conn, $period);
        break;
    case 'leastSelling':
        getLeastSellingData($conn, $period);
        break;
    case 'sales':
        getSalesData($conn, $period);
        break;
    case 'inventory':
        getInventoryData($conn);
        break;
    case 'peakDays':
        getPeakDaysData($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid chart type']);
        exit;
}

// Function to get stock level data
function getStockLevelData($conn) {
    $sql = "SELECT i.ingredient_name, i.stock, i.unit, c.category_name 
            FROM ingredients i 
            JOIN categories c ON i.category_id = c.category_id 
            WHERE i.status = 'active' 
            ORDER BY i.stock ASC
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    $labels = [];
    $values = [];
    $units = [];
    $colors = [];
    $borderColors = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['ingredient_name'];
            $values[] = (float)$row['stock'];
            $units[] = $row['unit'];
            
            // Set color based on stock level
            $stockLevel = (float)$row['stock'];
            if ($stockLevel < 1) {
                $colors[] = 'rgba(220, 53, 69, 0.7)'; // Critical - Red
                $borderColors[] = 'rgba(220, 53, 69, 1)';
            } elseif ($stockLevel < 5) {
                $colors[] = 'rgba(255, 193, 7, 0.7)'; // Warning - Yellow
                $borderColors[] = 'rgba(255, 193, 7, 1)';
            } else {
                $colors[] = 'rgba(40, 167, 69, 0.7)'; // Good - Green
                $borderColors[] = 'rgba(40, 167, 69, 1)';
            }
        }
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values,
        'units' => $units,
        'colors' => $colors,
        'borderColors' => $borderColors
    ]);
}

// Function to get best selling products data
function getBestSellingData($conn, $period) {
    // Adjust SQL based on period
    $dateFilter = getDateFilter('o.created_at', $period);
    
    $sql = "SELECT p.product_name, COUNT(oi.order_item_id) as order_count 
            FROM products p 
            JOIN order_items oi ON p.product_id = oi.product_id 
            JOIN orders o ON oi.order_id = o.order_id 
            WHERE $dateFilter 
            GROUP BY p.product_id 
            ORDER BY order_count DESC 
            LIMIT 5";
    
    $result = $conn->query($sql);
    
    $labels = [];
    $values = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['product_name'];
            $values[] = (int)$row['order_count'];
        }
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);
}

// Function to get least selling products data
function getLeastSellingData($conn, $period) {
    // Adjust SQL based on period
    $dateFilter = getDateFilter('o.created_at', $period);
    
    $sql = "SELECT p.product_name, COUNT(oi.order_item_id) as order_count 
            FROM products p 
            JOIN order_items oi ON p.product_id = oi.product_id 
            JOIN orders o ON oi.order_id = o.order_id 
            WHERE $dateFilter 
            GROUP BY p.product_id 
            ORDER BY order_count ASC 
            LIMIT 5";
    
    $result = $conn->query($sql);
    
    $labels = [];
    $values = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['product_name'];
            $values[] = (int)$row['order_count'];
        }
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);
}

// Function to get sales data
function getSalesData($conn, $period) {
    $groupBy = '';
    $xAxisTitle = '';
    
    // Configure group by and labels based on period
    switch ($period) {
        case 'all':
            $groupBy = "DATE(created_at)";
            $dateFormat = "%Y-%m-%d";
            $xAxisTitle = "All Orders";
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '$dateFormat') as date_label,
                        SUM(total_amount) as total_sales
                    FROM orders
                    GROUP BY $groupBy
                    ORDER BY created_at ASC";
            break;
        case 'daily':
            $groupBy = "DATE(created_at)";
            $dateFormat = "%Y-%m-%d";
            $xAxisTitle = "Date";
            $daysToShow = 7;
            $startDate = date('Y-m-d', strtotime("-$daysToShow days"));
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '$dateFormat') as date_label,
                        SUM(total_amount) as total_sales
                    FROM orders
                    WHERE created_at >= '$startDate'
                    GROUP BY $groupBy
                    ORDER BY created_at ASC";
            break;
        case 'monthly':
            $groupBy = "MONTH(created_at), YEAR(created_at)";
            $dateFormat = "%Y-%m";
            $xAxisTitle = "Month";
            $monthsToShow = 6;
            $startDate = date('Y-m-01', strtotime("-$monthsToShow months"));
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '$dateFormat') as date_label,
                        SUM(total_amount) as total_sales
                    FROM orders
                    WHERE created_at >= '$startDate'
                    GROUP BY $groupBy
                    ORDER BY created_at ASC";
            break;
        case 'yearly':
            $groupBy = "YEAR(created_at)";
            $dateFormat = "%Y";
            $xAxisTitle = "Year";
            $yearsToShow = 5;
            $startDate = date('Y-01-01', strtotime("-$yearsToShow years"));
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '$dateFormat') as date_label,
                        SUM(total_amount) as total_sales
                    FROM orders
                    WHERE created_at >= '$startDate'
                    GROUP BY $groupBy
                    ORDER BY created_at ASC";
            break;
        default:
            $groupBy = "DATE(created_at)";
            $dateFormat = "%Y-%m-%d";
            $xAxisTitle = "Date";
            $daysToShow = 7;
            $startDate = date('Y-m-d', strtotime("-$daysToShow days"));
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '$dateFormat') as date_label,
                        SUM(total_amount) as total_sales
                    FROM orders
                    WHERE created_at >= '$startDate'
                    GROUP BY $groupBy
                    ORDER BY created_at ASC";
    }
    
    $result = $conn->query($sql);
    
    $labels = [];
    $values = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['date_label'];
            $values[] = (float)$row['total_sales'];
        }
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values,
        'xAxisTitle' => $xAxisTitle
    ]);
}

// Function to get inventory status data
function getInventoryData($conn) {
    // Count ingredients by stock level
    $sql = "SELECT 
                CASE 
                    WHEN stock < 1 THEN 'Critical'
                    WHEN stock < 5 THEN 'Warning'
                    ELSE 'Good'
                END as stock_status,
                COUNT(*) as count
            FROM ingredients
            WHERE status = 'active'
            GROUP BY stock_status
            ORDER BY FIELD(stock_status, 'Good', 'Warning', 'Critical')";
    
    $result = $conn->query($sql);
    
    $labels = [];
    $values = [];
    
    // Initialize default values
    $stockStatus = [
        'Good' => 0,
        'Warning' => 0,
        'Critical' => 0
    ];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stockStatus[$row['stock_status']] = (int)$row['count'];
        }
    }
    
    $labels = array_keys($stockStatus);
    $values = array_values($stockStatus);
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);
}

// Function to get peak days data
function getPeakDaysData($conn) {
    // Get orders grouped by day for the last 3 months
    $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
    
    $sql = "SELECT 
                DATE(created_at) as order_date,
                COUNT(*) as order_count,
                SUM(total_amount) as total_revenue
            FROM orders
            WHERE created_at >= '$threeMonthsAgo'
            GROUP BY DATE(created_at)
            ORDER BY order_count DESC";
    
    $result = $conn->query($sql);
    
    $events = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Determine event color based on order count
            $orderCount = (int)$row['order_count'];
            $color = '#36A2EB'; // Default blue
            
            if ($orderCount > 10) {
                $color = '#dc3545'; // High traffic - Red
            } elseif ($orderCount > 5) {
                $color = '#ffc107'; // Medium traffic - Yellow
            }
            
            $events[] = [
                'title' => $orderCount . ' Orders',
                'start' => $row['order_date'],
                'backgroundColor' => $color,
                'orders' => $orderCount,
                'revenue' => (float)$row['total_revenue']
            ];
        }
    }
    
    echo json_encode([
        'events' => $events
    ]);
}

// Helper function to get date filter SQL based on period
function getDateFilter($dateField, $period) {
    switch ($period) {
        case 'daily':
            return "$dateField >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        case 'monthly':
            return "$dateField >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        case 'yearly':
            return "$dateField >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)";
        default:
            return "$dateField >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    }
}
?>