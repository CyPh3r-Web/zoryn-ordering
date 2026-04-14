<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'dbconn.php';
session_start();

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_notifications':
            getNotifications();
            break;
            
        case 'mark_notification_read':
            markNotificationAsRead();
            break;
            
        case 'delete_notification':
            deleteNotification();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

function getNotifications() {
    global $conn;
    
    try {
        // Get all notifications for the current user
        $stmt = $conn->prepare("
            SELECT n.*, o.order_id, o.order_status, o.payment_status
            FROM notifications n
            LEFT JOIN orders o ON n.order_id = o.order_id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
        ");
        
        $user_id = $_SESSION['user_id'] ?? null;
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'order_id' => $row['order_id'],
                'message' => $row['message'],
                'is_read' => $row['is_read'],
                'created_at' => $row['created_at'],
                'order_status' => $row['order_status'],
                'payment_status' => $row['payment_status']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching notifications: ' . $e->getMessage()
        ]);
    }
}

function markNotificationAsRead() {
    global $conn;
    
    try {
        if (!isset($_POST['notification_id'])) {
            throw new Exception('Notification ID is required');
        }
        
        $notification_id = $_POST['notification_id'];
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Mark the specific notification as read
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error marking notification as read: ' . $e->getMessage()
        ]);
    }
}

function deleteNotification() {
    global $conn;
    
    try {
        if (!isset($_POST['notification_id'])) {
            throw new Exception('Notification ID is required');
        }
        
        $notification_id = $_POST['notification_id'];
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Delete the specific notification
        $stmt = $conn->prepare("
            DELETE FROM notifications 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting notification: ' . $e->getMessage()
        ]);
    }
}
?> 