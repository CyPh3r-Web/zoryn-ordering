<?php
require_once '../backend/dbconn.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
    $user_id = mysqli_real_escape_string($conn, $data['user_id']);
    $username = mysqli_real_escape_string($conn, $data['username']);
    $full_name = mysqli_real_escape_string($conn, $data['full_name']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $role = mysqli_real_escape_string($conn, $data['role']);
    $status = mysqli_real_escape_string($conn, $data['status']);
    
    // Check if username or email already exists for other users
    $check_query = "SELECT * FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Update user
    $query = "UPDATE users 
              SET username = ?, 
                  full_name = ?, 
                  email = ?, 
                  role = ?, 
                  account_status = ?, 
                  updated_at = NOW() 
              WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssssi", $username, $full_name, $email, $role, $status, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?> 