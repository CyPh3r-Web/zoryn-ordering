<?php
require_once 'dbconn.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the form data
        $ingredient_name = $_POST['ingredientName'];
        $category_id = $_POST['ingredientCategory'];
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

        // Handle image upload
        $imagePath = null;
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
            }
        }

        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO ingredients (ingredient_name, image_path, category_id, fifo_group_key, stock, unit, moisture_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisdsss", $ingredient_name, $imagePath, $category_id, $fifo_group_key, $stock, $unit, $moisture_type, $status);

        // Execute the statement
        if ($stmt->execute()) {
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Ingredient added successfully',
                'ingredient_id' => $stmt->insert_id
            ]);
        } else {
            throw new Exception('Error adding ingredient: ' . $conn->error);
        }

        // Close the statement
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    // Return error if not POST request
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// Close the connection
$conn->close();
?> 