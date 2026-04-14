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
        $stmt = $conn->prepare("INSERT INTO ingredients (ingredient_name, image_path, category_id, stock, unit, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssidss", $ingredient_name, $imagePath, $category_id, $stock, $unit, $status);

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