<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'dbconn.php';
session_start();

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_orders':
            getOrders();
            break;
            
        case 'get_user_orders':
            getUserOrders();
            break;
            
        case 'get_order_counts':
            getOrderCounts();
            break;
            
        case 'get_order_details':
            getOrderDetails();
            break;
            
        case 'get_active_order':
            getActiveOrder();
            break;
            
        case 'set_active_order':
            setActiveOrder();
            break;
            
        case 'clear_active_order':
            clearActiveOrder();
            break;
            
        case 'check_feedback_exists':
            checkFeedbackExists();
            break;
            
        case 'get_notifications':
            $response = checkNotifications();
            echo json_encode($response);
            break;
            
        case 'delete_order':
            deleteOrder();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

function getOrders() {
    global $conn;
    $dateFilter = isset($_POST['date']) ? $_POST['date'] : null;
    $paymentStatus = isset($_POST['payment_status']) ? $_POST['payment_status'] : null;
    $orderStatus = isset($_POST['order_status']) ? $_POST['order_status'] : null;
    $orderType = isset($_POST['order_type']) ? $_POST['order_type'] : null;
    
    $query = "SELECT o.*, 
              (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
              FROM orders o
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($dateFilter) {
        $query .= " AND DATE(o.created_at) = ?";
        $params[] = $dateFilter;
        $types .= "s";
    }
    
    if ($paymentStatus) {
        $query .= " AND o.payment_status = ?";
        $params[] = $paymentStatus;
        $types .= "s";
    }
    
    if ($orderStatus) {
        $query .= " AND o.order_status = ?";
        $params[] = $orderStatus;
        $types .= "s";
    }
    
    if ($orderType) {
        $query .= " AND o.order_type = ?";
        $params[] = $orderType;
        $types .= "s";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

function getUserOrders() {
    global $conn;
    $dateFilter = isset($_POST['date']) ? $_POST['date'] : null;
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    $query = "SELECT o.*, 
              (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count,
              (SELECT COUNT(*) FROM product_feedback pf WHERE pf.order_id = o.order_id) > 0 as has_feedback
              FROM orders o
              WHERE o.user_id = ?";
    
    if ($dateFilter) {
        $query .= " AND DATE(o.created_at) = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_id, $dateFilter);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

function getOrderCounts() {
    global $conn;
    $query = "SELECT 
              SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN order_status = 'preparing' THEN 1 ELSE 0 END) as preparing,
              SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed
              FROM orders";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    
    echo json_encode(['success' => true, 'counts' => $counts]);
}

function getOrderDetails() {
    global $conn;
    $orderId = $_POST['order_id'];
    
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
        FROM orders o
        WHERE o.order_id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if ($order) {
        // Get order items with product images
        $stmt = $conn->prepare("
            SELECT oi.*, p.product_name, p.image_path
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        $order['items'] = $items;
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
}

function getActiveOrder() {
    if (isset($_SESSION['active_order_id'])) {
        $orderId = $_SESSION['active_order_id'];
        
        // Check if order exists
        global $conn;
        $stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            echo json_encode(['success' => true, 'order_id' => $order['order_id']]);
        } else {
            // Order not found, clear session
            unset($_SESSION['active_order_id']);
            echo json_encode(['success' => false, 'message' => 'No active order found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No active order found']);
    }
}

function setActiveOrder() {
    if (isset($_POST['order_id'])) {
        $_SESSION['active_order_id'] = $_POST['order_id'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
}

function clearActiveOrder() {
    unset($_SESSION['active_order_id']);
    echo json_encode(['success' => true]);
}

function checkFeedbackExists() {
    global $conn;
    $orderId = $_POST['order_id'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as feedback_count FROM product_feedback WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'has_feedback' => $data['feedback_count'] > 0]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error checking feedback']);
    }
}

// Add this function to handle notifications
function checkNotifications() {
    global $conn;
    
    try {
        // Get unread notifications
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        // Mark notifications as read
        if (!empty($notifications)) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
            $stmt->execute();
        }
        
        return [
            'success' => true,
            'notifications' => $notifications
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error checking notifications: ' . $e->getMessage()
        ];
    }
}

function deleteOrder() {
    global $conn;
    $orderId = $_POST['order_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete order items
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // Then delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete order: ' . $e->getMessage()]);
    }
}
?> 