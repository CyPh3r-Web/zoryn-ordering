<?php
require_once 'dbconn.php';

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
        $productId = $_POST['productId'];
        $productName = $_POST['productName'];
        $categoryId = $_POST['productCategory'];
        $price = normalizeProductPrice($_POST['productPrice'] ?? '');
        $taxRate = isset($_POST['productTaxRate']) ? floatval($_POST['productTaxRate']) : 12.00;
        if ($taxRate < 0 || $taxRate > 100) {
            throw new Exception('Tax rate must be between 0 and 100');
        }
        $description = $_POST['productDescription'];
        $status = $_POST['productStatus'];
        $ingredients = json_decode($_POST['ingredients'], true);

        // Start transaction
        $conn->begin_transaction();

        // Update product
        $sql = "UPDATE products 
                SET product_name = ?, 
                    category_id = ?, 
                    price = ?, 
                    tax_rate = ?,
                    description = ?, 
                    status = ? 
                WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdssi", $productName, $categoryId, $price, $taxRate, $description, $status, $productId);
        $stmt->execute();

        // Handle image upload if provided
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
                
                // Update product image
                $sql = "UPDATE products SET image_path = ? WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $imagePath, $productId);
                $stmt->execute();
            }
        }

        // Delete existing product ingredients
        $sql = "DELETE FROM product_ingredients WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();

        // Insert new product ingredients
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
            'message' => 'Product updated successfully'
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