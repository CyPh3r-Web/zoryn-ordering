<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
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
        .action-buttons { display: flex; align-items: center; gap: 8px; }

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

        .payment-proof-modal { max-width: 90vw !important; max-height: 90vh !important; }
        .payment-proof-modal .swal2-image { max-width: 100%; max-height: 80vh; object-fit: contain; }

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
<body>
    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    
    <div class="main-content">
        <div class="orders-container">
            <div class="page-header">
                <h1><i class="fas fa-receipt"></i>Orders Management</h1>
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
            
            <!-- Orders Table -->
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Total</th>
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
                if (!result.isConfirmed) return;
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=mark_as_paid&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (buttonElement && buttonElement.parentNode) {
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
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while marking the order as paid',
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
                            
                            row.innerHTML = `
                                <td>${order.customer_name}</td>
                                <td>${new Date(order.created_at).toLocaleString()}</td>
                                <td>${renderTypeBadge(order.order_type, order.table_number)}</td>
                                <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
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

                        const paymentTypeRaw = (order.payment_type || '').trim();
                        const pt = paymentTypeRaw.toLowerCase() || 'cash';
                        const displayTypeLabel = paymentTypeRaw
                            ? paymentTypeRaw.charAt(0).toUpperCase() + paymentTypeRaw.slice(1)
                            : 'Cash';
                        const payStatusLabel = order.payment_status
                            ? order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)
                            : 'Pending';
                        const payStatusCss = order.payment_status === 'verified' ? 'text-success' : 'text-warning';
                        const canMarkPayment = order.order_status !== 'cancelled' && order.payment_status !== 'verified';

                        const cashAction = canMarkPayment && (pt === 'cash' || !paymentTypeRaw) ? `
                            <div class="proof-of-payment">
                                <button type="button" class="mark-paid-btn" onclick="markAsPaid(${order.order_id}, this)">
                                    <i class="fas fa-money-bill-wave"></i> Mark as Paid
                                </button>
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