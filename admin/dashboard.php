<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_SESSION['2fa_pending']) && $_SESSION['2fa_pending']) {
    header("Location: 2fa.php");
    exit();
}

require_once '../backend/dbconn.php';

$stmt = $conn->prepare("SELECT two_factor_enabled FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

if ((int) $admin['two_factor_enabled'] === 1 && !isset($_SESSION['2fa_verified'])) {
    header("Location: 2fa.php");
    exit();
}

function fetchRows(mysqli $conn, string $sql): array {
    $rows = [];
    $query = $conn->query($sql);
    if ($query instanceof mysqli_result) {
        while ($row = $query->fetch_assoc()) {
            $rows[] = $row;
        }
        $query->free();
    }
    return $rows;
}

function fetchValue(mysqli $conn, string $sql, $default = 0) {
    $query = $conn->query($sql);
    if ($query instanceof mysqli_result) {
        $row = $query->fetch_row();
        $query->free();
        if ($row && array_key_exists(0, $row)) {
            return $row[0];
        }
    }
    return $default;
}

$totalSales = (float) fetchValue($conn, "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status = 'completed'", 0);
$totalOrders = (int) fetchValue($conn, "SELECT COUNT(*) FROM orders WHERE order_status = 'completed'", 0);
$activeProducts = (int) fetchValue($conn, "SELECT COUNT(*) FROM products WHERE status = 'active'", 0);
$totalStaff = (int) fetchValue($conn, "SELECT COUNT(*) FROM users WHERE role IN ('waiter', 'cashier') AND account_status = 'active'", 0);

$salesDailyRows = fetchRows($conn, "
    SELECT DATE(created_at) AS metric_date, COALESCE(SUM(total_amount), 0) AS metric_value
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
      AND order_status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");

$ordersDailyRows = fetchRows($conn, "
    SELECT DATE(created_at) AS metric_date, COUNT(*) AS metric_value
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
      AND order_status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");

$staffDailyRows = fetchRows($conn, "
    SELECT DATE(created_at) AS metric_date, COUNT(*) AS metric_value
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
      AND role IN ('waiter', 'cashier')
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");

$categoryRows = fetchRows($conn, "
    SELECT pc.category_name, COUNT(p.product_id) AS total_products
    FROM product_categories pc
    LEFT JOIN products p
        ON p.category_id = pc.category_id
       AND p.status = 'active'
    WHERE pc.status = 'active'
    GROUP BY pc.category_id, pc.category_name
    HAVING COUNT(p.product_id) > 0
    ORDER BY total_products DESC, pc.category_name ASC
");

$latestRevenue = 0;
if (!empty($salesDailyRows)) {
    $latestRevenue = (float) end($salesDailyRows)['metric_value'];
}

$dashboardPayload = [
    'stats' => [
        'totalSales' => $totalSales,
        'totalOrders' => $totalOrders,
        'activeProducts' => $activeProducts,
        'totalStaff' => $totalStaff,
    ],
    'series' => [
        'salesDaily' => array_map(function ($row) {
            return ['date' => $row['metric_date'], 'value' => (float) $row['metric_value']];
        }, $salesDailyRows),
        'ordersDaily' => array_map(function ($row) {
            return ['date' => $row['metric_date'], 'value' => (int) $row['metric_value']];
        }, $ordersDailyRows),
        'staffDaily' => array_map(function ($row) {
            return ['date' => $row['metric_date'], 'value' => (int) $row['metric_value']];
        }, $staffDailyRows),
        'categoryDistribution' => array_map(function ($row) {
            return ['label' => $row['category_name'], 'value' => (int) $row['total_products']];
        }, $categoryRows),
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/active-page.js"></script>
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
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

        .chart-panel {
            min-height: 340px;
            position: relative;
        }

        .chart-canvas-wrap {
            position: relative;
            height: 280px;
        }

        .chart-skeleton {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 6px 0;
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.35), rgba(0, 0, 0, 0.2));
            transition: opacity 0.25s ease;
            pointer-events: none;
        }

        .chart-skeleton.hidden {
            opacity: 0;
        }

        .skeleton-bar,
        .skeleton-pill {
            background: linear-gradient(90deg, rgba(255,255,255,0.04) 0%, rgba(212,175,55,0.14) 50%, rgba(255,255,255,0.04) 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s linear infinite;
            border-radius: 9999px;
        }

        .skeleton-pill {
            height: 12px;
            width: 35%;
        }

        .skeleton-bar:nth-child(2) { width: 100%; height: 14px; }
        .skeleton-bar:nth-child(3) { width: 82%; height: 14px; }
        .skeleton-bar:nth-child(4) { width: 92%; height: 14px; }
        .skeleton-bar:nth-child(5) { width: 74%; height: 14px; }
        .skeleton-bar:nth-child(6) { width: 88%; height: 14px; }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        @media print {
            .sidebar,
            nav,
            .dashboard-actions,
            .chart-actions {
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
            <div class="relative overflow-hidden rounded-[28px] border border-yellow-500/20 bg-gray-900/80 p-6 shadow-2xl shadow-black/40 backdrop-blur-xl">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.20),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(212,175,55,0.12),transparent_30%)]"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex items-center rounded-full border border-yellow-500/20 bg-yellow-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.32em] text-yellow-300">Executive Analytics</span>
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-white md:text-4xl">Sales Dashboard</h1>
                            <p class="mt-2 max-w-2xl text-sm text-gray-300 md:text-base">Monitor sales trends, revenue, product mix, and platform growth.</p>
                        </div>
                    </div>

                    <div class="dashboard-actions flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                        <div class="inline-flex rounded-2xl border border-yellow-500/20 bg-black/40 p-1 backdrop-blur-md" role="tablist" aria-label="Dashboard time range filters">
                            <button type="button" class="filter-btn rounded-xl px-4 py-2 text-sm font-medium text-gray-300 transition-all duration-300 hover:text-yellow-300" data-range="daily" aria-pressed="true">Daily</button>
                            <button type="button" class="filter-btn rounded-xl px-4 py-2 text-sm font-medium text-gray-300 transition-all duration-300 hover:text-yellow-300" data-range="weekly" aria-pressed="false">Weekly</button>
                            <button type="button" class="filter-btn rounded-xl px-4 py-2 text-sm font-medium text-gray-300 transition-all duration-300 hover:text-yellow-300" data-range="monthly" aria-pressed="false">Monthly</button>
                            <button type="button" class="filter-btn rounded-xl px-4 py-2 text-sm font-medium text-gray-300 transition-all duration-300 hover:text-yellow-300" data-range="yearly" aria-pressed="false">Yearly</button>
                        </div>

                        <button type="button" id="printDashboardBtn" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-yellow-500 to-amber-600 px-4 py-2.5 text-sm font-semibold text-black shadow-lg shadow-yellow-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-yellow-500/30" aria-label="Export dashboard to print view">
                            <i data-lucide="download" class="h-4 w-4"></i>
                            Export View
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="group rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-5 shadow-lg shadow-black/30 backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-yellow-400/35">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-400">Total Sales</p>
                            <h2 id="statTotalSales" class="mt-3 text-3xl font-bold text-white">PHP <?php echo number_format($totalSales, 2); ?></h2>
                            <p class="mt-2 text-xs text-yellow-300/90">Completed order value</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                            <i data-lucide="trending-up" class="h-5 w-5"></i>
                        </div>
                    </div>
                </article>

                <article class="group rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-5 shadow-lg shadow-black/30 backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-yellow-400/35">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-400">Monthly Revenue Pulse</p>
                            <h2 id="statMonthlyRevenue" class="mt-3 text-3xl font-bold text-white">PHP <?php echo number_format($latestRevenue, 2); ?></h2>
                            <p class="mt-2 text-xs text-yellow-300/90">Latest revenue bucket</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                            <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
                        </div>
                    </div>
                </article>

                <article class="group rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-5 shadow-lg shadow-black/30 backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-yellow-400/35">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-400">Active Products</p>
                            <h2 id="statActiveProducts" class="mt-3 text-3xl font-bold text-white"><?php echo number_format($activeProducts); ?></h2>
                            <p class="mt-2 text-xs text-yellow-300/90">Tracked in catalog</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                            <i data-lucide="pie-chart" class="h-5 w-5"></i>
                        </div>
                    </div>
                </article>

                <article class="group rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-5 shadow-lg shadow-black/30 backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-yellow-400/35">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-400">Active Staff</p>
                            <h2 id="statTotalStaff" class="mt-3 text-3xl font-bold text-white"><?php echo number_format($totalStaff); ?></h2>
                            <p class="mt-2 text-xs text-yellow-300/90"><?php echo number_format($totalOrders); ?> completed orders</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                            <i data-lucide="activity" class="h-5 w-5"></i>
                        </div>
                    </div>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="chart-panel rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-6 shadow-lg shadow-black/30 backdrop-blur-xl">
                    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                                    <i data-lucide="trending-up" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Sales Trends</h3>
                                    <p class="text-sm text-gray-400">Line chart of completed sales performance</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Peak: <strong id="salesTrendPeak" class="text-white">PHP 0.00</strong></span>
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Average: <strong id="salesTrendAverage" class="text-white">PHP 0.00</strong></span>
                            </div>
                        </div>
                        <div class="chart-actions flex gap-2">
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="salesTrendChart" data-format="png" aria-label="Export sales trends chart as PNG">PNG</button>
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="salesTrendChart" data-format="csv" aria-label="Export sales trends chart data as CSV">CSV</button>
                        </div>
                    </div>
                    <div class="chart-canvas-wrap">
                        <canvas id="salesTrendChart" aria-label="Sales trends line chart" role="img"></canvas>
                        <div class="chart-skeleton" data-skeleton-for="salesTrendChart">
                            <div class="skeleton-pill"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                        </div>
                    </div>
                </article>

                <article class="chart-panel rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-6 shadow-lg shadow-black/30 backdrop-blur-xl">
                    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                                    <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Revenue Performance</h3>
                                    <p class="text-sm text-gray-400">Bar chart with interactive revenue buckets</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Total: <strong id="revenueTotal" class="text-white">PHP 0.00</strong></span>
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Best Bucket: <strong id="revenueBestBucket" class="text-white">-</strong></span>
                            </div>
                        </div>
                        <div class="chart-actions flex gap-2">
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="monthlyRevenueChart" data-format="png" aria-label="Export monthly revenue chart as PNG">PNG</button>
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="monthlyRevenueChart" data-format="csv" aria-label="Export monthly revenue chart data as CSV">CSV</button>
                        </div>
                    </div>
                    <div class="chart-canvas-wrap">
                        <canvas id="monthlyRevenueChart" aria-label="Monthly revenue bar chart" role="img"></canvas>
                        <div class="chart-skeleton" data-skeleton-for="monthlyRevenueChart">
                            <div class="skeleton-pill"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                        </div>
                    </div>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_1.3fr]">
                <article class="chart-panel rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-6 shadow-lg shadow-black/30 backdrop-blur-xl">
                    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                                    <i data-lucide="pie-chart" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Product Category Distribution</h3>
                                    <p class="text-sm text-gray-400">Doughnut chart of active catalog allocation</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Top Category: <strong id="categoryLeader" class="text-white">-</strong></span>
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Share: <strong id="categoryLeaderShare" class="text-white">0%</strong></span>
                            </div>
                        </div>
                        <div class="chart-actions flex gap-2">
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="categoryDistributionChart" data-format="png" aria-label="Export category distribution chart as PNG">PNG</button>
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="categoryDistributionChart" data-format="csv" aria-label="Export category distribution chart data as CSV">CSV</button>
                        </div>
                    </div>
                    <div class="chart-canvas-wrap">
                        <canvas id="categoryDistributionChart" aria-label="Product category distribution doughnut chart" role="img"></canvas>
                        <div class="chart-skeleton" data-skeleton-for="categoryDistributionChart">
                            <div class="skeleton-pill"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                        </div>
                    </div>
                </article>

                <article class="chart-panel rounded-2xl border border-yellow-500/20 bg-gray-900/75 p-6 shadow-lg shadow-black/30 backdrop-blur-xl">
                    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-yellow-500/30 to-amber-600/20 text-yellow-300 ring-1 ring-yellow-500/20">
                                    <i data-lucide="activity" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Staff and Order Growth</h3>
                                    <p class="text-sm text-gray-400">New waiter &amp; cashier accounts vs completed orders (admin accounts excluded)</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Staff: <strong id="growthStaff" class="text-white">0</strong></span>
                                <span class="rounded-full border border-yellow-500/20 bg-black/30 px-3 py-1 text-gray-300">Orders: <strong id="growthOrders" class="text-white">0</strong></span>
                            </div>
                        </div>
                        <div class="chart-actions flex gap-2">
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="growthAreaChart" data-format="png" aria-label="Export growth chart as PNG">PNG</button>
                            <button type="button" class="export-btn rounded-xl border border-yellow-500/20 bg-black/30 px-3 py-2 text-xs font-medium text-gray-200 transition-all duration-300 hover:border-yellow-400/40 hover:text-yellow-300" data-chart="growthAreaChart" data-format="csv" aria-label="Export growth chart data as CSV">CSV</button>
                        </div>
                    </div>
                    <div class="chart-canvas-wrap">
                        <canvas id="growthAreaChart" aria-label="Staff and order growth area chart" role="img"></canvas>
                        <div class="chart-skeleton" data-skeleton-for="growthAreaChart">
                            <div class="skeleton-pill"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                            <div class="skeleton-bar"></div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <script>
        const dashboardPayload = <?php echo json_encode($dashboardPayload, JSON_UNESCAPED_SLASHES); ?>;

        const palette = {
            gold: '#D4AF37',
            goldSoft: '#C9A227',
            amber: '#F59E0B',
            yellow: '#FFD700',
            text: '#F9FAFB',
            muted: '#9CA3AF',
            grid: 'rgba(255, 255, 255, 0.08)',
            border: 'rgba(212, 175, 55, 0.18)',
            glass: 'rgba(17, 24, 39, 0.75)',
            bg: '#0B0B0F'
        };

        const charts = {};
        let currentRange = 'daily';

        Chart.defaults.color = palette.muted;
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.borderColor = palette.grid;

        const currency = new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            maximumFractionDigits: 2
        });

        const compactNumber = new Intl.NumberFormat('en-US', {
            notation: 'compact',
            maximumFractionDigits: 1
        });

        function parseDate(dateString) {
            return new Date(`${dateString}T00:00:00`);
        }

        function formatShortDate(dateString) {
            return parseDate(dateString).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
        }

        function getWeekKey(dateString) {
            const date = parseDate(dateString);
            const day = (date.getDay() + 6) % 7;
            date.setDate(date.getDate() - day);
            const yearStart = new Date(date.getFullYear(), 0, 1);
            const week = Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
            return `${date.getFullYear()}-W${String(week).padStart(2, '0')}`;
        }

        function getMonthKey(dateString) {
            const date = parseDate(dateString);
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        }

        function getYearKey(dateString) {
            return String(parseDate(dateString).getFullYear());
        }

        function labelFromKey(key, range) {
            if (range === 'weekly') {
                return key.replace('-', ' ');
            }
            if (range === 'monthly') {
                const [year, month] = key.split('-');
                return new Date(Number(year), Number(month) - 1, 1).toLocaleDateString('en-US', {
                    month: 'short',
                    year: 'numeric'
                });
            }
            if (range === 'yearly') {
                return key;
            }
            return formatShortDate(key);
        }

        function sliceSeries(series, limit) {
            if (!Array.isArray(series)) {
                return [];
            }
            return series.slice(Math.max(series.length - limit, 0));
        }

        function aggregateSeries(series, range) {
            const bucketMap = new Map();
            const sliced = range === 'daily'
                ? sliceSeries(series, 7)
                : range === 'weekly'
                    ? sliceSeries(series, 56)
                    : range === 'monthly'
                        ? sliceSeries(series, 365)
                        : series;

            sliced.forEach((item) => {
                const key = range === 'weekly'
                    ? getWeekKey(item.date)
                    : range === 'monthly'
                        ? getMonthKey(item.date)
                        : range === 'yearly'
                            ? getYearKey(item.date)
                            : item.date;

                bucketMap.set(key, (bucketMap.get(key) || 0) + Number(item.value || 0));
            });

            const entries = Array.from(bucketMap.entries()).sort((a, b) => a[0].localeCompare(b[0]));
            return {
                keys: entries.map(([key]) => key),
                labels: entries.map(([key]) => labelFromKey(key, range)),
                values: entries.map(([, value]) => Number(value))
            };
        }

        function summarize(values) {
            const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
            const peak = values.length ? Math.max(...values) : 0;
            const average = values.length ? total / values.length : 0;
            return { total, peak, average };
        }

        function goldGradient(context) {
            const chart = context.chart;
            const {ctx, chartArea} = chart;
            if (!chartArea) {
                return palette.gold;
            }
            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, 'rgba(255, 215, 0, 0.35)');
            gradient.addColorStop(0.45, 'rgba(212, 175, 55, 0.20)');
            gradient.addColorStop(1, 'rgba(212, 175, 55, 0.02)');
            return gradient;
        }

        function barGradient(chart) {
            const {ctx, chartArea} = chart;
            if (!chartArea) {
                return palette.gold;
            }
            const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
            gradient.addColorStop(0, '#8B6A13');
            gradient.addColorStop(0.45, '#C9A227');
            gradient.addColorStop(1, '#FFD700');
            return gradient;
        }

        function commonChartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                animation: {
                    duration: 900,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#E5E7EB',
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 18
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(10, 10, 10, 0.92)',
                        titleColor: '#FFF8DC',
                        bodyColor: '#F3F4F6',
                        borderColor: 'rgba(212, 175, 55, 0.35)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.04)'
                        },
                        ticks: {
                            color: '#9CA3AF'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: palette.grid
                        },
                        ticks: {
                            color: '#9CA3AF'
                        }
                    }
                }
            };
        }

        function hideSkeleton(chartId) {
            const skeleton = document.querySelector(`[data-skeleton-for="${chartId}"]`);
            if (skeleton) {
                skeleton.classList.add('hidden');
                setTimeout(() => skeleton.remove(), 280);
            }
        }

        function updateFilterButtons(activeRange) {
            document.querySelectorAll('.filter-btn').forEach((button) => {
                const isActive = button.dataset.range === activeRange;
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                button.classList.toggle('bg-gradient-to-r', isActive);
                button.classList.toggle('from-yellow-500', isActive);
                button.classList.toggle('to-amber-600', isActive);
                button.classList.toggle('text-black', isActive);
                button.classList.toggle('shadow-lg', isActive);
                button.classList.toggle('shadow-yellow-500/20', isActive);
                button.classList.toggle('text-gray-300', !isActive);
            });
        }

        function renderSalesTrendChart(range) {
            const chartId = 'salesTrendChart';
            const dataset = aggregateSeries(dashboardPayload.series.salesDaily, range);
            const stats = summarize(dataset.values);
            const canvas = document.getElementById(chartId);
            const ctx = canvas.getContext('2d');

            document.getElementById('salesTrendPeak').textContent = currency.format(stats.peak);
            document.getElementById('salesTrendAverage').textContent = currency.format(stats.average);

            if (charts[chartId]) {
                charts[chartId].data.labels = dataset.labels;
                charts[chartId].data.datasets[0].data = dataset.values;
                charts[chartId].update();
                hideSkeleton(chartId);
                return;
            }

            charts[chartId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataset.labels,
                    datasets: [{
                        label: 'Sales Trends',
                        data: dataset.values,
                        borderColor: '#FFD700',
                        backgroundColor: goldGradient,
                        fill: true,
                        borderWidth: 3,
                        tension: 0.38,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderWidth: 2,
                        pointBackgroundColor: '#0B0B0F',
                        pointBorderColor: '#FFD700'
                    }]
                },
                options: {
                    ...commonChartOptions(),
                    plugins: {
                        ...commonChartOptions().plugins,
                        tooltip: {
                            ...commonChartOptions().plugins.tooltip,
                            callbacks: {
                                label: (context) => `Sales: ${currency.format(context.parsed.y || 0)}`
                            }
                        }
                    },
                    scales: {
                        ...commonChartOptions().scales,
                        y: {
                            ...commonChartOptions().scales.y,
                            ticks: {
                                color: '#9CA3AF',
                                callback: (value) => compactNumber.format(value)
                            }
                        }
                    }
                }
            });

            hideSkeleton(chartId);
        }

        function renderRevenueChart(range) {
            const chartId = 'monthlyRevenueChart';
            const dataset = aggregateSeries(dashboardPayload.series.salesDaily, range);
            const stats = summarize(dataset.values);
            const bestValue = stats.peak;
            const bestIndex = dataset.values.findIndex((value) => value === bestValue);
            const canvas = document.getElementById(chartId);
            const ctx = canvas.getContext('2d');

            document.getElementById('revenueTotal').textContent = currency.format(stats.total);
            document.getElementById('revenueBestBucket').textContent = bestIndex >= 0 ? `${dataset.labels[bestIndex]} (${currency.format(bestValue)})` : '-';

            const backgroundColors = dataset.values.map((value) => value === bestValue ? '#FFD700' : '#C9A227');

            if (charts[chartId]) {
                charts[chartId].data.labels = dataset.labels;
                charts[chartId].data.datasets[0].data = dataset.values;
                charts[chartId].data.datasets[0].backgroundColor = backgroundColors;
                charts[chartId].update();
                hideSkeleton(chartId);
                return;
            }

            charts[chartId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dataset.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: dataset.values,
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const peak = Math.max(...(chart.data.datasets[0].data || [0]));
                            return context.raw === peak ? '#FFD700' : barGradient(chart);
                        },
                        borderRadius: 12,
                        borderSkipped: false,
                        hoverBackgroundColor: '#FFD700',
                        maxBarThickness: 40
                    }]
                },
                options: {
                    ...commonChartOptions(),
                    plugins: {
                        ...commonChartOptions().plugins,
                        tooltip: {
                            ...commonChartOptions().plugins.tooltip,
                            callbacks: {
                                label: (context) => `Revenue: ${currency.format(context.parsed.y || 0)}`
                            }
                        }
                    },
                    scales: {
                        ...commonChartOptions().scales,
                        y: {
                            ...commonChartOptions().scales.y,
                            ticks: {
                                color: '#9CA3AF',
                                callback: (value) => compactNumber.format(value)
                            }
                        }
                    }
                }
            });

            hideSkeleton(chartId);
        }

        function renderCategoryChart() {
            const chartId = 'categoryDistributionChart';
            const dataset = dashboardPayload.series.categoryDistribution || [];
            const total = dataset.reduce((sum, item) => sum + Number(item.value || 0), 0);
            const leader = dataset[0] || {label: '-', value: 0};
            const colors = ['#FFD700', '#D4AF37', '#C9A227', '#F59E0B', '#EAB308', '#CA8A04', '#FCD34D', '#B45309', '#92400E', '#78350F'];
            const canvas = document.getElementById(chartId);
            const ctx = canvas.getContext('2d');

            document.getElementById('categoryLeader').textContent = leader.label;
            document.getElementById('categoryLeaderShare').textContent = total > 0 ? `${((leader.value / total) * 100).toFixed(1)}%` : '0%';

            charts[chartId] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: dataset.map((item) => item.label),
                    datasets: [{
                        data: dataset.map((item) => item.value),
                        backgroundColor: colors,
                        borderColor: '#0B0B0F',
                        borderWidth: 3,
                        hoverOffset: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#E5E7EB',
                                usePointStyle: true,
                                padding: 16
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(10, 10, 10, 0.92)',
                            titleColor: '#FFF8DC',
                            bodyColor: '#F3F4F6',
                            borderColor: 'rgba(212, 175, 55, 0.35)',
                            borderWidth: 1,
                            callbacks: {
                                label: (context) => {
                                    const value = context.parsed || 0;
                                    const share = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                    return `${context.label}: ${value} products (${share}%)`;
                                }
                            }
                        }
                    }
                }
            });

            hideSkeleton(chartId);
        }

        function renderGrowthChart(range) {
            const chartId = 'growthAreaChart';
            const staff = aggregateSeries(dashboardPayload.series.staffDaily, range);
            const orders = aggregateSeries(dashboardPayload.series.ordersDaily, range);
            const orderedKeys = Array.from(new Set([...(staff.keys || []), ...(orders.keys || [])])).sort((a, b) => a.localeCompare(b));
            const staffMap = new Map((staff.keys || []).map((key, index) => [key, staff.values[index] || 0]));
            const orderMap = new Map((orders.keys || []).map((key, index) => [key, orders.values[index] || 0]));
            const labels = orderedKeys.map((key) => labelFromKey(key, range));
            const staffValues = orderedKeys.map((key) => staffMap.get(key) || 0);
            const orderValues = orderedKeys.map((key) => orderMap.get(key) || 0);
            const canvas = document.getElementById(chartId);
            const ctx = canvas.getContext('2d');

            document.getElementById('growthStaff').textContent = staffValues.reduce((sum, value) => sum + value, 0).toLocaleString();
            document.getElementById('growthOrders').textContent = orderValues.reduce((sum, value) => sum + value, 0).toLocaleString();

            const userGradient = (context) => {
                const chart = context.chart;
                const chartArea = chart.chartArea;
                if (!chartArea) {
                    return 'rgba(255, 215, 0, 0.18)';
                }
                const gradient = chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, 'rgba(255, 215, 0, 0.30)');
                gradient.addColorStop(1, 'rgba(255, 215, 0, 0.01)');
                return gradient;
            };

            const orderGradient = (context) => {
                const chart = context.chart;
                const chartArea = chart.chartArea;
                if (!chartArea) {
                    return 'rgba(245, 158, 11, 0.16)';
                }
                const gradient = chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, 'rgba(245, 158, 11, 0.28)');
                gradient.addColorStop(1, 'rgba(245, 158, 11, 0.01)');
                return gradient;
            };

            if (charts[chartId]) {
                charts[chartId].data.labels = labels;
                charts[chartId].data.datasets[0].data = staffValues;
                charts[chartId].data.datasets[1].data = orderValues;
                charts[chartId].update();
                hideSkeleton(chartId);
                return;
            }

            charts[chartId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Staff signups',
                            data: staffValues,
                            borderColor: '#FFD700',
                            backgroundColor: userGradient,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'Orders',
                            data: orderValues,
                            borderColor: '#F59E0B',
                            backgroundColor: orderGradient,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    ...commonChartOptions(),
                    plugins: {
                        ...commonChartOptions().plugins,
                        tooltip: {
                            ...commonChartOptions().plugins.tooltip
                        }
                    }
                }
            });

            hideSkeleton(chartId);
        }

        function exportChartAsPNG(chartId) {
            const chart = charts[chartId];
            if (!chart) {
                return;
            }
            const link = document.createElement('a');
            link.href = chart.toBase64Image('image/png', 1);
            link.download = `${chartId}-${currentRange}.png`;
            link.click();
        }

        function exportChartAsCSV(chartId) {
            const chart = charts[chartId];
            if (!chart) {
                return;
            }
            const labels = chart.data.labels || [];
            const datasets = chart.data.datasets || [];
            const header = ['Label', ...datasets.map((dataset) => dataset.label)];
            const rows = labels.map((label, index) => {
                return [label, ...datasets.map((dataset) => dataset.data[index] ?? 0)];
            });
            const csv = [header, ...rows]
                .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
                .join('\n');

            const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${chartId}-${currentRange}.csv`;
            link.click();
            URL.revokeObjectURL(url);
        }

        function renderDashboard(range) {
            currentRange = range;
            updateFilterButtons(range);
            renderSalesTrendChart(range);
            renderRevenueChart(range);
            renderGrowthChart(range);
        }

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            setTimeout(() => {
                renderDashboard('daily');
                renderCategoryChart();
            }, 450);

            document.querySelectorAll('.filter-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    renderDashboard(button.dataset.range);
                });
            });

            document.querySelectorAll('.export-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    const chartId = button.dataset.chart;
                    const format = button.dataset.format;
                    if (format === 'png') {
                        exportChartAsPNG(chartId);
                    } else {
                        exportChartAsCSV(chartId);
                    }
                });
            });

            document.getElementById('printDashboardBtn').addEventListener('click', () => {
                window.print();
            });
        });
    </script>
</body>
</html>
