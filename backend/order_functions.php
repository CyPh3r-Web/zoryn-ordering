<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'dbconn.php';
require_once 'update_inventory.php';
session_start();

function assertCanManageOrders() {
    if (!empty($_SESSION['admin_id'])) {
        return true;
    }
    if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'cashier') {
        return true;
    }
    return false;
}

/**
 * Recompute subtotal / tax / total from remaining order_items (tax-inclusive line prices).
 */
function recalculateOrderTotals($conn, $order_id) {
    $stmt = $conn->prepare("
        SELECT oi.price, oi.quantity, COALESCE(p.tax_rate, 12) AS tax_rate
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $subtotal = 0.0;
    $tax_amount = 0.0;

    while ($row = $result->fetch_assoc()) {
        $line = (float) $row['price'] * (int) $row['quantity'];
        $rate = (float) $row['tax_rate'];
        if ($rate > 0) {
            $net = $line / (1 + ($rate / 100));
            $subtotal += $net;
            $tax_amount += ($line - $net);
        } else {
            $subtotal += $line;
        }
    }

    $total_amount = round($subtotal + $tax_amount, 2);
    $subtotal = round($subtotal, 2);
    $tax_amount = round($tax_amount, 2);

    $upd = $conn->prepare("UPDATE orders SET subtotal = ?, tax_amount = ?, total_amount = ?, updated_at = NOW() WHERE order_id = ?");
    $upd->bind_param("dddi", $subtotal, $tax_amount, $total_amount, $order_id);
    $upd->execute();

    return ['subtotal' => $subtotal, 'tax_amount' => $tax_amount, 'total_amount' => $total_amount];
}

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

        case 'cancel_order':
            cancelOrder();
            break;

        case 'remove_order_item':
            removeOrderItem();
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
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at ASC, id ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            if (isset($row['created_at'])) {
                $row['created_at'] = zoryn_datetime_to_iso8601($row['created_at']);
            }
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
    if (!assertCanManageOrders()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $orderId = (int) ($_POST['order_id'] ?? 0);
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order']);
        return;
    }

    $conn->begin_transaction();

    try {
        $lines = [];
        $q = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $q->bind_param("i", $orderId);
        $q->execute();
        $rs = $q->get_result();
        while ($row = $rs->fetch_assoc()) {
            $lines[] = ['product_id' => (int) $row['product_id'], 'quantity' => (int) $row['quantity']];
        }

        if (!empty($lines)) {
            $inv = new InventoryUpdater($conn);
            if (!$inv->restockIngredientsForLines($lines, $orderId, 'Order delete restock')) {
                throw new Exception('Failed to restock inventory before delete');
            }
        }

        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete order: ' . $e->getMessage()]);
    }
}

function cancelOrder() {
    global $conn;
    if (!assertCanManageOrders()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $orderId = (int) ($_POST['order_id'] ?? 0);
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order']);
        return;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT order_id, order_status FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        if ($order['order_status'] === 'cancelled') {
            $conn->rollback();
            echo json_encode(['success' => true, 'message' => 'Order is already cancelled']);
            return;
        }

        if ($order['order_status'] === 'completed') {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Cannot cancel a completed order']);
            return;
        }

        $lines = [];
        $q = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $q->bind_param("i", $orderId);
        $q->execute();
        $rs = $q->get_result();
        while ($row = $rs->fetch_assoc()) {
            $lines[] = ['product_id' => (int) $row['product_id'], 'quantity' => (int) $row['quantity']];
        }

        if (!empty($lines)) {
            $inv = new InventoryUpdater($conn);
            if (!$inv->restockIngredientsForLines($lines, $orderId, 'Order cancelled')) {
                throw new Exception('Failed to restock inventory');
            }
        }

        $cancelled = 'cancelled';
        $upd = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
        $upd->bind_param("si", $cancelled, $orderId);
        $upd->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order cancelled']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function removeOrderItem() {
    global $conn;
    if (!assertCanManageOrders()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $orderItemId = (int) ($_POST['order_item_id'] ?? 0);
    if ($orderId <= 0 || $orderItemId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        return;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT o.order_id, o.order_status, oi.order_item_id, oi.product_id, oi.quantity
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.order_id = ? AND oi.order_item_id = ?
        ");
        $stmt->bind_param("ii", $orderId, $orderItemId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Line item not found']);
            return;
        }

        if (in_array($row['order_status'], ['completed', 'cancelled'], true)) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Cannot remove items from this order']);
            return;
        }

        $inv = new InventoryUpdater($conn);
        if (!$inv->restockIngredientsForLines(
            [['product_id' => (int) $row['product_id'], 'quantity' => (int) $row['quantity']]],
            $orderId,
            'Line item removed'
        )) {
            throw new Exception('Failed to restock inventory');
        }

        $del = $conn->prepare("DELETE FROM order_items WHERE order_item_id = ? AND order_id = ?");
        $del->bind_param("ii", $orderItemId, $orderId);
        $del->execute();

        $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM order_items WHERE order_id = ?");
        $countStmt->bind_param("i", $orderId);
        $countStmt->execute();
        $remaining = (int) $countStmt->get_result()->fetch_assoc()['c'];

        if ($remaining === 0) {
            $cancelled = 'cancelled';
            $zero = 0.0;
            $z = $conn->prepare("UPDATE orders SET order_status = ?, subtotal = ?, tax_amount = ?, total_amount = ?, updated_at = NOW() WHERE order_id = ?");
            $z->bind_param("sdddi", $cancelled, $zero, $zero, $zero, $orderId);
            $z->execute();
        } else {
            recalculateOrderTotals($conn, $orderId);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item removed', 'order_empty' => $remaining === 0]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 