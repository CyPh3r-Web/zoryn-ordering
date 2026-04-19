<?php
session_start();
// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Orders tracking page is cashier-only. Waiters and everyone else are redirected.
if (($_SESSION['role'] ?? '') !== 'cashier') {
    header("Location: order-details.php");
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
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <script src="js/active-page.js"></script>
    <style>
        /* ---------- Base (self-contained; immune to Tailwind Preflight reset) ---------- */
        body.zoryn-orders-page {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(212,175,55,0.16), transparent 42%),
                radial-gradient(circle at 88% 0%, rgba(212,175,55,0.10), transparent 38%),
                linear-gradient(145deg, #0D0D0D 0%, #1a1204 38%, #0D0D0D 100%);
            color: #fff;
            min-height: 100vh;
        }
        body.zoryn-orders-page * { box-sizing: border-box; }
        .zoryn-orders-page .main-content {
            margin-left: 260px;
            padding: 96px 24px 32px;
            transition: margin-left 0.3s ease;
        }
        .zoryn-orders-page .main-content.expanded { margin-left: 0; }
        @media (max-width: 1024px) {
            .zoryn-orders-page .main-content { margin-left: 0; padding: 88px 16px 24px; }
        }

        /* ---------- Container / header / filters ---------- */
        .zoryn-orders-page .orders-container { max-width: 1400px; margin: 0 auto; }
        .zoryn-orders-page .orders-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; padding: 22px 26px; flex-wrap: wrap; gap: 16px;
            background: rgba(31, 31, 31, 0.85);
            border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .zoryn-orders-page .orders-header h1 {
            font-size: 1.5rem; font-weight: 700;
            color: #D4AF37; margin: 0; letter-spacing: 0.3px;
        }
        .zoryn-orders-page .orders-filter { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .zoryn-orders-page .filter-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .zoryn-orders-page input[type="date"],
        .zoryn-orders-page .filter-select {
            padding: 9px 14px;
            border: 1px solid #2E2E2E;
            border-radius: 10px;
            background: #1F1F1F;
            color: #E5E5E5;
            font-size: 13px;
            min-width: 150px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }
        .zoryn-orders-page input[type="date"] { min-width: 160px; color-scheme: dark; }
        .zoryn-orders-page input[type="date"]:focus,
        .zoryn-orders-page .filter-select:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.18);
        }

        /* ---------- Table ---------- */
        .zoryn-orders-page .orders-table-container {
            background: rgba(31, 31, 31, 0.85);
            border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .zoryn-orders-page .orders-table { width: 100%; border-collapse: collapse; }
        .zoryn-orders-page .orders-table thead th {
            background: #161616;
            color: #F5D76E;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #2E2E2E;
        }
        .zoryn-orders-page .orders-table tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid #2E2E2E;
            color: #D1D1D1;
            font-size: 14px;
            vertical-align: middle;
        }
        .zoryn-orders-page .orders-table tbody tr:hover td { background: rgba(212,175,55,0.05); color: #fff; }
        .zoryn-orders-page .orders-table tbody tr:last-child td { border-bottom: none; }
        .zoryn-orders-page .orders-table tbody tr.no-orders td {
            text-align: center; color: #7a7a7a; padding: 40px 16px; font-style: italic;
        }

        /* ---------- Buttons / badges / actions ---------- */
        .zoryn-orders-page .action-buttons { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .zoryn-orders-page .action-btn {
            padding: 7px 13px; border-radius: 9px; font-size: 12px; cursor: pointer; border: none;
            font-weight: 600; transition: all 0.2s; font-family: 'Poppins', sans-serif;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .zoryn-orders-page .action-btn.view { background: rgba(212,175,55,0.15); color: #F5D76E; border: 1px solid rgba(212,175,55,0.25); }
        .zoryn-orders-page .action-btn.view:hover { background: rgba(212,175,55,0.25); transform: translateY(-1px); }

        .zoryn-orders-page .status-btn {
            padding: 7px 14px; border: none; border-radius: 9px; cursor: pointer;
            transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; font-family: 'Poppins', sans-serif;
        }
        .zoryn-orders-page .status-btn.preparing { background: rgba(116,185,255,0.18); color: #8fcbff; border: 1px solid rgba(116,185,255,0.28); }
        .zoryn-orders-page .status-btn.preparing:hover { background: rgba(116,185,255,0.28); transform: translateY(-1px); }
        .zoryn-orders-page .status-btn.completed { background: rgba(0,184,148,0.18); color: #78ebca; border: 1px solid rgba(0,184,148,0.28); }
        .zoryn-orders-page .status-btn.completed:hover { background: rgba(0,184,148,0.28); transform: translateY(-1px); }

        .zoryn-orders-page .status-badge {
            padding: 5px 12px; border-radius: 9999px; font-size: 11px;
            font-weight: 600; display: inline-flex; align-items: center; gap: 5px;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .zoryn-orders-page .status-badge.completed { background: rgba(0,184,148,0.15); color: #78ebca; }
        .zoryn-orders-page .status-badge.cancelled {
            background: rgba(220,53,69,0.15); color: #ff8b92;
        }
        .zoryn-orders-page .status-badge i { font-size: 10px; }

        /* Order-type badges */
        .zoryn-orders-page .type-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 9999px;
            font-size: 11px; font-weight: 600; letter-spacing: 0.3px;
            text-transform: uppercase; white-space: nowrap;
        }
        .zoryn-orders-page .type-badge i { font-size: 10px; }
        .zoryn-orders-page .type-badge.dine-in       { background: rgba(212,175,55,0.14); color: #f4d26b; border: 1px solid rgba(212,175,55,0.28); }
        .zoryn-orders-page .type-badge.take-out      { background: rgba(116,185,255,0.15); color: #8fcbff; border: 1px solid rgba(116,185,255,0.28); }
        .zoryn-orders-page .type-badge.walk-in       { background: rgba(0,184,148,0.14); color: #78ebca; border: 1px solid rgba(0,184,148,0.28); }
        .zoryn-orders-page .type-badge.account-order { background: rgba(162,155,254,0.16); color: #c5bdff; border: 1px solid rgba(162,155,254,0.28); }
        .zoryn-orders-page .type-badge .table-tag {
            margin-left: 6px; padding: 1px 6px; border-radius: 6px;
            background: rgba(0,0,0,0.35); color: #fff; font-size: 10px; letter-spacing: 0.5px;
        }

        /* Payment badges */
        .zoryn-orders-page .payment-status {
            padding: 5px 12px; border-radius: 9999px; font-size: 11px;
            font-weight: 600; display: inline-flex; align-items: center; gap: 5px;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .zoryn-orders-page .payment-status.pending  { background: rgba(253,203,110,0.15); color: #FDCB6E; }
        .zoryn-orders-page .payment-status.verified { background: rgba(0,184,148,0.15);   color: #78ebca; }
        .zoryn-orders-page .payment-status.unpaid   { background: rgba(220,53,69,0.15);   color: #ff8b92; }
        .zoryn-orders-page .payment-status i { font-size: 10px; }

        /* Mark-as-paid button + verify + image proof */
        .zoryn-orders-page .mark-paid-btn {
            background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D; border: none;
            padding: 8px 14px; border-radius: 9px; cursor: pointer; display: inline-flex;
            align-items: center; gap: 6px; font-size: 12px; font-weight: 700;
            margin-top: 10px; font-family: 'Poppins', sans-serif; transition: all 0.2s;
        }
        .zoryn-orders-page .mark-paid-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-1px); }

        .zoryn-orders-page .verify-btn {
            margin-top: 15px; padding: 10px 20px;
            background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D;
            border: none; border-radius: 10px; cursor: pointer; font-weight: 700;
            transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;
            font-family: 'Poppins', sans-serif;
        }
        .zoryn-orders-page .verify-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-2px); }

        /* Swal overrides (scoped) */
        .payment-proof-modal { max-width: 90vw !important; max-height: 90vh !important; }
        .payment-proof-modal .swal2-image { max-width: 100%; max-height: 80vh; object-fit: contain; }

        /* Details modal helpers */
        .zoryn-orders-page .text-success { color: #78ebca !important; }
        .zoryn-orders-page .text-warning { color: #FDCB6E !important; }

        /* ==========================================================
         * Shared "View Order" modal — same look as admin/orders.php
         * These rules are NOT scoped to .zoryn-orders-page because
         * the Swal popup is portaled to <body> outside the page scope.
         * ========================================================== */
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
        .product-modal-title { color: #f8e7a4 !important; font-size: 1.1rem !important; font-weight: 700 !important; letter-spacing: 0.01em; }
        .product-modal-content { margin: 0 !important; padding-top: 0.5rem !important; }
        .swal-modern-product-confirm,
        .swal-modern-product-deny { border-radius: 12px !important; padding: 0.75rem 1.35rem !important; font-weight: 700 !important; }
        .swal-modern-product-confirm { color: #111 !important; box-shadow: 0 10px 24px rgba(212, 175, 55, 0.22) !important; }
        .swal-modern-product-deny    { box-shadow: 0 10px 24px rgba(220, 53, 69, 0.20) !important; }
        .swal-modern-product-close   { color: #d7b75b !important; }

        .product-view-modal { display: flex; flex-direction: column; gap: 18px; text-align: left; }

        .product-modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding-bottom: 6px; }
        .product-modal-header-info h2 { margin: 0; color: #fff; font-size: 1.45rem; font-weight: 700; }
        .product-modal-subtitle { color: #9ca3af; font-size: 13px; margin-top: 4px; }
        .product-modal-status {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 6px; padding: 5px 12px; border-radius: 999px;
            font-size: 11px; font-weight: 700; text-transform: capitalize; margin-top: 10px;
        }
        .product-modal-status.pending   { background: rgba(255,193,7,0.16);  color: #ffd666; }
        .product-modal-status.preparing { background: rgba(116,185,255,0.18); color: #8fcbff; }
        .product-modal-status.completed { background: rgba(0,184,148,0.18);   color: #78ebca; }
        .product-modal-status.cancelled  { background: rgba(220,53,69,0.18);   color: #ff8b92; }

        .product-info-section { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .product-info-card,
        .product-section-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.12);
            border-radius: 18px;
            padding: 16px;
        }
        .product-info-card { display: flex; align-items: center; gap: 14px; }
        .product-info-icon {
            width: 46px; height: 46px; border-radius: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.18);
            color: #f4d26b; font-weight: 700; flex-shrink: 0;
        }
        .product-info-content { min-width: 0; }
        .product-info-label,
        .product-section-title {
            color: #9d9d9d; font-size: 11px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .product-info-value { color: #fff; font-size: 14px; font-weight: 600; margin-top: 6px; word-break: break-word; }
        .product-section-title { margin: 0 0 12px; }

        .order-items-list { display: flex; flex-direction: column; gap: 12px; }
        .order-item-card {
            display: flex; align-items: center; gap: 14px; padding: 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(212, 175, 55, 0.1);
            position: relative;
        }
        .order-item-remove-btn {
            flex-shrink: 0; align-self: flex-start;
            width: 36px; height: 36px; border-radius: 10px; border: 1px solid rgba(220,53,69,0.35);
            background: rgba(220,53,69,0.12); color: #ff8b92; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            transition: background 0.2s, transform 0.2s;
        }
        .order-item-remove-btn:hover { background: rgba(220,53,69,0.22); transform: translateY(-1px); }
        .order-item-image {
            width: 74px; height: 74px; border-radius: 16px;
            overflow: hidden; flex-shrink: 0;
            background: linear-gradient(180deg, #222, #141414);
            border: 1px solid rgba(212, 175, 55, 0.12);
        }
        .order-item-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .order-item-details { flex: 1; min-width: 0; }
        .order-item-name { margin: 0 0 6px; color: #fff; font-size: 15px; font-weight: 600; }
        .order-item-meta { display: flex; flex-wrap: wrap; gap: 8px; }
        .order-item-pill {
            display: inline-flex; align-items: center;
            padding: 6px 10px; border-radius: 999px;
            background: rgba(212, 175, 55, 0.1);
            color: #f6e2a2; font-size: 12px; font-weight: 600;
        }
        .order-total-bar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-top: 16px; padding-top: 16px;
            border-top: 1px solid rgba(212, 175, 55, 0.12);
        }
        .order-total-label { color: #9d9d9d; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
        .order-total-value { color: #f4d26b; font-size: 1.2rem; font-weight: 700; }

        /* Modal-scoped payment extras (user side only) */
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

        .view-empty-note { color: #9d9d9d; font-size: 13px; }

        @media (max-width: 680px) {
            .product-modal-header    { flex-direction: column; }
            .product-info-section    { grid-template-columns: 1fr; }
            .order-item-card         { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body class="zoryn-orders-page">
    <?php include("../navigation/navbar.php");?>
    <?php include("../navigation/cashier-sidebar.php");?>
    <div class="main-content">
        <div class="orders-container">
            <div class="orders-header">
                <h1>Orders Management</h1>
                <div class="orders-filter">
                    <div class="filter-group">
                        <input type="date" id="orderDateFilter" placeholder="Filter by date">
                        <select id="paymentStatusFilter" class="filter-select">
                            <option value="">All Payment Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Paid</option>
                        </select>
                        <select id="orderStatusFilter" class="filter-select">
                            <option value="">All Order Status</option>
                            <option value="pending">Pending</option>
                            <option value="preparing">Preparing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select id="orderTypeFilter" class="filter-select">
                            <option value="">All Order Types</option>
                            <option value="dine-in">Dine-in</option>
                            <option value="take-out">Take-out</option>
                            <option value="walk-in">Walk-in</option>
                            <option value="account-order">Account Order</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Payment Status</th>
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
    // Move these functions outside of DOMContentLoaded
    window.verifyPayment = function(orderId, buttonElement) {
        Swal.fire({
            title: 'Verify Payment',
            text: 'Are you sure you want to verify this payment?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#D4AF37',
            cancelButtonColor: '#2E2E2E',
            confirmButtonText: 'Yes, verify it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=verify_payment&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        Swal.fire({
                            title: 'Verified!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#D4AF37',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            if (typeof window.loadOrders === 'function') {
                                window.loadOrders();
                            } else if (data.should_reload) {
                                window.location.href = window.location.href.split('#')[0] + '?t=' + new Date().getTime();
                            }
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
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while verifying payment',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }
        });
    }

    window.showFullImage = function(src) {
        Swal.fire({
            imageUrl: src,
            imageAlt: 'Proof of Payment',
            width: 'auto',
            padding: '1em',
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {
                popup: 'payment-proof-modal'
            }
        });
    }

    function canEditOrderLines(orderStatus) {
        const s = (orderStatus || '').toLowerCase();
        return s === 'pending' || s === 'preparing';
    }

    window.removeOrderLine = function(orderId, orderItemId) {
        Swal.fire({
            title: 'Remove this item?',
            text: 'It will be taken off the order and stock will be restored.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#2E2E2E',
            confirmButtonText: 'Yes, remove it'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=remove_order_item&order_id=${orderId}&order_item_id=${orderItemId}`
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

    window.cancelWholeOrder = function(orderId) {
        Swal.fire({
            title: 'Cancel this order?',
            text: 'The full order will be marked cancelled and ingredients will be restocked.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#2E2E2E',
            confirmButtonText: 'Yes, cancel order'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=cancel_order&order_id=${orderId}`
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

    window.markAsPaid = function(orderId, buttonElement) {
        Swal.fire({
            title: 'Mark as Paid',
            text: 'Are you sure you want to mark this order as paid?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#D4AF37',
            cancelButtonColor: '#2E2E2E',
            confirmButtonText: 'Yes, mark as paid!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_as_paid&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the payment status in the UI
                        const paymentInfo = buttonElement.closest('.payment-info');
                        if (paymentInfo) {
                            const statusElement = paymentInfo.querySelector('.info-value');
                            if (statusElement) {
                                statusElement.textContent = 'Paid';
                                statusElement.className = 'info-value text-success';
                            }
                        }
                        
                        // Remove the mark as paid button
                        if (buttonElement.parentNode) {
                            buttonElement.parentNode.removeChild(buttonElement);
                        }
                        
                        Swal.fire({
                            title: 'Success!',
                            text: 'Order has been marked as paid',
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        }).then(() => {
                            if (typeof window.loadOrders === 'function') window.loadOrders();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to mark order as paid',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while marking the order as paid',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }
        });
    }

    // Normalize order_type into label / icon / css class
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

    document.addEventListener('DOMContentLoaded', function() {
        // Load initial data
        loadOrders();
        
        // Add auto-reload functionality
        let autoReloadInterval = setInterval(loadOrders, 3000); // Reload every 3 seconds
        
        // Add event listeners for all filters
        const filters = ['orderDateFilter', 'paymentStatusFilter', 'orderStatusFilter', 'orderTypeFilter'];
        filters.forEach(filterId => {
            document.getElementById(filterId).addEventListener('change', function() {
                // Clear existing interval and start a new one when filters change
                clearInterval(autoReloadInterval);
                loadOrders();
                autoReloadInterval = setInterval(loadOrders, 3000);
            });
        });

        // Pause auto-reload when modal is open
        document.addEventListener('click', function(e) {
            if (e.target.closest('.action-btn')) {
                clearInterval(autoReloadInterval);
            }
        });

        // Resume auto-reload when modal is closed
        document.addEventListener('swal:close', function() {
            autoReloadInterval = setInterval(loadOrders, 3000);
        });
        
        // Update loadOrders function to handle all filters
        function loadOrders() {
            const dateFilter = document.getElementById('orderDateFilter').value;
            const paymentStatus = document.getElementById('paymentStatusFilter').value;
            const orderStatus = document.getElementById('orderStatusFilter').value;
            const orderType = document.getElementById('orderTypeFilter').value;
            
            const params = new URLSearchParams({
                action: 'get_orders',
                date: dateFilter,
                payment_status: paymentStatus,
                order_status: orderStatus,
                order_type: orderType
            });

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
                        // Determine payment status display
                        let paymentStatusHtml = '';
                        if (order.payment_status === 'verified') {
                            paymentStatusHtml = `
                                <span class="payment-status verified">
                                    <i class="fas fa-check-circle"></i>
                                    Paid
                                </span>
                            `;
                        } else if (order.payment_status === 'pending') {
                            paymentStatusHtml = `
                                <span class="payment-status pending">
                                    <i class="fas fa-clock"></i>
                                    Pending
                                </span>
                            `;
                        } else {
                            paymentStatusHtml = `
                                <span class="payment-status unpaid">
                                    <i class="fas fa-times-circle"></i>
                                    Unpaid
                                </span>
                            `;
                        }

                        const row = document.createElement('tr');
                        row.className = `order-row`;
                        
                        row.innerHTML = `
                            <td>${order.customer_name}</td>
                            <td>${new Date(order.created_at).toLocaleString()}</td>
                            <td>${renderTypeBadge(order.order_type, order.table_number)}</td>
                            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td>${paymentStatusHtml}</td>
                            <td class="action-buttons">
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

                    const itemsHtml = order.items && order.items.length > 0
                        ? order.items.map(item => `
                            <div class="order-item-card">
                                <div class="order-item-image">
                                    <img src="../${item.image_path || 'assets/images/products/default.jpg'}" alt="${item.product_name}" onerror="this.onerror=null;this.src='../assets/images/products/default.jpg';">
                                </div>
                                <div class="order-item-details">
                                    <h4 class="order-item-name">${item.product_name}</h4>
                                    <div class="order-item-meta">
                                        <span class="order-item-pill">Qty: ${item.quantity}</span>
                                        <span class="order-item-pill">Price: ₱${parseFloat(item.price).toFixed(2)}</span>
                                        <span class="order-item-pill">Subtotal: ₱${parseFloat(item.price * item.quantity).toFixed(2)}</span>
                                    </div>
                                </div>
                                ${canEditOrderLines(order.order_status) ? `
                                <button type="button" class="order-item-remove-btn" onclick="removeOrderLine(${order.order_id}, ${item.order_item_id})" title="Remove this line">
                                    <i class="fas fa-times"></i>
                                </button>` : ''}
                            </div>
                        `).join('')
                        : '<span class="view-empty-note">No order items found.</span>';

                    // Payment section (always show; NULL/empty payment_type treated as cash at counter)
                    const paymentTypeRaw = (order.payment_type || '').trim();
                    const pt = paymentTypeRaw.toLowerCase() || 'cash';
                    const displayTypeLabel = paymentTypeRaw
                        ? paymentTypeRaw.charAt(0).toUpperCase() + paymentTypeRaw.slice(1)
                        : 'Cash';
                    const paymentStatusLabel = order.payment_status
                        ? order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)
                        : 'Pending';
                    const statusCssExtra = order.payment_status === 'verified' ? 'text-success' : 'text-warning';
                    const canMarkPayment = order.order_status !== 'cancelled' && order.payment_status !== 'verified';

                    const cashAction = canMarkPayment && (pt === 'cash' || !paymentTypeRaw) ? `
                            <div class="proof-of-payment">
                                <button class="mark-paid-btn" onclick="markAsPaid(${order.order_id}, this)">
                                    <i class="fas fa-money-bill-wave"></i> Mark as Paid
                                </button>
                            </div>` : '';

                    const onlineProof = canMarkPayment && pt !== 'cash' && order.proof_of_payment ? `
                            <div class="proof-of-payment">
                                <h4>Proof of Payment</h4>
                                <div class="proof-image">
                                    <img src="../${order.proof_of_payment}" alt="Proof of Payment" onclick="showFullImage(this.src)">
                                </div>
                                <button class="verify-btn" onclick="verifyPayment(${order.order_id}, this)">
                                    <i class="fas fa-check"></i> Verify Payment
                                </button>
                            </div>` : '';

                    const onlinePendingNoProof = canMarkPayment && pt !== 'cash' && !order.proof_of_payment ? `
                            <div class="proof-of-payment">
                                <p class="view-empty-note">Awaiting payment proof upload from the customer.</p>
                            </div>` : '';

                    const paymentHtml = `
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
                                            <div class="product-info-value ${statusCssExtra}">${paymentStatusLabel}</div>
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
                                        <div class="product-info-icon"><i class="fas fa-user"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Customer</div>
                                            <div class="product-info-value">${order.customer_name}</div>
                                        </div>
                                    </div>
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas ${orderTypeMeta(order.order_type).icon}"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Order Type</div>
                                            <div class="product-info-value">${renderTypeBadge(order.order_type, null)}</div>
                                        </div>
                                    </div>
                                    ${order.table_number ? `
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-chair"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Table Number</div>
                                            <div class="product-info-value">${order.table_number}</div>
                                        </div>
                                    </div>` : ''}
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-receipt"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Items</div>
                                            <div class="product-info-value">${order.item_count || (order.items ? order.items.length : 0)} item(s)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="product-section-card">
                                    <h3 class="product-section-title">Order Items</h3>
                                    <div class="order-items-list">${itemsHtml}</div>
                                    <div class="order-total-bar">
                                        <div class="order-total-label">Total Amount</div>
                                        <div class="order-total-value">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                    </div>
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