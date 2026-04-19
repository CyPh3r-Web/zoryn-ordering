<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

        // Audit-trail movement — adjustment_add for Stock IN, adjustment_less for Stock OUT
        $movementType = $type === 'add' ? 'adjustment_add' : 'adjustment_less';
        $createdBy = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id']
                   : (isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null);
        $noteText  = ($type === 'add' ? 'Manual stock-in' : 'Manual stock-out') . ' (admin)';
        $logStmt = $conn->prepare("
            INSERT INTO inventory_movements
                (ingredient_id, movement_type, quantity, unit_cost,
                 reference_type, reference_id, notes, movement_date, created_by)
            VALUES (?, ?, ?, 0, 'manual_adjustment', NULL, ?, CURDATE(), ?)
        ");
        $logStmt->bind_param("isdsi", $ingredient_id, $movementType, $amount, $noteText, $createdBy);
        $logStmt->execute();

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