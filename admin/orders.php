<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = strtolower($_SESSION['role'] ?? '');
$isAdminUser = isset($_SESSION['admin_id']);
$isKitchenUser = isset($_SESSION['user_id']) && in_array($userRole, ['kitchen', 'crew'], true);

if (!$isAdminUser && !$isKitchenUser) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Orders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <!-- Active Page Detection -->
    <script src="js/active-page.js"></script>
    <style>
        .status-buttons { display: inline-flex; gap: 5px; margin-left: 10px; }
        .status-btn {
            padding: 6px 14px; border: none; border-radius: 8px; cursor: pointer;
            transition: all 0.2s ease; display: inline-flex; align-items: center;
            gap: 6px; font-size: 12px; font-weight: 600; font-family: 'Poppins', sans-serif;
        }
        .status-btn.preparing { background: rgba(116,185,255,0.2); color: #74B9FF; }
        .status-btn.preparing:hover { background: rgba(116,185,255,0.3); }
        .status-btn.completed { background: rgba(0,184,148,0.2); color: #00B894; }
        .status-btn.completed:hover { background: rgba(0,184,148,0.3); }
        .status-badge {
            padding: 4px 12px; border-radius: 9999px; font-size: 11px;
            font-weight: 600; display: inline-flex; align-items: center; gap: 4px;
        }
        .status-badge.completed { background: rgba(0,184,148,0.15); color: #00B894; }
        .status-badge.cancelled {
            background: rgba(220,53,69,0.15); color: #ff8b92;
        }
        .status-badge i { font-size: 10px; }
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            min-width: 240px;
        }

        /* Order-type badges */
        .type-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 9999px;
            font-size: 11px; font-weight: 600; letter-spacing: 0.3px;
            text-transform: uppercase; white-space: nowrap;
        }
        .type-badge i { font-size: 10px; }
        .type-badge.dine-in   { background: rgba(212,175,55,0.14); color: #f4d26b; border: 1px solid rgba(212,175,55,0.28); }
        .type-badge.take-out  { background: rgba(116,185,255,0.15); color: #8fcbff; border: 1px solid rgba(116,185,255,0.28); }
        .type-badge.walk-in   { background: rgba(0,184,148,0.14); color: #78ebca; border: 1px solid rgba(0,184,148,0.28); }
        .type-badge.account-order { background: rgba(162,155,254,0.16); color: #c5bdff; border: 1px solid rgba(162,155,254,0.28); }
        .type-badge .table-tag {
            margin-left: 6px; padding: 1px 6px; border-radius: 6px;
            background: rgba(0,0,0,0.35); color: #fff; font-size: 10px; letter-spacing: 0.5px;
        }

        .swal-modern-product-popup {
            background:
                radial-gradient(circle at top right, rgba(212, 175, 55, 0.14), transparent 28%),
                linear-gradient(180deg, #191919 0%, #111111 100%) !important;
            border: 1px solid rgba(212, 175, 55, 0.18) !important;
            border-radius: 24px !important;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45) !important;
            color: #f5f5f5 !important;
            padding: 1.25rem !important;
        }

        .product-modal-title {
            color: #f8e7a4 !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em;
        }

        .product-modal-content {
            margin: 0 !important;
            padding-top: 0.5rem !important;
        }

        .swal-modern-product-confirm,
        .swal-modern-product-deny {
            border-radius: 12px !important;
            padding: 0.75rem 1.35rem !important;
            font-weight: 700 !important;
        }

        .swal-modern-product-confirm {
            color: #111 !important;
            box-shadow: 0 10px 24px rgba(212, 175, 55, 0.22) !important;
        }

        .swal-modern-product-deny {
            box-shadow: 0 10px 24px rgba(220, 53, 69, 0.2) !important;
        }

        .swal-modern-product-close {
            color: #d7b75b !important;
        }

        .product-view-modal {
            display: flex;
            flex-direction: column;
            gap: 18px;
            text-align: left;
        }

        .product-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 6px;
        }

        .product-modal-header-info h2 {
            margin: 0;
            color: #fff;
            font-size: 1.45rem;
            font-weight: 700;
        }

        .product-modal-subtitle {
            color: #9ca3af;
            font-size: 13px;
            margin-top: 4px;
        }

        .product-modal-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
            margin-top: 10px;
        }

        .product-modal-status.pending {
            background: rgba(255, 193, 7, 0.16);
            color: #ffd666;
        }

        .product-modal-status.preparing {
            background: rgba(116, 185, 255, 0.18);
            color: #8fcbff;
        }

        .product-modal-status.completed {
            background: rgba(0, 184, 148, 0.18);
            color: #78ebca;
        }

        .product-modal-status.cancelled {
            background: rgba(220, 53, 69, 0.18);
            color: #ff8b92;
        }

        .product-info-section {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .product-info-card,
        .product-section-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.12);
            border-radius: 18px;
            padding: 16px;
        }

        .product-info-card {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .product-info-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.18);
            color: #f4d26b;
            font-weight: 700;
            flex-shrink: 0;
        }

        .product-info-content {
            min-width: 0;
        }

        .product-info-label,
        .product-section-title {
            color: #9d9d9d;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .product-info-value {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            margin-top: 6px;
            word-break: break-word;
        }

        .product-section-title {
            margin: 0 0 12px;
        }

        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-item-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(212, 175, 55, 0.1);
            position: relative;
        }

        .order-item-remove-btn {
            flex-shrink: 0;
            align-self: flex-start;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid rgba(220, 53, 69, 0.35);
            background: rgba(220, 53, 69, 0.12);
            color: #ff8b92;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, transform 0.2s;
        }

        .order-item-remove-btn:hover {
            background: rgba(220, 53, 69, 0.22);
            transform: translateY(-1px);
        }

        .order-item-image {
            width: 74px;
            height: 74px;
            border-radius: 16px;
            overflow: hidden;
            flex-shrink: 0;
            background: linear-gradient(180deg, #222, #141414);
            border: 1px solid rgba(212, 175, 55, 0.12);
        }

        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .order-item-details {
            flex: 1;
            min-width: 0;
        }

        .order-item-name {
            margin: 0 0 6px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
        }

        .order-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .order-item-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(212, 175, 55, 0.1);
            color: #f6e2a2;
            font-size: 12px;
            font-weight: 600;
        }

        .order-total-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(212, 175, 55, 0.12);
        }

        .order-total-label {
            color: #9d9d9d;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .order-total-value {
            color: #f4d26b;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .view-empty-note {
            color: #9d9d9d;
            font-size: 13px;
        }

        .product-section-card .proof-of-payment { margin-top: 16px; text-align: center; }
        .product-section-card .proof-of-payment h4 {
            margin: 0 0 12px; color: #9d9d9d; font-size: 11px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .product-section-card .proof-image {
            max-width: 320px; margin: 0 auto; border: 1px solid rgba(212,175,55,0.15);
            border-radius: 12px; overflow: hidden; cursor: pointer;
            transition: transform 0.25s ease, border-color 0.25s ease;
        }
        .product-section-card .proof-image:hover { transform: scale(1.02); border-color: rgba(212,175,55,0.4); }
        .product-section-card .proof-image img { width: 100%; height: auto; display: block; }

        .mark-paid-btn {
            background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D; border: none;
            padding: 8px 14px; border-radius: 9px; cursor: pointer; display: inline-flex;
            align-items: center; gap: 6px; font-size: 12px; font-weight: 700;
            margin-top: 10px; font-family: 'Poppins', sans-serif; transition: all 0.2s;
        }
        .mark-paid-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-1px); }
        .payment-select {
            width: 100%;
            margin-top: 12px;
            background: rgba(255,255,255,0.06);
            color: #f1f1f1;
            border: 1px solid rgba(212,175,55,0.28);
            border-radius: 10px;
            padding: 10px 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
        }
        .payment-select:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .payment-type-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(212,175,55,0.12);
            border: 1px solid rgba(212,175,55,0.25);
            color: #f4d26b;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .verify-btn {
            margin-top: 15px; padding: 10px 20px;
            background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D;
            border: none; border-radius: 10px; cursor: pointer; font-weight: 700;
            transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;
            font-family: 'Poppins', sans-serif;
        }
        .verify-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-2px); }

        .text-success { color: #78ebca !important; }
        .text-warning { color: #FDCB6E !important; }
        .text-info { color: #8fcbff !important; }

        .payment-proof-modal { max-width: 90vw !important; max-height: 90vh !important; }
        .payment-proof-modal .swal2-image { max-width: 100%; max-height: 80vh; object-fit: contain; }

        /* Kitchen/Crew full-width landscape table layout */
        body.kitchen-layout .main-content {
            margin-left: 0 !important;
            padding: 14px 16px 18px !important;
        }
        body.kitchen-layout .orders-container {
            max-width: 100% !important;
            width: 100%;
        }
        body.kitchen-layout .orders-table-container {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            resize: horizontal;
            min-height: 360px;
        }
        body.kitchen-layout .orders-table {
            min-width: 980px;
        }
        body.kitchen-layout .orders-table thead th,
        body.kitchen-layout .orders-table tbody td {
            white-space: nowrap;
        }

        /* Kitchen: station columns (menu category lanes) */
        body.kitchen-layout .page-header h1 i { margin-right: 8px; }
        body.kitchen-layout.hidden-table-view .orders-table-container { display: none; }
        .kitchen-station-board {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            overflow-x: auto;
            padding-bottom: 10px;
            min-height: 420px;
            scroll-snap-type: x proximity;
        }
        .kitchen-station {
            flex: 0 0 min(280px, 88vw);
            scroll-snap-align: start;
            background: linear-gradient(180deg, rgba(25,25,28,0.95) 0%, rgba(16,16,18,1) 100%);
            border: 1px solid rgba(212, 175, 55, 0.22);
            border-radius: 16px;
            min-height: 360px;
            max-height: calc(100vh - 220px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .kitchen-station-header {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.14);
            background: rgba(212, 175, 55, 0.06);
        }
        .kitchen-station-header h3 {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 700;
            color: #f4d26b;
            letter-spacing: 0.03em;
        }
        .kitchen-station-header .kitchen-station-count {
            margin-top: 4px;
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
        }
        .kitchen-station-body {
            padding: 10px;
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .kitchen-station-empty {
            color: #6b7280;
            font-size: 12px;
            text-align: center;
            padding: 24px 8px;
            font-style: italic;
        }
        .kitchen-ticket {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 10px 11px;
        }
        .kitchen-ticket.kitchen-ticket-completed {
            opacity: 0.55;
            border-color: rgba(0, 184, 148, 0.2);
        }
        .kitchen-ticket.kitchen-ticket-cancelled {
            opacity: 0.45;
            border-style: dashed;
        }
        .kitchen-ticket-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .kitchen-ticket-id {
            font-size: 15px;
            font-weight: 800;
            color: #fff;
        }
        .kitchen-ticket-time {
            font-size: 10px;
            color: #9ca3af;
            white-space: nowrap;
        }
        .kitchen-ticket-customer {
            font-size: 12px;
            color: #d1d5db;
            margin-bottom: 6px;
        }
        .kitchen-ticket-lines {
            list-style: none;
            margin: 0 0 10px;
            padding: 0;
        }
        .kitchen-ticket-lines li {
            font-size: 13px;
            font-weight: 600;
            color: #f3f4f6;
            padding: 5px 0;
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: baseline;
        }
        .kitchen-ticket-lines li:first-child { border-top: none; }
        .kitchen-ticket-lines .qty {
            color: #f4d26b;
            flex-shrink: 0;
            font-variant-numeric: tabular-nums;
        }
        .kitchen-ticket-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .kitchen-ticket-actions .kitchen-btn-details {
            background: transparent;
            border: 1px solid rgba(212,175,55,0.35);
            color: #f4d26b;
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        .kitchen-ticket-actions .kitchen-btn-details:hover {
            background: rgba(212,175,55,0.12);
        }
        .kitchen-board-empty {
            text-align: center;
            padding: 48px 16px;
            color: #9ca3af;
            font-size: 14px;
        }
        .kitchen-items-by-station {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .kitchen-modal-station-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #f4d26b;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid rgba(212,175,55,0.2);
        }
        .orders-table-container {
            overflow-x: auto;
        }
        .orders-table {
            width: 100%;
            min-width: 980px;
            table-layout: auto;
        }
        .orders-table thead th,
        .orders-table tbody td {
            white-space: nowrap;
            vertical-align: middle;
        }
        .orders-table thead th:nth-child(1),
        .orders-table tbody td:nth-child(1) { min-width: 170px; }
        .orders-table thead th:nth-child(2),
        .orders-table tbody td:nth-child(2) { min-width: 180px; }
        .orders-table thead th:nth-child(3),
        .orders-table tbody td:nth-child(3) { min-width: 150px; }
        .orders-table thead th:nth-child(4),
        .orders-table tbody td:nth-child(4) { min-width: 90px; }
        .orders-table thead th:last-child,
        .orders-table tbody td:last-child { min-width: 300px; }

        @media (max-width: 680px) {
            .product-modal-header {
                flex-direction: column;
            }

            .product-info-section {
                grid-template-columns: 1fr;
            }

            .order-item-card {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="<?php echo $isKitchenUser ? 'kitchen-layout hidden-table-view' : ''; ?>">
    <?php if ($isAdminUser): ?>
        <?php include("../navigation/admin-navbar.php");?>
        <?php include("../navigation/admin-sidebar.php");?>
    <?php else: ?>
        <header style="padding: 14px 18px; border-bottom: 1px solid rgba(212,175,55,0.2); background: #0d0d0d; color: #f4d26b;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="font-weight:600;">Kitchen Orders</div>
                <a href="logout.php" style="color:#f4d26b; text-decoration:none;">Logout</a>
            </div>
        </header>
    <?php endif; ?>
    
    <div class="main-content">
        <div class="orders-container">
            <div class="page-header">
                <h1><i class="fas <?php echo $isKitchenUser ? 'fa-utensils' : 'fa-receipt'; ?>"></i><?php echo $isKitchenUser ? 'Kitchen station board' : 'Orders Management'; ?></h1>
                <div class="filter-bar">
                    <input type="date" id="orderDateFilter" placeholder="Filter by date">
                    <select id="orderTypeFilter" class="filter-select">
                        <option value="">All Order Types</option>
                        <option value="dine-in">Dine-in</option>
                        <option value="take-out">Take-out</option>
                        <option value="walk-in">Walk-in</option>
                        <option value="account-order">Account Order</option>
                    </select>
                </div>
            </div>
            
            <?php if ($isKitchenUser): ?>
            <div id="kitchenStationBoard" class="kitchen-station-board" aria-label="Kitchen stations by category">
                <!-- Filled by loadOrders -->
            </div>
            <?php endif; ?>

            <!-- Orders Table -->
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Type</th>
                            <?php if ($isAdminUser): ?><th>Total</th><?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <!-- Orders will be loaded here dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        const IS_KITCHEN_VIEW = <?php echo $isKitchenUser ? 'true' : 'false'; ?>;
        const KITCHEN_STATION_SLUG_ORDER = ['soup', 'noodles', 'pasta', 'fry', 'salad', 'soda_wares'];
        function money(n) {
            return parseFloat(n || 0).toFixed(2);
        }
        function computeVatBreakdown(items) {
            let vatableSales = 0, vatAmount = 0, vatExemptSales = 0, total = 0;
            (items || []).forEach(i => {
                const lineTotal = parseFloat(i.price || 0) * parseInt(i.quantity || 0, 10);
                const taxRate = parseFloat(i.tax_rate || 12);
                total += lineTotal;
                if (taxRate > 0) {
                    const vatable = lineTotal / (1 + taxRate / 100);
                    vatableSales += vatable;
                    vatAmount += lineTotal - vatable;
                } else {
                    vatExemptSales += lineTotal;
                }
            });
            return { vatableSales, vatAmount, vatExemptSales, total };
        }
        function printOrderReceipt(order) {
            if (!order || !Array.isArray(order.items) || order.items.length === 0) {
                Swal.fire({ title: 'No items to print', text: 'This order has no printable line items.', icon: 'warning', confirmButtonColor: '#D4AF37' });
                return;
            }
            const createdAt = order.created_at ? new Date(order.created_at) : new Date();
            const dateStr = createdAt.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
            const timeStr = createdAt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
            let itemsHtml = '';
            order.items.forEach(i => {
                const qty = parseInt(i.quantity || 0, 10);
                const price = parseFloat(i.price || 0);
                const subtotal = price * qty;
                itemsHtml += `<tr><td style="text-align:left">${i.product_name}</td><td style="text-align:center">${qty}</td><td style="text-align:right">₱${money(price)}</td><td style="text-align:right">₱${money(subtotal)}</td></tr>`;
            });
            const vat = computeVatBreakdown(order.items);
            const vatHtml = `${vat.vatableSales > 0 ? `<div class="receipt-vat-row"><span>VATable Sales</span><span>₱${money(vat.vatableSales)}</span></div><div class="receipt-vat-row"><span>VAT (12%)</span><span>₱${money(vat.vatAmount)}</span></div>` : ''}${vat.vatExemptSales > 0 ? `<div class="receipt-vat-row"><span>VAT-Exempt Sales</span><span>₱${money(vat.vatExemptSales)}</span></div>` : ''}`;
            const logoUrl = new URL('../assets/zoryn/zoryn_logo.jpg', window.location.href).href;
            const orderTypeLabel = (order.order_type || 'walk-in').toString().replace(/\b\w/g, c => c.toUpperCase());
            const receiptHtml = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Receipt #${order.order_id}</title><style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Poppins',sans-serif;background:#fff;color:#1a1a1a}.receipt{width:320px;margin:0 auto;padding:24px 20px}.receipt-logo{display:block;width:80px;height:80px;object-fit:cover;border-radius:50%;margin:0 auto 8px;border:2px solid #D4AF37}.receipt-brand{text-align:center;font-size:16px;font-weight:700;letter-spacing:1px;margin-bottom:2px}.receipt-tagline{text-align:center;font-size:10px;color:#888;margin-bottom:14px;letter-spacing:.5px}.receipt-divider{border:none;border-top:1px dashed #ccc;margin:10px 0}.receipt-meta{font-size:11px;color:#555;margin-bottom:3px;display:flex;justify-content:space-between}.receipt-table{width:100%;border-collapse:collapse;margin:10px 0;font-size:11px}.receipt-table th{text-align:left;font-weight:600;font-size:10px;text-transform:uppercase;color:#888;letter-spacing:.5px;padding:4px 0;border-bottom:1px solid #ddd}.receipt-table td{padding:5px 0;vertical-align:top}.receipt-vat-row{display:flex;justify-content:space-between;font-size:10px;color:#666;padding:2px 0}.receipt-total{display:flex;justify-content:space-between;font-size:15px;font-weight:700;padding:8px 0;border-top:2px solid #1a1a1a;margin-top:4px}.receipt-footer{text-align:center;margin-top:16px;font-size:10px;color:#999;line-height:1.6}.receipt-footer strong{color:#D4AF37;font-size:11px}@media print{@page{size:80mm auto;margin:0}.receipt{width:100%;padding:10px 8px}.no-print{display:none!important}}</style></head><body><div style="text-align:center;padding:16px 0" class="no-print"><button onclick="window.print()" style="padding:10px 28px;background:#D4AF37;color:#0D0D0D;border:none;border-radius:8px;font-weight:600;font-family:Poppins,sans-serif;cursor:pointer;font-size:14px;margin-right:8px"><i class="fas fa-print" style="margin-right:6px"></i>Print</button><button onclick="window.close()" style="padding:10px 28px;background:#2A2A2A;color:#B0B0B0;border:1px solid #ddd;border-radius:8px;font-weight:500;font-family:Poppins,sans-serif;cursor:pointer;font-size:14px">Close</button></div><div class="receipt"><img src="${logoUrl}" class="receipt-logo" alt="Zoryn" onerror="this.style.display='none'"><div class="receipt-brand">ZORYN RESTAURANT</div><div class="receipt-tagline">Taste the Excellence</div><hr class="receipt-divider"><div class="receipt-meta"><span>Date: ${dateStr}</span><span>${timeStr}</span></div><div class="receipt-meta"><span>Order #${order.order_id}</span></div><div class="receipt-meta"><span>Customer: ${order.customer_name || 'Guest'}</span></div><div class="receipt-meta"><span>Type: ${orderTypeLabel}</span>${order.table_number ? `<span>Table: ${order.table_number}</span>` : ''}</div><hr class="receipt-divider"><table class="receipt-table"><thead><tr><th style="text-align:left">Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Total</th></tr></thead><tbody>${itemsHtml}</tbody></table><hr class="receipt-divider">${vatHtml}<div class="receipt-total"><span>TOTAL</span><span>₱${money(vat.total)}</span></div><hr class="receipt-divider"><div class="receipt-footer"><strong>Thank you for dining with us!</strong><br>Please come again.<br>&mdash; Zoryn Restaurant &mdash;</div></div><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></body></html>`;
            const receiptWindow = window.open('', '_blank', 'width=420,height=760');
            if (!receiptWindow) return;
            receiptWindow.document.write(receiptHtml);
            receiptWindow.document.close();
        }
        function canEditOrderLines(orderStatus) {
            if (IS_KITCHEN_VIEW) return false;
            const s = (orderStatus || '').toLowerCase();
            return s === 'pending' || s === 'preparing';
        }

        window.removeOrderLine = function(orderId, orderItemId) {
            Swal.fire({
                title: 'Remove this item?',
                html: `
                    <p style="margin-bottom:10px;">It will be taken off the order and stock will be restored.</p>
                    <input id="adminPinInput" type="password" class="swal2-input" inputmode="numeric" maxlength="8" placeholder="Enter admin PIN">
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#2E2E2E',
                confirmButtonText: 'Yes, remove it',
                preConfirm: () => {
                    const pin = (document.getElementById('adminPinInput')?.value || '').trim();
                    if (!/^\d{4,8}$/.test(pin)) {
                        Swal.showValidationMessage('Admin PIN is required (4-8 digits).');
                        return false;
                    }
                    return pin;
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                const adminPin = result.value;
                fetch('../backend/order_functions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove_order_item&order_id=${orderId}&order_item_id=${orderItemId}&admin_pin=${encodeURIComponent(adminPin)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.close();
                        if (typeof window.loadOrders === 'function') window.loadOrders();
                        Swal.fire({
                            title: data.order_empty ? 'Order cancelled' : 'Updated',
                            text: data.message || 'Item removed',
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Could not remove item',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Request failed',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            });
        };

        window.showFullImage = function(src) {
            Swal.fire({
                imageUrl: src,
                imageAlt: 'Proof of Payment',
                width: 'auto',
                padding: '1em',
                showConfirmButton: false,
                showCloseButton: true,
                customClass: { popup: 'payment-proof-modal' }
            });
        };

        window.verifyPayment = function(orderId) {
            Swal.fire({
                title: 'Verify Payment',
                text: 'Are you sure you want to verify this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#D4AF37',
                cancelButtonColor: '#2E2E2E',
                confirmButtonText: 'Yes, verify it!'
            }).then((result) => {
                if (!result.isConfirmed) return;
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=verify_payment&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Verified!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        }).then(() => {
                            if (typeof window.loadOrders === 'function') window.loadOrders();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to verify payment',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while verifying payment',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            });
        };

        const PAYMENT_STATUS_LABELS = { paid: 'Paid', unpaid: 'Unpaid', pending: 'Pending', charge_corp: 'Charge to corp', verified: 'Paid' };

        window.updatePaymentStatus = function(orderId, paymentStatus) {
            const statusLabel = PAYMENT_STATUS_LABELS[paymentStatus] || paymentStatus;
            Swal.fire({
                title: 'Update payment status?',
                text: `Set payment status to "${statusLabel}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#D4AF37',
                cancelButtonColor: '#2E2E2E',
                confirmButtonText: 'Yes, update'
            }).then((result) => {
                if (!result.isConfirmed) return;
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update_payment_status&order_id=${orderId}&payment_status=${encodeURIComponent(paymentStatus)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Payment status updated',
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        }).then(() => {
                            if (typeof window.loadOrders === 'function') window.loadOrders();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to update payment status',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while updating payment status',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            });
        };

        window.cancelWholeOrder = function(orderId) {
            Swal.fire({
                title: 'Cancel this order?',
                html: `
                    <p style="margin-bottom:10px;">The full order will be marked cancelled and ingredients will be restocked.</p>
                    <input id="adminPinCancelInput" type="password" class="swal2-input" inputmode="numeric" maxlength="8" placeholder="Enter admin PIN">
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#2E2E2E',
                confirmButtonText: 'Yes, cancel order',
                preConfirm: () => {
                    const pin = (document.getElementById('adminPinCancelInput')?.value || '').trim();
                    if (!/^\d{4,8}$/.test(pin)) {
                        Swal.showValidationMessage('Admin PIN is required (4-8 digits).');
                        return false;
                    }
                    return pin;
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                const adminPin = result.value;
                fetch('../backend/order_functions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=cancel_order&order_id=${orderId}&admin_pin=${encodeURIComponent(adminPin)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.close();
                        if (typeof window.loadOrders === 'function') window.loadOrders();
                        Swal.fire({
                            title: 'Cancelled',
                            text: data.message || 'Order cancelled',
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Could not cancel order',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Request failed',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            });
        };

        // Map raw order_type to label + icon for consistent rendering.
        function orderTypeMeta(type) {
            const t = (type || '').toLowerCase();
            const map = {
                'dine-in':       { label: 'Dine-in',       icon: 'fa-utensils' },
                'take-out':      { label: 'Take-out',      icon: 'fa-bag-shopping' },
                'walk-in':       { label: 'Walk-in',       icon: 'fa-person-walking' },
                'account-order': { label: 'Account Order', icon: 'fa-user-circle' }
            };
            const meta = map[t] || { label: type || '—', icon: 'fa-tag' };
            meta.cls = t || 'walk-in';
            return meta;
        }
        function renderTypeBadge(type, tableNumber) {
            const m = orderTypeMeta(type);
            const tableTag = (tableNumber && String(tableNumber).trim() !== '')
                ? `<span class="table-tag">T# ${tableNumber}</span>` : '';
            return `<span class="type-badge ${m.cls}"><i class="fas ${m.icon}"></i>${m.label}${tableTag}</span>`;
        }

        function kitchenTicketModifierClass(orderStatus) {
            const s = (orderStatus || '').toLowerCase();
            if (s === 'completed') return 'kitchen-ticket-completed';
            if (s === 'cancelled') return 'kitchen-ticket-cancelled';
            return '';
        }

        function kitchenOrderStatusButtons(order) {
            const oid = order.order_id;
            if (order.order_status === 'cancelled') {
                return `<span class="status-badge cancelled"><i class="fas fa-ban"></i><span>Cancelled</span></span>`;
            }
            if (order.order_status === 'pending') {
                return `<button type="button" class="status-btn preparing" data-order-id="${oid}"><i class="fas fa-utensils"></i><span>Prepare</span></button>`;
            }
            if (order.order_status === 'preparing') {
                return `<button type="button" class="status-btn completed" data-order-id="${oid}"><i class="fas fa-check"></i><span>Complete</span></button>`;
            }
            return `<span class="status-badge completed"><i class="fas fa-check-circle"></i><span>Completed</span></span>`;
        }

        function renderKitchenStationBoard(stations) {
            const board = document.getElementById('kitchenStationBoard');
            if (!board) return;

            const list = Array.isArray(stations) ? stations : [];
            if (list.length === 0) {
                board.innerHTML = '<div class="kitchen-board-empty">No stations configured. Add active menu categories in admin.</div>';
                return;
            }

            board.innerHTML = list.map((st) => {
                const ticketCount = (st.orders || []).length;
                const body = ticketCount === 0
                    ? '<div class="kitchen-station-empty">Clear — no items in queue</div>'
                    : (st.orders || []).map((o) => {
                        const cls = kitchenTicketModifierClass(o.order_status);
                        const lines = (o.items || []).map((it) => `
                            <li><span class="name">${it.product_name}</span><span class="qty">${it.quantity}×</span></li>
                        `).join('');
                        return `
                            <article class="kitchen-ticket ${cls}">
                                <div class="kitchen-ticket-top">
                                    <div>
                                        <div class="kitchen-ticket-id">#${o.order_id}</div>
                                        <div class="kitchen-ticket-customer">${o.customer_name || 'Guest'}</div>
                                    </div>
                                    <div class="kitchen-ticket-time">${new Date(o.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                                <div style="margin-bottom:8px;">${renderTypeBadge(o.order_type, o.table_number)}</div>
                                <ul class="kitchen-ticket-lines">${lines}</ul>
                                <div class="kitchen-ticket-actions">
                                    <button type="button" class="kitchen-btn-details" data-order-id="${o.order_id}">Details</button>
                                    ${kitchenOrderStatusButtons(o)}
                                </div>
                            </article>
                        `;
                    }).join('');

                return `
                    <section class="kitchen-station" data-category-id="${st.category_id}">
                        <header class="kitchen-station-header">
                            <h3>${st.category_name}</h3>
                            <div class="kitchen-station-count">${ticketCount} ticket${ticketCount === 1 ? '' : 's'}</div>
                        </header>
                        <div class="kitchen-station-body">${body}</div>
                    </section>
                `;
            }).join('');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data
            loadOrders();

            // Filter handlers
            document.getElementById('orderDateFilter').addEventListener('change', loadOrders);
            document.getElementById('orderTypeFilter').addEventListener('change', loadOrders);

            // Function to load orders
            function loadOrders() {
                const dateFilter = document.getElementById('orderDateFilter').value;
                const typeFilter = document.getElementById('orderTypeFilter').value;

                if (IS_KITCHEN_VIEW) {
                    const kParams = new URLSearchParams({ action: 'get_kitchen_station_board' });
                    if (dateFilter) kParams.set('date', dateFilter);
                    if (typeFilter) kParams.set('order_type', typeFilter);
                    fetch('../backend/order_functions.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: kParams.toString()
                    })
                    .then((r) => r.json())
                    .then((data) => {
                        if (data.success) {
                            renderKitchenStationBoard(data.stations);
                        }
                    })
                    .catch((err) => console.error('Error loading kitchen board:', err));
                    return;
                }

                const params = new URLSearchParams({ action: 'get_orders' });
                if (dateFilter) params.set('date', dateFilter);
                if (typeFilter) params.set('order_type', typeFilter);

                fetch('../backend/order_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('ordersTableBody');
                        tbody.innerHTML = '';
                        
                        data.orders.forEach(order => {
                            const row = document.createElement('tr');
                            row.className = `order-row`;
                            
                            const paymentTypeRaw = (order.payment_type || '').trim();
                            const paymentTypeLabel = paymentTypeRaw
                                ? paymentTypeRaw.charAt(0).toUpperCase() + paymentTypeRaw.slice(1)
                                : 'Cash';

                            row.innerHTML = `
                                <td>${order.customer_name}</td>
                                <td>${new Date(order.created_at).toLocaleString()}</td>
                                <td>${renderTypeBadge(order.order_type, order.table_number)}</td>
                                ${IS_KITCHEN_VIEW ? '' : `<td>₱${parseFloat(order.total_amount).toFixed(2)}</td>`}
                                <td class="action-buttons">
                                    ${IS_KITCHEN_VIEW ? '' : `<span class="payment-type-chip"><i class="fas fa-credit-card"></i>${paymentTypeLabel}</span>`}
                                    <button class="action-btn view" data-order-id="${order.order_id}" data-action="view" title="View Order Details">
                                        <i class="fas fa-eye"></i>
                                        <span>View Details</span>
                                    </button>
                                    ${order.order_status === 'cancelled' ?
                                        `<span class="status-badge cancelled">
                                            <i class="fas fa-ban"></i>
                                            <span>Cancelled</span>
                                        </span>` :
                                        order.order_status === 'pending' ?
                                        `<button class="status-btn preparing" data-order-id="${order.order_id}" title="Start Preparing Order">
                                            <i class="fas fa-utensils"></i>
                                            <span>Prepare</span>
                                        </button>` :
                                        order.order_status === 'preparing' ?
                                        `<button class="status-btn completed" data-order-id="${order.order_id}" title="Mark Order as Completed">
                                            <i class="fas fa-check"></i>
                                            <span>Complete</span>
                                        </button>` :
                                        `<span class="status-badge completed">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Completed</span>
                                        </span>`
                                    }
                                </td>
                            `;
                            
                            tbody.appendChild(row);
                            
                            // Add event listener to view button
                            row.querySelector('.action-btn').addEventListener('click', function(e) {
                                e.stopPropagation();
                                const orderId = this.getAttribute('data-order-id');
                                viewOrderDetails(orderId);
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading orders:', error);
                });
            }
            window.loadOrders = loadOrders;
            
            // Function to view order details
            function viewOrderDetails(orderId) {
                fetch('../backend/order_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_order_details&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.order;
                        const orderStatusLabel = order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1);
                        const statusIconClass = order.order_status === 'pending'
                            ? 'fa-clock'
                            : order.order_status === 'preparing'
                                ? 'fa-utensils'
                                : order.order_status === 'cancelled'
                                    ? 'fa-ban'
                                    : 'fa-check-circle';
                        const itemsFlatHtml = order.items && order.items.length > 0
                            ? order.items.map(item => `
                                <div class="order-item-card">
                                    <div class="order-item-image">
                                        <img src="../${item.image_path || 'assets/images/products/default.jpg'}" alt="${item.product_name}" onerror="this.onerror=null;this.src='../assets/images/products/default.jpg';">
                                    </div>
                                    <div class="order-item-details">
                                        <h4 class="order-item-name">${item.product_name}</h4>
                                        <div class="order-item-meta">
                                            <span class="order-item-pill">Qty: ${item.quantity}</span>
                                            ${IS_KITCHEN_VIEW ? '' : `<span class="order-item-pill">Price: ₱${parseFloat(item.price).toFixed(2)}</span>`}
                                            ${IS_KITCHEN_VIEW ? '' : `<span class="order-item-pill">Subtotal: ₱${parseFloat(item.price * item.quantity).toFixed(2)}</span>`}
                                        </div>
                                    </div>
                                    ${canEditOrderLines(order.order_status) ? `
                                    <button type="button" class="order-item-remove-btn" onclick="removeOrderLine(${order.order_id}, ${item.order_item_id})" title="Remove this line">
                                        <i class="fas fa-times"></i>
                                    </button>` : ''}
                                </div>
                            `).join('')
                            : '';
                        const groupedKitchenItems = () => {
                            const bySlug = new Map();
                            (order.items || []).forEach((item) => {
                                const slug = (item.kitchen_station || 'fry').toLowerCase();
                                if (!bySlug.has(slug)) bySlug.set(slug, []);
                                bySlug.get(slug).push(item);
                            });
                            const extras = [...bySlug.keys()].filter((s) => !KITCHEN_STATION_SLUG_ORDER.includes(s)).sort();
                            const slugsOrdered = [...KITCHEN_STATION_SLUG_ORDER.filter((s) => bySlug.has(s)), ...extras];
                            const renderStationChunk = (items) => items.map(item => `
                                <div class="order-item-card">
                                    <div class="order-item-image">
                                        <img src="../${item.image_path || 'assets/images/products/default.jpg'}" alt="${item.product_name}" onerror="this.onerror=null;this.src='../assets/images/products/default.jpg';">
                                    </div>
                                    <div class="order-item-details">
                                        <h4 class="order-item-name">${item.product_name}</h4>
                                        <div class="order-item-meta">
                                            <span class="order-item-pill">Qty: ${item.quantity}</span>
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                            return slugsOrdered.map((slug) => {
                                const bucket = bySlug.get(slug);
                                const label = (bucket[0] && bucket[0].kitchen_station_label) ? bucket[0].kitchen_station_label : slug;
                                return `<div class="kitchen-items-by-station"><div class="kitchen-modal-station-label">${label}</div>${renderStationChunk(bucket)}</div>`;
                            }).join('');
                        };
                        const itemsHtml = order.items && order.items.length > 0
                            ? (IS_KITCHEN_VIEW ? groupedKitchenItems() : itemsFlatHtml)
                            : '<span class="view-empty-note">No order items found.</span>';

                        const paymentTypeRaw = (order.payment_type || '').trim();
                        const pt = paymentTypeRaw.toLowerCase() || 'cash';
                        const displayTypeLabel = paymentTypeRaw
                            ? paymentTypeRaw.charAt(0).toUpperCase() + paymentTypeRaw.slice(1)
                            : 'Cash';
                        const payStatusLabel = PAYMENT_STATUS_LABELS[(order.payment_status || '').toLowerCase()]
                            || (order.payment_status
                                ? order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1).replace(/_/g, ' ')
                                : 'Pending');
                        const payStatusCss = order.payment_status === 'verified' ? 'text-success'
                            : ((order.payment_status || '').toLowerCase() === 'charge_corp' ? 'text-info' : 'text-warning');
                        const isCancelledOrder = (order.order_status || '').toLowerCase() === 'cancelled';
                        const canMarkPayment = !IS_KITCHEN_VIEW && !isCancelledOrder && order.payment_status !== 'verified';

                        const paymentStatusRaw = (order.payment_status || '').toLowerCase();
                        const paymentSelectValue = paymentStatusRaw === 'verified'
                            ? 'paid'
                            : (paymentStatusRaw === 'charge_corp' ? 'charge_corp' : (paymentStatusRaw || 'unpaid'));
                        const cashAction = !IS_KITCHEN_VIEW ? `
                            <div class="proof-of-payment">
                                <label style="display:block; margin-bottom:6px; font-size:12px; color:#9d9d9d;">Payment Status</label>
                                <select class="payment-select" onchange="updatePaymentStatus(${order.order_id}, this.value)" ${(isCancelledOrder || paymentStatusRaw === 'verified') ? 'disabled' : ''}>
                                    <option value="unpaid" ${paymentSelectValue === 'unpaid' ? 'selected' : ''}>Unpaid</option>
                                    <option value="pending" ${paymentSelectValue === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="paid" ${paymentSelectValue === 'paid' ? 'selected' : ''}>Paid</option>
                                    <option value="charge_corp" ${paymentSelectValue === 'charge_corp' ? 'selected' : ''}>Charge to corp</option>
                                </select>
                                ${isCancelledOrder ? `<div class="view-empty-note" style="margin-top:8px;">Payment status cannot be changed for cancelled orders.</div>` : ''}
                            </div>` : '';

                        const onlineProof = canMarkPayment && pt !== 'cash' && order.proof_of_payment ? `
                            <div class="proof-of-payment">
                                <h4>Proof of Payment</h4>
                                <div class="proof-image">
                                    <img src="../${order.proof_of_payment}" alt="Proof of Payment" onclick="showFullImage(this.src)">
                                </div>
                                <button type="button" class="verify-btn" onclick="verifyPayment(${order.order_id})">
                                    <i class="fas fa-check"></i> Verify Payment
                                </button>
                            </div>` : '';

                        const onlinePendingNoProof = canMarkPayment && pt !== 'cash' && !order.proof_of_payment ? `
                            <div class="proof-of-payment">
                                <p class="view-empty-note">Awaiting payment proof upload from the customer.</p>
                            </div>` : '';

                        const paymentHtml = IS_KITCHEN_VIEW ? '' : `
                            <div class="product-section-card">
                                <h3 class="product-section-title">Payment Details</h3>
                                <div class="product-info-section">
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-credit-card"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Payment Type</div>
                                            <div class="product-info-value">${displayTypeLabel}</div>
                                        </div>
                                    </div>
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-check-circle"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Payment Status</div>
                                            <div class="product-info-value ${payStatusCss}">${payStatusLabel}</div>
                                        </div>
                                    </div>
                                </div>
                                ${cashAction}
                                ${onlineProof}
                                ${onlinePendingNoProof}
                            </div>`;
                        
                        Swal.fire({
                            title: `<div class="product-modal-header">
                                <div class="product-modal-header-info">
                                    <h2>Order #${order.order_id}</h2>
                                    <div class="product-modal-status ${order.order_status}">
                                        <i class="fas ${statusIconClass}"></i>
                                        ${orderStatusLabel}
                                    </div>
                                </div>
                                <div class="product-modal-subtitle">${new Date(order.created_at).toLocaleString()}</div>
                            </div>`,
                            html: `
                                <div class="product-view-modal">
                                    <div class="product-info-section">
                                        <div class="product-info-card">
                                            <div class="product-info-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="product-info-content">
                                                <div class="product-info-label">Customer</div>
                                                <div class="product-info-value">${order.customer_name}</div>
                                            </div>
                                        </div>
                                    <div class="product-info-card">
                                        <div class="product-info-icon">
                                            <i class="fas fa-concierge-bell"></i>
                                        </div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Waiter</div>
                                            <div class="product-info-value">${order.waiter_name || 'Not recorded'}</div>
                                        </div>
                                    </div>
                                    <div class="product-info-card">
                                        <div class="product-info-icon">
                                            <i class="fas fa-cash-register"></i>
                                        </div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Cashier</div>
                                            <div class="product-info-value">${order.cashier_name || 'Not recorded'}</div>
                                        </div>
                                    </div>
                                        <div class="product-info-card">
                                            <div class="product-info-icon">
                                                <i class="fas ${orderTypeMeta(order.order_type).icon}"></i>
                                            </div>
                                            <div class="product-info-content">
                                                <div class="product-info-label">Order Type</div>
                                                <div class="product-info-value">${renderTypeBadge(order.order_type, null)}</div>
                                            </div>
                                        </div>
                                        ${order.table_number ? `
                                        <div class="product-info-card">
                                            <div class="product-info-icon">
                                                <i class="fas fa-chair"></i>
                                            </div>
                                            <div class="product-info-content">
                                                <div class="product-info-label">Table Number</div>
                                                <div class="product-info-value">${order.table_number}</div>
                                            </div>
                                        </div>` : ''}
                                        <div class="product-info-card">
                                            <div class="product-info-icon">
                                                <i class="fas fa-receipt"></i>
                                            </div>
                                            <div class="product-info-content">
                                                <div class="product-info-label">Items</div>
                                                <div class="product-info-value">${order.item_count || (order.items ? order.items.length : 0)} item(s)</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="product-section-card">
                                        <h3 class="product-section-title">Order Items</h3>
                                        <div class="order-items-list">
                                            ${itemsHtml}
                                        </div>
                                        <div style="margin-top:12px;">
                                            <button type="button" class="mark-paid-btn" id="print-receipt-btn">
                                                <i class="fas fa-print"></i> Print Receipt
                                            </button>
                                        </div>
                                        ${IS_KITCHEN_VIEW ? '' : `
                                        <div class="order-total-bar">
                                            <div class="order-total-label">Total Amount</div>
                                            <div class="order-total-value">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                        </div>`}
                                    </div>

                                    ${paymentHtml}
                                </div>
                            `,
                            width: '900px',
                            showCloseButton: true,
                            showConfirmButton: true,
                            showDenyButton: canEditOrderLines(order.order_status),
                            confirmButtonText: 'Close',
                            denyButtonText: 'Cancel order',
                            confirmButtonColor: '#D4AF37',
                            denyButtonColor: '#dc3545',
                            customClass: {
                                popup: 'swal-modern-product-popup',
                                title: 'product-modal-title',
                                htmlContainer: 'product-modal-content',
                                confirmButton: 'swal-modern-product-confirm',
                                denyButton: 'swal-modern-product-deny',
                                closeButton: 'swal-modern-product-close'
                            },
                            didOpen: () => {
                                const printBtn = document.getElementById('print-receipt-btn');
                                if (printBtn) {
                                    printBtn.addEventListener('click', () => printOrderReceipt(order));
                                }
                            }
                        }).then((result) => {
                            if (result.isDenied) {
                                cancelWholeOrder(orderId);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to load order details',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }
            
            // Add event listeners for status buttons
            document.addEventListener('click', function(e) {
                const kitchenDet = e.target.closest('.kitchen-btn-details');
                if (kitchenDet) {
                    e.preventDefault();
                    const oid = kitchenDet.getAttribute('data-order-id');
                    if (oid) viewOrderDetails(oid);
                    return;
                }
                if (e.target.closest('.status-btn')) {
                    const button = e.target.closest('.status-btn');
                    const orderId = button.getAttribute('data-order-id');
                    
                    updateOrderStatus(orderId);
                }
            });
            
            function updateOrderStatus(orderId) {
                fetch('../backend/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the orders to show updated status
                        loadOrders();
                        
                        // Show success message
                        Swal.fire({
                            title: 'Success',
                            text: 'Order status updated successfully',
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to update order status',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while updating the order status',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }
        });
    </script>
</body>
</html>