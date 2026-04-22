<?php
require_once '../backend/dbconn.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['admin_id']) && (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin'))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = (int) ($data['user_id'] ?? 0);
$pin = trim((string) ($data['pin'] ?? ''));
if ($userId <= 0 || !preg_match('/^\d{4,8}$/', $pin)) {
    echo json_encode(['success' => false, 'message' => 'PIN must be 4 to 8 digits']);
    exit;
}

$q = mysqli_prepare($conn, "SELECT user_id, role FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($q, "i", $userId);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);
$user = mysqli_fetch_assoc($res);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$role = strtolower((string) $user['role']);
$hash = password_hash($pin, PASSWORD_DEFAULT);
$adminId = !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : (int) $_SESSION['user_id'];

if ($role === 'admin') {
    $upd = mysqli_prepare($conn, "UPDATE users SET admin_override_pin_hash = ?, admin_pin_updated_at = NOW(), updated_at = NOW() WHERE user_id = ?");
    mysqli_stmt_bind_param($upd, "si", $hash, $userId);
} else if ($role === 'cashier') {
    $upd = mysqli_prepare($conn, "UPDATE users SET cashier_pin_hash = ?, cashier_pin_set_by = ?, cashier_pin_set_at = NOW(), cashier_pin_updated_at = NOW(), updated_at = NOW() WHERE user_id = ?");
    mysqli_stmt_bind_param($upd, "sii", $hash, $adminId, $userId);
} else {
    echo json_encode(['success' => false, 'message' => 'PIN setup is for cashier/admin only']);
    exit;
}

if (mysqli_stmt_execute($upd)) {
    echo json_encode(['success' => true, 'message' => 'PIN saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save PIN']);
}
?>
