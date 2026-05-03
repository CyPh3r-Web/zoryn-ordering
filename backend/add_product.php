<?php
require_once 'dbconn.php';
require_once 'kitchen_stations_helper.php';

header('Content-Type: application/json');

function normalizeProductPrice($rawPrice) {
    $price = trim((string) $rawPrice);
    $price = str_replace([',', 'PHP', 'php', '₱', ' '], '', $price);

    if ($price === '' || !is_numeric($price)) {
        throw new Exception('Invalid product price');
    }

    $normalized = number_format((float) $price, 2, '.', '');

    if ((float) $normalized < 0) {
        throw new Exception('Product price cannot be negative');
    }

    return $normalized;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $productName = $_POST['productName'];
        $categoryId = $_POST['productCategory'];
        $price = normalizeProductPrice($_POST['productPrice'] ?? '');
        $taxRate = isset($_POST['productTaxRate']) ? floatval($_POST['productTaxRate']) : 12.00;
        if ($taxRate < 0 || $taxRate > 100) {
            throw new Exception('Tax rate must be between 0 and 100');
        }
        $description = $_POST['productDescription'];
        $status = $_POST['productStatus'];
        $kitchenStationRaw = $_POST['productKitchenStation'] ?? '';
        $kitchenStation = ($kitchenStationRaw !== '')
            ? zoryn_normalize_kitchen_station($kitchenStationRaw)
            : zoryn_default_kitchen_station_for_category((int) $categoryId);
        $ingredients = json_decode($_POST['ingredients'], true);

        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/products/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['productImage']['tmp_name'], $targetPath)) {
                $imagePath = 'assets/images/products/' . $fileName;
            }
        }

        // Validate category exists in product_categories
        $checkCategory = $conn->prepare("SELECT category_id FROM product_categories WHERE category_id = ? AND status = 'active'");
        $checkCategory->bind_param("i", $categoryId);
        $checkCategory->execute();
        $checkCategory->store_result();
        
        if ($checkCategory->num_rows === 0) {
            throw new Exception("Invalid product category selected");
        }

        // Start transaction
        $conn->begin_transaction();

        // Insert product
        $sql = "INSERT INTO products (product_name, category_id, kitchen_station, price, tax_rate, description, image_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissdsss", $productName, $categoryId, $kitchenStation, $price, $taxRate, $description, $imagePath, $status);
        $stmt->execute();
        
        $productId = $conn->insert_id;

        // Insert product ingredients
        $sql = "INSERT INTO product_ingredients (product_id, ingredient_id, quantity, unit) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($ingredients as $ingredient) {
            $stmt->bind_param("iids", 
                $productId, 
                $ingredient['id'], 
                $ingredient['quantity'], 
                $ingredient['unit']
            );
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully'
        ]);

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
?> 