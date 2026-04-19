<?php
require_once '../backend/dbconn.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
    $username = mysqli_real_escape_string($conn, $data['username']);
    $full_name = mysqli_real_escape_string($conn, $data['full_name']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $role = mysqli_real_escape_string($conn, $data['role']);
    
    // Default password for accounts created by admin
    $default_password = 'zoryn123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    // Check if username or email already exists
    $check_query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Insert new user
    $query = "INSERT INTO users (username, password, full_name, email, role, created_at, updated_at, account_status) 
              VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 'active')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $full_name, $email, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?> 