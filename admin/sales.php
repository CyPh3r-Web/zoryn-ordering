<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Sales Reading</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .sales-container { max-width: 1400px; margin: 0 auto; }
        .sales-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; padding: 22px 26px; flex-wrap: wrap; gap: 16px;
            background: rgba(31, 31, 31, 0.85);
            border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .sales-header h1 { margin: 0; color: #D4AF37; font-size: 1.5rem; font-weight: 700; }
        .sales-filter { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .filter-group, .action-group { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        input, select, button { font-family:'Poppins',sans-serif; }
        input[type="date"], .filter-select {
            padding: 9px 14px; border: 1px solid #2E2E2E; border-radius: 10px;
            background: #1F1F1F; color: #E5E5E5; font-size: 13px; min-width: 150px;
            outline: none; transition: border-color .25s ease, box-shadow .25s ease;
        }
        input[type="date"] { min-width: 160px; color-scheme: dark; }
        input[type="date"]:focus, .filter-select:focus {
            border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.18);
        }
        .action-btn {
            border: none; border-radius: 10px; padding: 9px 14px; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
            font-size: 12px; font-weight: 700; transition: transform .2s ease;
        }
        .action-btn:hover { transform: translateY(-1px); }
        .action-btn.print { background: #2A2A2A; color: #ddd; border: 1px solid #3b3b3b; }
        .action-btn.gold { background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #111; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:18px; }
        .stat-card {
            background: rgba(31,31,31,0.85); border: 1px solid rgba(212,175,55,0.15);
            border-radius: 14px; padding: 14px;
        }
        .stat-card span { display:block; font-size:12px; color:#a6a6a6; margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
        .stat-card strong { color:#F5D76E; font-size:1.1rem; }
        .sales-table-container {
            background: rgba(31, 31, 31, 0.85); border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px; overflow: auto; box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .sales-table { width: 100%; border-collapse: collapse; min-width: 880px; }
        .sales-table thead th {
            background: #161616; color: #F5D76E; font-weight: 600; font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.7px; padding: 14px 18px; text-align: left;
            border-bottom: 1px solid #2E2E2E;
        }
        .sales-table tbody td { padding: 14px 18px; border-bottom: 1px solid #2E2E2E; color: #D1D1D1; font-size: 14px; }
        .sales-table tbody tr:hover td { background: rgba(212,175,55,0.05); color: #fff; }
        .print-only { display: none; }
        @media print {
            body { background: #fff !important; color: #000 !important; }
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
<body>
    <?php include("../navigation/admin-navbar.php"); ?>
    <?php include("../navigation/admin-sidebar.php"); ?>

    <div class="main-content">
        <div class="sales-container">
            <div class="sales-header no-print">
                <h1><i class="fas fa-sack-dollar"></i> All Sales Reading</h1>
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
                        <select id="cashierFilter" class="filter-select"><option value="">All Cashiers</option></select>
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

            <div class="sales-table-container no-print">
                <table class="sales-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Cashier</th>
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
                <h2 class="print-title">All Sales Reading</h2>
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
                            <th>Cashier</th>
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
            const cashierId = document.getElementById('cashierFilter').value;
            if (dateFrom) q.set('date_from', dateFrom);
            if (dateTo) q.set('date_to', dateTo);
            if (paymentType) q.set('payment_type', paymentType);
            if (cashierId) q.set('cashier_id', cashierId);
            return q.toString();
        }
        function currentFilterSummary() {
            const dateFrom = document.getElementById('dateFrom').value || 'Any';
            const dateTo = document.getElementById('dateTo').value || 'Any';
            const paymentType = document.getElementById('paymentType').value || 'All';
            const cashierText = document.getElementById('cashierFilter').selectedOptions[0]?.text || 'All Cashiers';
            return `Date: ${dateFrom} to ${dateTo} | Payment: ${paymentType} | Cashier: ${cashierText}`;
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

                    const cashierSel = document.getElementById('cashierFilter');
                    const selectedCashier = cashierSel.value;
                    cashierSel.innerHTML = '<option value="">All Cashiers</option>';
                    (data.cashiers || []).forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.user_id;
                        opt.textContent = c.cashier_name;
                        if (String(c.user_id) === String(selectedCashier)) opt.selected = true;
                        cashierSel.appendChild(opt);
                    });
                    document.getElementById('printFilterSummary').textContent = currentFilterSummary();

                    const tbody = document.getElementById('salesRows');
                    const printTbody = document.getElementById('printSalesRows');
                    tbody.innerHTML = '';
                    printTbody.innerHTML = '';
                    if (!data.sales.length) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:26px;">No sales records found.</td></tr>';
                        printTbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No sales records found.</td></tr>';
                        return;
                    }
                    data.sales.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row.order_id}</td>
                            <td>${new Date(row.created_at).toLocaleString()}</td>
                            <td>${row.cashier_name || '-'}</td>
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
                            <td>${row.cashier_name || '-'}</td>
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
        loadSales();
    </script>
</body>
</html>
