<?php
require_once 'dbconn.php';

// Function to fetch all ingredient categories
function fetchIngredientCategories() {
    global $conn;
    try {
        $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
        $result = $conn->query($sql);
        
        $categories = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return ['success' => true, 'data' => $categories];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to fetch all product categories
function fetchProductCategories() {
    global $conn;
    try {
        $sql = "SELECT category_id, category_name, description, status 
                FROM product_categories 
                WHERE status = 'active'
                ORDER BY category_name";
        $result = $conn->query($sql);
        
        $categories = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return ['success' => true, 'data' => $categories];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to fetch all ingredients
function fetchIngredients() {
    global $conn;
    try {
        $sql = "SELECT 
                    i.ingredient_id,
                    i.ingredient_name,
                    i.image_path,
                    i.category_id,
                    i.stock,
                    i.unit,
                    i.status,
                    i.created_at,
                    i.updated_at,
                    c.category_name 
                FROM ingredients i 
                JOIN categories c ON i.category_id = c.category_id 
                ORDER BY i.ingredient_name";
        $result = $conn->query($sql);
        
        $ingredients = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $ingredients[] = $row;
            }
        }
        return ['success' => true, 'data' => $ingredients];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to fetch all products
function fetchProducts() {
    global $conn;
    try {
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.category_id,
                    p.price,
                    p.description,
                    p.image_path,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    pc.category_name
                FROM products p
                JOIN product_categories pc ON p.category_id = pc.category_id
                ORDER BY p.product_name";
        $result = $conn->query($sql);
        
        $products = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Fetch ingredients for each product
                $ingredientsSql = "SELECT 
                                    i.ingredient_name,
                                    pi.quantity,
                                    pi.unit
                                FROM product_ingredients pi
                                JOIN ingredients i ON pi.ingredient_id = i.ingredient_id
                                WHERE pi.product_id = ?";
                $stmt = $conn->prepare($ingredientsSql);
                $stmt->bind_param("i", $row['product_id']);
                $stmt->execute();
                $ingredientsResult = $stmt->get_result();
                
                $ingredients = [];
                if ($ingredientsResult->num_rows > 0) {
                    while($ingredient = $ingredientsResult->fetch_assoc()) {
                        $ingredients[] = $ingredient;
                    }
                }
                
                $row['ingredients'] = $ingredients;
                $products[] = $row;
            }
        }
        return ['success' => true, 'data' => $products];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to get total products count
function getTotalProductsCount() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as total FROM products";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        return ['success' => true, 'data' => $row['total']];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to fetch a single ingredient by ID
function fetchIngredient($ingredientId) {
    global $conn;
    try {
        $sql = "SELECT 
                    i.ingredient_id,
                    i.ingredient_name,
                    i.image_path,
                    i.category_id,
                    i.stock,
                    i.unit,
                    i.status,
                    i.created_at,
                    i.updated_at,
                    c.category_name 
                FROM ingredients i 
                JOIN categories c ON i.category_id = c.category_id 
                WHERE i.ingredient_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ingredientId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => true, 'data' => $result->fetch_assoc()];
        } else {
            return ['success' => false, 'error' => 'Ingredient not found'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'ingredient-categories':
                echo json_encode(fetchIngredientCategories());
                break;
            case 'product-categories':
                echo json_encode(fetchProductCategories());
                break;
            case 'ingredients':
                echo json_encode(fetchIngredients());
                break;
            case 'ingredient':
                if (!isset($_GET['id'])) {
                    throw new Exception('Ingredient ID is required');
                }
                echo json_encode(fetchIngredient($_GET['id']));
                break;
            case 'products':
                echo json_encode(fetchProducts());
                break;
            case 'total-products':
                echo json_encode(getTotalProductsCount());
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?> 