<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $productId = $_POST['productId'];
        $productName = $_POST['productName'];
        $categoryId = $_POST['productCategory'];
        $price = $_POST['productPrice'];
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
                    description = ?, 
                    status = ? 
                WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidssi", $productName, $categoryId, $price, $description, $status, $productId);
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