<?php
require_once 'dbconn.php';

// Initialize response array
$response = [
    'total_sales' => 0,
    'total_orders' => 0,
    'active_products' => 0,
    'total_customers' => 0
];

try {
    // Get total sales
    $stmt = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE order_status = 'completed'");
    $result = $stmt->fetch_assoc();
    $response['total_sales'] = $result['total'];

    // Get total orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'completed'");
    $result = $stmt->fetch_assoc();
    $response['total_orders'] = $result['total'];

    // Get active products
    $stmt = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $result = $stmt->fetch_assoc();
    $response['active_products'] = $result['total'];

    // Active operational staff (waiter + cashier); admin excluded
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role IN ('waiter', 'cashier') AND account_status = 'active'");
    $result = $stmt->fetch_assoc();
    $response['total_customers'] = $result['total'];

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    // Log error and send error response
    error_log("Error in fetch_dashboard_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch dashboard statistics']);
}
?> 