<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'dbconn.php';
require_once 'shift_access.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminSalesUser(): bool {
    return !empty($_SESSION['admin_id']);
}

function isCashierSalesUser(): bool {
    return !empty($_SESSION['user_id']) && strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier';
}

function canViewSales(): bool {
    global $conn;
    if (!empty($_SESSION['user_id']) && strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier') {
        $access = zoryn_get_cashier_shift_access($conn, (int) $_SESSION['user_id']);
        if (!$access['is_within_shift'] && !$access['is_grace_period']) {
            return false;
        }
    }
    return isAdminSalesUser() || isCashierSalesUser();
}

function buildSalesWhereClause(array &$params, string &$types): string {
    $where = "WHERE o.order_status = 'completed' AND (o.payment_status = 'verified' OR o.payment_status = 'paid')";

    $dateFrom = trim((string) ($_REQUEST['date_from'] ?? ''));
    $dateTo = trim((string) ($_REQUEST['date_to'] ?? ''));
    $paymentType = trim((string) ($_REQUEST['payment_type'] ?? ''));

    if ($dateFrom !== '') {
        $where .= " AND DATE(o.created_at) >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }
    if ($dateTo !== '') {
        $where .= " AND DATE(o.created_at) <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }
    if ($paymentType !== '') {
        $where .= " AND LOWER(COALESCE(o.payment_type, 'cash')) = ?";
        $params[] = strtolower($paymentType);
        $types .= "s";
    }

    if (isCashierSalesUser()) {
        $where .= " AND o.user_id = ?";
        $params[] = (int) $_SESSION['user_id'];
        $types .= "i";
    } else {
        $cashierId = (int) ($_REQUEST['cashier_id'] ?? 0);
        if ($cashierId > 0) {
            $where .= " AND o.user_id = ?";
            $params[] = $cashierId;
            $types .= "i";
        }
    }

    return $where;
}

function fetchSalesRows(mysqli $conn): array {
    $params = [];
    $types = '';
    $where = buildSalesWhereClause($params, $types);

    $sql = "
        SELECT
            o.order_id,
            o.customer_name,
            o.order_type,
            o.payment_type,
            o.total_amount,
            o.subtotal,
            o.tax_amount,
            o.created_at,
            o.user_id AS cashier_id,
            COALESCE(u.full_name, u.username, CONCAT('Cashier #', o.user_id)) AS cashier_name
        FROM orders o
        LEFT JOIN users u ON u.user_id = o.user_id
        $where
        ORDER BY o.created_at DESC, o.order_id DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare sales query.');
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function getSales(): void {
    global $conn;
    header('Content-Type: application/json');

    if (!canViewSales()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    try {
        $rows = fetchSalesRows($conn);
        $summary = [
            'total_sales' => 0.0,
            'total_subtotal' => 0.0,
            'total_tax' => 0.0,
            'total_orders' => count($rows)
        ];
        foreach ($rows as $row) {
            $summary['total_sales'] += (float) ($row['total_amount'] ?? 0);
            $summary['total_subtotal'] += (float) ($row['subtotal'] ?? 0);
            $summary['total_tax'] += (float) ($row['tax_amount'] ?? 0);
        }

        $cashiers = [];
        if (isAdminSalesUser()) {
            $cashierQuery = $conn->query("
                SELECT user_id, COALESCE(full_name, username, CONCAT('Cashier #', user_id)) AS cashier_name
                FROM users
                WHERE role = 'cashier' AND account_status = 'active'
                ORDER BY cashier_name ASC
            ");
            while ($cashier = $cashierQuery->fetch_assoc()) {
                $cashiers[] = $cashier;
            }
        }

        echo json_encode([
            'success' => true,
            'sales' => $rows,
            'summary' => $summary,
            'cashiers' => $cashiers
        ]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function exportSalesExcel(): void {
    global $conn;

    if (!canViewSales()) {
        http_response_code(403);
        echo 'Unauthorized';
        return;
    }

    try {
        $rows = fetchSalesRows($conn);
        $filename = 'sales-report-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', 'Date', 'Cashier', 'Customer', 'Order Type', 'Payment Type', 'Subtotal', 'Tax', 'Total']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['order_id'],
                $row['created_at'],
                $row['cashier_name'],
                $row['customer_name'],
                $row['order_type'],
                $row['payment_type'] ?: 'cash',
                number_format((float) $row['subtotal'], 2, '.', ''),
                number_format((float) $row['tax_amount'], 2, '.', ''),
                number_format((float) $row['total_amount'], 2, '.', '')
            ]);
        }

        fclose($output);
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Export failed: ' . $e->getMessage();
    }
}

$action = $_REQUEST['action'] ?? 'get_sales';
if ($action === 'export_excel') {
    exportSalesExcel();
    exit;
}
getSales();
?>
