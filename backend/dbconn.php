<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "zoryn";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Set charset to utf8
$conn->set_charset("utf8");

// Add payment_status column to orders table if it doesn't exist
$addPaymentStatusColumn = "
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'pending', 'verified') DEFAULT 'unpaid'";

try {
    $conn->query($addPaymentStatusColumn);
} catch (Exception $e) {
    error_log("Error adding payment_status column: " . $e->getMessage());
}
?>
