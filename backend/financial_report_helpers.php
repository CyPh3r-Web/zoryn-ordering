<?php

function fr_table_exists(mysqli $conn, string $tableName): bool
{
    static $cache = [];
    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }
    $escaped = $conn->real_escape_string($tableName);
    $result = $conn->query("SHOW TABLES LIKE '{$escaped}'");
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    $cache[$tableName] = $exists;
    return $exists;
}

function fr_column_exists(mysqli $conn, string $tableName, string $columnName): bool
{
    static $cache = [];
    $key = $tableName . '.' . $columnName;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    if (!fr_table_exists($conn, $tableName)) {
        $cache[$key] = false;
        return false;
    }
    $escapedTable = $conn->real_escape_string($tableName);
    $escapedColumn = $conn->real_escape_string($columnName);
    $result = $conn->query("SHOW COLUMNS FROM `{$escapedTable}` LIKE '{$escapedColumn}'");
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    $cache[$key] = $exists;
    return $exists;
}

function fr_fetch_value(mysqli $conn, string $sql, string $types = '', array $params = [], $default = 0)
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $default;
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) { $stmt->close(); return $default; }
    $result = $stmt->get_result();
    if (!$result instanceof mysqli_result) { $stmt->close(); return $default; }
    $row = $result->fetch_row();
    $result->free();
    $stmt->close();
    return ($row && array_key_exists(0, $row)) ? $row[0] : $default;
}

function fr_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) { $stmt->close(); return []; }
    $result = $stmt->get_result();
    if (!$result instanceof mysqli_result) { $stmt->close(); return []; }
    $rows = [];
    while ($row = $result->fetch_assoc()) { $rows[] = $row; }
    $result->free();
    $stmt->close();
    return $rows;
}

function fr_currency(float $value): float { return round($value, 2); }

function fr_parse_filters(array $input): array
{
    $preset = isset($input['preset']) ? strtolower(trim((string) $input['preset'])) : 'monthly';
    $allowed = ['daily', 'weekly', 'monthly', 'yearly', 'custom'];
    if (!in_array($preset, $allowed, true)) $preset = 'monthly';

    $today = new DateTimeImmutable('today');

    switch ($preset) {
        case 'daily':
            $start = $today; $end = $today; break;
        case 'weekly':
            $start = $today->modify('monday this week');
            $end = $today->modify('sunday this week'); break;
        case 'yearly':
            $start = $today->setDate((int) $today->format('Y'), 1, 1);
            $end = $today->setDate((int) $today->format('Y'), 12, 31); break;
        case 'custom':
            $rawStart = isset($input['start_date']) ? trim((string) $input['start_date']) : '';
            $rawEnd = isset($input['end_date']) ? trim((string) $input['end_date']) : '';
            $start = fr_create_date($rawStart) ?? $today->modify('first day of this month');
            $end = fr_create_date($rawEnd) ?? $today; break;
        case 'monthly': default:
            $start = $today->modify('first day of this month');
            $end = $today->modify('last day of this month'); break;
    }

    if ($start > $end) [$start, $end] = [$end, $start];

    return [
        'preset' => $preset,
        'start_date' => $start->format('Y-m-d'),
        'end_date' => $end->format('Y-m-d'),
        'start_datetime' => $start->format('Y-m-d 00:00:00'),
        'end_datetime' => $end->format('Y-m-d 23:59:59'),
        'category_id' => isset($input['category_id']) && $input['category_id'] !== '' ? (int) $input['category_id'] : null,
        'supplier_id' => isset($input['supplier_id']) && $input['supplier_id'] !== '' ? (int) $input['supplier_id'] : null,
    ];
}

function fr_create_date(string $value): ?DateTimeImmutable
{
    if ($value === '') return null;
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($date instanceof DateTimeImmutable) return $date;
    try { return new DateTimeImmutable($value); } catch (Exception $e) { return null; }
}

function fr_build_bucket_expression(string $preset, string $dateCol = 'o.created_at'): string
{
    switch ($preset) {
        case 'daily': return "DATE({$dateCol})";
        case 'weekly': return "DATE_SUB(DATE({$dateCol}), INTERVAL WEEKDAY({$dateCol}) DAY)";
        case 'yearly': return "DATE_FORMAT({$dateCol}, '%Y-01-01')";
        case 'monthly': case 'custom': default: return "DATE_FORMAT({$dateCol}, '%Y-%m-01')";
    }
}

function fr_format_bucket_label(string $bucket, string $preset): string
{
    $date = fr_create_date($bucket);
    if (!$date) return $bucket;
    switch ($preset) {
        case 'daily': return $date->format('M d, Y');
        case 'weekly': return $date->format('M d') . ' - ' . $date->modify('+6 days')->format('M d');
        case 'yearly': return $date->format('Y');
        case 'monthly': case 'custom': default: return $date->format('M Y');
    }
}

function fr_completed_orders_where(string $alias = 'o'): string
{
    return "{$alias}.order_status = 'completed'";
}

/**
 * Unit conversion aligned with InventoryUpdater (update_inventory.php) for COGS.
 * Returns null if units are incompatible or unknown.
 */
function fr_units_compatible_for_cogs(string $unit1, string $unit2): bool
{
    $weightUnits = ['kg', 'g', 'mg', 'oz', 'lb'];
    $volumeUnits = ['liters', 'l', 'ml', 'cup', 'tbsp', 'tsp', 'fl oz'];
    $countUnits = ['pcs', 'pieces', 'units'];
    $u1 = strtolower(trim($unit1));
    $u2 = strtolower(trim($unit2));
    if ($u1 === '' || $u2 === '') {
        return false;
    }
    if (in_array($u1, $weightUnits, true) && in_array($u2, $weightUnits, true)) {
        return true;
    }
    if (in_array($u1, $volumeUnits, true) && in_array($u2, $volumeUnits, true)) {
        return true;
    }
    if (in_array($u1, $countUnits, true) && in_array($u2, $countUnits, true)) {
        return true;
    }
    return false;
}

function fr_cogs_to_base_unit(float $quantity, string $unit): ?float
{
    $unit = strtolower(trim($unit));
    switch ($unit) {
        case 'kg':
            return $quantity * 1000;
        case 'g':
            return $quantity;
        case 'mg':
            return $quantity / 1000;
        case 'oz':
            return $quantity * 28.3495;
        case 'lb':
            return $quantity * 453.592;
        case 'liters':
        case 'l':
            return $quantity * 1000;
        case 'ml':
            return $quantity;
        case 'cup':
            return $quantity * 236.588;
        case 'tbsp':
            return $quantity * 14.7868;
        case 'tsp':
            return $quantity * 4.92892;
        case 'fl oz':
            return $quantity * 29.5735;
        case 'pcs':
        case 'pieces':
        case 'units':
            return $quantity;
        default:
            return null;
    }
}

function fr_cogs_from_base_unit(float $quantity, string $unit): ?float
{
    $unit = strtolower(trim($unit));
    switch ($unit) {
        case 'kg':
            return $quantity / 1000;
        case 'g':
            return $quantity;
        case 'mg':
            return $quantity * 1000;
        case 'oz':
            return $quantity / 28.3495;
        case 'lb':
            return $quantity / 453.592;
        case 'liters':
        case 'l':
            return $quantity / 1000;
        case 'ml':
            return $quantity;
        case 'cup':
            return $quantity / 236.588;
        case 'tbsp':
            return $quantity / 14.7868;
        case 'tsp':
            return $quantity / 4.92892;
        case 'fl oz':
            return $quantity / 29.5735;
        case 'pcs':
        case 'pieces':
        case 'units':
            return $quantity;
        default:
            return null;
    }
}

function fr_convert_quantity_for_cogs(float $quantity, string $fromUnit, string $toUnit): ?float
{
    $from = strtolower(trim($fromUnit));
    $to = strtolower(trim($toUnit));
    if ($from === '' || $to === '') {
        return null;
    }
    if ($from === $to) {
        return $quantity;
    }
    if (!fr_units_compatible_for_cogs($fromUnit, $toUnit)) {
        return null;
    }
    $base = fr_cogs_to_base_unit($quantity, $from);
    if ($base === null) {
        return null;
    }
    return fr_cogs_from_base_unit($base, $to);
}

/**
 * Sum COGS for rows matching $orderWhereSql (full WHERE clause for joined orders o).
 */
function fr_sum_cogs_from_order_lines(mysqli $conn, string $orderWhereSql, string $paramTypes, array $paramValues): float
{
    $sql = "SELECT oi.quantity AS order_qty, pi.quantity AS recipe_qty, pi.unit AS recipe_unit,
                   COALESCE(i.default_unit_cost, 0) AS default_unit_cost, i.unit AS ingredient_unit
            FROM order_items oi
            INNER JOIN orders o ON o.order_id = oi.order_id
            INNER JOIN product_ingredients pi ON pi.product_id = oi.product_id
            INNER JOIN ingredients i ON i.ingredient_id = pi.ingredient_id
            WHERE {$orderWhereSql}";
    $rows = fr_fetch_all($conn, $sql, $paramTypes, $paramValues);
    $total = 0.0;
    foreach ($rows as $row) {
        $need = (float) $row['order_qty'] * (float) $row['recipe_qty'];
        $converted = fr_convert_quantity_for_cogs($need, (string) $row['recipe_unit'], (string) $row['ingredient_unit']);
        if ($converted === null) {
            continue;
        }
        $total += $converted * (float) $row['default_unit_cost'];
    }
    return fr_currency($total);
}

/**
 * Per-product COGS (converted units) and units sold for reporting tables/charts.
 */
function fr_cogs_breakdown_by_product(mysqli $conn, string $orderWhereSql, string $paramTypes, array $paramValues): array
{
    $detailSql = "SELECT oi.product_id, p.product_name, oi.quantity AS order_qty, pi.quantity AS recipe_qty, pi.unit AS recipe_unit,
                         COALESCE(i.default_unit_cost, 0) AS default_unit_cost, i.unit AS ingredient_unit
                  FROM order_items oi
                  INNER JOIN orders o ON o.order_id = oi.order_id
                  INNER JOIN products p ON p.product_id = oi.product_id
                  INNER JOIN product_ingredients pi ON pi.product_id = oi.product_id
                  INNER JOIN ingredients i ON i.ingredient_id = pi.ingredient_id
                  WHERE {$orderWhereSql}";
    $detailRows = fr_fetch_all($conn, $detailSql, $paramTypes, $paramValues);

    $cogsByProduct = [];
    foreach ($detailRows as $row) {
        $pid = (int) $row['product_id'];
        $need = (float) $row['order_qty'] * (float) $row['recipe_qty'];
        $converted = fr_convert_quantity_for_cogs($need, (string) $row['recipe_unit'], (string) $row['ingredient_unit']);
        if ($converted === null) {
            continue;
        }
        $line = $converted * (float) $row['default_unit_cost'];
        if (!isset($cogsByProduct[$pid])) {
            $cogsByProduct[$pid] = ['product_name' => $row['product_name'], 'cogs_value' => 0.0];
        }
        $cogsByProduct[$pid]['cogs_value'] += $line;
    }

    $unitsSql = "SELECT oi.product_id, SUM(oi.quantity) AS units_sold
                 FROM order_items oi
                 INNER JOIN orders o ON o.order_id = oi.order_id
                 WHERE {$orderWhereSql}
                 GROUP BY oi.product_id";
    $unitsRows = fr_fetch_all($conn, $unitsSql, $paramTypes, $paramValues);
    $unitsMap = [];
    foreach ($unitsRows as $ur) {
        $unitsMap[(int) $ur['product_id']] = (float) $ur['units_sold'];
    }

    $out = [];
    foreach ($cogsByProduct as $pid => $info) {
        $out[] = [
            'product_name' => $info['product_name'],
            'units_sold' => $unitsMap[$pid] ?? 0,
            'cogs_value' => fr_currency($info['cogs_value']),
        ];
    }
    usort($out, static function ($a, $b) {
        return ($b['cogs_value'] <=> $a['cogs_value']);
    });
    return $out;
}

// ====================================================================
// INCOME STATEMENT - derives from orders, order_items, product_ingredients
// ====================================================================
function fr_build_income_statement(mysqli $conn, array $filters): array
{
    $sd = $filters['start_datetime'];
    $ed = $filters['end_datetime'];
    $where = fr_completed_orders_where('o');

    $revenue = (float) fr_fetch_value($conn,
        "SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o WHERE {$where} AND o.created_at BETWEEN ? AND ?",
        'ss', [$sd, $ed], 0);

    $totalOrders = (int) fr_fetch_value($conn,
        "SELECT COUNT(*) FROM orders o WHERE {$where} AND o.created_at BETWEEN ? AND ?",
        'ss', [$sd, $ed], 0);

    $avgOrderValue = $totalOrders > 0 ? fr_currency($revenue / $totalOrders) : 0;

    $cogs = 0.0;
    $cogsRows = [];
    $hasUnitCost = fr_column_exists($conn, 'ingredients', 'default_unit_cost');

    if ($hasUnitCost && fr_table_exists($conn, 'product_ingredients')) {
        $orderWhere = "{$where} AND o.created_at BETWEEN ? AND ?";
        $cogs = fr_sum_cogs_from_order_lines($conn, $orderWhere, 'ss', [$sd, $ed]);
        $cogsRows = fr_cogs_breakdown_by_product($conn, $orderWhere, 'ss', [$sd, $ed]);
    }

    if ($cogs == 0 && $revenue > 0) {
        $cogs = fr_currency($revenue * 0.35);
    }

    $expenseTotal = 0.0;
    $expenseBreakdown = [];
    if (fr_table_exists($conn, 'expenses')) {
        $expenseBreakdown = fr_fetch_all($conn,
            "SELECT category, COALESCE(SUM(amount), 0) AS total_amount
             FROM expenses WHERE expense_date BETWEEN ? AND ? AND status = 'posted'
             GROUP BY category ORDER BY total_amount DESC",
            'ss', [$filters['start_date'], $filters['end_date']]);
        foreach ($expenseBreakdown as $r) $expenseTotal += (float) ($r['total_amount'] ?? 0);
    }

    $grossProfit = fr_currency($revenue - $cogs);
    $netProfit = fr_currency($grossProfit - $expenseTotal);

    $bucket = fr_build_bucket_expression($filters['preset']);
    $trendRows = fr_fetch_all($conn,
        "SELECT {$bucket} AS bucket_date, COALESCE(SUM(o.total_amount), 0) AS revenue
         FROM orders o WHERE {$where} AND o.created_at BETWEEN ? AND ?
         GROUP BY bucket_date ORDER BY bucket_date ASC",
        'ss', [$sd, $ed]);

    $chartLabels = []; $revenueSeries = [];
    foreach ($trendRows as $r) {
        $chartLabels[] = fr_format_bucket_label((string) $r['bucket_date'], $filters['preset']);
        $revenueSeries[] = fr_currency((float) $r['revenue']);
    }

    $expLabels = []; $expSeries = [];
    if (!empty($expenseBreakdown)) {
        foreach ($expenseBreakdown as $r) {
            $expLabels[] = $r['category'] ?: 'Uncategorized';
            $expSeries[] = fr_currency((float) ($r['total_amount'] ?? 0));
        }
    } else {
        $expLabels = ['COGS (est.)', 'Net Profit'];
        $expSeries = [fr_currency($cogs), fr_currency($netProfit)];
    }

    return [
        'summary' => [
            'revenue' => fr_currency($revenue),
            'cogs' => fr_currency($cogs),
            'gross_profit' => fr_currency($grossProfit),
            'operating_expenses' => fr_currency($expenseTotal),
            'net_profit' => $netProfit,
            'total_orders' => $totalOrders,
        ],
        'charts' => [
            'primary' => ['labels' => $chartLabels, 'datasets' => [['label' => 'Revenue', 'data' => $revenueSeries]]],
            'secondary' => ['labels' => $expLabels, 'datasets' => [['label' => 'Breakdown', 'data' => $expSeries]]],
        ],
        'table_rows' => [
            ['line_item' => 'Revenue / Sales', 'amount' => fr_currency($revenue), 'section' => 'Revenue'],
            ['line_item' => 'Cost of Goods Sold' . ($hasUnitCost ? '' : ' (est. 35%)'), 'amount' => fr_currency($cogs), 'section' => 'Cost'],
            ['line_item' => 'Gross Profit', 'amount' => fr_currency($grossProfit), 'section' => 'Subtotal'],
            ['line_item' => 'Operating Expenses', 'amount' => fr_currency($expenseTotal), 'section' => 'Cost'],
            ['line_item' => 'Net Profit', 'amount' => $netProfit, 'section' => 'Net'],
        ],
    ];
}

// ====================================================================
// BALANCE SHEET - derives cash and AR from real order payments
// ====================================================================
function fr_build_balance_sheet(mysqli $conn, array $filters, array $incomeStatement): array
{
    $endDate = $filters['end_date'];
    $endDt = $filters['end_datetime'];

    $cashReceived = (float) fr_fetch_value($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders
         WHERE order_status = 'completed' AND payment_status = 'verified'
         AND created_at <= ?", 's', [$endDt], 0);

    $accountsReceivable = (float) fr_fetch_value($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders
         WHERE order_status = 'completed' AND payment_status IN ('pending','unpaid')
         AND created_at <= ?", 's', [$endDt], 0);

    $inventoryValue = 0.0;
    if (fr_column_exists($conn, 'ingredients', 'default_unit_cost')) {
        $inventoryValue = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(stock * default_unit_cost), 0) FROM ingredients", '', [], 0);
    }

    $expensesPaid = 0.0;
    if (fr_table_exists($conn, 'expenses')) {
        $expensesPaid = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'posted' AND expense_date <= ?",
            's', [$endDate], 0);
    }

    $netCashAfterExpenses = fr_currency($cashReceived - $expensesPaid);

    $ownerCapital = 0.0;
    $ownerWithdrawals = 0.0;
    if (fr_table_exists($conn, 'equity_transactions')) {
        $ownerCapital = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM equity_transactions WHERE equity_type = 'capital' AND transaction_date <= ?",
            's', [$endDate], 0);
        $ownerWithdrawals = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM equity_transactions WHERE equity_type = 'withdrawal' AND transaction_date <= ?",
            's', [$endDate], 0);
    }

    $allTimeRevenue = (float) fr_fetch_value($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status = 'completed' AND created_at <= ?",
        's', [$endDt], 0);

    $allTimeCOGS = fr_currency($allTimeRevenue * 0.35);
    if (fr_column_exists($conn, 'ingredients', 'default_unit_cost') && fr_table_exists($conn, 'product_ingredients')) {
        $computedCOGS = fr_sum_cogs_from_order_lines(
            $conn,
            "o.order_status = 'completed' AND o.created_at <= ?",
            's',
            [$endDt]
        );
        if ($computedCOGS > 0) {
            $allTimeCOGS = fr_currency($computedCOGS);
        }
    }

    $retainedEarnings = fr_currency($allTimeRevenue - $allTimeCOGS - $expensesPaid);

    $totalAssets = fr_currency($netCashAfterExpenses + $accountsReceivable + $inventoryValue);
    $totalLiabilities = 0.0;
    $totalEquity = fr_currency($ownerCapital - $ownerWithdrawals + $retainedEarnings);
    $variance = fr_currency($totalAssets - ($totalLiabilities + $totalEquity));

    $assetRows = [
        ['group' => 'Assets', 'account' => 'Cash (verified payments - expenses)', 'amount' => fr_currency($netCashAfterExpenses)],
        ['group' => 'Assets', 'account' => 'Accounts Receivable (unpaid orders)', 'amount' => fr_currency($accountsReceivable)],
        ['group' => 'Assets', 'account' => 'Inventory', 'amount' => fr_currency($inventoryValue)],
    ];
    $liabilityRows = [
        ['group' => 'Liabilities', 'account' => 'Accounts Payable', 'amount' => 0.0],
    ];
    $equityRows = [
        ['group' => 'Equity', 'account' => 'Owner Capital', 'amount' => fr_currency($ownerCapital)],
        ['group' => 'Equity', 'account' => 'Withdrawals', 'amount' => fr_currency($ownerWithdrawals * -1)],
        ['group' => 'Equity', 'account' => 'Retained Earnings', 'amount' => fr_currency($retainedEarnings)],
    ];

    return [
        'summary' => [
            'total_assets' => fr_currency($totalAssets),
            'total_liabilities' => fr_currency($totalLiabilities),
            'total_equity' => fr_currency($totalEquity),
            'equation_valid' => abs($variance) < 0.02,
            'variance' => $variance,
        ],
        'charts' => [
            'primary' => [
                'labels' => ['Assets', 'Liabilities', 'Equity'],
                'datasets' => [['label' => 'Balance Sheet', 'data' => [fr_currency($totalAssets), fr_currency($totalLiabilities), fr_currency($totalEquity)]]],
            ],
            'secondary' => [
                'labels' => ['Cash', 'Receivables', 'Inventory', 'Owner Capital', 'Retained Earnings'],
                'datasets' => [['label' => 'Breakdown', 'data' => [
                    fr_currency($netCashAfterExpenses), fr_currency($accountsReceivable), fr_currency($inventoryValue),
                    fr_currency($ownerCapital), fr_currency($retainedEarnings)
                ]]],
            ],
        ],
        'table_rows' => array_merge($assetRows, $liabilityRows, $equityRows, [
            ['group' => 'Validation', 'account' => 'Total Assets', 'amount' => fr_currency($totalAssets)],
            ['group' => 'Validation', 'account' => 'Total Liabilities + Equity', 'amount' => fr_currency($totalLiabilities + $totalEquity)],
            ['group' => 'Validation', 'account' => 'Variance', 'amount' => $variance],
        ]),
    ];
}

// ====================================================================
// CASH FLOW - derives from orders payment data (indirect method)
// ====================================================================
function fr_build_cash_flow(mysqli $conn, array $filters, array $incomeStatement): array
{
    $sd = $filters['start_datetime'];
    $ed = $filters['end_datetime'];
    $startDate = $filters['start_date'];
    $endDate = $filters['end_date'];

    $netIncome = (float) ($incomeStatement['summary']['net_profit'] ?? 0);

    $periodCashSales = (float) fr_fetch_value($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders
         WHERE order_status = 'completed' AND payment_status = 'verified'
         AND created_at BETWEEN ? AND ?", 'ss', [$sd, $ed], 0);

    $priorCashSales = (float) fr_fetch_value($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders
         WHERE order_status = 'completed' AND payment_status = 'verified'
         AND created_at < ?", 's', [$sd], 0);

    $periodUnpaid = (float) fr_fetch_value($conn,
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders
         WHERE order_status = 'completed' AND payment_status IN ('pending','unpaid')
         AND created_at BETWEEN ? AND ?", 'ss', [$sd, $ed], 0);

    $changeAR = fr_currency($periodUnpaid);
    $cogs = (float) ($incomeStatement['summary']['cogs'] ?? 0);
    $changeInventory = fr_currency($cogs * -1);

    $expensesPaid = 0.0;
    if (fr_table_exists($conn, 'expenses')) {
        $expensesPaid = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'posted' AND expense_date BETWEEN ? AND ?",
            'ss', [$startDate, $endDate], 0);
    }

    $operatingCash = fr_currency($periodCashSales - $expensesPaid);

    $investingCash = 0.0;
    $financingCash = 0.0;
    if (fr_table_exists($conn, 'cash_transactions')) {
        $investIn = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE direction='inflow' AND activity_type='investing' AND transaction_date BETWEEN ? AND ?",
            'ss', [$startDate, $endDate], 0);
        $investOut = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE direction='outflow' AND activity_type='investing' AND transaction_date BETWEEN ? AND ?",
            'ss', [$startDate, $endDate], 0);
        $finIn = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE direction='inflow' AND activity_type='financing' AND transaction_date BETWEEN ? AND ?",
            'ss', [$startDate, $endDate], 0);
        $finOut = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE direction='outflow' AND activity_type='financing' AND transaction_date BETWEEN ? AND ?",
            'ss', [$startDate, $endDate], 0);
        $investingCash = fr_currency($investIn - $investOut);
        $financingCash = fr_currency($finIn - $finOut);
    }

    $priorExpenses = 0.0;
    if (fr_table_exists($conn, 'expenses')) {
        $priorExpenses = (float) fr_fetch_value($conn,
            "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'posted' AND expense_date < ?",
            's', [$startDate], 0);
    }
    $openingCash = fr_currency($priorCashSales - $priorExpenses);
    $netCashFlow = fr_currency($operatingCash + $investingCash + $financingCash);
    $closingCash = fr_currency($openingCash + $netCashFlow);

    return [
        'summary' => [
            'opening_cash' => $openingCash,
            'operating_cash' => $operatingCash,
            'investing_cash' => $investingCash,
            'financing_cash' => $financingCash,
            'net_cash_flow' => $netCashFlow,
            'closing_cash' => $closingCash,
        ],
        'charts' => [
            'primary' => [
                'labels' => ['Operating', 'Investing', 'Financing'],
                'datasets' => [['label' => 'Cash Flow', 'data' => [$operatingCash, $investingCash, $financingCash]]],
            ],
            'secondary' => [
                'labels' => ['Opening Cash', 'Net Cash Flow', 'Closing Cash'],
                'datasets' => [['label' => 'Cash Position', 'data' => [$openingCash, $netCashFlow, $closingCash]]],
            ],
        ],
        'table_rows' => [
            ['activity' => 'Operating', 'line_item' => 'Cash Received from Sales', 'amount' => fr_currency($periodCashSales)],
            ['activity' => 'Operating', 'line_item' => 'Expenses Paid', 'amount' => fr_currency($expensesPaid * -1)],
            ['activity' => 'Operating', 'line_item' => 'Net Operating Cash Flow', 'amount' => $operatingCash],
            ['activity' => 'Investing', 'line_item' => 'Net Investing Cash Flow', 'amount' => $investingCash],
            ['activity' => 'Financing', 'line_item' => 'Net Financing Cash Flow', 'amount' => $financingCash],
            ['activity' => 'Summary', 'line_item' => 'Opening Cash Balance', 'amount' => $openingCash],
            ['activity' => 'Summary', 'line_item' => 'Net Cash Flow', 'amount' => $netCashFlow],
            ['activity' => 'Summary', 'line_item' => 'Closing Cash Balance', 'amount' => $closingCash],
        ],
    ];
}

// ====================================================================
// INVENTORY REPORT
// ====================================================================
function fr_build_inventory_report(mysqli $conn, array $filters): array
{
    $hasReorder = fr_column_exists($conn, 'ingredients', 'reorder_level');
    $hasCost = fr_column_exists($conn, 'ingredients', 'default_unit_cost');

    $fields = [
        'i.ingredient_id', 'i.ingredient_name', 'i.stock', 'i.unit', 'c.category_name',
        ($hasReorder ? 'COALESCE(i.reorder_level, 0)' : '0') . ' AS reorder_level',
        ($hasCost ? 'COALESCE(i.default_unit_cost, 0)' : '0') . ' AS unit_cost',
    ];

    $where = [];
    $types = '';
    $params = [];
    if ($filters['category_id'] !== null) {
        $where[] = 'i.category_id = ?';
        $types .= 'i';
        $params[] = $filters['category_id'];
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $rows = fr_fetch_all($conn,
        "SELECT " . implode(', ', $fields) . "
         FROM ingredients i LEFT JOIN categories c ON c.category_id = i.category_id
         {$whereSql} ORDER BY i.ingredient_name ASC",
        $types, $params);

    $stockInMap = [];
    $stockOutMap = [];
    if (fr_table_exists($conn, 'inventory_movements')) {
        $mvRows = fr_fetch_all($conn,
            "SELECT ingredient_id,
                    SUM(CASE WHEN movement_type IN ('stock_in','purchase','return_in','adjustment_add') THEN quantity ELSE 0 END) AS stock_in,
                    SUM(CASE WHEN movement_type IN ('stock_out','usage','sale','waste','adjustment_less') THEN quantity ELSE 0 END) AS stock_out
             FROM inventory_movements WHERE movement_date BETWEEN ? AND ? GROUP BY ingredient_id",
            'ss', [$filters['start_date'], $filters['end_date']]);
        foreach ($mvRows as $r) {
            $stockInMap[(int) $r['ingredient_id']] = (float) ($r['stock_in'] ?? 0);
            $stockOutMap[(int) $r['ingredient_id']] = (float) ($r['stock_out'] ?? 0);
        }
    }

    $tableRows = [];
    $totalValue = 0.0;
    $lowCount = 0;
    foreach ($rows as $r) {
        $stock = (float) ($r['stock'] ?? 0);
        $reorder = (float) ($r['reorder_level'] ?? 0);
        $cost = (float) ($r['unit_cost'] ?? 0);
        $val = fr_currency($stock * $cost);
        $id = (int) $r['ingredient_id'];
        $isLow = $reorder > 0 && $stock <= $reorder;
        if ($isLow) $lowCount++;
        $totalValue += $val;

        $tableRows[] = [
            'ingredient_name' => $r['ingredient_name'],
            'category' => $r['category_name'] ?: 'Uncategorized',
            'stock' => $stock,
            'unit' => $r['unit'],
            'stock_in' => fr_currency((float) ($stockInMap[$id] ?? 0)),
            'stock_out' => fr_currency((float) ($stockOutMap[$id] ?? 0)),
            'unit_cost' => fr_currency($cost),
            'value' => $val,
            'reorder_level' => $reorder,
            'is_low_stock' => $isLow,
        ];
    }

    $topSelling = fr_fetch_all($conn,
        "SELECT p.product_name, SUM(oi.quantity) AS units_sold
         FROM order_items oi
         INNER JOIN orders o ON o.order_id = oi.order_id
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE " . fr_completed_orders_where('o') . " AND o.created_at BETWEEN ? AND ?
         GROUP BY p.product_id, p.product_name ORDER BY units_sold DESC LIMIT 8",
        'ss', [$filters['start_datetime'], $filters['end_datetime']]);

    $catDist = [];
    foreach ($tableRows as $r) {
        $catDist[$r['category']] = ($catDist[$r['category']] ?? 0) + (float) $r['value'];
    }

    return [
        'summary' => [
            'total_items' => count($tableRows),
            'inventory_value' => fr_currency($totalValue),
            'low_stock_count' => $lowCount,
            'total_stock_out' => fr_currency(array_sum(array_column($tableRows, 'stock_out'))),
        ],
        'charts' => [
            'primary' => [
                'labels' => array_column($topSelling, 'product_name'),
                'datasets' => [['label' => 'Units Sold', 'data' => array_map('intval', array_column($topSelling, 'units_sold'))]],
            ],
            'secondary' => [
                'labels' => array_keys($catDist),
                'datasets' => [['label' => 'Inventory Value', 'data' => array_values($catDist)]],
            ],
        ],
        'table_rows' => $tableRows,
    ];
}

// ====================================================================
// PURCHASE ORDER REPORT - supplier spend, qty purchased, line detail
// ====================================================================
function fr_build_purchase_order_report(mysqli $conn, array $filters): array
{
    if (!fr_table_exists($conn, 'purchase_orders')) {
        return [
            'summary' => ['total_pos' => 0, 'total_spend' => 0, 'total_ingredients' => 0, 'top_supplier' => '—'],
            'charts'  => ['primary' => ['labels' => [], 'datasets' => []], 'secondary' => ['labels' => [], 'datasets' => []]],
            'table_rows' => [],
        ];
    }

    $startDate = $filters['start_date'];
    $endDate   = $filters['end_date'];

    $whereSupplier = '';
    $types  = 'ss';
    $params = [$startDate, $endDate];
    if ($filters['supplier_id'] !== null) {
        $whereSupplier = ' AND po.supplier_id = ?';
        $types .= 'i';
        $params[] = $filters['supplier_id'];
    }

    $rows = fr_fetch_all($conn,
        "SELECT po.po_id, po.po_number, po.po_date, po.total_amount, po.status,
                COALESCE(s.supplier_name, 'Unknown') AS supplier_name,
                (SELECT COALESCE(SUM(pi.quantity),0) FROM purchase_order_items pi WHERE pi.po_id = po.po_id) AS total_qty,
                (SELECT COUNT(*) FROM purchase_order_items pi WHERE pi.po_id = po.po_id) AS item_count
         FROM purchase_orders po
         LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
         WHERE po.po_date BETWEEN ? AND ? {$whereSupplier}
         ORDER BY po.po_date DESC, po.po_id DESC",
        $types, $params);

    $totalSpend = 0.0; $totalQty = 0.0; $supplierSpend = [];
    foreach ($rows as $r) {
        if ($r['status'] === 'cancelled') continue;
        $totalSpend += (float) $r['total_amount'];
        $totalQty   += (float) $r['total_qty'];
        $supplierSpend[$r['supplier_name']] = ($supplierSpend[$r['supplier_name']] ?? 0) + (float) $r['total_amount'];
    }
    arsort($supplierSpend);
    $topSupplier = !empty($supplierSpend) ? array_key_first($supplierSpend) : '—';

    $tableRows = [];
    foreach ($rows as $r) {
        $tableRows[] = [
            'po_number'   => $r['po_number'],
            'po_date'     => $r['po_date'],
            'supplier'    => $r['supplier_name'],
            'line_items'  => (int) $r['item_count'],
            'total_qty'   => round((float) $r['total_qty'], 2),
            'total_price' => fr_currency((float) $r['total_amount']),
            'status'      => $r['status'],
        ];
    }

    return [
        'summary' => [
            'total_pos'         => count($rows),
            'total_spend'       => fr_currency($totalSpend),
            'total_ingredients' => round($totalQty, 2),
            'top_supplier'      => $topSupplier,
        ],
        'charts' => [
            'primary' => [
                'labels' => array_keys($supplierSpend),
                'datasets' => [['label' => 'Spend by Supplier', 'data' => array_map(fn($v) => fr_currency($v), array_values($supplierSpend))]],
            ],
            'secondary' => [
                'labels' => array_map(fn($r) => $r['po_number'], array_slice($rows, 0, 10)),
                'datasets' => [['label' => 'Recent POs', 'data' => array_map(fn($r) => fr_currency((float) $r['total_amount']), array_slice($rows, 0, 10))]],
            ],
        ],
        'table_rows' => $tableRows,
    ];
}

// ====================================================================
// FILTER OPTIONS
// ====================================================================
function fr_get_filter_options(mysqli $conn): array
{
    $categories = fr_fetch_all($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
    $suppliers  = fr_table_exists($conn, 'suppliers')
        ? fr_fetch_all($conn, "SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'active' ORDER BY supplier_name ASC")
        : [];
    return ['categories' => $categories, 'suppliers' => $suppliers];
}

// ====================================================================
// MAIN PAYLOAD BUILDER
// ====================================================================
function fr_build_financial_reports_payload(mysqli $conn, array $rawFilters): array
{
    $filters = fr_parse_filters($rawFilters);
    $income = fr_build_income_statement($conn, $filters);
    $balance = fr_build_balance_sheet($conn, $filters, $income);
    $cashFlow = fr_build_cash_flow($conn, $filters, $income);
    $inventory = fr_build_inventory_report($conn, $filters);
    $purchase = fr_build_purchase_order_report($conn, $filters);

    return [
        'filters' => $filters,
        'filter_options' => fr_get_filter_options($conn),
        'reports' => [
            'income_statement' => $income,
            'balance_sheet' => $balance,
            'cash_flow' => $cashFlow,
            'inventory' => $inventory,
            'purchase_orders' => $purchase,
        ],
    ];
}
