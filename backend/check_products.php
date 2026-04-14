<?php
require_once 'dbconn.php';

// Check if Milky Series category exists
$category_query = "SELECT * FROM product_categories WHERE category_name = 'Milky Series'";
$category_result = $conn->query($category_query);

if ($category_result->num_rows > 0) {
    $category = $category_result->fetch_assoc();
    echo "Milky Series category found (ID: " . $category['category_id'] . ")\n\n";
    
    // Check products in this category
    $product_query = "SELECT * FROM products WHERE category_id = " . $category['category_id'];
    $product_result = $conn->query($product_query);
    
    if ($product_result->num_rows > 0) {
        echo "Products found:\n";
        while ($product = $product_result->fetch_assoc()) {
            echo "ID: " . $product['product_id'] . "\n";
            echo "Name: " . $product['product_name'] . "\n";
            echo "Price: " . $product['price'] . "\n";
            echo "Image: " . $product['image_path'] . "\n";
            echo "Status: " . $product['status'] . "\n";
            echo "-------------------\n";
        }
    } else {
        echo "No products found in Milky Series category\n";
    }
} else {
    echo "Milky Series category not found\n";
}

$conn->close();
?> 