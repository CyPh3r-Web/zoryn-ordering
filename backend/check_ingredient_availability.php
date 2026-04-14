<?php
require_once 'dbconn.php';

function checkIngredientAvailability($product_id) {
    global $conn;
    
    // Get all ingredients and their required quantities for the product
    $query = "SELECT i.ingredient_id, i.stock, i.unit, pi.quantity, pi.unit as required_unit 
              FROM ingredients i 
              JOIN product_ingredients pi ON i.ingredient_id = pi.ingredient_id 
              WHERE pi.product_id = ? AND i.status = 'active'";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $isAvailable = true;
    $lowIngredients = [];
    
    while ($row = $result->fetch_assoc()) {
        // Convert units to a common unit for comparison
        $availableQuantity = convertToCommonUnit($row['stock'], $row['unit']);
        $requiredQuantity = convertToCommonUnit($row['quantity'], $row['required_unit']);
        
        // Check if we have enough stock
        if ($availableQuantity < $requiredQuantity) {
            $isAvailable = false;
            $lowIngredients[] = [
                'ingredient_id' => $row['ingredient_id'],
                'available' => $availableQuantity,
                'required' => $requiredQuantity
            ];
        }
    }
    
    return [
        'is_available' => $isAvailable,
        'low_ingredients' => $lowIngredients
    ];
}

function convertToCommonUnit($quantity, $unit) {
    // Convert all units to milliliters or grams for comparison
    switch (strtolower($unit)) {
        case 'liters':
            return $quantity * 1000; // Convert to ml
        case 'kg':
            return $quantity * 1000; // Convert to g
        case 'pcs':
            return $quantity; // Keep as is
        case 'ml':
        case 'g':
            return $quantity;
        default:
            return $quantity;
    }
}

// Handle API request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id > 0) {
        $availability = checkIngredientAvailability($product_id);
        header('Content-Type: application/json');
        echo json_encode($availability);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product ID']);
    }
}
?> 