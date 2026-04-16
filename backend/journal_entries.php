<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once 'dbconn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';

            if ($action === 'balance_sheet') {
                echo json_encode(['success' => true, 'data' => getBalanceSheetData($conn)]);
            } elseif ($action === 'accounts') {
                echo json_encode(['success' => true, 'data' => getAccounts($conn)]);
            } elseif ($action === 'view' && isset($_GET['id'])) {
                echo json_encode(['success' => true, 'data' => getJournalEntry($conn, (int)$_GET['id'])]);
            } else {
                echo json_encode(['success' => true, 'data' => listJournalEntries($conn, $_GET)]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }

            $action = $input['action'] ?? 'create';

            if ($action === 'create') {
                echo json_encode(createJournalEntry($conn, $input, (int)$_SESSION['admin_id']));
            } elseif ($action === 'void' && isset($input['entry_id'])) {
                echo json_encode(voidJournalEntry($conn, (int)$input['entry_id']));
            } else {
                throw new Exception('Unknown action');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getAccounts(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT account_id, account_code, account_name, account_type, account_subtype
         FROM finance_accounts WHERE status = 'active' ORDER BY account_code ASC"
    );
    if (!$result) return [];
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    $result->free();
    return $accounts;
}

function listJournalEntries(mysqli $conn, array $params): array
{
    $page = max(1, (int)($params['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $search = trim($params['search'] ?? '');
    $statusFilter = trim($params['status'] ?? '');

    $whereParts = [];
    $types = '';
    $values = [];

    if ($search !== '') {
        $whereParts[] = "(je.memo LIKE ? OR je.entry_id = ? OR je.reference_type LIKE ?)";
        $types .= 'sis';
        $values[] = "%{$search}%";
        $values[] = (int)$search;
        $values[] = "%{$search}%";
    }

    if ($statusFilter !== '' && in_array($statusFilter, ['draft', 'posted', 'void'])) {
        $whereParts[] = "je.status = ?";
        $types .= 's';
        $values[] = $statusFilter;
    }

    $whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';
    $total = 0;

    $countSql = "SELECT COUNT(*) AS cnt FROM finance_journal_entries je {$whereSql}";
    $countStmt = $conn->prepare($countSql);
    if ($countStmt) {
        if ($types !== '' && !empty($values)) {
            $countStmt->bind_param($types, ...$values);
        }
        $countStmt->execute();
        $countRes = $countStmt->get_result();
        if ($countRes) {
            $row = $countRes->fetch_assoc();
            $total = (int)($row['cnt'] ?? 0);
            $countRes->free();
        }
        $countStmt->close();
    }

    $sql = "SELECT je.entry_id, je.entry_date, je.reference_type, je.reference_id,
                   je.memo, je.status, je.created_at,
                   u.full_name AS created_by_name,
                   COALESCE(SUM(jl.debit_amount), 0) AS total_debit,
                   COALESCE(SUM(jl.credit_amount), 0) AS total_credit
            FROM finance_journal_entries je
            LEFT JOIN finance_journal_lines jl ON jl.entry_id = je.entry_id
            LEFT JOIN users u ON u.user_id = je.created_by
            {$whereSql}
            GROUP BY je.entry_id, je.entry_date, je.reference_type, je.reference_id,
                     je.memo, je.status, je.created_at, u.full_name
            ORDER BY je.entry_date DESC, je.entry_id DESC
            LIMIT {$limit} OFFSET {$offset}";

    $entries = [];
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($types !== '' && !empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
            $result->free();
        }
        $stmt->close();
    }

    return [
        'entries' => $entries,
        'total' => $total,
        'page' => $page,
        'pages' => max(1, (int)ceil($total / $limit)),
    ];
}

function getJournalEntry(mysqli $conn, int $entryId): ?array
{
    $stmt = $conn->prepare(
        "SELECT je.entry_id, je.entry_date, je.reference_type, je.reference_id,
                je.memo, je.status, je.created_by, je.created_at, je.updated_at,
                u.full_name AS created_by_name
         FROM finance_journal_entries je
         LEFT JOIN users u ON u.user_id = je.created_by
         WHERE je.entry_id = ?"
    );
    if (!$stmt) return null;
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $entry = $result ? $result->fetch_assoc() : null;
    if ($result) $result->free();
    $stmt->close();

    if (!$entry) return null;

    $lineStmt = $conn->prepare(
        "SELECT jl.line_id, jl.entry_id, jl.account_id, jl.debit_amount, jl.credit_amount,
                jl.description, fa.account_code, fa.account_name, fa.account_type
         FROM finance_journal_lines jl
         INNER JOIN finance_accounts fa ON fa.account_id = jl.account_id
         WHERE jl.entry_id = ? ORDER BY jl.line_id ASC"
    );
    $lines = [];
    if ($lineStmt) {
        $lineStmt->bind_param('i', $entryId);
        $lineStmt->execute();
        $lineResult = $lineStmt->get_result();
        if ($lineResult) {
            while ($row = $lineResult->fetch_assoc()) {
                $lines[] = $row;
            }
            $lineResult->free();
        }
        $lineStmt->close();
    }

    $entry['lines'] = $lines;
    return $entry;
}

function createJournalEntry(mysqli $conn, array $input, int $createdBy): array
{
    $entryDate = trim($input['entry_date'] ?? '');
    $memo = trim($input['memo'] ?? '');
    $referenceType = trim($input['reference_type'] ?? '');
    $status = trim($input['status'] ?? 'posted');
    $lines = $input['lines'] ?? [];

    if ($entryDate === '') {
        throw new Exception('Entry date is required');
    }
    if (empty($lines) || count($lines) < 2) {
        throw new Exception('At least two journal lines are required (debit and credit)');
    }
    if (!in_array($status, ['draft', 'posted'])) {
        $status = 'posted';
    }

    $totalDebit = 0;
    $totalCredit = 0;
    foreach ($lines as &$line) {
        $line['debit'] = round((float)($line['debit'] ?? 0), 2);
        $line['credit'] = round((float)($line['credit'] ?? 0), 2);
        $totalDebit += $line['debit'];
        $totalCredit += $line['credit'];

        if (empty($line['account_id'])) {
            throw new Exception('Each line must have an account selected');
        }
        if ($line['debit'] == 0 && $line['credit'] == 0) {
            throw new Exception('Each line must have a debit or credit amount');
        }
        if ($line['debit'] > 0 && $line['credit'] > 0) {
            throw new Exception('A line cannot have both debit and credit');
        }
    }
    unset($line);

    if (abs($totalDebit - $totalCredit) > 0.009) {
        throw new Exception(
            "Total debits ({$totalDebit}) must equal total credits ({$totalCredit})"
        );
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "INSERT INTO finance_journal_entries (entry_date, reference_type, memo, status, created_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssi', $entryDate, $referenceType, $memo, $status, $createdBy);
        $stmt->execute();
        $entryId = $conn->insert_id;
        $stmt->close();

        $lineStmt = $conn->prepare(
            "INSERT INTO finance_journal_lines (entry_id, account_id, debit_amount, credit_amount, description)
             VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($lines as $line) {
            $accountId = (int)$line['account_id'];
            $debit = $line['debit'];
            $credit = $line['credit'];
            $desc = trim($line['description'] ?? '');
            $lineStmt->bind_param('iidds', $entryId, $accountId, $debit, $credit, $desc);
            $lineStmt->execute();
        }
        $lineStmt->close();

        $conn->commit();

        return [
            'success' => true,
            'message' => 'Journal entry created successfully',
            'entry_id' => $entryId,
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function voidJournalEntry(mysqli $conn, int $entryId): array
{
    $stmt = $conn->prepare(
        "UPDATE finance_journal_entries SET status = 'void', updated_at = NOW() WHERE entry_id = ? AND status != 'void'"
    );
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        throw new Exception('Entry not found or already voided');
    }

    return ['success' => true, 'message' => 'Journal entry voided successfully'];
}

function safeQuery(mysqli $conn, string $sql)
{
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    return $result;
}

function safeValue(mysqli $conn, string $sql, $default = 0)
{
    $result = $conn->query($sql);
    if (!$result) return $default;
    $row = $result->fetch_row();
    $result->free();
    return ($row && isset($row[0])) ? $row[0] : $default;
}

function getBalanceSheetData(mysqli $conn): array
{
    $accounts = [];
    $result = $conn->query(
        "SELECT fa.account_id, fa.account_code, fa.account_name, fa.account_type,
                fa.account_subtype, fa.is_system,
                COALESCE(SUM(jl.debit_amount), 0) AS total_debit,
                COALESCE(SUM(jl.credit_amount), 0) AS total_credit
         FROM finance_accounts fa
         LEFT JOIN finance_journal_lines jl ON jl.account_id = fa.account_id
            AND jl.entry_id IN (SELECT entry_id FROM finance_journal_entries WHERE status = 'posted')
         WHERE fa.status = 'active'
         GROUP BY fa.account_id, fa.account_code, fa.account_name, fa.account_type,
                  fa.account_subtype, fa.is_system
         ORDER BY fa.account_code ASC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $debit = (float)$row['total_debit'];
            $credit = (float)$row['total_credit'];
            $type = $row['account_type'];

            if (in_array($type, ['asset', 'expense'])) {
                $row['balance'] = round($debit - $credit, 2);
            } else {
                $row['balance'] = round($credit - $debit, 2);
            }

            $accounts[] = $row;
        }
        $result->free();
    }

    $orderCash = (float)safeValue($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders
         WHERE order_status = 'completed' AND payment_status = 'verified'");

    $orderAR = (float)safeValue($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders
         WHERE order_status = 'completed' AND payment_status IN ('pending','unpaid')");

    $orderRevenue = (float)safeValue($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status = 'completed'");

    $inventoryValue = (float)safeValue($conn,
        "SELECT COALESCE(SUM(stock * default_unit_cost), 0) FROM ingredients");

    $expensesPaid = 0.0;
    $tableCheck = $conn->query("SHOW TABLES LIKE 'expenses'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $expensesPaid = (float)safeValue($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'posted'");
    }

    $assets = [
        'Cash on Hand' => round($orderCash - $expensesPaid, 2),
        'Accounts Receivable' => round($orderAR, 2),
        'Inventory' => round($inventoryValue, 2),
    ];
    $liabilities = [];
    $equity = [];

    foreach ($accounts as $acc) {
        $bal = (float)$acc['balance'];
        $isSystem = (int)($acc['is_system'] ?? 0);
        if ($bal == 0 && $isSystem) continue;

        switch ($acc['account_type']) {
            case 'asset':
                $assets[$acc['account_name'] . ' (Journal)'] = $bal;
                break;
            case 'liability':
                $liabilities[$acc['account_name']] = $bal;
                break;
            case 'equity':
                $equity[$acc['account_name']] = $bal;
                break;
        }
    }

    if (empty($liabilities)) {
        $liabilities['Accounts Payable'] = 0;
    }

    $retainedFromOrders = round($orderRevenue - ($orderRevenue * 0.35) - $expensesPaid, 2);
    if (!isset($equity['Retained Earnings']) || $equity['Retained Earnings'] == 0) {
        $equity['Retained Earnings (from operations)'] = $retainedFromOrders;
    }

    $totalAssets = round(array_sum($assets), 2);
    $totalLiabilities = round(array_sum($liabilities), 2);
    $totalEquity = round(array_sum($equity), 2);
    $variance = round($totalAssets - ($totalLiabilities + $totalEquity), 2);

    return [
        'assets' => $assets,
        'liabilities' => $liabilities,
        'equity' => $equity,
        'totals' => [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_equity' => round($totalLiabilities + $totalEquity, 2),
            'variance' => $variance,
            'balanced' => abs($variance) < 0.02,
        ],
        'journal_accounts' => $accounts,
    ];
}
