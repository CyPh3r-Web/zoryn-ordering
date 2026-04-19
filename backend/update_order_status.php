<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

try {
    // Get current status and order details including user_id
    $stmt = $conn->prepare("SELECT o.order_status, o.user_id, o.order_type, o.total_amount, o.customer_name, o.payment_status,
                           GROUP_CONCAT(p.product_name SEPARATOR ', ') as products
                           FROM orders o 
                           JOIN order_items oi ON o.order_id = oi.order_id
                           JOIN products p ON oi.product_id = p.product_id
                           WHERE o.order_id = ?
                           GROUP BY o.order_id");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    if (!$order['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Order has no associated user']);
        exit;
    }
    
    // Determine next status
    $current_status = $order['order_status'];
    $new_status = '';
    
    if ($current_status === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Order is cancelled']);
        exit;
    }

    if ($current_status === 'pending') {
        $new_status = 'preparing';
    } else if ($current_status === 'preparing') {
        $new_status = 'completed';
    } else {
        echo json_encode(['success' => false, 'message' => 'Order is already completed']);
        exit;
    }
    
    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        // Create notification message
        $message = '';
        if ($new_status === 'preparing') {
            // Format the products list more naturally
            $products = explode(', ', $order['products']);
            $productList = '';
            if (count($products) === 1) {
                $productList = $products[0];
            } else {
                $lastProduct = array_pop($products);
                $productList = implode(', ', $products) . ' and ' . $lastProduct;
            }
            
            $message = "Hi {$order['customer_name']}, your {$productList} (₱{$order['total_amount']}) is now being prepared. ";
            
            // Add payment notification if payment is not yet made
            if (!$order['payment_status']) {
                if ($order['order_type'] === 'dine-in') {
                    $message .= "Please proceed to the counter to make your payment.";
                } else if ($order['order_type'] === 'takeout') {
                    $message .= "Please proceed to the counter to make your payment and collect your order when it's ready.";
                } else if ($order['order_type'] === 'delivery') {
                    $message .= "Please complete your payment before delivery. You can pay via cash on delivery or online payment.";
                }
            }
        } else if ($new_status === 'completed') {
            // Format the products list more naturally for completed orders too
            $products = explode(', ', $order['products']);
            $productList = '';
            if (count($products) === 1) {
                $productList = $products[0];
            } else {
                $lastProduct = array_pop($products);
                $productList = implode(', ', $products) . ' and ' . $lastProduct;
            }
            
            $message = "Hi {$order['customer_name']}, your {$productList} (₱{$order['total_amount']}) has been completed. ";
            
            if ($order['order_type'] === 'takeout') {
                $message .= "You can now collect your order at the counter.";
            } else if ($order['order_type'] === 'delivery') {
                $message .= "Your order is on its way!";
            }
        }
        
        // Insert notification with user_id
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, order_id, message, is_read, created_at) 
                               VALUES (?, ?, ?, 0, NOW())");
        $stmt->bind_param("iis", $order['user_id'], $order_id, $message);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'new_status' => $new_status,
            'needs_payment' => ($new_status === 'preparing' && !$order['payment_status'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update order status'
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 