<?php
session_start();
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'cashier')) {
    header("Location: home.php");
    exit();
}
require_once '../backend/dbconn.php';
require_once '../backend/shift_access.php';

$shiftAccess = zoryn_get_cashier_shift_access($conn, (int) $_SESSION['user_id']);
$activeShift = null;
if (!empty($shiftAccess['active_shift_id'])) {
    $activeShiftId = (int) $shiftAccess['active_shift_id'];
    $stmt = $conn->prepare("
        SELECT s.shift_id, s.shift_date, s.start_time, s.end_time, s.status,
            c.cash_count_id, c.total_cash, c.recorded_at,
            COALESCE(c.expected_cash, 0) AS expected_cash,
            COALESCE(c.cash_variance, 0) AS cash_variance
        FROM cashier_shifts s
        LEFT JOIN cashier_shift_cash_counts c ON c.shift_id = s.shift_id
        WHERE s.shift_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $activeShiftId);
    $stmt->execute();
    $activeShift = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - My Sales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        body.zoryn-sales-page {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(212,175,55,0.16), transparent 42%),
                radial-gradient(circle at 88% 0%, rgba(212,175,55,0.10), transparent 38%),
                linear-gradient(145deg, #0D0D0D 0%, #1a1204 38%, #0D0D0D 100%);
            color: #fff;
            min-height: 100vh;
        }
        body.zoryn-sales-page * { box-sizing: border-box; }
        .zoryn-sales-page .main-content { margin-left: 260px; padding: 96px 24px 32px; transition: margin-left 0.3s ease; }
        .zoryn-sales-page .main-content.expanded { margin-left: 0; }
        @media (max-width: 1024px) {
            .zoryn-sales-page .main-content { margin-left: 0; padding: 88px 16px 24px; }
        }
        .zoryn-sales-page .sales-container { max-width: 1400px; margin: 0 auto; }
        .zoryn-sales-page .sales-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; padding: 22px 26px; flex-wrap: wrap; gap: 16px;
            background: rgba(31, 31, 31, 0.85);
            border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .zoryn-sales-page .sales-header h1 { font-size: 1.5rem; font-weight: 700; color: #D4AF37; margin: 0; }
        .zoryn-sales-page .sales-filter { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .zoryn-sales-page .filter-group,
        .zoryn-sales-page .action-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .zoryn-sales-page input[type="date"], .zoryn-sales-page .filter-select {
            padding: 9px 14px; border: 1px solid #2E2E2E; border-radius: 10px;
            background: #1F1F1F; color: #E5E5E5; font-size: 13px; min-width: 150px;
            font-family: 'Poppins', sans-serif; outline: none;
            transition: border-color .25s ease, box-shadow .25s ease;
        }
        .zoryn-sales-page input[type="date"] { min-width: 160px; color-scheme: dark; }
        .zoryn-sales-page input[type="date"]:focus, .zoryn-sales-page .filter-select:focus {
            border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.18);
        }
        .zoryn-sales-page .action-btn {
            border: none; border-radius: 10px; padding: 9px 14px; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
            font-size: 12px; font-weight: 700; font-family: 'Poppins', sans-serif;
            transition: transform .2s ease, opacity .2s ease;
        }
        .zoryn-sales-page .action-btn:hover { transform: translateY(-1px); }
        .zoryn-sales-page .action-btn.print { background: #2A2A2A; color: #ddd; border: 1px solid #3b3b3b; }
        .zoryn-sales-page .action-btn.gold { background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #111; }
        .zoryn-sales-page .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:18px; }
        .zoryn-sales-page .stat-card {
            background: rgba(31,31,31,0.85); border: 1px solid rgba(212,175,55,0.15);
            border-radius: 14px; padding: 14px;
        }
        .zoryn-sales-page .stat-card span { display:block; font-size:12px; color:#a6a6a6; margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
        .zoryn-sales-page .stat-card strong { color:#F5D76E; font-size:1.1rem; }
        .zoryn-sales-page .sales-table-container {
            background: rgba(31, 31, 31, 0.85); border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px; overflow: auto; box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .zoryn-sales-page .sales-table { width: 100%; border-collapse: collapse; min-width: 760px; }
        .zoryn-sales-page .sales-table thead th {
            background: #161616; color: #F5D76E; font-weight: 600; font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.7px; padding: 14px 18px; text-align: left;
            border-bottom: 1px solid #2E2E2E;
        }
        .zoryn-sales-page .sales-table tbody td {
            padding: 14px 18px; border-bottom: 1px solid #2E2E2E; color: #D1D1D1; font-size: 14px;
        }
        .zoryn-sales-page .sales-table tbody tr:hover td { background: rgba(212,175,55,0.05); color: #fff; }
        .print-only { display: none; }
        .shift-cash-modal {
            text-align: left;
            margin-top: 6px;
        }
        .shift-cash-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(212,175,55,0.22);
            background: rgba(18,18,18,0.7);
        }
        .shift-cash-label {
            color: #F5D76E;
            font-weight: 600;
            font-size: 13px;
            min-width: 150px;
        }
        .shift-cash-input {
            width: 120px;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #2E2E2E;
            background: #1F1F1F;
            color: #E5E5E5;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            outline: none;
        }
        .shift-cash-input:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 2px rgba(212,175,55,0.18);
        }
        .shift-cash-total {
            margin-top: 14px;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(212,175,55,0.3);
            background: linear-gradient(135deg, rgba(212,175,55,0.2), rgba(184,146,30,0.2));
            color: #111;
            font-size: 14px;
            font-weight: 600;
        }
        .shift-cash-total strong {
            color: #000;
            font-size: 16px;
            margin-left: 6px;
        }
        @media print {
            body, .zoryn-sales-page { background: #fff !important; color: #000 !important; }
            body * { visibility: hidden !important; }
            .print-area, .print-area * { visibility: visible !important; }
            .print-area {
                position: absolute; left: 0; top: 0; width: 100%;
                padding: 8mm; background: #fff !important; color: #000 !important;
            }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .print-table { width: 100%; border-collapse: collapse; font-size: 12px; }
            .print-table th, .print-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            .print-table th { background: #f3f3f3; }
            .print-summary { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 6px 12px; margin: 10px 0; }
            .print-title { margin: 0 0 6px; font-size: 20px; }
            .print-meta { margin: 0; font-size: 12px; color: #333; }
        }
    </style>
</head>
<body class="zoryn-sales-page">
    <?php include("../navigation/navbar.php"); ?>
    <?php include("../navigation/cashier-sidebar.php"); ?>

    <div class="main-content">
        <div class="sales-container">
            <div class="sales-header no-print">
                <h1><i class="fas fa-chart-line"></i> My Sales Reading</h1>
                <div class="action-group">
                    <button class="action-btn print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                    <button class="action-btn gold" id="exportBtn"><i class="fas fa-file-excel"></i> Export Excel</button>
                </div>
            </div>

            <div class="sales-header no-print" style="padding:14px 16px;">
                <div class="sales-filter">
                    <div class="filter-group">
                        <input type="date" id="dateFrom">
                        <input type="date" id="dateTo">
                        <select id="paymentType" class="filter-select">
                    <option value="">All Payment Types</option>
                    <option value="cash">Cash</option>
                    <option value="online">Online</option>
                    <option value="gcash">GCash</option>
                    <option value="maya">Maya</option>
                    <option value="card">Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
                        <button class="action-btn gold" id="applyFilter"><i class="fas fa-filter"></i> Apply</button>
                    </div>
                </div>
            </div>

            <div class="stats-grid no-print">
                <div class="stat-card"><span>Total Sales</span><strong id="totalSales">P 0.00</strong></div>
                <div class="stat-card"><span>Total Orders</span><strong id="totalOrders">0</strong></div>
                <div class="stat-card"><span>Subtotal</span><strong id="totalSubtotal">P 0.00</strong></div>
                <div class="stat-card"><span>Tax</span><strong id="totalTax">P 0.00</strong></div>
            </div>

            <?php if ($activeShift): ?>
            <div class="sales-header no-print" style="padding:14px 16px;">
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <h3 style="margin:0;color:#D4AF37;">Shift Monitoring</h3>
                    <div style="font-size:13px;color:#ddd;">
                        Shift: <?php echo htmlspecialchars($activeShift['shift_date']); ?> <?php echo htmlspecialchars($activeShift['start_time']); ?> - <?php echo htmlspecialchars($activeShift['end_time']); ?>
                    </div>
                    <div id="cashCountStatus" style="font-size:12px;color:#bbb;line-height:1.5;">
                        <?php if (!empty($activeShift['cash_count_id'])):
                            $submittedTotal = (float) ($activeShift['total_cash'] ?? 0);
                            $submittedExpected = (float) ($activeShift['expected_cash'] ?? 0);
                            $variance = round((float) ($activeShift['cash_variance'] ?? 0), 2);
                            ?>
                            Cash count submitted: P <?php echo number_format($submittedTotal, 2); ?>.
                            <span style="display:block;margin-top:4px;color:#dcdcdc;">
                                <?php if ($variance < -0.005): ?>
                                    <span style="color:#ff8a80;">Cashier short: P <?php echo number_format(abs($variance), 2); ?></span>
                                    <span style="display:block;color:#999;font-size:11px;">Counted total is lower than verified shift cash sales (P <?php echo number_format($submittedExpected, 2); ?>); starting float / misc. cash is not included.</span>
                                <?php elseif ($variance > 0.005): ?>
                                    Over: P <?php echo number_format($variance, 2); ?> vs shift cash sales (P <?php echo number_format($submittedExpected, 2); ?>).
                                <?php else: ?>
                                    Count matches shift cash sales (P <?php echo number_format($submittedExpected, 2); ?>) — no shortage.
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            Submit your denomination counts after your shift ends.
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (empty($activeShift['cash_count_id'])): ?>
                <div class="action-group">
                    <button class="action-btn gold" id="submitCashCountBtn"><i class="fas fa-cash-register"></i> Submit End-Shift Cash Count</button>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="sales-table-container no-print">
                <table class="sales-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Order Type</th>
                            <th>Payment</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="salesRows"></tbody>
                </table>
            </div>

            <div class="print-area print-only">
                <h2 class="print-title">My Sales Reading</h2>
                <p class="print-meta" id="printGeneratedAt"></p>
                <p class="print-meta" id="printFilterSummary"></p>
                <div class="print-summary">
                    <div><strong>Total Sales:</strong> <span id="printTotalSales">P 0.00</span></div>
                    <div><strong>Total Orders:</strong> <span id="printTotalOrders">0</span></div>
                    <div><strong>Subtotal:</strong> <span id="printTotalSubtotal">P 0.00</span></div>
                    <div><strong>Tax:</strong> <span id="printTotalTax">P 0.00</span></div>
                </div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Order Type</th>
                            <th>Payment</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="printSalesRows"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function pesos(v) { return 'P ' + Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function buildQuery() {
            const q = new URLSearchParams({ action: 'get_sales' });
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const paymentType = document.getElementById('paymentType').value;
            if (dateFrom) q.set('date_from', dateFrom);
            if (dateTo) q.set('date_to', dateTo);
            if (paymentType) q.set('payment_type', paymentType);
            return q.toString();
        }
        function currentFilterSummary() {
            const dateFrom = document.getElementById('dateFrom').value || 'Any';
            const dateTo = document.getElementById('dateTo').value || 'Any';
            const paymentType = document.getElementById('paymentType').value || 'All';
            return `Date: ${dateFrom} to ${dateTo} | Payment: ${paymentType}`;
        }
        function loadSales() {
            fetch('../backend/sales_report.php?' + buildQuery())
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    document.getElementById('totalSales').textContent = pesos(data.summary.total_sales);
                    document.getElementById('totalOrders').textContent = data.summary.total_orders || 0;
                    document.getElementById('totalSubtotal').textContent = pesos(data.summary.total_subtotal);
                    document.getElementById('totalTax').textContent = pesos(data.summary.total_tax);
                    document.getElementById('printTotalSales').textContent = pesos(data.summary.total_sales);
                    document.getElementById('printTotalOrders').textContent = data.summary.total_orders || 0;
                    document.getElementById('printTotalSubtotal').textContent = pesos(data.summary.total_subtotal);
                    document.getElementById('printTotalTax').textContent = pesos(data.summary.total_tax);
                    document.getElementById('printGeneratedAt').textContent = 'Generated: ' + new Date().toLocaleString();
                    document.getElementById('printFilterSummary').textContent = currentFilterSummary();

                    const tbody = document.getElementById('salesRows');
                    const printTbody = document.getElementById('printSalesRows');
                    tbody.innerHTML = '';
                    printTbody.innerHTML = '';
                    if (!data.sales.length) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;padding:26px;">No sales records found.</td></tr>';
                        printTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No sales records found.</td></tr>';
                        return;
                    }
                    data.sales.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row.order_id}</td>
                            <td>${new Date(row.created_at).toLocaleString()}</td>
                            <td>${row.customer_name || '-'}</td>
                            <td>${row.order_type || '-'}</td>
                            <td>${row.payment_type || 'cash'}</td>
                            <td>${pesos(row.total_amount)}</td>
                        `;
                        tbody.appendChild(tr);

                        const ptr = document.createElement('tr');
                        ptr.innerHTML = `
                            <td>${row.order_id}</td>
                            <td>${new Date(row.created_at).toLocaleString()}</td>
                            <td>${row.customer_name || '-'}</td>
                            <td>${row.order_type || '-'}</td>
                            <td>${row.payment_type || 'cash'}</td>
                            <td>${pesos(row.total_amount)}</td>
                        `;
                        printTbody.appendChild(ptr);
                    });
                });
        }
        document.getElementById('applyFilter').addEventListener('click', loadSales);
        document.getElementById('exportBtn').addEventListener('click', function() {
            const q = new URLSearchParams(buildQuery());
            q.set('action', 'export_excel');
            window.location.href = '../backend/sales_report.php?' + q.toString();
        });
        const submitCashCountBtn = document.getElementById('submitCashCountBtn');
        if (submitCashCountBtn) {
            submitCashCountBtn.addEventListener('click', function() {
                const shiftId = <?php echo isset($activeShift['shift_id']) ? (int) $activeShift['shift_id'] : 0; ?>;
                Swal.fire({
                    title: 'End-Shift Cash Count',
                    html: `
                        <div class="shift-cash-modal">
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count1000">P 1000 bills</label>
                                <input id="count1000" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count500">P 500 bills</label>
                                <input id="count500" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count100">P 100 bills</label>
                                <input id="count100" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count50">P 50 bills</label>
                                <input id="count50" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count20">P 20 bills</label>
                                <input id="count20" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count10">P 10 coins</label>
                                <input id="count10" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count5">P 5 coins</label>
                                <input id="count5" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-row">
                                <label class="shift-cash-label" for="count1">P 1 coins</label>
                                <input id="count1" class="shift-cash-input" type="number" min="0" step="1" value="0">
                            </div>
                            <div class="shift-cash-total">
                                Total Cash: <strong id="cashCountLiveTotal">P 0.00</strong>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    confirmButtonColor: '#D4AF37',
                    didOpen: () => {
                        const ids = ['count1000', 'count500', 'count100', 'count50', 'count20', 'count10', 'count5', 'count1'];
                        const computeTotal = () => {
                            const c1000 = Number(document.getElementById('count1000').value || 0);
                            const c500 = Number(document.getElementById('count500').value || 0);
                            const c100 = Number(document.getElementById('count100').value || 0);
                            const c50 = Number(document.getElementById('count50').value || 0);
                            const c20 = Number(document.getElementById('count20').value || 0);
                            const c10 = Number(document.getElementById('count10').value || 0);
                            const c5 = Number(document.getElementById('count5').value || 0);
                            const c1 = Number(document.getElementById('count1').value || 0);
                            const total = (c1000 * 1000) + (c500 * 500) + (c100 * 100) + (c50 * 50) + (c20 * 20)
                                + (c10 * 10) + (c5 * 5) + c1;
                            document.getElementById('cashCountLiveTotal').textContent = 'P ' + total.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        };
                        ids.forEach(id => {
                            const input = document.getElementById(id);
                            input.addEventListener('input', computeTotal);
                        });
                        computeTotal();
                    },
                    preConfirm: () => ({
                        shift_id: shiftId,
                        count_1000: Number(document.getElementById('count1000').value || 0),
                        count_500: Number(document.getElementById('count500').value || 0),
                        count_100: Number(document.getElementById('count100').value || 0),
                        count_50: Number(document.getElementById('count50').value || 0),
                        count_20: Number(document.getElementById('count20').value || 0),
                        count_10: Number(document.getElementById('count10').value || 0),
                        count_5: Number(document.getElementById('count5').value || 0),
                        count_1: Number(document.getElementById('count1').value || 0)
                    })
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch('../backend/shift_functions.php?action=submit_shift_cash_count', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(result.value)
                    })
                    .then(r => r.json())
                    .then(data => {
                        let body = data.message || 'Request failed';
                        if (data.success) {
                            const tot = pesos(data.total_cash);
                            const exp = pesos(data.expected_cash);
                            const v = Number(data.cash_variance || 0);
                            let varianceLine = '';
                            if (v < -0.005) {
                                varianceLine = 'Cashier short: ' + pesos(Math.abs(v)) + ' (counted vs shift cash sales ' + exp + ').';
                            } else if (v > 0.005) {
                                varianceLine = 'Over by ' + pesos(v) + ' vs shift cash sales ' + exp + '.';
                            } else {
                                varianceLine = 'No shortage vs shift cash sales ' + exp + '.';
                            }
                            body = 'Counted total: ' + tot + '\n\n' + varianceLine;
                        }
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Submitted' : 'Cannot Submit',
                            text: body,
                            confirmButtonColor: '#D4AF37'
                        }).then(() => {
                            if (data.success) window.location.reload();
                        });
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to submit cash count', confirmButtonColor: '#D4AF37' });
                    });
                });
            });
        }
        loadSales();
    </script>
</body>
</html>
