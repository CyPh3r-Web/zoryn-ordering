<?php
session_start();
require_once '../users/email_functions.php';
require_once '../backend/dbconn.php';

header('Content-Type: application/json');

// Debug: Log session variables
error_log("Session variables: " . print_r($_SESSION, true));

// Check if admin is in 2FA verification process
if (!isset($_SESSION['2fa_pending']) || !$_SESSION['2fa_pending']) {
    error_log("2FA verification failed: No pending 2FA verification found in session");
    echo json_encode(['success' => false, 'message' => 'Invalid 2FA verification request']);
    exit;
}

// Get admin_id from session
if (!isset($_SESSION['admin_id'])) {
    error_log("2FA verification failed: No admin_id found in session");
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
error_log("Verifying 2FA for admin_id: " . $admin_id);

// Get admin's 2FA status and verification code from database
$stmt = $conn->prepare("SELECT two_factor_enabled, twofa_code, twofa_expires, two_factor_attempts FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Debug: Log admin data
error_log("Admin data from database: " . print_r($admin, true));

if (!$admin) {
    error_log("2FA verification failed: Admin not found in database");
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
    exit;
}

if (!$admin['two_factor_enabled']) {
    error_log("2FA verification failed: 2FA not enabled for admin_id: " . $admin_id);
    echo json_encode(['success' => false, 'message' => '2FA is not enabled for this account']);
    exit;
}

// Check if code is provided
if (!isset($_POST['verification_code'])) {
    echo json_encode(['success' => false, 'message' => 'Verification code is required']);
    exit;
}

$verification_code = trim($_POST['verification_code']);

// Debug: Log verification code
error_log("Verification code received: " . $verification_code);
error_log("Stored verification code: " . (isset($admin['twofa_code']) ? $admin['twofa_code'] : 'not set'));

// Validate code format
if (!preg_match('/^\d{6}$/', $verification_code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format']);
    exit;
}

// Check if code has expired
if (time() > strtotime($admin['twofa_expires'])) {
    echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
    exit;
}

// Verify the code
if ($verification_code === $admin['twofa_code']) {
    // Code is correct, clear verification data
    $stmt = $conn->prepare("UPDATE users SET twofa_code = NULL, twofa_expires = NULL, two_factor_attempts = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    
    // Set 2FA as verified and complete login
    $_SESSION['2fa_verified'] = true;
    unset($_SESSION['2fa_pending']);
    
    echo json_encode(['success' => true]);
} else {
    // Debug: Log verification failure
    error_log("Verification failed - Code mismatch");
    
    // Increment failed attempts
    $stmt = $conn->prepare("UPDATE users SET two_factor_attempts = two_factor_attempts + 1 WHERE user_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
} 