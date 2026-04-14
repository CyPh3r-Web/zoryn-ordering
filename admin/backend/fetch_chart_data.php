<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$period = isset($_GET['period']) ? $_GET['period'] : 'daily';
$chartType = isset($_GET['chart']) ? $_GET['chart'] : '';

try {
    $db = new Database();
    $conn = $db->getConnection();

    switch($chartType) {
        case 'stockLevel':
            $data = fetchStockLevelData($conn, $period);
            break;
        case 'bestSelling':
            $data = fetchBestSellingData($conn, $period);
            break;
        case 'sales':
            $data = fetchSalesData($conn, $period);
            break;
        case 'inventory':
            $data = fetchInventoryData($conn, $period);
            break;
        case 'peakDays':
            $data = fetchPeakDaysData($conn, $period);
            break;
        default:
            throw new Exception('Invalid chart type');
    }

    echo json_encode($data);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function fetchStockLevelData($conn, $period) {
    $sql = "SELECT 
                i.name as label,
                s.quantity as value,
                i.unit,
                CASE 
                    WHEN s.quantity < i.min_stock THEN '#FF6384'
                    WHEN s.quantity < i.max_stock * 0.3 THEN '#FFCE56'
                    ELSE '#36A2EB'
                END as color
            FROM stock s
            JOIN ingredients i ON s.ingredient_id = i.id
            ORDER BY s.quantity ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'labels' => array_column($result, 'label'),
        'values' => array_column($result, 'value'),
        'units' => array_column($result, 'unit'),
        'colors' => array_column($result, 'color')
    ];
}

function fetchBestSellingData($conn, $period) {
    $dateFilter = getDateFilter($period);
    
    $sql = "SELECT 
                p.name as label,
                COUNT(oi.id) as value
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.order_date $dateFilter
            GROUP BY p.id
            ORDER BY value DESC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'labels' => array_column($result, 'label'),
        'values' => array_column($result, 'value')
    ];
}

function fetchSalesData($conn, $period) {
    $dateFilter = getDateFilter($period);
    
    $sql = "SELECT 
                DATE_FORMAT(order_date, '%Y-%m-%d') as label,
                SUM(total_amount) as value
            FROM orders
            WHERE order_date $dateFilter
            GROUP BY DATE(order_date)
            ORDER BY order_date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'labels' => array_column($result, 'label'),
        'values' => array_column($result, 'value'),
        'xAxisTitle' => getXAxisTitle($period)
    ];
}

function fetchInventoryData($conn, $period) {
    $sql = "SELECT 
                CASE 
                    WHEN s.quantity >= i.max_stock * 0.7 THEN 'Good'
                    WHEN s.quantity >= i.min_stock THEN 'Warning'
                    ELSE 'Critical'
                END as label,
                COUNT(*) as value
            FROM stock s
            JOIN ingredients i ON s.ingredient_id = i.id
            GROUP BY label";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'labels' => array_column($result, 'label'),
        'values' => array_column($result, 'value')
    ];
}

function fetchPeakDaysData($conn, $period) {
    $dateFilter = getDateFilter($period);
    
    $sql = "SELECT 
                order_date as start,
                COUNT(*) as orders,
                SUM(total_amount) as revenue
            FROM orders
            WHERE order_date $dateFilter
            GROUP BY DATE(order_date)
            ORDER BY orders DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach($result as $row) {
        $events[] = [
            'title' => $row['orders'] . ' orders',
            'start' => $row['start'],
            'extendedProps' => [
                'orders' => $row['orders'],
                'revenue' => $row['revenue']
            ]
        ];
    }

    return ['events' => $events];
}

function getDateFilter($period) {
    switch($period) {
        case 'daily':
            return ">= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        case 'monthly':
            return ">= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        case 'yearly':
            return ">= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        default:
            return ">= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    }
}

function getXAxisTitle($period) {
    switch($period) {
        case 'daily':
            return 'Last 7 Days';
        case 'monthly':
            return 'Last 6 Months';
        case 'yearly':
            return 'Last 12 Months';
        default:
            return 'Last 7 Days';
    }
}
?> 