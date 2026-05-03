<?php
/**
 * Admin bell notifications — uses logged-in admin user_id (session admin_id).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/dbconn.php';
require_once __DIR__ . '/fifo_stock_helper.php';

$adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
if ($adminId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $raw = ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET')
        ? ($_SERVER['REQUEST_METHOD'] === 'POST' ? (file_get_contents('php://input') ?: '') : '')
        : '';
    $parsed = [];
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        $parsed = is_array($decoded) ? $decoded : [];
    }

    $action = trim((string) (
        ($_REQUEST['action'] ?? '')
            ?: ($parsed['action'] ?? '')
    ));

    if ($action === 'list') {
        $hasExtra = fifo_notifications_has_link_columns($conn);

        $cols = $hasExtra
            ? 'id, message, order_id, is_read, created_at,
               COALESCE(notification_type, \'order\') AS notification_type,
               link_url,
               COALESCE(related_ingredient_id, 0) AS related_ingredient_id'
            : 'id, message, order_id, is_read, created_at';

        $sql = "SELECT {$cols} FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 80";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $res = $stmt->get_result();

        $list = [];
        $unread = 0;
        while ($row = $res->fetch_assoc()) {
            if ((int) ($row['is_read'] ?? 0) === 0) {
                $unread++;
            }
            $item = [
                'id'            => (int) $row['id'],
                'message'       => (string) $row['message'],
                'order_id'      => (int) ($row['order_id'] ?? 0),
                'is_read'       => (int) ($row['is_read'] ?? 0),
                'created_at'    => zoryn_datetime_to_iso8601($row['created_at'] ?? ''),
                'notification_type' => isset($row['notification_type'])
                    ? (string) $row['notification_type'] : 'order',
                'link_url'      => isset($row['link_url']) ? (string) ($row['link_url'] ?? '') : '',
                'related_ingredient_id' => isset($row['related_ingredient_id'])
                    ? (int) $row['related_ingredient_id'] : 0,
            ];
            $list[] = $item;
        }

        echo json_encode(['success' => true, 'notifications' => $list, 'unread_count' => $unread]);
        exit;
    }

    if ($action === 'mark_read') {
        $id = isset($parsed['id']) ? (int) $parsed['id'] : (int) ($_POST['notification_id'] ?? 0);
        if (!$id && isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }
        if ($id <= 0) {
            throw new Exception('Invalid notification');
        }

        $stm = $conn->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        );
        $stm->bind_param('ii', $id, $adminId);
        $stm->execute();
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
