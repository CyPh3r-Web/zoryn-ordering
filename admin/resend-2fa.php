<?php
session_start();
require_once '../users/email_functions.php';
require_once '../backend/dbconn.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Get admin's 2FA status and last sent time
$stmt = $conn->prepare("SELECT email, full_name, two_factor_enabled, last_2fa_sent, two_factor_attempts FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin || !$admin['two_factor_enabled']) {
    header('Location: admin_login.php');
    exit;
}

// Check if we can resend (minimum 1 minute between resends)
if ($admin['last_2fa_sent'] && (time() - strtotime($admin['last_2fa_sent'])) < 60) {
    header('Location: 2fa.php?error=resend_too_soon');
    exit;
}

// Generate new 6-digit code
$new_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Set expiration time (10 minutes from now)
$expires = time() + (10 * 60);

// Update database with new code and timestamp
$stmt = $conn->prepare("UPDATE users SET twofa_code = ?, twofa_expires = FROM_UNIXTIME(?), last_2fa_sent = NOW(), two_factor_attempts = 0 WHERE user_id = ?");
$stmt->bind_param("sii", $new_code, $expires, $_SESSION['admin_id']);
$stmt->execute();

// Set session variables
$_SESSION['2fa_expires'] = $expires;
$_SESSION['2fa_pending'] = true;

// Send new verification email
if (sendVerificationEmail($admin['full_name'], $admin['email'], $new_code)) {
    header('Location: 2fa.php?resend=success');
} else {
    header('Location: 2fa.php?error=email_failed');
}
exit;