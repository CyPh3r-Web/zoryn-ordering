<?php
require_once 'dbconn.php';

header('Content-Type: application/json');

/**
 * Paths in DB are often stored as "assets/zoryn/products/foo.jpg".
 * Pages under users/ must receive "../assets/..." or the browser resolves "assets/..."
 * relative to /users/ and requests /users/assets/... (404).
 * Leading "/" would hit the domain root (/assets/...) and also 404 on typical WAMP setups.
 * The filename is URL-encoded so characters like "&" in "chicken & beef.jpg" work in img src.
 */
function image_path_for_users_folder($path) {
    if ($path === null || trim((string) $path) === '') {
        return null;
    }
    $path = str_replace('\\', '/', trim($path));
    $path = ltrim($path, '/');
    if (strpos($path, '../') === 0) {
        $norm = $path;
    } elseif (strpos($path, 'assets/') === 0) {
        $norm = '../' . $path;
    } else {
        $norm = '../' . $path;
    }
    $dir = dirname($norm);
    $base = basename($norm);
    if ($base === '' || $base === '.') {
        return $norm;
    }
    return $dir . '/' . rawurlencode($base);
}

// Mapping of category names to their image files (relative from users/ directory)
$category_images = array(
    'Baked Meals'      => '../assets/zoryn/products/baked-meals.jpg',
    'Barkada Meryenda' => '../assets/zoryn/products/barkada-meryenda.jpg',
    'Best Choice'      => '../assets/zoryn/products/best-choice.jpg',
    'Chicken & Beef'   => '../assets/zoryn/products/chicken & beef.jpg',
    'Desserts'         => '../assets/zoryn/products/dessert.jpg',
    'Drinks'           => '../assets/zoryn/products/drinks.jpg',
    'Family Set'       => '../assets/zoryn/products/family-set.jpg',
    'Halo Halo'        => '../assets/zoryn/products/halo-halo.jpg',
    'Iced Coffee'      => '../assets/zoryn/products/iced-coffee.jpg',
    'Platter'          => '../assets/zoryn/products/platter.jpg',
    'Rice Platter'     => '../assets/zoryn/products/rice_platter.jpg',
    'Salad'            => '../assets/zoryn/products/salad.jpg',
    'Seafood'          => '../assets/zoryn/products/seafood.jpg',
    'Solo Meals'       => '../assets/zoryn/products/solo-meals.jpg',
    'Soup'             => '../assets/zoryn/products/soup.jpg',
);

try {
    $category_query = "SELECT category_id, category_name, description, image_path FROM product_categories WHERE status = 'active' ORDER BY category_id ASC";
    $category_result = $conn->query($category_query);

    if ($category_result === false) {
        throw new Exception("Category query failed: " . $conn->error);
    }

    $categories = array();
    while ($cat_row = $category_result->fetch_assoc()) {
        $cat_name = $cat_row['category_name'];
        $db_path = isset($cat_row['image_path']) ? $cat_row['image_path'] : '';
        $image_path = image_path_for_users_folder($db_path);
        if ($image_path === null) {
            $fallback = isset($category_images[$cat_name]) ? $category_images[$cat_name] : null;
            $image_path = $fallback !== null ? image_path_for_users_folder($fallback) : null;
        }

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
