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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <!-- Active Page Detection -->
    <script src="js/active-page.js"></script>
    <style>
        .status-buttons {
            display: inline-flex;
            gap: 5px;
            margin-left: 10px;
        }
        
        .status-btn {
            padding: 5px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
            font-size: 14px;
        }
        
        .status-btn.preparing {
            background-color: #17a2b8;
            color: #fff;
        }
        
        .status-btn.completed {
            background-color: #28a745;
            color: #fff;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .status-badge.completed {
            background-color: #28a745;
            color: #fff;
        }
        
        .status-btn:hover {
            opacity: 0.9;
        }
        
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .action-btn.view {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .action-btn.view:hover {
            background-color: #dee2e6;
        }
        
        .status-btn {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            color: white;
        }
        
        .status-btn.preparing {
            background-color: #0d6efd;
        }
        
        .status-btn.completed {
            background-color: #198754;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: white;
        }
        
        .status-badge.completed {
            background-color: #198754;
        }
        
        .status-badge i {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    
    <div class="main-content">
        <div class="orders-container">
            <div class="orders-header">
                <h1>Orders Management</h1>
                <div class="orders-filter">
                    <input type="date" id="orderDateFilter" placeholder="Filter by date">
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
                    body: `action=get_orders${dateFilter ? '&date=' + dateFilter : ''}`
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
                                <td>${order.order_type}</td>
                                <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
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
                                </div>
                            `,
                            width: '900px',
                            showCloseButton: true,
                            showConfirmButton: true,
                            showDenyButton: true,
                            confirmButtonText: 'Close',
                            denyButtonText: 'Delete Order',
                            confirmButtonColor: '#634832',
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
                                    cancelButtonColor: '#6c757d',
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
                                                    confirmButtonColor: '#634832'
                                                }).then(() => {
                                                    // Reload the orders table
                                                    loadOrders();
                                                });
                                            } else {
                                                Swal.fire({
                                                    title: 'Error',
                                                    text: data.message || 'Failed to delete the order',
                                                    icon: 'error',
                                                    confirmButtonColor: '#634832'
                                                });
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            Swal.fire({
                                                title: 'Error',
                                                text: 'An error occurred while deleting the order',
                                                icon: 'error',
                                                confirmButtonColor: '#634832'
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
                        confirmButtonColor: '#634832'
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
                            confirmButtonColor: '#634832'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to update order status',
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while updating the order status',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                });
            }
        });
    </script>
</body>
</html>