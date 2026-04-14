<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');

require_once 'dbconn.php';

class InventoryManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Get all ingredients with their current stock
    public function getAllIngredients() {
        try {
            // First check if the ingredient_categories table exists
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'ingredient_categories'");
            if ($checkTable->num_rows > 0) {
                // If table exists, use the join query
                $query = "SELECT i.*, c.category_name 
                         FROM ingredients i 
                         JOIN ingredient_categories c ON i.category_id = c.category_id 
                         ORDER BY i.category_id, i.ingredient_name";
            } else {
                // If table doesn't exist, use a simple query with hardcoded category names
                $query = "SELECT i.*, 
                         CASE i.category_id
                             WHEN 1 THEN 'Coffee'
                             WHEN 2 THEN 'Syrup'
                             WHEN 3 THEN 'Powder'
                             WHEN 4 THEN 'Dairy'
                             WHEN 5 THEN 'Topping'
                             WHEN 6 THEN 'Other'
                             ELSE 'Unknown'
                         END as category_name
                         FROM ingredients i 
                         ORDER BY i.category_id, i.ingredient_name";
            }
            
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Database query failed");
            }
            
            $ingredients = array();
            while ($row = $result->fetch_assoc()) {
                $ingredients[] = $row;
            }
            
            return $ingredients;
        } catch (Exception $e) {
            throw new Exception("Failed to get ingredients: " . $e->getMessage());
        }
    }

    // Update ingredient stock
    public function updateIngredientStock($ingredient_id, $quantity) {
        $stmt = $this->conn->prepare("UPDATE ingredients SET stock = stock - ? WHERE ingredient_id = ?");
        $stmt->bind_param("di", $quantity, $ingredient_id);
        return $stmt->execute();
    }

    // Get ingredient stock
    public function getIngredientStock($ingredient_id) {
        $stmt = $this->conn->prepare("SELECT stock FROM ingredients WHERE ingredient_id = ?");
        $stmt->bind_param("i", $ingredient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['stock'];
    }

    // Update stock when an order is placed
    public function updateStockForOrder($order_id) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Get all items in the order
            $stmt = $this->conn->prepare("
                SELECT oi.product_id, oi.quantity 
                FROM order_items oi 
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($order_item = $result->fetch_assoc()) {
                // Get ingredients for this product
                $stmt = $this->conn->prepare("
                    SELECT pi.ingredient_id, pi.quantity, pi.unit 
                    FROM product_ingredients pi 
                    WHERE pi.product_id = ?
                ");
                $stmt->bind_param("i", $order_item['product_id']);
                $stmt->execute();
                $ingredients = $stmt->get_result();
                
                while ($ingredient = $ingredients->fetch_assoc()) {
                    // Calculate total quantity needed (product quantity * ingredient quantity)
                    $total_quantity = $order_item['quantity'] * $ingredient['quantity'];
                    
                    // Update the ingredient stock
                    $this->updateIngredientStock($ingredient['ingredient_id'], $total_quantity);
                }
            }

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return false;
        }
    }

    // Check if there's enough stock for an order
    public function checkStockForOrder($order_id) {
        try {
            // Get all items in the order
            $stmt = $this->conn->prepare("
                SELECT oi.product_id, oi.quantity 
                FROM order_items oi 
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($order_item = $result->fetch_assoc()) {
                // Get ingredients for this product
                $stmt = $this->conn->prepare("
                    SELECT pi.ingredient_id, pi.quantity, pi.unit, i.stock, i.ingredient_name
                    FROM product_ingredients pi 
                    JOIN ingredients i ON pi.ingredient_id = i.ingredient_id
                    WHERE pi.product_id = ?
                ");
                $stmt->bind_param("i", $order_item['product_id']);
                $stmt->execute();
                $ingredients = $stmt->get_result();
                
                while ($ingredient = $ingredients->fetch_assoc()) {
                    // Calculate total quantity needed
                    $total_quantity = $order_item['quantity'] * $ingredient['quantity'];
                    
                    // Check if there's enough stock
                    if ($ingredient['stock'] < $total_quantity) {
                        return array(
                            'success' => false,
                            'message' => "Not enough stock for {$ingredient['ingredient_name']}. Required: {$total_quantity}{$ingredient['unit']}, Available: {$ingredient['stock']}{$ingredient['unit']}"
                        );
                    }
                }
            }
            
            return array('success' => true);
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $inventoryManager = new InventoryManager($conn);
        
        switch ($action) {
            case 'get_ingredients':
                $ingredients = $inventoryManager->getAllIngredients();
                echo json_encode(['success' => true, 'data' => $ingredients]);
                break;
                
            case 'check_stock':
                $order_id = $_POST['order_id'] ?? 0;
                $result = $inventoryManager->checkStockForOrder($order_id);
                echo json_encode($result);
                break;
                
            case 'update_stock':
                $order_id = $_POST['order_id'] ?? 0;
                $result = $inventoryManager->updateStockForOrder($order_id);
                echo json_encode(['success' => $result]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>