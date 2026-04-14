<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

try {
    // Get all ingredients with their current stock
    $ingredients_query = "SELECT i.*, c.category_name 
                         FROM ingredients i 
                         LEFT JOIN categories c ON i.category_id = c.category_id 
                         WHERE i.status = 'active'";
    $ingredients_result = $conn->query($ingredients_query);
    $ingredients = [];
    
    while ($row = $ingredients_result->fetch_assoc()) {
        $ingredients[$row['ingredient_id']] = [
            'ingredient_id' => $row['ingredient_id'],
            'ingredient_name' => $row['ingredient_name'],
            'category_name' => $row['category_name'],
            'current_stock' => floatval($row['stock']), // Convert to float
            'unit' => $row['unit'],
            'total_used' => 0,
            'image_path' => $row['image_path'] // Add image path
        ];
    }

    // Get all completed orders
    $orders_query = "SELECT o.order_id, oi.product_id, oi.quantity 
                    FROM orders o 
                    JOIN order_items oi ON o.order_id = oi.order_id 
                    WHERE o.order_status = 'completed'";
    $orders_result = $conn->query($orders_query);

    // Calculate ingredient usage for each order
    while ($order = $orders_result->fetch_assoc()) {
        $product_id = $order['product_id'];
        $quantity = intval($order['quantity']); // Convert to integer

        // Get ingredients used in this product
        $product_ingredients_query = "SELECT ingredient_id, quantity, unit 
                                    FROM product_ingredients 
                                    WHERE product_id = ?";
        $stmt = $conn->prepare($product_ingredients_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_ingredients = $stmt->get_result();

        while ($ingredient = $product_ingredients->fetch_assoc()) {
            $ingredient_id = $ingredient['ingredient_id'];
            $ingredient_quantity = floatval($ingredient['quantity']); // Convert to float
            $ingredient_unit = $ingredient['unit'];

            // Convert units if necessary (ml to liters, g to kg)
            if ($ingredient_unit === 'ml') {
                $ingredient_quantity = $ingredient_quantity / 1000; // Convert to liters
            } elseif ($ingredient_unit === 'g') {
                $ingredient_quantity = $ingredient_quantity / 1000; // Convert to kg
            }

            // Multiply by order quantity
            $total_used = $ingredient_quantity * $quantity;

            // Add to total usage
            if (isset($ingredients[$ingredient_id])) {
                $ingredients[$ingredient_id]['total_used'] += $total_used;
            }
        }
    }

    // Convert the ingredients array to a list
    $ingredients_list = array_values($ingredients);

    echo json_encode([
        'success' => true,
        'data' => $ingredients_list
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>