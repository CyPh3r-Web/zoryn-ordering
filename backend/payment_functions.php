<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'dbconn.php';
require_once 'shift_access.php';
session_start();

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_SESSION['user_id']) && strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier') {
        $access = zoryn_get_cashier_shift_access($conn, (int) $_SESSION['user_id']);
        if (!$access['is_within_shift']) {
            echo json_encode(['success' => false, 'message' => 'Cashier shift is not active']);
            exit;
        }
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'process_payment':
            processPayment();
            break;
            
        case 'get_payment_status':
            getPaymentStatus();
            break;
            
        case 'verify_payment':
            verifyPayment();
            break;
            
        case 'mark_as_paid':
            markAsPaid();
            break;

        case 'update_payment_status':
            updatePaymentStatus();
            break;

        case 'update_payment_method':
            updatePaymentMethod();
            break;
            
        case 'upload_payment_proof':
            uploadPaymentProof();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

function processPayment() {
    global $conn;
    
    try {
        if (!isset($_POST['order_id']) || !isset($_POST['payment_type'])) {
            throw new Exception('Missing required payment information');
        }
        
        $order_id = $_POST['order_id'];
        $payment_type = $_POST['payment_type'];
        $proof_of_payment = null;
        
        // Start transaction
        $conn->begin_transaction();
        
        // Set initial payment status as pending for all payment types
        $payment_status = 'pending';
        
        // Handle file upload for proof of payment (only for online payments)
        if ($payment_type === 'online' && isset($_FILES['proof_of_payment'])) {
            $file = $_FILES['proof_of_payment'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, JPEG, and PNG files are allowed.');
            }
            
            $upload_dir = '../uploads/payment_proofs/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = 'payment_' . $order_id . '_' . time() . '_' . basename($file['name']);
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $proof_of_payment = 'uploads/payment_proofs/' . $filename;
            } else {
                throw new Exception('Failed to upload proof of payment');
            }
        }
        
        // Update order with payment information
        $stmt = $conn->prepare("
            UPDATE orders 
            SET payment_type = ?,
                proof_of_payment = ?,
                payment_status = ?
            WHERE order_id = ?
        ");
        
        $stmt->bind_param("sssi", $payment_type, $proof_of_payment, $payment_status, $order_id);
        
        if ($stmt->execute()) {
            // Get order details for notification
            $stmt = $conn->prepare("
                SELECT o.customer_name, o.user_id, o.total_amount
                FROM orders o
                WHERE o.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            
            // Create appropriate notification message based on payment type
            if ($payment_type === 'cash') {
                $message = "Cash payment of ₱{$order['total_amount']} is pending. Please pay at the counter.";
            } else {
                $message = "Payment of ₱{$order['total_amount']} for your order has been received and is being processed.";
            }
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, order_id, message, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $stmt->bind_param("iis", $order['user_id'], $order_id, $message);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_status' => $payment_status
            ]);
        } else {
            throw new Exception('Failed to update payment information');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn && method_exists($conn, 'rollback')) {
            $conn->rollback();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getPaymentStatus() {
    global $conn;
    
    try {
        if (!isset($_POST['order_id'])) {
            throw new Exception('Order ID is required');
        }
        
        $order_id = $_POST['order_id'];
        
        $stmt = $conn->prepare("
            SELECT payment_type, payment_status, proof_of_payment
            FROM orders
            WHERE order_id = ?
        ");
        
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($payment = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'payment' => $payment
            ]);
        } else {
            throw new Exception('Order not found');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function verifyPayment() {
    global $conn;
    
    try {
        if (!isset($_POST['order_id'])) {
            throw new Exception('Order ID is required');
        }
        
        $order_id = $_POST['order_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update payment status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = 'verified'
            WHERE order_id = ?
        ");
        
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Get order details for notification
        $stmt = $conn->prepare("
            SELECT o.*, u.username,
                   GROUP_CONCAT(p.product_name SEPARATOR ', ') as products
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE o.order_id = ?
            GROUP BY o.order_id
        ");
        
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        
        if ($order) {
            // Format the products list more naturally
            $products = explode(', ', $order['products']);
            $productList = '';
            if (count($products) === 1) {
                $productList = $products[0];
            } else {
                $lastProduct = array_pop($products);
                $productList = implode(', ', $products) . ' and ' . $lastProduct;
            }
            
            // Create notification for user
            $message = "Hi {$order['customer_name']}, your payment for {$productList} (₱{$order['total_amount']}) has been verified.";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, order_id, message)
                VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param("iis", $order['user_id'], $order_id, $message);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Get the current page URL for redirect
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/users/orders.php';
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment verified successfully',
                'redirect_url' => $redirect_url,
                'should_reload' => true
            ]);
        } else {
            throw new Exception('Order not found');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn && method_exists($conn, 'rollback')) {
            $conn->rollback();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function uploadPaymentProof() {
    global $conn;
    
    try {
        if (!isset($_POST['order_id'])) {
            throw new Exception('Order ID is required');
        }
        
        $order_id = $_POST['order_id'];
        
        // Check if file was uploaded
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $file = $_FILES['payment_proof'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, and PNG files are allowed.');
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/payment_proofs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $filename = uniqid('payment_') . '_' . basename($file['name']);
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save payment proof');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update order with payment proof and status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = 'pending',
                proof_of_payment = ?
            WHERE order_id = ?
        ");
        
        $relative_path = 'uploads/payment_proofs/' . $filename;
        $stmt->bind_param("si", $relative_path, $order_id);
        $stmt->execute();
        
        // Get order details for notification
        $stmt = $conn->prepare("
            SELECT o.*, u.username 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = ?
        ");
        
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        
        if ($order) {
            // Create notification for payment proof upload
            $message = "Payment proof has been uploaded for Order #{$order_id}. Waiting for verification.";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, order_id, message)
                VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param("iis", $order['user_id'], $order_id, $message);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment proof uploaded successfully',
                'proof_path' => $relative_path
            ]);
        } else {
            throw new Exception('Order not found');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn && method_exists($conn, 'rollback')) {
            $conn->rollback();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function markAsPaid() {
    global $conn;
    
    try {
        if (!isset($_POST['order_id'])) {
            throw new Exception('Order ID is required');
        }
        
        $order_id = $_POST['order_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        // Cash counter payments: treat NULL/empty payment_type as cash (legacy rows).
        $stmt = $conn->prepare("
            UPDATE orders
            SET payment_status = 'verified',
                payment_type = CASE
                    WHEN payment_type IS NULL OR payment_type = '' THEN 'cash'
                    ELSE payment_type
                END
            WHERE order_id = ?
              AND (
                    payment_type = 'cash'
                 OR payment_type IS NULL
                 OR payment_type = ''
              )
        ");

        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Get order details for notification
            $stmt = $conn->prepare("
                SELECT o.customer_name, o.user_id, o.total_amount
                FROM orders o
                WHERE o.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            
            // Create notification
            $message = "Cash payment of ₱{$order['total_amount']} for your order has been received and verified.";
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, order_id, message, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $stmt->bind_param("iis", $order['user_id'], $order_id, $message);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment marked as paid successfully'
            ]);
        } else {
            throw new Exception('Cannot mark as paid: order must be cash at counter (or payment type not set), and not already verified.');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn && method_exists($conn, 'rollback')) {
            $conn->rollback();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function updatePaymentStatus() {
    global $conn;

    try {
        if (empty($_SESSION['admin_id']) && (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'cashier')) {
            throw new Exception('Unauthorized');
        }
        if (!isset($_POST['order_id']) || !isset($_POST['payment_status'])) {
            throw new Exception('Order and payment status are required');
        }

        $orderId = (int) $_POST['order_id'];
        $statusInput = strtolower(trim((string) $_POST['payment_status']));
        $allowed = ['unpaid', 'pending', 'paid', 'verified'];
        if (!in_array($statusInput, $allowed, true)) {
            throw new Exception('Invalid payment status');
        }

        // Keep compatibility with existing code that checks for "verified".
        $dbStatus = ($statusInput === 'paid') ? 'verified' : $statusInput;

        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT user_id, total_amount, payment_status FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) {
            throw new Exception('Order not found');
        }

        $upd = $conn->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE order_id = ?");
        $upd->bind_param("si", $dbStatus, $orderId);
        $upd->execute();

        if ($dbStatus === 'verified' && ($order['payment_status'] ?? '') !== 'verified') {
            $message = "Payment of ₱{$order['total_amount']} for your order has been received and verified.";
            $n = $conn->prepare("INSERT INTO notifications (user_id, order_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
            $n->bind_param("iis", $order['user_id'], $orderId, $message);
            $n->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment status updated']);
    } catch (Exception $e) {
        if (method_exists($conn, 'rollback')) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updatePaymentMethod() {
    global $conn;

    try {
        if (empty($_SESSION['admin_id']) && (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'cashier')) {
            throw new Exception('Unauthorized');
        }
        if (!isset($_POST['order_id']) || !isset($_POST['payment_type'])) {
            throw new Exception('Order and payment method are required');
        }

        $orderId = (int) $_POST['order_id'];
        $paymentType = strtolower(trim((string) $_POST['payment_type']));
        $allowed = ['cash', 'online', 'gcash', 'maya', 'card', 'bank_transfer'];
        if (!in_array($paymentType, $allowed, true)) {
            throw new Exception('Invalid payment method');
        }

        $stmt = $conn->prepare("UPDATE orders SET payment_type = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->bind_param("si", $paymentType, $orderId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Payment method updated']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 