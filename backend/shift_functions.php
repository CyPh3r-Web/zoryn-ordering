<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'dbconn.php';
require_once 'shift_access.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function shift_is_admin(): bool {
    return !empty($_SESSION['admin_id']);
}

function shift_is_cashier(): bool {
    return !empty($_SESSION['user_id']) && strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier';
}

/**
 * Sum verified cash orders for this cashier within the scheduled shift window
 * (same cash rules as cashier sales_report; excludes opening float).
 */
function zoryn_shift_expected_cash_from_sales(mysqli $conn, int $cashierUserId, string $shiftDate, string $startTime, string $endTime): float {
    try {
        $tz = new DateTimeZone('Asia/Manila');
        $start = (new DateTimeImmutable($shiftDate . ' ' . $startTime, $tz))->format('Y-m-d H:i:s');
        $end = (new DateTimeImmutable($shiftDate . ' ' . $endTime, $tz))->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        return 0.0;
    }
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(o.total_amount), 0) AS totals
        FROM orders o
        WHERE o.order_status = 'completed'
          AND (o.payment_status = 'verified' OR o.payment_status = 'paid')
          AND LOWER(COALESCE(o.payment_type, 'cash')) = 'cash'
          AND o.user_id = ?
          AND o.created_at >= ?
          AND o.created_at <= ?"
    );
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param("iss", $cashierUserId, $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return round((float) ($row['totals'] ?? 0), 2);
}

function assign_shift(): void {
    global $conn;
    if (!shift_is_admin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $userId = (int) ($payload['user_id'] ?? 0);
    $shiftDate = trim((string) ($payload['shift_date'] ?? ''));
    $startTime = trim((string) ($payload['start_time'] ?? ''));
    $endTime = trim((string) ($payload['end_time'] ?? ''));
    $notes = trim((string) ($payload['notes'] ?? ''));
    $createdBy = (int) $_SESSION['admin_id'];

    if ($userId <= 0 || $shiftDate === '' || $startTime === '' || $endTime === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    if ($startTime >= $endTime) {
        echo json_encode(['success' => false, 'message' => 'Shift end time must be later than start time']);
        return;
    }

    $checkUser = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'cashier' AND account_status = 'active'");
    $checkUser->bind_param("i", $userId);
    $checkUser->execute();
    if (!$checkUser->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Selected user is not an active cashier']);
        return;
    }

    $sql = "
        INSERT INTO cashier_shifts (user_id, shift_date, start_time, end_time, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            start_time = VALUES(start_time),
            end_time = VALUES(end_time),
            notes = VALUES(notes),
            status = 'scheduled',
            created_by = VALUES(created_by),
            updated_at = CURRENT_TIMESTAMP
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $userId, $shiftDate, $startTime, $endTime, $notes, $createdBy);
    $ok = $stmt->execute();

    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Shift schedule saved successfully' : 'Failed to save shift schedule'
    ]);
}

function get_admin_shift_list(): void {
    global $conn;
    if (!shift_is_admin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $sql = "
        SELECT
            s.shift_id,
            s.user_id,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.status,
            s.notes,
            s.updated_at,
            COALESCE(u.full_name, u.username, CONCAT('Cashier #', u.user_id)) AS cashier_name,
            c.cash_count_id,
            c.count_1000,
            c.count_500,
            c.count_100,
            c.count_50,
            c.count_20,
            COALESCE(c.count_10, 0) AS count_10,
            COALESCE(c.count_5, 0) AS count_5,
            COALESCE(c.count_1, 0) AS count_1,
            c.total_cash,
            COALESCE(c.expected_cash, 0) AS expected_cash,
            COALESCE(c.cash_variance, 0) AS cash_variance,
            c.recorded_at
        FROM cashier_shifts s
        JOIN users u ON u.user_id = s.user_id
        LEFT JOIN cashier_shift_cash_counts c ON c.shift_id = s.shift_id
        WHERE u.role = 'cashier'
        ORDER BY s.shift_date DESC, s.start_time DESC
        LIMIT 200
    ";
    $result = $conn->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode(['success' => true, 'shifts' => $rows]);
}

function get_cashier_shift_status(): void {
    global $conn;
    if (!shift_is_cashier()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $userId = (int) $_SESSION['user_id'];
    $access = zoryn_get_cashier_shift_access($conn, $userId);
    $response = ['success' => true, 'access' => $access];

    if (!empty($access['active_shift_id'])) {
        $shiftId = (int) $access['active_shift_id'];
        $stmt = $conn->prepare("
            SELECT shift_id, shift_date, start_time, end_time, status
            FROM cashier_shifts
            WHERE shift_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $shiftId);
        $stmt->execute();
        $response['shift'] = $stmt->get_result()->fetch_assoc();
    }

    echo json_encode($response);
}

function submit_shift_cash_count(): void {
    global $conn;
    if (!shift_is_cashier()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $shiftId = (int) ($payload['shift_id'] ?? 0);
    $userId = (int) $_SESSION['user_id'];

    $count1000 = max(0, (int) ($payload['count_1000'] ?? 0));
    $count500 = max(0, (int) ($payload['count_500'] ?? 0));
    $count100 = max(0, (int) ($payload['count_100'] ?? 0));
    $count50 = max(0, (int) ($payload['count_50'] ?? 0));
    $count20 = max(0, (int) ($payload['count_20'] ?? 0));
    $count10 = max(0, (int) ($payload['count_10'] ?? 0));
    $count5 = max(0, (int) ($payload['count_5'] ?? 0));
    $count1 = max(0, (int) ($payload['count_1'] ?? 0));

    if ($shiftId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid shift']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT shift_id, user_id, shift_date, start_time, end_time, status
        FROM cashier_shifts
        WHERE shift_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $shiftId);
    $stmt->execute();
    $shift = $stmt->get_result()->fetch_assoc();

    if (!$shift || (int) $shift['user_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Shift not found']);
        return;
    }

    try {
        $tz = new DateTimeZone('Asia/Manila');
        $now = new DateTimeImmutable('now', $tz);
        $end = new DateTimeImmutable($shift['shift_date'] . ' ' . $shift['end_time'], $tz);
        $graceEnd = $end->modify('+5 minutes');
        if ($now < $end) {
            echo json_encode(['success' => false, 'message' => 'Cash count can only be submitted after your shift end time']);
            return;
        }
        if ($now > $graceEnd) {
            echo json_encode(['success' => false, 'message' => 'Submission window closed. Please contact admin.']);
            return;
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Unable to validate submission window']);
        return;
    }

    $existsStmt = $conn->prepare("SELECT cash_count_id FROM cashier_shift_cash_counts WHERE shift_id = ? LIMIT 1");
    $existsStmt->bind_param("i", $shiftId);
    $existsStmt->execute();
    if ($existsStmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Shift cash count has already been submitted']);
        return;
    }

    $totalCash = ($count1000 * 1000) + ($count500 * 500) + ($count100 * 100) + ($count50 * 50) + ($count20 * 20)
        + ($count10 * 10) + ($count5 * 5) + $count1;

    $expectedCash = zoryn_shift_expected_cash_from_sales(
        $conn,
        $userId,
        $shift['shift_date'],
        $shift['start_time'],
        $shift['end_time']
    );
    $cashVariance = round(((float) $totalCash) - $expectedCash, 2);

    $conn->begin_transaction();
    try {
        $insert = $conn->prepare("
            INSERT INTO cashier_shift_cash_counts
            (shift_id, count_1000, count_500, count_100, count_50, count_20, count_10, count_5, count_1, total_cash, expected_cash, cash_variance, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param(
            "iiiiiiiiidddi",
            $shiftId,
            $count1000,
            $count500,
            $count100,
            $count50,
            $count20,
            $count10,
            $count5,
            $count1,
            $totalCash,
            $expectedCash,
            $cashVariance,
            $userId
        );
        $insert->execute();

        $update = $conn->prepare("UPDATE cashier_shifts SET status = 'closed' WHERE shift_id = ?");
        $update->bind_param("i", $shiftId);
        $update->execute();

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Shift cash count submitted',
            'total_cash' => $totalCash,
            'expected_cash' => $expectedCash,
            'cash_variance' => $cashVariance,
            'cash_short_abs' => $cashVariance < 0 ? round(abs($cashVariance), 2) : 0,
        ]);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to submit cash count']);
    }
}

$action = $_REQUEST['action'] ?? '';
switch ($action) {
    case 'assign_shift':
        assign_shift();
        break;
    case 'get_admin_shift_list':
        get_admin_shift_list();
        break;
    case 'get_cashier_shift_status':
        get_cashier_shift_status();
        break;
    case 'submit_shift_cash_count':
        submit_shift_cash_count();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

