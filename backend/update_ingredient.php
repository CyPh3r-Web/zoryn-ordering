<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $ingredientId = isset($_POST['ingredientId']) ? (int) $_POST['ingredientId'] : 0;
        $ingredientName = $_POST['ingredientName'];
        $categoryId = $_POST['ingredientCategory'];
        $stock = $_POST['ingredientStock'];
        $unit = $_POST['ingredientUnit'];
        $status = $_POST['ingredientStatus'];
        $moisture_type = isset($_POST['ingredientMoisture']) ? $_POST['ingredientMoisture'] : 'dry';
        if (!in_array($moisture_type, ['dry', 'wet'], true)) {
            $moisture_type = 'dry';
        }
        $fifo_group_key = isset($_POST['fifo_group_key']) ? trim((string) $_POST['fifo_group_key']) : '';
        if (strlen($fifo_group_key) > 64) {
            throw new Exception('FIFO group key must be 64 characters or less.');
        }
        $fifo_group_key = $fifo_group_key === '' ? null : $fifo_group_key;

        if ($ingredientId <= 0) {
            throw new Exception("Invalid ingredient ID");
        }

        // Start transaction
        $conn->begin_transaction();

        // Update ingredient
        $sql = "UPDATE ingredients 
                SET ingredient_name = ?, 
                    category_id = ?, 
                    fifo_group_key = ?,
                    stock = ?, 
                    unit = ?, 
                    moisture_type = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE ingredient_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdsssi", $ingredientName, $categoryId, $fifo_group_key, $stock, $unit, $moisture_type, $status, $ingredientId);

        // Handle image upload if provided
        if (isset($_FILES['ingredientImage']) && $_FILES['ingredientImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/ingredients/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['ingredientImage']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['ingredientImage']['tmp_name'], $targetPath)) {
                $imagePath = 'assets/images/ingredients/' . $fileName;
                
                // Update ingredient image
                $sql = "UPDATE ingredients SET image_path = ? WHERE ingredient_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $imagePath, $ingredientId);
                $stmt->execute();
            }
        }

        if ($stmt->execute()) {
            if ($stmt->affected_rows === 0) {
                throw new Exception("No ingredient was updated. Please check the ingredient ID.");
            }
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Ingredient updated successfully'
            ]);
        } else {
            throw new Exception("Failed to update ingredient");
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