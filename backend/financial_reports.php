<?php
require_once 'dbconn.php';
require_once 'financial_report_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

try {
    $payload = fr_build_financial_reports_payload($conn, $_GET);

    echo json_encode([
        'success' => true,
        'data' => $payload,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
