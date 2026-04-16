<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

if (isset($_GET['product_id'])) {
    try {
        $product_id = $_GET['product_id'];
        
        // Get product details
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.category_id,
                    p.price,
                    p.tax_rate,
                    p.description,
                    p.image_path,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    pc.category_name
                FROM products p
                JOIN product_categories pc ON p.category_id = pc.category_id
                WHERE p.product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            // Fetch ingredients for the product
            $ingredientsSql = "SELECT 
                                i.ingredient_id,
                                i.ingredient_name,
                                pi.quantity,
                                pi.unit
                            FROM product_ingredients pi
                            JOIN ingredients i ON pi.ingredient_id = i.ingredient_id
                            WHERE pi.product_id = ?";
            $stmt = $conn->prepare($ingredientsSql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $ingredientsResult = $stmt->get_result();
            
            $ingredients = [];
            if ($ingredientsResult->num_rows > 0) {
                while($ingredient = $ingredientsResult->fetch_assoc()) {
                    $ingredients[] = $ingredient;
                }
            }
            
            $product['ingredients'] = $ingredients;
            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
}
?> 