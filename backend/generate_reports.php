<?php
// Database connection
require_once 'dbconn.php';

// Set headers to handle AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get action from POST request
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Handle different actions
    switch ($action) {
        case 'get_report_data':
            getReportData($_POST);
            break;
            
        case 'export_report':
            exportReport($_POST);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

/**
 * Get report data based on the type and date filter
 * 
 * @param array $params Request parameters
 */
function getReportData($params) {
    global $conn;
    
    // Get parameters
    $reportType = isset($params['report_type']) ? $params['report_type'] : 'sales';
    $dateFilter = isset($params['date']) ? $params['date'] : date('Y-m');
    
    // Prepare date range for filtering
    $startDate = $dateFilter . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    
    try {
        // Process report based on type
        switch ($reportType) {
            case 'sales':
                getSalesReport($startDate, $endDate);
                break;
                
            case 'products':
                getProductsReport($startDate, $endDate);
                break;
                
            case 'feedback':
                getFeedbackReport($startDate, $endDate);
                break;
                
            default:
                throw new Exception('Invalid report type');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Generate sales report
 * 
 * @param string $startDate Start date for filtering
 * @param string $endDate End date for filtering
 */
function getSalesReport($startDate, $endDate) {
    global $conn;
    
    // Summary data for sales report
    $totalSales = 0;
    $totalOrders = 0;
    $avgOrderValue = 0;
    
    // Get total sales and orders count
    $query = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_sales 
              FROM orders 
              WHERE created_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $totalSales = $row['total_sales'] ? $row['total_sales'] : 0;
        $totalOrders = $row['total_orders'];
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
    }
    
    // Prepare chart data - Sales by day
    $labels = [];
    $data = [];
    
    $query = "SELECT DATE(created_at) as order_date, SUM(total_amount) as daily_total 
              FROM orders 
              WHERE created_at BETWEEN ? AND ? 
              GROUP BY DATE(created_at) 
              ORDER BY order_date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M d', strtotime($row['order_date']));
        $data[] = (float) $row['daily_total'];
    }
    
    // Prepare secondary chart data - Order Types
    $orderTypeLabels = [];
    $orderTypeData = [];
    
    $query = "SELECT order_type, COUNT(*) as type_count 
              FROM orders 
              WHERE created_at BETWEEN ? AND ? 
              GROUP BY order_type";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $orderTypeLabels[] = $row['order_type'];
        $orderTypeData[] = (int) $row['type_count'];
    }
    
    // Get detailed sales data for table
    $tableData = [];
    
    $query = "SELECT order_id, customer_name, order_type, order_status, total_amount, created_at 
              FROM orders 
              WHERE created_at BETWEEN ? AND ? 
              ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $tableData[] = $row;
    }
    
    // Send response
    echo json_encode([
        'success' => true,
        'summary' => [
            'totalSales' => $totalSales,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue
        ],
        'chartData' => [
            'main' => [
                'labels' => $labels,
                'data' => $data
            ],
            'secondary' => [
                'labels' => $orderTypeLabels,
                'data' => $orderTypeData
            ]
        ],
        'tableData' => $tableData
    ]);
}

/**
 * Generate products report
 * 
 * @param string $startDate Start date for filtering
 * @param string $endDate End date for filtering
 */
function getProductsReport($startDate, $endDate) {
    global $conn;
    
    // Get products performance data
    $query = "SELECT p.product_id, p.product_name, 
              COUNT(oi.order_item_id) as order_count, 
              SUM(oi.quantity) as units_sold,
              SUM(oi.quantity * oi.price) as total_revenue
              FROM order_items oi
              JOIN orders o ON oi.order_id = o.order_id
              JOIN products p ON oi.product_id = p.product_id
              WHERE o.created_at BETWEEN ? AND ?
              GROUP BY p.product_id
              ORDER BY units_sold DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productsData = [];
    $productLabels = [];
    $unitsSoldData = [];
    $topProduct = '';
    $totalProductsSold = 0;
    
    while ($row = $result->fetch_assoc()) {
        $productsData[] = $row;
        $productLabels[] = $row['product_name'];
        $unitsSoldData[] = (int) $row['units_sold'];
        $totalProductsSold += (int) $row['units_sold'];
        
        // First product is the top seller (ordered by units_sold DESC)
        if (empty($topProduct)) {
            $topProduct = $row['product_name'];
        }
    }
    
    // Get product ratings
    $query = "SELECT p.product_id, p.product_name, AVG(pf.rating) as avg_rating
              FROM product_feedback pf
              JOIN products p ON pf.product_id = p.product_id
              JOIN orders o ON pf.order_id = o.order_id
              WHERE o.created_at BETWEEN ? AND ?
              GROUP BY p.product_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ratingLabels = [];
    $ratingData = [];
    $totalRating = 0;
    $ratingCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $ratingLabels[] = $row['product_name'];
        $ratingData[] = (float) $row['avg_rating'];
        $totalRating += (float) $row['avg_rating'];
        $ratingCount++;
        
        // Update product data with ratings
        foreach ($productsData as &$product) {
            if ($product['product_id'] == $row['product_id']) {
                $product['avg_rating'] = (float) $row['avg_rating'];
                break;
            }
        }
    }
    
    $avgRating = $ratingCount > 0 ? $totalRating / $ratingCount : 0;
    
    // Send response
    echo json_encode([
        'success' => true,
        'summary' => [
            'topProduct' => $topProduct,
            'totalProductsSold' => $totalProductsSold,
            'avgRating' => $avgRating
        ],
        'chartData' => [
            'main' => [
                'labels' => $productLabels,
                'data' => $unitsSoldData
            ],
            'secondary' => [
                'labels' => $ratingLabels,
                'data' => $ratingData
            ]
        ],
        'tableData' => $productsData
    ]);
}

/**
 * Generate feedback report
 * 
 * @param string $startDate Start date for filtering
 * @param string $endDate End date for filtering
 */
function getFeedbackReport($startDate, $endDate) {
    global $conn;
    
    try {
        // Get product ratings data
        $query = "SELECT p.product_id, p.product_name, 
                  AVG(pf.rating) as avg_rating,
                  COUNT(pf.rating) as rating_count
                  FROM product_feedback pf
                  JOIN products p ON pf.product_id = p.product_id
                  JOIN orders o ON pf.order_id = o.order_id
                  WHERE o.created_at BETWEEN ? AND ?
                  GROUP BY p.product_id
                  ORDER BY avg_rating DESC, rating_count DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('ss', $startDate, $endDate);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        $productRatings = [];
        $totalRating = 0;
        $ratingCount = 0;
        $topRatedProduct = '';
        $topRatedProductRating = 0;
        
        while ($row = $result->fetch_assoc()) {
            $productRatings[] = $row;
            $totalRating += (float) $row['avg_rating'] * (int) $row['rating_count'];
            $ratingCount += (int) $row['rating_count'];
            
            if ((float) $row['avg_rating'] > $topRatedProductRating) {
                $topRatedProduct = $row['product_name'];
                $topRatedProductRating = (float) $row['avg_rating'];
            }
        }
        
        $avgRating = $ratingCount > 0 ? $totalRating / $ratingCount : 0;
        
        // Get rating distribution
        $query = "SELECT rating, COUNT(*) as count
                  FROM product_feedback pf
                  JOIN orders o ON pf.order_id = o.order_id
                  WHERE o.created_at BETWEEN ? AND ?
                  GROUP BY rating
                  ORDER BY rating";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ratingDistribution = [0, 0, 0, 0, 0]; // Initialize array for 1-5 star ratings
        
        while ($row = $result->fetch_assoc()) {
            $rating = (int) $row['rating'];
            if ($rating >= 1 && $rating <= 5) {
                $ratingDistribution[$rating - 1] = (int) $row['count'];
            }
        }
        
        // Get feedback summary (existing sentiment analysis)
        $query = "SELECT COUNT(*) as total_feedback, AVG(feedback_ratings) as avg_rating
                  FROM orders 
                  WHERE feedback_comment IS NOT NULL 
                  AND feedback_comment != 'No comment'
                  AND created_at BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $totalFeedback = 0;
        $avgFeedbackRating = 0;
        
        if ($row = $result->fetch_assoc()) {
            $totalFeedback = (int) $row['total_feedback'];
            $avgFeedbackRating = (float) $row['avg_rating'];
        }
        
        // Get detailed feedback data with comments (existing sentiment analysis)
        $query = "SELECT order_id, feedback_comment, feedback_date, customer_name, created_at
                  FROM orders 
                  WHERE feedback_comment IS NOT NULL 
                  AND feedback_comment != 'No comment'
                  AND created_at BETWEEN ? AND ?
                  ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $feedbackData = [];
        $commentsToAnalyze = [];
        $sentimentCounts = [
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0
        ];
        
        // First pass: collect all comments
        while ($row = $result->fetch_assoc()) {
            $commentsToAnalyze[] = [
                'order_id' => $row['order_id'],
                'comment' => $row['feedback_comment']
            ];
        }
        
        // Batch analyze comments (existing sentiment analysis)
        $batchSize = 10;
        $totalBatches = ceil(count($commentsToAnalyze) / $batchSize);
        $sentimentResults = [];
        
        for ($i = 0; $i < $totalBatches; $i++) {
            $batch = array_slice($commentsToAnalyze, $i * $batchSize, $batchSize);
            $comments = array_column($batch, 'comment');
            
            try {
                $url = 'http://localhost:8000/analyze_batch';
                $data = json_encode(['comments' => $comments]);
                
                $options = [
                    'http' => [
                        'header' => "Content-type: application/json\r\n",
                        'method' => 'POST',
                        'content' => $data
                    ]
                ];
                
                $context = stream_context_create($options);
                $response = @file_get_contents($url, false, $context);
                
                if ($response === false) {
                    throw new Exception("Failed to connect to sentiment analyzer");
                }
                
                $batchResults = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON response from sentiment analyzer");
                }
                
                foreach ($batch as $index => $commentData) {
                    if (isset($batchResults[$index])) {
                        $sentimentResults[$commentData['order_id']] = $batchResults[$index];
                    } else {
                        $sentimentResults[$commentData['order_id']] = [
                            'category' => 'neutral',
                            'score' => 0
                        ];
                    }
                }
            } catch (Exception $e) {
                foreach ($batch as $commentData) {
                    try {
                        $url = 'http://localhost:8000/analyze?text=' . urlencode($commentData['comment']);
                        $response = @file_get_contents($url);
                        
                        if ($response === false) {
                            throw new Exception("Failed to connect to sentiment analyzer");
                        }
                        
                        $result = json_decode($response, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new Exception("Invalid JSON response from sentiment analyzer");
                        }
                        
                        $sentimentResults[$commentData['order_id']] = $result;
                    } catch (Exception $e) {
                        $sentimentResults[$commentData['order_id']] = [
                            'category' => 'neutral',
                            'score' => 0
                        ];
                    }
                }
            }
        }
        
        // Second pass: combine feedback data with sentiment results
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $sentiment = $sentimentResults[$row['order_id']] ?? [
                'category' => 'neutral',
                'score' => 0
            ];
            
            $sentimentCounts[$sentiment['category']]++;
            
            $feedbackData[] = [
                'order_id' => $row['order_id'],
                'customer_name' => $row['customer_name'],
                'comment' => $row['feedback_comment'],
                'sentiment' => $sentiment['category'],
                'sentiment_score' => $sentiment['score'],
                'date' => $row['created_at']
            ];
        }
        
        // Prepare chart data
        $chartData = [
            'main' => [
                'labels' => ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                'data' => $ratingDistribution
            ],
            'secondary' => [
                'labels' => ['Positive', 'Negative', 'Neutral'],
                'data' => [
                    $sentimentCounts['positive'],
                    $sentimentCounts['negative'],
                    $sentimentCounts['neutral']
                ]
            ]
        ];
        
        // Send response
        echo json_encode([
            'success' => true,
            'summary' => [
                'totalFeedback' => $totalFeedback,
                'avgRating' => $avgRating,
                'topRatedProduct' => $topRatedProduct,
                'topRatedProductRating' => $topRatedProductRating
            ],
            'chartData' => $chartData,
            'tableData' => $feedbackData
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Export report data as CSV
 * 
 * @param array $params Request parameters
 */
function exportReport($params) {
    global $conn;
    
    // Get parameters
    $reportType = isset($params['report_type']) ? $params['report_type'] : 'sales';
    $dateFilter = isset($params['date']) ? $params['date'] : date('Y-m');
    
    // Prepare date range for filtering
    $startDate = $dateFilter . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . $dateFilter . '.csv"');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    try {
        // Process report based on type
        switch ($reportType) {
            case 'sales':
                // CSV Headers
                fputcsv($output, ['Date', 'Order ID', 'Customer', 'Order Type', 'Status', 'Total Amount']);
                
                // Get sales data
                $query = "SELECT order_id, customer_name, order_type, order_status, total_amount, created_at 
                          FROM orders 
                          WHERE created_at BETWEEN ? AND ? 
                          ORDER BY created_at";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $startDate, $endDate);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        date('Y-m-d', strtotime($row['created_at'])),
                        $row['order_id'],
                        $row['customer_name'],
                        $row['order_type'],
                        $row['order_status'],
                        $row['total_amount']
                    ]);
                }
                break;
                
            case 'products':
                // CSV Headers
                fputcsv($output, ['Product ID', 'Product Name', 'Units Sold', 'Total Revenue', 'Average Rating']);
                
                // Get products data
                $query = "SELECT p.product_id, p.product_name, 
                          SUM(oi.quantity) as units_sold,
                          SUM(oi.quantity * oi.price) as total_revenue,
                          (SELECT AVG(rating) FROM product_feedback WHERE product_id = p.product_id) as avg_rating
                          FROM order_items oi
                          JOIN orders o ON oi.order_id = o.order_id
                          JOIN products p ON oi.product_id = p.product_id
                          WHERE o.created_at BETWEEN ? AND ?
                          GROUP BY p.product_id
                          ORDER BY units_sold DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $startDate, $endDate);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['product_id'],
                        $row['product_name'],
                        $row['units_sold'],
                        $row['total_revenue'],
                        $row['avg_rating'] ? number_format($row['avg_rating'], 1) : 'N/A'
                    ]);
                }
                break;
                
            case 'feedback':
                // CSV Headers
                fputcsv($output, ['Order ID', 'Product', 'Customer', 'Rating', 'Date']);
                
                // Get feedback data
                $query = "SELECT pf.order_id, pf.product_id, pf.rating, 
                          p.product_name, o.customer_name, o.created_at
                          FROM product_feedback pf
                          JOIN products p ON pf.product_id = p.product_id
                          JOIN orders o ON pf.order_id = o.order_id
                          WHERE o.created_at BETWEEN ? AND ?
                          ORDER BY o.created_at";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $startDate, $endDate);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['order_id'],
                        $row['product_name'],
                        $row['customer_name'],
                        $row['rating'],
                        date('Y-m-d', strtotime($row['created_at']))
                    ]);
                }
                break;
                
            default:
                throw new Exception('Invalid report type');
        }
    } catch (Exception $e) {
        // If an error occurs, output error as CSV
        fputcsv($output, ['Error', $e->getMessage()]);
    }
    
    // Close the file pointer
    fclose($output);
    exit;
}
?>