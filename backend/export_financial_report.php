<?php
require_once 'dbconn.php';
require_once 'financial_report_helpers.php';

$reportType = isset($_GET['report']) ? strtolower(trim((string) $_GET['report'])) : 'income_statement';
$format = isset($_GET['format']) ? strtolower(trim((string) $_GET['format'])) : 'csv';
$autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';

$payload = fr_build_financial_reports_payload($conn, $_GET);
$report = $payload['reports'][$reportType] ?? null;

if ($report === null) {
    http_response_code(404);
    echo 'Invalid report type.';
    exit;
}

$reportTitles = [
    'income_statement' => 'Income Statement',
    'balance_sheet' => 'Balance Sheet',
    'cash_flow' => 'Statement of Cash Flows',
    'inventory' => 'Inventory Report',
];

$reportTitle = $reportTitles[$reportType] ?? 'Financial Report';
$startDate = $payload['filters']['start_date'];
$endDate = $payload['filters']['end_date'];
$generatedAt = date('Y-m-d h:i A');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $reportType . '_' . $startDate . '_to_' . $endDate . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Zoryn', $reportTitle]);
    fputcsv($output, ['Date Range', $startDate . ' to ' . $endDate]);
    fputcsv($output, ['Generated At', $generatedAt]);
    fputcsv($output, []);

    if (!empty($report['summary'])) {
        fputcsv($output, ['Summary']);
        foreach ($report['summary'] as $label => $value) {
            fputcsv($output, [ucwords(str_replace('_', ' ', (string) $label)), is_bool($value) ? ($value ? 'Yes' : 'No') : $value]);
        }
        fputcsv($output, []);
    }

    if (!empty($report['table_rows'])) {
        $headers = array_keys($report['table_rows'][0]);
        fputcsv($output, $headers);
        foreach ($report['table_rows'] as $row) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 32px;
            color: #111827;
            background: #ffffff;
        }
        .header {
            border-bottom: 2px solid #D4AF37;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .brand {
            font-size: 28px;
            font-weight: 700;
            color: #111111;
        }
        .accent {
            color: #D4AF37;
        }
        .meta {
            color: #4B5563;
            font-size: 14px;
            margin-top: 6px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .summary-card {
            border: 1px solid #E5E7EB;
            border-top: 3px solid #D4AF37;
            border-radius: 16px;
            padding: 14px 16px;
        }
        .summary-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6B7280;
            margin-bottom: 8px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #E5E7EB;
            padding: 10px 12px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background: #111827;
            color: #F9FAFB;
        }
        .signature-row {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }
        .signature-box {
            padding-top: 30px;
            border-top: 1px solid #9CA3AF;
            font-size: 13px;
            color: #4B5563;
        }
        @media print {
            body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <section class="header">
        <div class="brand">Zoryn <span class="accent">Financial Reports</span></div>
        <h1><?= htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="meta">Date Range: <?= htmlspecialchars($startDate . ' to ' . $endDate, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="meta">Generated At: <?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') ?></div>
    </section>

    <?php if (!empty($report['summary'])): ?>
        <section class="summary-grid">
            <?php foreach ($report['summary'] as $label => $value): ?>
                <div class="summary-card">
                    <div class="summary-label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $label)), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="summary-value"><?= htmlspecialchars(is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($report['table_rows'])): ?>
        <table>
            <thead>
                <tr>
                    <?php foreach (array_keys($report['table_rows'][0]) as $header): ?>
                        <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $header)), ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['table_rows'] as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <section class="signature-row">
        <div class="signature-box">Prepared By</div>
        <div class="signature-box">Approved By</div>
    </section>

    <?php if ($autoPrint): ?>
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    <?php endif; ?>
</body>
</html>
