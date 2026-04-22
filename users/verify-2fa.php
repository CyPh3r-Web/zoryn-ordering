<?php
session_start();
require_once 'email_functions.php';
require_once '../backend/dbconn.php';

header('Content-Type: application/json');

// Debug: Log session variables
error_log("Session variables: " . print_r($_SESSION, true));

// Check if user is in 2FA verification process
if (!isset($_SESSION['2fa_pending']) || !$_SESSION['2fa_pending']) {
    error_log("2FA verification failed: No pending 2FA verification found in session");
    echo json_encode(['success' => false, 'message' => 'Invalid 2FA verification request']);
    exit;
}

// Get user_id from session
if (!isset($_SESSION['user_id'])) {
    error_log("2FA verification failed: No user_id found in session");
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

$user_id = $_SESSION['user_id'];
error_log("Verifying 2FA for user_id: " . $user_id);

// Get user's 2FA status and verification code from database
$stmt = $conn->prepare("SELECT two_factor_enabled, twofa_code, twofa_expires, two_factor_attempts FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Debug: Log user data
error_log("User data from database: " . print_r($user, true));

if (!$user) {
    error_log("2FA verification failed: User not found in database");
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if (!$user['two_factor_enabled']) {
    error_log("2FA verification failed: 2FA not enabled for user_id: " . $user_id);
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
error_log("Stored verification code: " . (isset($user['twofa_code']) ? $user['twofa_code'] : 'not set'));

// Validate code format
if (!preg_match('/^\d{6}$/', $verification_code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format']);
    exit;
}

// Check if code has expired
if (time() > strtotime($user['twofa_expires'])) {
    echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
    exit;
}

// Verify the code
if ($verification_code === $user['twofa_code']) {
    // Code is correct, clear verification data
    $stmt = $conn->prepare("UPDATE users SET twofa_code = NULL, twofa_expires = NULL, two_factor_attempts = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Set 2FA as verified and complete login
    $_SESSION['2fa_verified'] = true;
    if (isset($_SESSION['2fa_role'])) {
        $_SESSION['role'] = $_SESSION['2fa_role'];
        unset($_SESSION['2fa_role']);
    }
    unset($_SESSION['2fa_pending']);
    
    echo json_encode(['success' => true]);
} else {
    // Debug: Log verification failure
    error_log("Verification failed - Code mismatch");
    
    // Increment failed attempts
    $stmt = $conn->prepare("UPDATE users SET two_factor_attempts = two_factor_attempts + 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
}
