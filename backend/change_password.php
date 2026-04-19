<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/dbconn.php';

$userId = null;
if (!empty($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
} elseif (!empty($_SESSION['admin_id'])) {
    $userId = (int) $_SESSION['admin_id'];
}

if (!$userId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$current = $data['current_password'] ?? '';
$new = $data['new_password'] ?? '';
$confirm = $data['confirm_password'] ?? '';

if ($current === '' || $new === '' || $confirm === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit;
}
if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit;
}
if (strlen($new) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
    exit;
}
if ($new === $current) {
    echo json_encode(['success' => false, 'message' => 'New password must be different from your current password']);
    exit;
}

$stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row || !password_verify($current, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$upd = $conn->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?');
if (!$upd) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$upd->bind_param('si', $hash, $userId);
$ok = $upd->execute();
$upd->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not update password']);
}
