<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Financial Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/active-page.js"></script>
    <style>
        body {
            background:
                radial-gradient(circle at top, rgba(212, 175, 55, 0.12), transparent 28%),
                linear-gradient(180deg, #050505 0%, #0a0a0a 45%, #111827 100%);
            color: #ffffff;
        }

        .main-content {
            margin-left: 260px;
            padding: 24px;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        .glass-card {
            background: rgba(17, 24, 39, 0.78);
            border: 1px solid rgba(212, 175, 55, 0.18);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(18px);
        }

        .chart-wrap {
            position: relative;
            height: 320px;
        }

        .reports-table-wrap {
            overflow-x: auto;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reports-table thead th {
            position: sticky;
            top: 0;
            background: rgba(10, 10, 10, 0.95);
            color: #f5d76e;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.16);
            text-align: left;
        }

        .reports-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            color: #d1d5db;
            vertical-align: top;
        }

        .reports-table tbody tr:hover td {
            background: rgba(212, 175, 55, 0.05);
            color: #ffffff;
        }

        .metric-pill {
            border: 1px solid rgba(212, 175, 55, 0.2);
            background: rgba(0, 0, 0, 0.28);
            border-radius: 9999px;
        }

        .tab-btn[aria-pressed="true"] {
            background: linear-gradient(135deg, #f4d26b, #c99b2a);
            color: #050505;
            box-shadow: 0 10px 24px rgba(212, 175, 55, 0.2);
        }

        .tab-btn[aria-pressed="false"] {
            color: #d1d5db;
        }

        .status-positive {
            color: #00B894;
        }

        .status-negative {
            color: #FF7675;
        }

        .status-neutral {
            color: #F5D76E;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        @media print {
            nav,
            .sidebar,
            .print-hide {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-black text-white">
    <?php include("../navigation/admin-navbar.php"); ?>
    <?php include("../navigation/admin-sidebar.php"); ?>

    <main class="main-content">
        <section class="mx-auto max-w-7xl space-y-6">
            <div class="glass-card relative overflow-hidden p-6">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.18),transparent_30%),radial-gradient(circle_at_bottom_left,rgba(212,175,55,0.12),transparent_32%)]"></div>
                <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="space-y-4">
                        <span class="inline-flex items-center rounded-full border border-yellow-500/20 bg-yellow-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.32em] text-yellow-300">Finance Command Center</span>
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-white md:text-4xl">Financial Reports</h1>
                            <p class="mt-2 max-w-3xl text-sm text-gray-300 md:text-base">
                                Review profitability, balance position, cash movement, and inventory valuation in a premium reporting workspace.
                            </p>
                        </div>
                    </div>

                    <div class="print-hide grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Preset</span>
                            <select id="presetFilter" class="w-full rounded-2xl border border-yellow-500/20 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-400">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Start Date</span>
                            <input id="startDateFilter" type="date" class="w-full rounded-2xl border border-yellow-500/20 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-400">
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">End Date</span>
                            <input id="endDateFilter" type="date" class="w-full rounded-2xl border border-yellow-500/20 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-400">
                        </label>
                    </div>
                </div>

                <div class="relative mt-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div id="reportTabs" class="print-hide inline-flex flex-wrap gap-2 rounded-2xl border border-yellow-500/20 bg-black/35 p-2">
                        <button class="tab-btn rounded-2xl px-4 py-3 text-sm font-semibold transition-all duration-300" data-report="income_statement" aria-pressed="true">
                            <span class="inline-flex items-center gap-2"><i data-lucide="trending-up" class="h-4 w-4"></i>Income Statement</span>
                        </button>
                        <button class="tab-btn rounded-2xl px-4 py-3 text-sm font-semibold transition-all duration-300" data-report="balance_sheet" aria-pressed="false">
                            <span class="inline-flex items-center gap-2"><i data-lucide="scale" class="h-4 w-4"></i>Balance Sheet</span>
                        </button>
                        <button class="tab-btn rounded-2xl px-4 py-3 text-sm font-semibold transition-all duration-300" data-report="cash_flow" aria-pressed="false">
                            <span class="inline-flex items-center gap-2"><i data-lucide="wallet" class="h-4 w-4"></i>Cash Flow</span>
                        </button>
                        <button class="tab-btn rounded-2xl px-4 py-3 text-sm font-semibold transition-all duration-300" data-report="inventory" aria-pressed="false">
                            <span class="inline-flex items-center gap-2"><i data-lucide="package" class="h-4 w-4"></i>Inventory Report</span>
                        </button>
                    </div>

                    <div class="print-hide flex flex-wrap gap-3">
                        <label class="block min-w-[170px]">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Category</span>
                            <select id="categoryFilter" class="w-full rounded-2xl border border-yellow-500/20 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-400">
                                <option value="">All Categories</option>
                            </select>
                        </label>
                        <label class="block min-w-[170px]">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Supplier</span>
                            <select id="supplierFilter" class="w-full rounded-2xl border border-yellow-500/20 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-400">
                                <option value="">All Suppliers</option>
                            </select>
                        </label>
                        <div class="flex items-end gap-3">
                            <button id="applyFiltersBtn" class="rounded-2xl bg-gradient-to-r from-yellow-500 to-amber-600 px-5 py-3 text-sm font-semibold text-black shadow-lg shadow-yellow-500/20 transition hover:-translate-y-0.5">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="reportMetaCard" class="glass-card p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Selected Report</p>
                        <h2 id="activeReportTitle" class="mt-2 text-2xl font-semibold text-white">Income Statement</h2>
                        <p id="activeReportDescription" class="mt-1 text-sm text-gray-300">Track revenue, cost of goods sold, operating expenses, and net profit.</p>
                    </div>
                    <div class="print-hide flex flex-wrap gap-3">
                        <button id="exportCsvBtn" class="rounded-2xl border border-yellow-500/25 bg-black/35 px-4 py-2.5 text-sm font-semibold text-yellow-200 transition hover:border-yellow-400/45 hover:text-yellow-100">
                            <span class="inline-flex items-center gap-2"><i data-lucide="file-spreadsheet" class="h-4 w-4"></i>Export CSV</span>
                        </button>
                        <button id="exportPdfBtn" class="rounded-2xl border border-yellow-500/25 bg-black/35 px-4 py-2.5 text-sm font-semibold text-yellow-200 transition hover:border-yellow-400/45 hover:text-yellow-100">
                            <span class="inline-flex items-center gap-2"><i data-lucide="file-output" class="h-4 w-4"></i>Export PDF</span>
                        </button>
                        <button id="printBtn" class="rounded-2xl border border-yellow-500/25 bg-black/35 px-4 py-2.5 text-sm font-semibold text-yellow-200 transition hover:border-yellow-400/45 hover:text-yellow-100">
                            <span class="inline-flex items-center gap-2"><i data-lucide="printer" class="h-4 w-4"></i>Print</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="summaryGrid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="glass-card p-6">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <div>
                            <h3 id="primaryChartTitle" class="text-lg font-semibold text-white">Revenue Trend</h3>
                            <p class="text-sm text-gray-400">Primary visual summary for the selected report.</p>
                        </div>
                        <div class="metric-pill px-3 py-1 text-xs text-gray-300">Chart A</div>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="primaryChart"></canvas>
                    </div>
                </article>

                <article class="glass-card p-6">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <div>
                            <h3 id="secondaryChartTitle" class="text-lg font-semibold text-white">Expense Breakdown</h3>
                            <p class="text-sm text-gray-400">Secondary mix and category view.</p>
                        </div>
                        <div class="metric-pill px-3 py-1 text-xs text-gray-300">Chart B</div>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="secondaryChart"></canvas>
                    </div>
            </div>

            <div class="glass-card p-6">
                <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h3 id="reportTableTitle" class="text-xl font-semibold text-white">Statement Lines</h3>
                        <p class="text-sm text-gray-400">Detailed report rows for the active financial view.</p>
                    </div>
                    <div id="equationBadge" class="hidden rounded-full border px-4 py-2 text-sm font-semibold"></div>
                </div>
                <div class="reports-table-wrap">
                    <table class="reports-table">
                        <thead id="reportTableHead">
                        </thead>
                        <tbody id="reportTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();

            const reportMeta = {
                income_statement: {
                    title: 'Income Statement',
                    description: 'Track revenue, cost of goods sold, operating expenses, and net profit.',
                    primaryChart: 'Revenue Trend',
                    secondaryChart: 'Expense Breakdown',
                    tableTitle: 'Statement Lines'
                },
                balance_sheet: {
                    title: 'Balance Sheet',
                    description: 'Review assets, liabilities, equity, and accounting equation health.',
                    primaryChart: 'Assets vs Liabilities vs Equity',
                    secondaryChart: 'Balance Category Mix',
                    tableTitle: 'Balance Sheet Lines'
                },
                cash_flow: {
                    title: 'Statement of Cash Flows',
                    description: 'Follow operating, investing, and financing movements using the indirect method.',
                    primaryChart: 'Cash Flow Activities',
                    secondaryChart: 'Cash Position Summary',
                    tableTitle: 'Cash Flow Lines'
                },
                inventory: {
                    title: 'Inventory Report',
                    description: 'Monitor stock levels, stock movement, valuation, and low stock alerts.',
                    primaryChart: 'Top Selling Products',
                    secondaryChart: 'Inventory Value by Category',
                    tableTitle: 'Inventory Detail'
                }
            };

            const presetFilter = document.getElementById('presetFilter');
            const startDateFilter = document.getElementById('startDateFilter');
            const endDateFilter = document.getElementById('endDateFilter');
            const categoryFilter = document.getElementById('categoryFilter');
            const supplierFilter = document.getElementById('supplierFilter');
            const summaryGrid = document.getElementById('summaryGrid');
            const reportTableHead = document.getElementById('reportTableHead');
            const reportTableBody = document.getElementById('reportTableBody');
            const equationBadge = document.getElementById('equationBadge');

            let activeReport = 'income_statement';
            let payloadCache = null;
            let primaryChart = null;
            let secondaryChart = null;

            function todayString() {
                return new Date().toISOString().split('T')[0];
            }

            function getMonthStart() {
                const date = new Date();
                return new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
            }

            startDateFilter.value = getMonthStart();
            endDateFilter.value = todayString();

            function formatCurrency(value) {
                const amount = Number(value || 0);
                return new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(amount);
            }

            function baseChartOptions() {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 700,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#E5E7EB',
                                usePointStyle: true,
                                padding: 18
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(10,10,10,0.92)',
                            titleColor: '#FFF8DC',
                            bodyColor: '#F3F4F6',
                            borderColor: 'rgba(212,175,55,0.35)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#9CA3AF' },
                            grid: { color: 'rgba(255,255,255,0.04)' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#9CA3AF',
                                callback: function (value) {
                                    return formatCurrency(value);
                                }
                            },
                            grid: { color: 'rgba(255,255,255,0.06)' }
                        }
                    }
                };
            }

            function updatePresetBehavior() {
                const custom = presetFilter.value === 'custom';
                startDateFilter.disabled = !custom;
                endDateFilter.disabled = !custom;
            }

            function buildQueryParams() {
                const params = new URLSearchParams({
                    preset: presetFilter.value,
                    start_date: startDateFilter.value,
                    end_date: endDateFilter.value,
                    category_id: categoryFilter.value,
                    supplier_id: supplierFilter.value
                });
                return params;
            }

            async function loadReports() {
                updatePresetBehavior();

                const response = await fetch(`../backend/financial_reports.php?${buildQueryParams().toString()}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Unable to load financial reports.');
                }

                payloadCache = data.data;
                populateFilters(data.data.filter_options || {});
                renderActiveReport();
            }

            function populateFilters(options) {
                const currentCategory = categoryFilter.value;
                const currentSupplier = supplierFilter.value;

                categoryFilter.innerHTML = '<option value="">All Categories</option>';
                (options.categories || []).forEach((item) => {
                    categoryFilter.insertAdjacentHTML('beforeend', `<option value="${item.category_id}">${item.category_name}</option>`);
                });
                categoryFilter.value = currentCategory;

                supplierFilter.innerHTML = '<option value="">All Suppliers</option>';
                (options.suppliers || []).forEach((item) => {
                    supplierFilter.insertAdjacentHTML('beforeend', `<option value="${item.supplier_id}">${item.supplier_name}</option>`);
                });
                supplierFilter.value = currentSupplier;
            }

            function renderSummaryCards(summary) {
                summaryGrid.innerHTML = '';
                Object.entries(summary || {}).forEach(([key, value]) => {
                    const isBoolean = typeof value === 'boolean';
                    const isNumeric = typeof value === 'number';
                    const isCountMetric = /count|total_orders|total_items/i.test(key);
                    const isQuantityMetric = /stock_out/i.test(key);
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
                    const displayValue = isBoolean
                        ? (value ? 'Yes' : 'No')
                        : (isNumeric ? (isCountMetric ? Number(value).toLocaleString() : (isQuantityMetric ? Number(value).toFixed(2) : formatCurrency(value))) : value);
                    const card = `
                        <article class="glass-card group p-5 transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm text-gray-400">${label}</p>
                                    <h3 class="mt-3 text-2xl font-bold text-white break-words">${displayValue}</h3>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/25 to-amber-600/15 text-yellow-300 ring-1 ring-yellow-500/20">
                                    <i data-lucide="sparkles" class="h-5 w-5"></i>
                                </div>
                            </div>
                        </article>
                    `;
                    summaryGrid.insertAdjacentHTML('beforeend', card);
                });
                lucide.createIcons();
            }

            function createChart(chartInstance, canvasId, type, chartData) {
                const canvas = document.getElementById(canvasId);
                const context = canvas.getContext('2d');
                const palette = ['#FFD700', '#D4AF37', '#C9A227', '#F59E0B', '#EAB308', '#CA8A04', '#B45309'];

                if (chartInstance) {
                    chartInstance.destroy();
                }

                const datasets = (chartData.datasets || []).map((dataset, index) => ({
                    label: dataset.label,
                    data: dataset.data,
                    borderColor: palette[index % palette.length],
                    backgroundColor: type === 'line'
                        ? 'rgba(212, 175, 55, 0.18)'
                        : palette.map((color, itemIndex) => palette[itemIndex % palette.length]),
                    fill: type === 'line',
                    tension: 0.35,
                    borderWidth: 3,
                    borderRadius: 12,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }));

                return new Chart(context, {
                    type,
                    data: {
                        labels: chartData.labels || [],
                        datasets
                    },
                    options: type === 'doughnut'
                        ? {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: '#E5E7EB',
                                        usePointStyle: true,
                                        padding: 16
                                    }
                                }
                            }
                        }
                        : baseChartOptions()
                });
            }

            function getChartType(reportKey, slot) {
                if (reportKey === 'income_statement') return slot === 'primary' ? 'line' : 'doughnut';
                if (reportKey === 'balance_sheet') return slot === 'primary' ? 'bar' : 'doughnut';
                if (reportKey === 'cash_flow') return 'bar';
                return slot === 'primary' ? 'bar' : 'doughnut';
            }

            function renderCharts(reportKey, report) {
                const meta = reportMeta[reportKey];
                document.getElementById('primaryChartTitle').textContent = meta.primaryChart;
                document.getElementById('secondaryChartTitle').textContent = meta.secondaryChart;
                primaryChart = createChart(primaryChart, 'primaryChart', getChartType(reportKey, 'primary'), report.charts.primary || { labels: [], datasets: [] });
                secondaryChart = createChart(secondaryChart, 'secondaryChart', getChartType(reportKey, 'secondary'), report.charts.secondary || { labels: [], datasets: [] });
            }

            function buildHeaders(row) {
                return Object.keys(row).map((key) => `<th>${key.replace(/_/g, ' ')}</th>`).join('');
            }

            function formatCell(value, key) {
                if (typeof value === 'boolean') {
                    return value ? 'Yes' : 'No';
                }
                if (typeof value === 'number' && key !== 'stock' && key !== 'stock_in' && key !== 'stock_out' && key !== 'reorder_level') {
                    return formatCurrency(value);
                }
                if (key === 'is_low_stock') {
                    return value ? '<span class="status-negative font-semibold">Low</span>' : '<span class="status-positive font-semibold">Healthy</span>';
                }
                return value ?? '';
            }

            function renderTable(reportKey, report) {
                const rows = report.table_rows || [];
                reportTableHead.innerHTML = '';
                reportTableBody.innerHTML = '';
                document.getElementById('reportTableTitle').textContent = reportMeta[reportKey].tableTitle;

                if (!rows.length) {
                    reportTableHead.innerHTML = '<tr><th>Notice</th></tr>';
                    reportTableBody.innerHTML = '<tr><td>No data available for the selected range.</td></tr>';
                    return;
                }

                reportTableHead.innerHTML = `<tr>${buildHeaders(rows[0])}</tr>`;
                rows.forEach((row) => {
                    const cells = Object.entries(row).map(([key, value]) => `<td>${formatCell(value, key)}</td>`).join('');
                    reportTableBody.insertAdjacentHTML('beforeend', `<tr>${cells}</tr>`);
                });
            }

            function renderEquationBadge(reportKey, report) {
                if (reportKey !== 'balance_sheet') {
                    equationBadge.classList.add('hidden');
                    equationBadge.textContent = '';
                    equationBadge.className = 'hidden';
                    return;
                }

                const valid = report.summary.equation_valid;
                equationBadge.className = `rounded-full border px-4 py-2 text-sm font-semibold ${valid ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-red-500/30 bg-red-500/10 text-red-300'}`;
                equationBadge.textContent = valid
                    ? 'Accounting equation validated'
                    : `Variance detected: ${formatCurrency(report.summary.variance || 0)}`;
            }

            function renderActiveReport() {
                if (!payloadCache) return;

                const report = payloadCache.reports[activeReport];
                const meta = reportMeta[activeReport];

                document.getElementById('activeReportTitle').textContent = meta.title;
                document.getElementById('activeReportDescription').textContent = meta.description;

                renderSummaryCards(report.summary || {});
                renderCharts(activeReport, report);
                renderTable(activeReport, report);
                renderEquationBadge(activeReport, report);
            }

            function exportReport(format) {
                const params = buildQueryParams();
                params.set('report', activeReport);
                params.set('format', format);
                if (format === 'csv') {
                    window.location.href = `../backend/export_financial_report.php?${params.toString()}`;
                    return;
                }

                if (format === 'pdf') {
                    params.set('format', 'print');
                    params.set('autoprint', '1');
                }

                window.open(`../backend/export_financial_report.php?${params.toString()}`, '_blank');
            }

            document.querySelectorAll('.tab-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    activeReport = this.dataset.report;
                    document.querySelectorAll('.tab-btn').forEach((tab) => tab.setAttribute('aria-pressed', 'false'));
                    this.setAttribute('aria-pressed', 'true');
                    renderActiveReport();
                });
            });

            presetFilter.addEventListener('change', function () {
                const today = todayString();
                if (this.value === 'daily') {
                    startDateFilter.value = today;
                    endDateFilter.value = today;
                } else if (this.value === 'weekly') {
                    const now = new Date();
                    const day = (now.getDay() + 6) % 7;
                    const monday = new Date(now);
                    monday.setDate(now.getDate() - day);
                    startDateFilter.value = monday.toISOString().split('T')[0];
                    endDateFilter.value = today;
                } else if (this.value === 'monthly') {
                    startDateFilter.value = getMonthStart();
                    endDateFilter.value = today;
                } else if (this.value === 'yearly') {
                    const year = new Date().getFullYear();
                    startDateFilter.value = `${year}-01-01`;
                    endDateFilter.value = `${year}-12-31`;
                }
                updatePresetBehavior();
            });

            document.getElementById('applyFiltersBtn').addEventListener('click', async function () {
                try {
                    await loadReports();
                } catch (error) {
                    Swal.fire({
                        title: 'Error',
                        text: error.message,
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            });

            document.getElementById('exportCsvBtn').addEventListener('click', function () {
                exportReport('csv');
            });

            document.getElementById('exportPdfBtn').addEventListener('click', function () {
                exportReport('pdf');
            });

            document.getElementById('printBtn').addEventListener('click', function () {
                exportReport('print');
            });

            loadReports().catch((error) => {
                Swal.fire({
                    title: 'Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#D4AF37'
                });
            });
        });
    </script>
</body>
</html>
