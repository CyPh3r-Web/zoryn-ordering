<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - My Orders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/orders.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <style>
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .status-badge.pending { background: rgba(253,203,110,0.15); color: #FDCB6E; }
        .status-badge.preparing { background: rgba(116,185,255,0.15); color: #74B9FF; }
        .status-badge.completed { background: rgba(0,184,148,0.15); color: #00B894; }
        .status-badge i { font-size: 10px; }
        .action-btn { padding: 6px 12px; border: none; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; font-family: 'Poppins', sans-serif; background: rgba(212,175,55,0.15); color: #D4AF37; }
        .action-btn:hover { background: rgba(212,175,55,0.25); }
        .action-btn.feedback { background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D; font-weight: 600; }
        .action-btn.feedback:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); }
        .action-btn.feedback.disabled { background: #2E2E2E; color: #666; cursor: not-allowed; }
        
        .order-details-modal {
            max-width: 900px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .order-header-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            color: white;
        }
        
        .order-status.pending {
            background-color: #ffc107;
        }
        
        .order-status.preparing {
            background-color: #0d6efd;
        }
        
        .order-status.completed {
            background-color: #198754;
        }
        
        .order-date {
            color: #6c757d;
            font-size: 14px;
        }
        
        .order-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .order-info-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #634832;
            color: white;
            border-radius: 8px;
            font-size: 18px;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #212529;
        }
        
        .order-items-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #212529;
        }
        
        .order-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .order-item-card {
            display: flex;
            gap: 12px;
            padding: 12px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .item-name {
            font-size: 14px;
            font-weight: 500;
            color: #212529;
            margin: 0;
        }
        
        .item-quantity {
            font-size: 13px;
            color: #6c757d;
        }
        
        .item-price {
            font-size: 13px;
            color: #198754;
            font-weight: 500;
        }
        
        .item-total {
            font-size: 13px;
            color: #212529;
            font-weight: 500;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .total-label {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
        }
        
        .total-value {
            font-size: 18px;
            font-weight: 700;
            color: #198754;
        }
        
        .feedback-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .feedback-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .rating-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .rating-label {
            font-size: 14px;
            font-weight: 500;
            color: #212529;
        }
        
        .rating-stars {
            display: flex;
            gap: 5px;
        }
        
        .rating-star {
            font-size: 24px;
            color: #dee2e6;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .rating-star.active {
            color: #ffc107;
        }
        
        .feedback-comment {
            width: 100%;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .feedback-submit {
            align-self: flex-end;
            padding: 8px 20px;
            background-color: #634832;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .feedback-submit:hover {
            background-color: #4a3525;
        }
    </style>
</head>
<body>
    <?php include("../navigation/user-navbar.php");?>
    <?php include("../navigation/user-sidebar.php");?>
    
    <div class="main-content">
        <div class="orders-container">
            <div class="orders-header">
                <h1>My Orders</h1>
                <div class="orders-filter">
                    <input type="date" id="orderDateFilter" placeholder="Filter by date">
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Payment Type</th>
                            <th>Status</th>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data
            loadOrders();
            
            // Date filter handler
            document.getElementById('orderDateFilter').addEventListener('change', function() {
                loadOrders(this.value);
            });
            
            // Function to load orders
            function loadOrders(dateFilter = null) {
                fetch('../backend/order_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_user_orders${dateFilter ? '&date=' + dateFilter : ''}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('ordersTableBody');
                        tbody.innerHTML = '';
                        
                        data.orders.forEach(order => {
                            const row = document.createElement('tr');
                            row.className = `order-row`;
                            
                            // Get status badge class
                            const statusClass = order.order_status.toLowerCase();
                            const statusIcon = order.order_status === 'pending' ? 'fa-clock' : 
                                             order.order_status === 'preparing' ? 'fa-utensils' : 
                                             'fa-check-circle';
                            
                            const paymentTypeRaw = (order.payment_type || '').trim();
                            const paymentTypeLabel = paymentTypeRaw
                                ? paymentTypeRaw.charAt(0).toUpperCase() + paymentTypeRaw.slice(1)
                                : 'Cash';

                            row.innerHTML = `
                                <td>#${order.order_id}</td>
                                <td>${new Date(order.created_at).toLocaleString()}</td>
                                <td>${order.order_type}</td>
                                <td>${paymentTypeLabel}</td>
                                <td>
                                    <span class="status-badge ${statusClass}">
                                        <i class="fas ${statusIcon}"></i>
                                        <span>${order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1)}</span>
                                    </span>
                                </td>
                                <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                                <td class="action-buttons">
                                    <button class="action-btn view" data-order-id="${order.order_id}" title="View Order Details">
                                        <i class="fas fa-eye"></i>
                                        <span>View Details</span>
                                    </button>
                                    ${order.order_status === 'completed' ? 
                                        `<button class="action-btn feedback ${order.has_feedback ? 'disabled' : ''}" 
                                         data-order-id="${order.order_id}" 
                                         ${order.has_feedback ? 'disabled' : ''}
                                         title="${order.has_feedback ? 'Feedback already submitted' : 'Submit Feedback'}">
                                            <i class="fas ${order.has_feedback ? 'fa-check' : 'fa-star'}"></i>
                                            <span>${order.has_feedback ? 'Feedback Submitted' : 'Give Feedback'}</span>
                                        </button>` : ''
                                    }
                                </td>
                            `;
                            
                            tbody.appendChild(row);
                            
                            // Add event listeners
                            row.querySelector('.action-btn.view').addEventListener('click', function() {
                                const orderId = this.getAttribute('data-order-id');
                                viewOrderDetails(orderId);
                            });
                            
                            const feedbackBtn = row.querySelector('.action-btn.feedback');
                            if (feedbackBtn && !feedbackBtn.disabled) {
                                feedbackBtn.addEventListener('click', function() {
                                    const orderId = this.getAttribute('data-order-id');
                                    showFeedbackForm(orderId);
                                });
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading orders:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to load orders',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
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
                        
                        // Get status badge class and icon
                        const statusClass = order.order_status.toLowerCase();
                        const statusIcon = order.order_status === 'pending' ? 'fa-clock' : 
                                         order.order_status === 'preparing' ? 'fa-utensils' : 
                                         'fa-check-circle';
                        
                        Swal.fire({
                            title: `<div class="modal-header">
                                <div class="order-header-info">
                                    <h2>Order #${order.order_id}</h2>
                                    <div class="order-status ${statusClass}">
                                        <i class="fas ${statusIcon}"></i>
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
                                </div>
                            `,
                            width: '900px',
                            showCloseButton: true,
                            showConfirmButton: true,
                            confirmButtonText: 'Close',
                            confirmButtonColor: '#D4AF37',
                            customClass: {
                                popup: 'order-details-modal',
                                title: 'modal-title',
                                htmlContainer: 'modal-content'
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
            
            // Function to show feedback form
            function showFeedbackForm(orderId) {
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
                                <div class="rating-group">
                                    <div class="rating-label">${item.product_name}</div>
                                    <div class="rating-stars" data-product-id="${item.product_id}">
                                        ${Array(5).fill().map((_, i) => `
                                            <i class="fas fa-star rating-star" data-rating="${i + 1}"></i>
                                        `).join('')}
                                    </div>
                                </div>
                            `).join('');
                        }
                        
                        Swal.fire({
                            title: 'Order Feedback',
                            html: `
                                <div class="feedback-form">
                                    <div class="rating-section">
                                        <h3>Rate your products</h3>
                                        ${itemsHtml}
                                    </div>
                                    <div class="comment-section">
                                        <h3>Additional Comments</h3>
                                        <textarea class="feedback-comment" placeholder="Share your experience with us..."></textarea>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Submit Feedback',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#D4AF37',
                            cancelButtonColor: '#2E2E2E',
                            showCloseButton: true,
                            customClass: {
                                popup: 'feedback-modal',
                                confirmButton: 'feedback-submit'
                            },
                            didOpen: () => {
                                // Add event listeners for star ratings
                                document.querySelectorAll('.rating-stars').forEach(starsContainer => {
                                    const stars = starsContainer.querySelectorAll('.rating-star');
                                    stars.forEach(star => {
                                        star.addEventListener('click', function() {
                                            const rating = parseInt(this.getAttribute('data-rating'));
                                            const productId = starsContainer.getAttribute('data-product-id');
                                            
                                            // Update stars
                                            stars.forEach(s => {
                                                const sRating = parseInt(s.getAttribute('data-rating'));
                                                s.classList.toggle('active', sRating <= rating);
                                            });
                                            
                                            // Store rating
                                            if (!window.ratings) window.ratings = {};
                                            window.ratings[productId] = rating;
                                        });
                                    });
                                });
                            },
                            preConfirm: () => {
                                const comment = document.querySelector('.feedback-comment').value;
                                const ratings = window.ratings || {};
                                
                                // Validate ratings
                                const allProductsRated = order.items.every(item => ratings[item.product_id]);
                                if (!allProductsRated) {
                                    Swal.showValidationMessage('Please rate all products');
                                    return false;
                                }
                                
                                return { ratings, comment };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const { ratings, comment } = result.value;
                                
                                // Submit feedback
                                fetch('../backend/order_functions.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `action=save_feedback&order_id=${orderId}&ratings=${JSON.stringify(ratings)}&comment=${encodeURIComponent(comment)}`
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Thank You!',
                                            text: 'Your feedback has been submitted successfully.',
                                            icon: 'success',
                                            confirmButtonColor: '#D4AF37'
                                        }).then(() => {
                                            // Reload orders to update feedback status
                                            loadOrders();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: data.message || 'Failed to submit feedback',
                                            icon: 'error',
                                            confirmButtonColor: '#D4AF37'
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error submitting feedback:', error);
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'An error occurred while submitting feedback',
                                        icon: 'error',
                                        confirmButtonColor: '#D4AF37'
                                    });
                                });
                            }
                            
                            // Clear ratings
                            window.ratings = null;
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
        });
    </script>
</body>
</html> 