<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_id <= 0) {
    echo json_encode(array('error' => 'Invalid category_id'));
    exit;
}

try {
    // Fetch category info
    $cat_query = "SELECT category_id, category_name, description FROM product_categories WHERE category_id = ? AND status = 'active'";
    $cat_stmt = $conn->prepare($cat_query);

    if ($cat_stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $cat_stmt->bind_param("i", $category_id);

    if (!$cat_stmt->execute()) {
        throw new Exception("Execute failed: " . $cat_stmt->error);
    }

    $cat_result = $cat_stmt->get_result();

    if ($cat_result->num_rows === 0) {
        echo json_encode(array('error' => 'Category not found'));
        exit;
    }

    $category = $cat_result->fetch_assoc();
    $cat_stmt->close();

    // Fetch products for this category
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

    $products = array();
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['image_path'])) {
            $filename = basename($row['image_path']);
            $row['image_path'] = '../assets/images/products/' . $filename;
        }
        $products[] = $row;
    }

    $stmt->close();

    echo json_encode(array(
        'category' => $category,
        'products' => $products
    ));
} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}

$conn->close();
?>
