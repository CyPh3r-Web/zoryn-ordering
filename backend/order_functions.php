<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'dbconn.php';
require_once 'update_inventory.php';
require_once 'shift_access.php';
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

function canViewOrders() {
    if (!empty($_SESSION['admin_id'])) {
        return true;
    }
    if (!empty($_SESSION['user_id']) && in_array(strtolower($_SESSION['role'] ?? ''), ['cashier', 'kitchen', 'crew'], true)) {
        return true;
    }
    return false;
}

function hasCashierShiftAccess() {
    global $conn;
    if (!empty($_SESSION['user_id']) && strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier') {
        $access = zoryn_get_cashier_shift_access($conn, (int) $_SESSION['user_id']);
        return $access['is_within_shift'];
    }
    return true;
}

function isKitchenRoleSession() {
    return !empty($_SESSION['user_id']) && in_array(strtolower($_SESSION['role'] ?? ''), ['kitchen', 'crew'], true);
}

function currentActorId() {
    if (!empty($_SESSION['admin_id'])) {
        return (int) $_SESSION['admin_id'];
    }
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }
    return null;
}

function verifyAdminOverridePin($pin) {
    global $conn;
    $pin = trim((string) $pin);
    if ($pin === '') {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT user_id, admin_override_pin_hash
        FROM users
        WHERE role = 'admin'
          AND account_status = 'active'
          AND admin_override_pin_hash IS NOT NULL
          AND admin_override_pin_hash <> ''
        ORDER BY user_id ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($admin = $result->fetch_assoc()) {
        if (!empty($admin['admin_override_pin_hash']) && password_verify($pin, $admin['admin_override_pin_hash'])) {
            return (int) $admin['user_id'];
        }
    }

    return null;
}

function hasConfiguredAdminOverridePin() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'admin'
          AND account_status = 'active'
          AND admin_override_pin_hash IS NOT NULL
          AND admin_override_pin_hash <> ''
    ");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ((int) ($row['total'] ?? 0)) > 0;
}

function insertOrderChangeLog($payload) {
    global $conn;
    if (!$conn->query("SHOW TABLES LIKE 'order_change_logs'")->num_rows) {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO order_change_logs
        (order_id, change_type, order_item_id, product_id, qty_before, qty_after, amount_before, amount_after, reason, requested_by, approved_by, pin_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        return;
    }

    $orderId = (int) ($payload['order_id'] ?? 0);
    $changeType = (string) ($payload['change_type'] ?? '');
    $orderItemId = isset($payload['order_item_id']) ? (int) $payload['order_item_id'] : null;
    $productId = isset($payload['product_id']) ? (int) $payload['product_id'] : null;
    $qtyBefore = isset($payload['qty_before']) ? (int) $payload['qty_before'] : null;
    $qtyAfter = isset($payload['qty_after']) ? (int) $payload['qty_after'] : null;
    $amountBefore = isset($payload['amount_before']) ? (float) $payload['amount_before'] : null;
    $amountAfter = isset($payload['amount_after']) ? (float) $payload['amount_after'] : null;
    $reason = isset($payload['reason']) ? (string) $payload['reason'] : null;
    $requestedBy = isset($payload['requested_by']) ? (int) $payload['requested_by'] : null;
    $approvedBy = isset($payload['approved_by']) ? (int) $payload['approved_by'] : null;
    $pinVerified = !empty($payload['pin_verified']) ? 1 : 0;

    $stmt->bind_param(
        "isiiiiddsiii",
        $orderId,
        $changeType,
        $orderItemId,
        $productId,
        $qtyBefore,
        $qtyAfter,
        $amountBefore,
        $amountAfter,
        $reason,
        $requestedBy,
        $approvedBy,
        $pinVerified
    );
    $stmt->execute();
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
    if (!hasCashierShiftAccess()) {
        echo json_encode(['success' => false, 'message' => 'Cashier shift is not active']);
        exit;
    }

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

        case 'add_order_item':
            addOrderItem();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

function getOrders() {
    global $conn;
    if (!canViewOrders()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $isKitchenRole = isKitchenRoleSession();
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
        if ($isKitchenRole) {
            $row['total_amount'] = null;
            $row['subtotal'] = null;
            $row['tax_amount'] = null;
        }
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
    if (!canViewOrders()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $isKitchenRole = isKitchenRoleSession();
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
            if ($isKitchenRole) {
                $row['price'] = null;
            }
            $items[] = $row;
        }
        if ($isKitchenRole) {
            $order['total_amount'] = null;
            $order['subtotal'] = null;
            $order['tax_amount'] = null;
            $order['payment_type'] = null;
            $order['payment_status'] = null;
            $order['proof_of_payment'] = null;
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
    $adminPin = $_POST['admin_pin'] ?? '';
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order']);
        return;
    }
    if (!hasConfiguredAdminOverridePin()) {
        echo json_encode(['success' => false, 'message' => 'No admin override PIN is configured yet. Set it first in Admin > Users.']);
        return;
    }
    $approvedBy = verifyAdminOverridePin($adminPin);
    if ($approvedBy === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid admin PIN for order cancellation']);
        return;
    }
    $requestedBy = currentActorId();

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

        insertOrderChangeLog([
            'order_id' => $orderId,
            'change_type' => 'cancel_order',
            'reason' => 'Cancelled from admin orders panel',
            'requested_by' => $requestedBy,
            'approved_by' => $approvedBy,
            'pin_verified' => 1
        ]);

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
    $adminPin = $_POST['admin_pin'] ?? '';
    if ($orderId <= 0 || $orderItemId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        return;
    }
    if (!hasConfiguredAdminOverridePin()) {
        echo json_encode(['success' => false, 'message' => 'No admin override PIN is configured yet. Set it first in Admin > Users.']);
        return;
    }
    $approvedBy = verifyAdminOverridePin($adminPin);
    if ($approvedBy === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid admin PIN for removing items']);
        return;
    }
    $requestedBy = currentActorId();

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT o.order_id, o.order_status, oi.order_item_id, oi.product_id, oi.quantity, oi.price
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

        insertOrderChangeLog([
            'order_id' => $orderId,
            'change_type' => 'remove_item',
            'order_item_id' => $orderItemId,
            'product_id' => (int) $row['product_id'],
            'qty_before' => (int) $row['quantity'],
            'qty_after' => 0,
            'amount_before' => (float) $row['quantity'] * (float) $row['price'],
            'amount_after' => 0.0,
            'reason' => 'Item removed from admin orders panel',
            'requested_by' => $requestedBy,
            'approved_by' => $approvedBy,
            'pin_verified' => 1
        ]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item removed', 'order_empty' => $remaining === 0]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addOrderItem() {
    global $conn;
    if (!assertCanManageOrders()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 0);

    if ($orderId <= 0 || $productId <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        return;
    }

    try {
        $stmt = $conn->prepare("SELECT order_id, order_status FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }
        if (!in_array($order['order_status'], ['pending', 'preparing'], true)) {
            echo json_encode(['success' => false, 'message' => 'Only pending/preparing orders can be updated']);
            return;
        }

        $p = $conn->prepare("SELECT product_id, price, status FROM products WHERE product_id = ? LIMIT 1");
        $p->bind_param("i", $productId);
        $p->execute();
        $product = $p->get_result()->fetch_assoc();
        if (!$product || ($product['status'] ?? '') !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Selected product is unavailable']);
            return;
        }

        // Best-effort stock validation for additional lines.
        $inv = new InventoryUpdater($conn);
        $stockCheck = $inv->checkStockForOrder([[
            'product_id' => $productId,
            'quantity' => $quantity
        ]]);
        if (!($stockCheck['success'] ?? false)) {
            echo json_encode(['success' => false, 'message' => $stockCheck['message'] ?? 'Insufficient stock']);
            return;
        }

        $conn->begin_transaction();

        $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $price = (float) $product['price'];
        $ins->bind_param("iiid", $orderId, $productId, $quantity, $price);
        $ins->execute();
        $newOrderItemId = (int) $conn->insert_id;

        recalculateOrderTotals($conn, $orderId);

        insertOrderChangeLog([
            'order_id' => $orderId,
            'change_type' => 'add_item',
            'order_item_id' => $newOrderItemId,
            'product_id' => $productId,
            'qty_before' => 0,
            'qty_after' => $quantity,
            'amount_before' => 0.0,
            'amount_after' => $price * $quantity,
            'reason' => 'Additional item added from orders panel',
            'requested_by' => currentActorId(),
            'approved_by' => null,
            'pin_verified' => 0
        ]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Additional item added']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 