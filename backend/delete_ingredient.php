<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the ingredient ID from the request body
        $data = json_decode(file_get_contents('php://input'), true);
        $ingredientId = $data['ingredient_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        // Check if the ingredient is used in any products
        $checkSql = "SELECT COUNT(*) as count FROM product_ingredients WHERE ingredient_id = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("i", $ingredientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            throw new Exception("Cannot delete ingredient as it is used in one or more products.");
        }
        
        // Delete the ingredient
        $sql = "DELETE FROM ingredients WHERE ingredient_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ingredientId);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Ingredient deleted successfully'
            ]);
        } else {
            throw new Exception("Failed to delete ingredient");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}

// Close the connection
$conn->close();
?> 