<?php
require_once 'dbconn.php';
require_once 'image_path_helper.php';

header('Content-Type: application/json');

try {
    $category_query = "SELECT category_id, category_name, description, image_path FROM product_categories WHERE status = 'active' ORDER BY category_id ASC";
    $category_result = $conn->query($category_query);

    if ($category_result === false) {
        throw new Exception("Category query failed: " . $conn->error);
    }

    $categories = array();
    while ($cat_row = $category_result->fetch_assoc()) {
        $db_path = isset($cat_row['image_path']) ? trim((string) $cat_row['image_path']) : '';
        $image_path = $db_path !== '' ? image_path_for_users_folder($db_path) : null;

        $categories[] = array(
            'category_id' => $cat_row['category_id'],
            'category_name' => $cat_row['category_name'],
            'description' => $cat_row['description'],
            'image_path' => $image_path
        );
    }

    if (empty($categories)) {
        echo json_encode(array('message' => 'No categories found'));
    } else {
        echo json_encode($categories);
    }
} catch (Exception $e) {
    echo json_encode(array(
        'error' => $e->getMessage()
    ));
}

$conn->close();
?>
