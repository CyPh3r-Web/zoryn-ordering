<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $ingredient_id = $_POST['ingredient_id'] ?? 0;
        $amount = floatval($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? '';
        
        // Validate input
        if (!$ingredient_id || $amount <= 0 || !in_array($type, ['add', 'remove'])) {
            throw new Exception("Invalid parameters");
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Get current stock
        $stmt = $conn->prepare("SELECT stock FROM ingredients WHERE ingredient_id = ?");
        $stmt->bind_param("i", $ingredient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_stock = $result->fetch_assoc()['stock'];
        
        // Calculate new stock
        if ($type === 'add') {
            $new_stock = $current_stock + $amount;
        } else {
            $new_stock = $current_stock - $amount;
            if ($new_stock < 0) {
                throw new Exception("Not enough stock to remove");
            }
        }
        
        // Update stock
        $stmt = $conn->prepare("UPDATE ingredients SET stock = ?, updated_at = NOW() WHERE ingredient_id = ?");
        $stmt->bind_param("di", $new_stock, $ingredient_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to update stock");
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?> 