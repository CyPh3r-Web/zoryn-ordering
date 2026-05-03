<?php
// Start output buffering at the very beginning
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display, we'll handle it ourselves
header('Content-Type: application/json');

require_once 'dbconn.php';
require_once 'image_path_helper.php';
session_start();

// Function to send JSON response
function sendJsonResponse($data) {
    while (ob_get_level()) {
        ob_end_clean(); // Clear all output buffers
    }
    echo json_encode($data);
    exit;
}

// Add this at the beginning of the file, after the database connection
$createNotificationsTable = "
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    message TEXT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
)";

try {
    $GLOBALS['conn']->query($createNotificationsTable);
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

class OrderManager {
    private $conn;
    private $session_id;

    public function __construct() {
        $this->conn = $GLOBALS['conn'];
        $this->session_id = session_id();
    }

    private static function formatImagePath($path) {
        if (empty($path)) {
            return '';
        }
        $out = image_path_for_users_folder($path);
        return $out === null ? '' : $out;
    }

    // Add item to current session order
    public function addItem($product_id, $quantity) {
        error_log("Starting addItem, product ID: {$product_id}, quantity: {$quantity}");
        $current_order = $this->getCurrentOrder();
        
        if (!isset($current_order['items'])) {
            $current_order['items'] = array();
            error_log("No items in current order, initializing empty array");
        }

        // Validate quantity is a positive number
        $quantity = max(0, intval($quantity));
        error_log("Validated quantity: {$quantity}");
        
        // Check if product already exists in order
        $found = false;
        foreach ($current_order['items'] as &$item) {
            if ($item['product_id'] == $product_id) {
                error_log("Found existing product in order, updating quantity from {$item['quantity']} to {$quantity}");
                $item['quantity'] = $quantity; // Set exact quantity, don't add
                $found = true;
                break;
            }
        }

        if (!$found) {
            error_log("Product not found in order, fetching details");
            // Get product details
            $stmt = $this->conn->prepare("SELECT product_id, product_name, price, tax_rate, image_path FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if ($product) {
                error_log("Found product in database: " . json_encode($product));
                $current_order['items'][] = array(
                    'product_id' => $product_id,
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'tax_rate' => $product['tax_rate'],
                    'image_path' => self::formatImagePath($product['image_path']),
                    'quantity' => $quantity
                );
            } else {
                error_log("Product not found in database");
                return array('error' => 'Product not found');
            }
        }

        $this->saveCurrentOrder($current_order);
        error_log("Order saved successfully with " . count($current_order['items']) . " items");
        return $current_order;
    }

    // Get current session order or specific order for feedback
    public function getCurrentOrder($order_id = null) {
        if ($order_id) {
            // Get specific order for feedback
            $stmt = $this->conn->prepare("
                SELECT oi.product_id, p.product_name, oi.price, p.tax_rate, p.image_path, oi.quantity 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items = array();
            while ($row = $result->fetch_assoc()) {
                $row['image_path'] = self::formatImagePath($row['image_path']);
                $items[] = $row;
            }
            
            return array('items' => $items);
        } else {
            // Get current session order
            $stmt = $this->conn->prepare("SELECT order_data FROM session_orders WHERE session_id = ?");
            $stmt->bind_param("s", $this->session_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $order_data = json_decode($row['order_data'], true);
                return $order_data;
            }
            
            return array('items' => array());
        }
    }

    /** Persist discount settings on the session cart (stored in session_orders JSON). */
    public function updateSessionDiscount($enabled, $percent) {
        $current_order = $this->getCurrentOrder();
        if (!isset($current_order['items'])) {
            $current_order['items'] = array();
        }
        $current_order['discount_enabled'] = $enabled ? 1 : 0;
        $current_order['discount_percent'] = max(0.0, min(100.0, round((float) $percent, 2)));
        $this->saveCurrentOrder($current_order);
        return $current_order;
    }

    // Save current session order
    private function saveCurrentOrder($order_data) {
        // Clear any existing order data for this session
        $stmt = $this->conn->prepare("DELETE FROM session_orders WHERE session_id = ?");
        $stmt->bind_param("s", $this->session_id);
        $stmt->execute();
        
        // Save the new order data
        $json_data = json_encode($order_data);
        $stmt = $this->conn->prepare("INSERT INTO session_orders (session_id, order_data) VALUES (?, ?)");
        $stmt->bind_param("ss", $this->session_id, $json_data);
        $stmt->execute();
    }

    // Clear current session order
    public function clearCurrentOrder() {
        $stmt = $this->conn->prepare("DELETE FROM session_orders WHERE session_id = ?");
        $stmt->bind_param("s", $this->session_id);
        $stmt->execute();
    }

    // Create final order
    public function createFinalOrder($customer_name, $order_type, $user_id, $payment_type = 'cash', $proof_of_payment = null, $table_number = null) {
        try {
            // Whitelist order_type
            $allowedTypes = ['walk-in', 'account-order', 'dine-in', 'take-out'];
            if (!in_array($order_type, $allowedTypes, true)) {
                $order_type = 'walk-in';
            }

            // Normalize table_number (only meaningful for dine-in)
            $table_number = is_string($table_number) ? trim($table_number) : null;
            if ($table_number === '') $table_number = null;

            // Start transaction
            $this->conn->begin_transaction();

            // Get current order
            $current_order = $this->getCurrentOrder();
            if (empty($current_order['items'])) {
                throw new Exception('No items in order');
            }

            error_log("Creating order for customer: " . $customer_name);
            error_log("Order items: " . print_r($current_order['items'], true));

            // Check if there's enough stock for all items (use the current items list)
            require_once 'update_inventory.php';
            $inventoryUpdater = new InventoryUpdater($this->conn);
            $stockCheck = $inventoryUpdater->checkStockForOrder($current_order['items']);
            if (!$stockCheck['success']) {
                throw new Exception($stockCheck['message']);
            }

            // Tax-inclusive pricing: split each line into net + VAT using its tax_rate
            $subtotal = 0.0;   // VAT-exclusive base + VAT-exempt sales
            $tax_amount = 0.0; // 12% (or whichever per-product rate) portion
            foreach ($current_order['items'] as $item) {
                $line = (float) $item['price'] * (int) $item['quantity'];
                $rate = isset($item['tax_rate']) ? (float) $item['tax_rate'] : 12.0;
                if ($rate > 0) {
                    $net = $line / (1 + ($rate / 100));
                    $subtotal   += $net;
                    $tax_amount += ($line - $net);
                } else {
                    $subtotal += $line; // VAT-exempt
                }
            }
            $gross_total = round($subtotal + $tax_amount, 2);
            $subtotal   = round($subtotal, 2);
            $tax_amount = round($tax_amount, 2);

            $disc_enabled = !empty($current_order['discount_enabled']);
            $disc_pct = isset($current_order['discount_percent']) ? (float) $current_order['discount_percent'] : 0.0;
            if ($disc_pct < 0) {
                $disc_pct = 0;
            }
            if ($disc_pct > 100) {
                $disc_pct = 100;
            }

            $discount_amount = 0.0;
            $db_discount_enabled = 0;
            $db_discount_percent = 0.0;

            if ($disc_enabled && $disc_pct > 0 && $gross_total > 0) {
                $discount_amount = round($gross_total * ($disc_pct / 100), 2);
                if ($discount_amount > $gross_total) {
                    $discount_amount = $gross_total;
                }
                $db_discount_enabled = 1;
                $db_discount_percent = round($disc_pct, 2);
            }

            $total_amount = round($gross_total - $discount_amount, 2);
            if ($gross_total > 0 && $discount_amount > 0) {
                $ratio = $total_amount / $gross_total;
                $subtotal = round($subtotal * $ratio, 2);
                $tax_amount = round($total_amount - $subtotal, 2);
            }

            // Handle payment proof upload if it's an online payment
            $proof_of_payment_path = null;
            if ($payment_type === 'online' && $proof_of_payment) {
                $upload_dir = '../uploads/payment_proofs/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($proof_of_payment['name'], PATHINFO_EXTENSION);
                $filename = uniqid('payment_') . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (!move_uploaded_file($proof_of_payment['tmp_name'], $target_path)) {
                    throw new Exception("Failed to upload payment proof");
                }
                
                $proof_of_payment_path = 'uploads/payment_proofs/' . $filename;
            }

            // Set payment status based on payment type
            $payment_status = ($payment_type === 'cash') ? 'unpaid' : 'pending';
            $waiter_id = null;

            // The waiter is the authenticated waiter account that encoded the order.
            if (!empty($_SESSION['user_id']) && strtolower((string) ($_SESSION['role'] ?? '')) === 'waiter') {
                $waiter_id = (int) $_SESSION['user_id'];
            }

            // Create order (with tax + table-number metadata + optional discount)
            $stmt = $this->conn->prepare("
                INSERT INTO orders (
                    customer_name, order_type, user_id, order_status,
                    total_amount, subtotal, tax_amount,
                    payment_type, payment_status, proof_of_payment, table_number, waiter_id,
                    discount_enabled, discount_percent, discount_amount
                ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssidddssssiidd",
                $customer_name,
                $order_type,
                $user_id,
                $total_amount,
                $subtotal,
                $tax_amount,
                $payment_type,
                $payment_status,
                $proof_of_payment_path,
                $table_number,
                $waiter_id,
                $db_discount_enabled,
                $db_discount_percent,
                $discount_amount
            );
            $stmt->execute();
            $order_id = $this->conn->insert_id;

            error_log("Created order with ID: " . $order_id);

            // Add order items
            $stmt = $this->conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");

            foreach ($current_order['items'] as $item) {
                error_log("Adding item to order: " . print_r($item, true));
                $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
            }

            // Update inventory
            $inventoryUpdater->updateInventoryForOrder($order_id);

            // Clear current order
            $this->clearCurrentOrder();

            // Create notification for the order
            $orderItems = array_map(function($item) {
                return $item['product_name'];
            }, $current_order['items']);
            
            $itemsList = implode(', ', $orderItems);
            $message = "Hi {$customer_name}, your order of {$itemsList} (Total: ₱{$total_amount}.00) has been placed successfully. ";
            $message .= ($payment_type === 'online') ? "Please wait for payment verification." : "Please proceed to the counter for payment.";
            
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, order_id, message) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iis", $user_id, $order_id, $message);
            $stmt->execute();

            // Commit transaction
            $this->conn->commit();

            return array(
                'success' => true,
                'order_id' => $order_id,
                'message' => 'Order created successfully'
            );
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            
            // Delete uploaded file if it exists
            if (isset($proof_of_payment_path) && file_exists('../' . $proof_of_payment_path)) {
                unlink('../' . $proof_of_payment_path);
            }
            
            error_log("Error creating order: " . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    // Remove specific item from order
    public function removeItem($product_id) {
        $current_order = $this->getCurrentOrder();
        
        if (!isset($current_order['items'])) {
            return array('error' => 'No items in order');
        }

        // Find and remove the item
        foreach ($current_order['items'] as $key => $item) {
            if ($item['product_id'] == $product_id) {
                unset($current_order['items'][$key]);
                // Reindex array to maintain sequential keys
                $current_order['items'] = array_values($current_order['items']);
                break;
            }
        }

        $this->saveCurrentOrder($current_order);
        return $current_order;
    }

    // Update item quantity
    public function updateItemQuantity($product_id, $quantity) {
        try {
            error_log("Starting updateItemQuantity, product ID: {$product_id}, quantity: {$quantity}");
            $current_order = $this->getCurrentOrder();
            
            // Validate quantity is a positive number
            $quantity = max(0, intval($quantity));
            error_log("Validated quantity: {$quantity}");
            
            if (!isset($current_order['items'])) {
                $current_order['items'] = array();
                error_log("No items in current order, initializing empty array");
            }

            // Get product details if we need to add a new item
            $product = null;
            if (!$this->findItemInOrder($current_order, $product_id)) {
                error_log("Product {$product_id} not in order, fetching details");
                $stmt = $this->conn->prepare("SELECT product_id, product_name, price, tax_rate, image_path FROM products WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                
                if (!$product) {
                    throw new Exception('Product not found');
                }
                error_log("Found product: " . json_encode($product));
            } else {
                error_log("Product {$product_id} already in order");
            }

            // Update or add the item
            $found = false;
            foreach ($current_order['items'] as &$item) {
                if ($item['product_id'] == $product_id) {
                    error_log("Updating existing item in order, old quantity: {$item['quantity']}, new quantity: {$quantity}");
                    if ($quantity <= 0) {
                        error_log("Quantity is zero or negative, removing item");
                        return $this->removeItem($product_id);
                    }
                    $item['quantity'] = $quantity;
                    $found = true;
                    break;
                }
            }

            if (!$found && $quantity > 0) {
                error_log("Adding new item to order with quantity: {$quantity}");
                $current_order['items'][] = array(
                    'product_id' => $product_id,
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'tax_rate' => $product['tax_rate'],
                    'image_path' => self::formatImagePath($product['image_path']),
                    'quantity' => $quantity
                );
            }

            $this->saveCurrentOrder($current_order);
            error_log("Order saved successfully");
            return $current_order;
        } catch (Exception $e) {
            error_log("Error updating quantity: " . $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }

    // Helper function to check if an item exists in the order
    private function findItemInOrder($order, $product_id) {
        if (!isset($order['items'])) {
            return false;
        }
        foreach ($order['items'] as $item) {
            if ($item['product_id'] == $product_id) {
                return true;
            }
        }
        return false;
    }

    // Save feedback for an order
    public function saveFeedback($order_id, $ratings, $comment) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Update order with feedback comment
            $stmt = $this->conn->prepare("UPDATE orders SET 
                feedback_comment = ?, 
                feedback_date = NOW()
                WHERE order_id = ?");
            
            $stmt->bind_param("si", $comment, $order_id);
            $stmt->execute();

            // Save individual product ratings
            $stmt = $this->conn->prepare("INSERT INTO product_feedback (order_id, product_id, rating) VALUES (?, ?, ?)");
            
            foreach ($ratings as $product_id => $rating) {
                $stmt->bind_param("iii", $order_id, $product_id, $rating);
                $stmt->execute();
            }

            $this->conn->commit();
            return array('success' => true);
        } catch (Exception $e) {
            $this->conn->rollback();
            return array('error' => 'Failed to save feedback: ' . $e->getMessage());
        }
    }

    // Get feedback for an order
    public function getOrderFeedback($order_id) {
        try {
            // Get order feedback comment
            $stmt = $this->conn->prepare("SELECT feedback_comment, feedback_date FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order_feedback = $result->fetch_assoc();

            // Get individual product ratings
            $stmt = $this->conn->prepare("
                SELECT pf.product_id, p.product_name, pf.rating 
                FROM product_feedback pf
                JOIN products p ON pf.product_id = p.product_id
                WHERE pf.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $product_ratings = array();
            while ($row = $result->fetch_assoc()) {
                $product_ratings[] = $row;
            }

            return array(
                'comment' => $order_feedback['feedback_comment'],
                'date' => $order_feedback['feedback_date'],
                'ratings' => $product_ratings
            );
        } catch (Exception $e) {
            return array('error' => 'Failed to get feedback: ' . $e->getMessage());
        }
    }

    // Get all active categories
    public function getCategories() {
        try {
            $stmt = $this->conn->prepare("SELECT category_id, category_name FROM product_categories WHERE status = 'active'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = array();
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            
            return array('categories' => $categories);
        } catch (Exception $e) {
            return array('error' => 'Failed to fetch categories: ' . $e->getMessage());
        }
    }

    // Get all active products
    public function getProducts() {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.product_id, p.product_name, p.price, p.tax_rate, p.image_path, p.category_id
                FROM products p
                WHERE p.status = 'active'
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $products = array();
            while ($row = $result->fetch_assoc()) {
                $row['image_path'] = self::formatImagePath($row['image_path']);
                $products[] = $row;
            }
            
            return array('products' => $products);
        } catch (Exception $e) {
            return array('error' => 'Failed to fetch products: ' . $e->getMessage());
        }
    }

    // Add multiple items to current session order
    public function addItems($product_ids) {
        try {
            $current_order = $this->getCurrentOrder();
            
            if (!isset($current_order['items'])) {
                $current_order['items'] = array();
            }

            // Get product details for all selected products
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $stmt = $this->conn->prepare("
                SELECT product_id, product_name, price, tax_rate, image_path 
                FROM products 
                WHERE product_id IN ($placeholders) AND status = 'active'
            ");
            $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($product = $result->fetch_assoc()) {
                // Check if product already exists in order
                $found = false;
                foreach ($current_order['items'] as &$item) {
                    if ($item['product_id'] == $product['product_id']) {
                        $item['quantity'] = 1;  // Set quantity to 1 instead of incrementing
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $current_order['items'][] = array(
                        'product_id' => $product['product_id'],
                        'product_name' => $product['product_name'],
                        'price' => $product['price'],
                        'tax_rate' => $product['tax_rate'],
                        'image_path' => self::formatImagePath($product['image_path']),
                        'quantity' => 1
                    );
                }
            }

            $this->saveCurrentOrder($current_order);
            return $current_order;
        } catch (Exception $e) {
            return array('error' => 'Failed to add items: ' . $e->getMessage());
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderManager = new OrderManager();
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add_item':
                $product_id = $_POST['product_id'] ?? 0;
                $quantity = $_POST['quantity'] ?? 0;
                $result = $orderManager->addItem($product_id, $quantity);
                echo json_encode($result);
                break;
                
            case 'update_quantity':
                $product_id = $_POST['product_id'] ?? 0;
                $quantity = $_POST['quantity'] ?? 0;
                $result = $orderManager->updateItemQuantity($product_id, $quantity);
                echo json_encode($result);
                break;

            case 'remove_item':
                $product_id = $_POST['product_id'] ?? 0;
                $result = $orderManager->removeItem($product_id);
                echo json_encode($result);
                break;

            case 'get_order':
                $order_id = $_POST['order_id'] ?? null;
                $result = $orderManager->getCurrentOrder($order_id);
                echo json_encode($result);
                break;

            case 'clear_order':
                $orderManager->clearCurrentOrder();
                echo json_encode(array('success' => true));
                break;

            case 'update_discount':
                $de = isset($_POST['discount_enabled']) && ($_POST['discount_enabled'] === '1' || $_POST['discount_enabled'] === 'true');
                $dp = isset($_POST['discount_percent']) ? (float) $_POST['discount_percent'] : 0.0;
                $result = $orderManager->updateSessionDiscount($de, $dp);
                echo json_encode($result);
                break;

            case 'create_order':
                $customer_name = $_POST['customer_name'] ?? '';
                $order_type = $_POST['order_type'] ?? 'account-order';
                $user_id = $_POST['user_id'] ?? null;
                $table_number = $_POST['table_number'] ?? null;

                if (empty($customer_name)) {
                    sendJsonResponse(['error' => 'Customer name is required']);
                }

                try {
                    $de = isset($_POST['discount_enabled']) && $_POST['discount_enabled'] === '1';
                    $dp = isset($_POST['discount_percent']) ? (float) $_POST['discount_percent'] : 0.0;
                    $orderManager->updateSessionDiscount($de, $dp);
                    $result = $orderManager->createFinalOrder($customer_name, $order_type, $user_id, 'cash', null, $table_number);
                    sendJsonResponse($result);
                } catch (Exception $e) {
                    error_log("Error creating order: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to create order: ' . $e->getMessage()]);
                }
                break;

            case 'get_feedback':
                $order_id = $_POST['order_id'] ?? 0;
                $result = $orderManager->getOrderFeedback($order_id);
                echo json_encode($result);
                break;

            case 'save_feedback':
                $order_id = $_POST['order_id'] ?? 0;
                $ratings = json_decode($_POST['ratings'] ?? '{}', true);
                $comment = $_POST['comment'] ?? '';
                $result = $orderManager->saveFeedback($order_id, $ratings, $comment);
                echo json_encode($result);
                break;

            case 'get_categories':
                $result = $orderManager->getCategories();
                echo json_encode($result);
                break;
            
            case 'get_products':
                $result = $orderManager->getProducts();
                echo json_encode($result);
                break;
            
            case 'add_items':
                $product_ids = json_decode($_POST['product_ids'] ?? '[]', true);
                $result = $orderManager->addItems($product_ids);
                echo json_encode($result);
                break;

            case 'get_active_order':
                // Get the active order from session
                if (isset($_SESSION['active_order_id'])) {
                    $orderId = $_SESSION['active_order_id'];
                    
                    // Check if order exists
                    $stmt = $GLOBALS['conn']->prepare("SELECT order_id FROM orders WHERE order_id = ?");
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
                break;

            case 'set_active_order':
                $orderId = $_POST['order_id'];
                $_SESSION['active_order_id'] = $orderId;
                echo json_encode(['success' => true]);
                break;

            case 'clear_active_order':
                unset($_SESSION['active_order_id']);
                echo json_encode(['success' => true]);
                break;

            case 'get_order_details':
                $orderId = $_POST['order_id'];
                
                // Get order details
                $stmt = $GLOBALS['conn']->prepare("
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
                    // Get order items
                    $stmt = $GLOBALS['conn']->prepare("
                        SELECT oi.*, p.product_name, p.tax_rate
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
                break;

            case 'create_order_with_payment':
                $customer_name = $_POST['customer_name'] ?? '';
                $order_type = $_POST['order_type'] ?? 'account-order';
                $user_id = $_POST['user_id'] ?? null;
                $payment_type = $_POST['payment_type'] ?? 'cash';
                $table_number = $_POST['table_number'] ?? null;
                $proof_of_payment = isset($_FILES['proof_of_payment']) ? $_FILES['proof_of_payment'] : null;

                if (empty($customer_name)) {
                    sendJsonResponse(['error' => 'Customer name is required']);
                }

                try {
                    $de = isset($_POST['discount_enabled']) && $_POST['discount_enabled'] === '1';
                    $dp = isset($_POST['discount_percent']) ? (float) $_POST['discount_percent'] : 0.0;
                    $orderManager->updateSessionDiscount($de, $dp);
                    $result = $orderManager->createFinalOrder(
                        $customer_name,
                        $order_type,
                        $user_id,
                        $payment_type,
                        $proof_of_payment,
                        $table_number
                    );
                    sendJsonResponse($result);
                } catch (Exception $e) {
                    error_log("Error creating order: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to create order: ' . $e->getMessage()]);
                }
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($GLOBALS['conn']->inTransaction()) {
            $GLOBALS['conn']->rollback();
        }
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
?>