<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
    <link rel="stylesheet" href="../admin/css/dashboard.css">
    <link rel="stylesheet" href="../admin/css/orders.css">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <!-- Active Page Detection -->
    <script src="js/active-page.js"></script>
    <style>
        .status-buttons { display: inline-flex; gap: 5px; margin-left: 10px; }
        .status-btn { padding: 6px 14px; border: none; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; font-family: 'Poppins', sans-serif; }
        .status-btn.preparing { background: rgba(116,185,255,0.2); color: #74B9FF; }
        .status-btn.preparing:hover { background: rgba(116,185,255,0.3); }
        .status-btn.completed { background: rgba(0,184,148,0.2); color: #00B894; }
        .status-btn.completed:hover { background: rgba(0,184,148,0.3); }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .status-badge.completed { background: rgba(0,184,148,0.15); color: #00B894; }
        .status-badge i { font-size: 10px; }
        .action-buttons { display: flex; align-items: center; gap: 8px; }

        .payment-section { margin-top: 20px; padding: 20px; background: #1F1F1F; border: 1px solid #2E2E2E; border-radius: 12px; }
        .payment-info { display: flex; gap: 20px; margin-bottom: 20px; }
        .proof-of-payment { margin-top: 20px; text-align: center; }
        .proof-of-payment h4 { margin-bottom: 15px; color: #D4AF37; }
        .proof-image { max-width: 300px; margin: 0 auto; border: 1px solid #2E2E2E; border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.3s ease; }
        .proof-image:hover { transform: scale(1.02); }
        .proof-image img { width: 100%; height: auto; display: block; }
        .verify-btn { margin-top: 15px; padding: 10px 20px; background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; font-family: 'Poppins', sans-serif; }
        .verify-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-2px); }
        .verify-btn i { font-size: 16px; }
        .text-success { color: #00B894 !important; }
        .text-warning { color: #FDCB6E !important; }

        .payment-proof-modal {
            max-width: 90vw !important;
            max-height: 90vh !important;
        }

        .payment-proof-modal .swal2-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }

        .payment-status { padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .payment-status.pending { background: rgba(253,203,110,0.15); color: #FDCB6E; }
        .payment-status.verified { background: rgba(0,184,148,0.15); color: #00B894; }
        .payment-status.unpaid { background: rgba(220,53,69,0.15); color: #dc3545; }
        .payment-status i { font-size: 10px; }
        .filter-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-select { padding: 8px 14px; border: 1px solid #2E2E2E; border-radius: 10px; background: #1F1F1F; color: #B0B0B0; font-size: 13px; min-width: 150px; font-family: 'Poppins', sans-serif; outline: none; transition: border-color 0.3s; }
        .filter-select:focus { border-color: #D4AF37; }
        .mark-paid-btn { background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; margin-top: 10px; font-family: 'Poppins', sans-serif; transition: all 0.2s; }
        .mark-paid-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-1px); }
        .mark-paid-btn i { font-size: 12px; }
    </style>
</head>
<body>
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
                        </select>
                        <select id="orderTypeFilter" class="filter-select">
                            <option value="">All Order Types</option>
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
                            if (data.should_reload) {
                                // Force a hard reload of the page
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

    // Add markAsPaid function
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
                            loadOrders();
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
                            <td>${order.order_type}</td>
                            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td>${paymentStatusHtml}</td>
                            <td class="action-buttons">
                                <button class="action-btn view" data-order-id="${order.order_id}" data-action="view" title="View Order Details">
                                    <i class="fas fa-eye"></i>
                                    <span>View Details</span>
                                </button>
                                ${order.order_status === 'pending' ? 
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
                    let itemsHtml = '';
                    
                    if (order.items && order.items.length > 0) {
                        itemsHtml = order.items.map(item => `
                            <div class="order-item-card">
                                <div class="item-image">
                                    <img src="../${item.image_path}" alt="${item.product_name}" onerror="this.src='../images/default-product.png'">
                                </div>
                                <div class="item-details">
                                    <h4 class="item-name">${item.product_name}</h4>
                                    <div class="item-quantity">Quantity: ${item.quantity}</div>
                                    <div class="item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                                    <div class="item-total">Total: ₱${parseFloat(item.price * item.quantity).toFixed(2)}</div>
                                </div>
                            </div>
                        `).join('');
                    }

                    // Add payment section if payment exists
                    let paymentHtml = '';
                    if (order.payment_type) {
                        paymentHtml = `
                            <div class="payment-section">
                                <h3 class="section-title">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Payment Details
                                </h3>
                                <div class="payment-info">
                                    <div class="info-card">
                                        <div class="info-icon">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Payment Type</div>
                                            <div class="info-value">${order.payment_type.charAt(0).toUpperCase() + order.payment_type.slice(1)}</div>
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <div class="info-icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Payment Status</div>
                                            <div class="info-value ${order.payment_status === 'verified' ? 'text-success' : 'text-warning'}">
                                                ${order.payment_status ? order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1) : 'Pending'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ${order.payment_type === 'cash' && order.payment_status !== 'verified' ? `
                                    <div class="proof-of-payment">
                                        <button class="mark-paid-btn" onclick="markAsPaid(${order.order_id}, this)">
                                            <i class="fas fa-money-bill-wave"></i>
                                            Mark as Paid
                                        </button>
                                    </div>
                                ` : ''}
                                ${order.payment_type !== 'cash' && order.proof_of_payment ? `
                                    <div class="proof-of-payment">
                                        <h4>Proof of Payment</h4>
                                        <div class="proof-image">
                                            <img src="../${order.proof_of_payment}" alt="Proof of Payment" onclick="showFullImage(this.src)">
                                        </div>
                                        ${order.payment_status !== 'verified' ? `
                                            <button class="verify-btn" onclick="verifyPayment(${order.order_id}, this)">
                                                <i class="fas fa-check"></i>
                                                Verify Payment
                                            </button>
                                        ` : ''}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }
                    
                    Swal.fire({
                        title: `<div class="modal-header">
                            <div class="order-header-info">
                                <h2>Order #${order.order_id}</h2>
                                <div class="order-status ${order.order_status}">
                                    <i class="fas ${order.order_status === 'pending' ? 'fa-clock' : 
                                                     order.order_status === 'preparing' ? 'fa-utensils' : 
                                                     'fa-check-circle'}"></i>
                                    ${order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1)}
                                </div>
                            </div>
                            <div class="order-date">${new Date(order.created_at).toLocaleString()}</div>
                        </div>`,
                        html: `
                            <div class="order-details">
                                <div class="order-info-section">
                                    <div class="info-card">
                                        <div class="info-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Customer</div>
                                            <div class="info-value">${order.customer_name}</div>
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <div class="info-icon">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                        <div class="info-content">
                                            <div class="info-label">Order Type</div>
                                            <div class="info-value">${order.order_type}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="order-items-section">
                                    <h3 class="section-title">
                                        <i class="fas fa-list"></i>
                                        Order Items
                                    </h3>
                                    <div class="order-items-grid">
                                        ${itemsHtml}
                                    </div>
                                    <div class="order-total">
                                        <div class="total-label">Total Amount</div>
                                        <div class="total-value">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                    </div>
                                </div>

                                ${paymentHtml}
                            </div>
                        `,
                        width: '900px',
                        showCloseButton: true,
                        showConfirmButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Close',
                        denyButtonText: 'Delete Order',
                        confirmButtonColor: '#D4AF37',
                        denyButtonColor: '#dc3545',
                        customClass: {
                            popup: 'order-details-modal',
                            title: 'modal-title',
                            htmlContainer: 'modal-content'
                        }
                    }).then((result) => {
                        if (result.isDenied) {
                            // Show confirmation dialog for deletion
                            Swal.fire({
                                title: 'Delete Order?',
                                text: 'This action cannot be undone. Are you sure you want to delete this order?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#dc3545',
                                cancelButtonColor: '#2E2E2E',
                                confirmButtonText: 'Yes, delete it!',
                                cancelButtonText: 'Cancel'
                            }).then((deleteResult) => {
                                if (deleteResult.isConfirmed) {
                                    // Delete the order
                                    fetch('../backend/order_functions.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                        },
                                        body: `action=delete_order&order_id=${orderId}`
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                title: 'Deleted!',
                                                text: 'The order has been deleted successfully.',
                                                icon: 'success',
                                                confirmButtonColor: '#D4AF37'
                                            }).then(() => {
                                                // Reload the orders table
                                                loadOrders();
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Error',
                                                text: data.message || 'Failed to delete the order',
                                                icon: 'error',
                                                confirmButtonColor: '#D4AF37'
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        Swal.fire({
                                            title: 'Error',
                                            text: 'An error occurred while deleting the order',
                                            icon: 'error',
                                            confirmButtonColor: '#D4AF37'
                                        });
                                    });
                                }
                            });
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