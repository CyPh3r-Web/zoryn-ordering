<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

try {
    // Get category_id for Milky Series
    $category_query = "SELECT category_id FROM product_categories WHERE category_name = 'Milky Series'";
    $category_result = $conn->query($category_query);
    
    if ($category_result === false) {
        throw new Exception("Category query failed: " . $conn->error);
    }
    
    if ($category_result->num_rows > 0) {
        $category_row = $category_result->fetch_assoc();
        $category_id = $category_row['category_id'];
        
        // Fetch products for Milky Series
        $product_query = "SELECT * FROM products WHERE category_id = ? AND status = 'active'";
        $stmt = $conn->prepare($product_query);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $category_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result === false) {
            throw new Exception("Get result failed: " . $stmt->error);
        }
        
        $products = array();
        while ($row = $result->fetch_assoc()) {
            // Ensure the image path is properly formatted
            if (!empty($row['image_path'])) {
                // Remove any existing path and just use the filename
                $filename = basename($row['image_path']);
                $row['image_path'] = '../assets/images/products/' . $filename;
            }
            $products[] = $row;
        }
        
        if (empty($products)) {
            echo json_encode(array('message' => 'No products found for Milky Series'));
        } else {
            echo json_encode($products);
        }
    } else {
        echo json_encode(array('error' => 'Category not found'));
    }
} catch (Exception $e) {
    echo json_encode(array(
        'error' => $e->getMessage(),
        'debug' => array(
            'category_query' => $category_query,
            'product_query' => $product_query ?? 'Not set',
            'category_id' => $category_id ?? 'Not set'
        )
    ));
}

$conn->close();
?> 