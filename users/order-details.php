<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Order Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="css/order-details.css">
    <link rel="stylesheet" href="css/rookie.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .order-value {
            font-size: 16px;
            color: #333;
            margin-top: 5px;
        }
        
        .order-value input,
        .order-value select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            color: #333;
            background-color: #fff;
        }
        
        .order-value select {
            cursor: pointer;
        }
        
        .order-value input:focus,
        .order-value select:focus {
            outline: none;
            border-color: #3c2415;
            box-shadow: 0 0 0 2px rgba(60, 36, 21, 0.1);
        }
    </style>
</head>
<body>
    <?php include("../navigation/navbar.php");?>
    <?php include("../navigation/cashier-sidebar.php");?>
    <div class="order-details-container">
        <div class="order-customer-info">
            <div class="order-info-field">
                <label>Customer Name</label>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'cashier'): ?>
                    <input type="text" class="order-value" id="customer-name" value="<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest'; ?>">
                <?php else: ?>
                    <div class="order-value" id="customer-name"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest'; ?></div>
                <?php endif; ?>
            </div>
            <div class="order-info-field">
                <label>Order Type</label>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'cashier'): ?>
                    <select class="order-value" id="order-type">
                        <option value="walk-in">Walk-in</option>
                    </select>
                <?php else: ?>
                    <div class="order-value">Account-Order</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="order-items-list">
            <!-- Existing orders will be loaded here -->
            <div class="add-order-btn-container" onclick="showProductsModal()">
                <button class="add-order-btn">
                    <i class="fas fa-plus"></i> Add Order
                </button>
            </div>
        </div>
        
        <div class="order-payment-details">
            <h3 class="order-payment-title">Payment Details</h3>
            
            <div class="order-item-row">
                <div>
                    <span class="order-item-quantity">1</span>
                    <span>Strawberry Milky</span>
                </div>
                <div class="order-item-price">P- 39</div>
            </div>
            
            <div class="order-item-row">
                <div>
                    <span class="order-item-quantity">1</span>
                    <span>Dark Choco-ey</span>
                </div>
                <div class="order-item-price">P- 39</div>
            </div>
            
            <div class="order-total-row">
                <div>Total</div>
                <div class="order-item-price">P- 78</div>
            </div>
        </div>
        
        <button class="order-confirm-btn">Confirm</button>
    </div>
    
    <!-- Products Modal -->
    <div id="productsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Select Products</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="category-tabs">
                    <!-- Category tabs will be populated by JavaScript -->
                </div>
                <div class="products-grid">
                    <!-- Products will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="confirm-order-btn" onclick="confirmSelectedProducts()">Confirm Order</button>
            </div>
        </div>
    </div>
    
    <script>
        let selectedProducts = new Set();
        let currentCategory = null;
        let categoriesData = [];

        // Function to show products modal
        function showProductsModal() {
            const modal = document.getElementById('productsModal');
            modal.style.display = 'block';
            loadCategories();
        }

        // Function to close modal
        document.querySelector('.close-modal').addEventListener('click', function() {
            const modal = document.getElementById('productsModal');
            modal.style.display = 'none';
            selectedProducts.clear();
            updateProductSelection();
        });

        // Function to load categories
        function loadCategories() {
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_categories'
            })
            .then(response => response.json())
            .then(data => {
                categoriesData = data.categories;
                const categoryTabs = document.querySelector('.category-tabs');
                categoryTabs.innerHTML = '';
                
                // Add "All" category tab
                const allTab = document.createElement('div');
                allTab.className = 'category-tab active';
                allTab.textContent = 'All';
                allTab.onclick = () => filterProducts(null);
                categoryTabs.appendChild(allTab);
                
                // Add other category tabs
                data.categories.forEach(category => {
                    const tab = document.createElement('div');
                    tab.className = 'category-tab';
                    tab.textContent = category.category_name;
                    tab.onclick = () => filterProducts(category.category_id);
                    categoryTabs.appendChild(tab);
                });
                
                loadProducts();
            })
            .catch(error => {
                console.error('Error loading categories:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load categories. Please try again later.',
                    icon: 'error'
                });
            });
        }

        // Function to load products
        function loadProducts() {
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_products'
            })
            .then(response => response.json())
            .then(data => {
                const productsGrid = document.querySelector('.products-grid');
                productsGrid.innerHTML = '';
                
                data.products.forEach(product => {
                    const productCard = document.createElement('div');
                    productCard.className = 'product-card';
                    productCard.dataset.productId = product.product_id;
                    productCard.dataset.categoryId = product.category_id;
                    productCard.innerHTML = `
                        <img src="/zoryn/assets/images/products/${product.image_path}" 
                             alt="${product.product_name}"
                             onerror="this.onerror=null; this.src='../assets/zoryn/logo.png';">
                        <h3>${product.product_name}</h3>
                        <div class="price">P- ${product.price}</div>
                    `;
                    productCard.onclick = () => toggleProductSelection(product.product_id);
                    productsGrid.appendChild(productCard);
                });
                
                filterProducts(currentCategory);
            })
            .catch(error => {
                console.error('Error loading products:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load products. Please try again later.',
                    icon: 'error'
                });
            });
        }

        // Function to filter products by category
        function filterProducts(categoryId) {
            currentCategory = categoryId;
            const products = document.querySelectorAll('.product-card');
            const tabs = document.querySelectorAll('.category-tab');
            
            // Update active tab
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if ((categoryId === null && tab.textContent === 'All') || 
                    (categoryId !== null && tab.textContent === categoriesData.find(c => c.category_id === categoryId)?.category_name)) {
                    tab.classList.add('active');
                }
            });
            
            // Show/hide products
            products.forEach(product => {
                if (categoryId === null || product.dataset.categoryId === categoryId.toString()) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        // Function to toggle product selection
        function toggleProductSelection(productId) {
            const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
            if (selectedProducts.has(productId)) {
                selectedProducts.delete(productId);
                productCard.classList.remove('selected');
            } else {
                selectedProducts.add(productId);
                productCard.classList.add('selected');
            }
        }

        // Function to update product selection UI
        function updateProductSelection() {
            const products = document.querySelectorAll('.product-card');
            products.forEach(product => {
                const productId = product.dataset.productId;
                if (selectedProducts.has(productId)) {
                    product.classList.add('selected');
                } else {
                    product.classList.remove('selected');
                }
            });
        }

        // Function to confirm selected products
        function confirmSelectedProducts() {
            if (selectedProducts.size === 0) {
                Swal.fire({
                    title: 'No Products Selected',
                    text: 'Please select at least one product to add to your order.',
                    icon: 'warning'
                });
                return;
            }

            const productIds = Array.from(selectedProducts);
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_items&product_ids=${JSON.stringify(productIds)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    Swal.fire({
                        title: 'Error',
                        text: data.error,
                        icon: 'error'
                    });
                } else {
                    const modal = document.getElementById('productsModal');
                    modal.style.display = 'none';
                    selectedProducts.clear();
                    loadOrderDetails();
                    Swal.fire({
                        title: 'Success',
                        text: 'Products added to your order successfully!',
                        icon: 'success'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to add products to your order. Please try again later.',
                    icon: 'error'
                });
            });
        }

        // Function to load order details
        function loadOrderDetails() {
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_order'
            })
            .then(response => response.json())
            .then(data => {
                const orderItemsList = document.querySelector('.order-items-list');
                const orderPaymentDetails = document.querySelector('.order-payment-details');
                
                // Create add order button HTML
                const addOrderButton = `
                    <div class="add-order-btn-container" onclick="showProductsModal()">
                        <button class="add-order-btn">
                            <i class="fas fa-plus"></i> Add Order
                        </button>
                    </div>
                `;

                if (data.error || !data.items || data.items.length === 0) {
                    orderItemsList.innerHTML = addOrderButton;
                    orderPaymentDetails.innerHTML = '<h3 class="order-payment-title">Payment Details</h3><p>No items to display</p>';
                    return;
                }

                // Start with the add order button
                let orderItemsHTML = '';
                
                // Add each item to the order list
                data.items.forEach(item => {
                    // Get filename from path using JavaScript, with fallback
                    const filename = item.image_path ? item.image_path.split('/').pop() : '';
                    orderItemsHTML += `
                        <div class="order-item-card" data-product-id="${item.product_id}">
                            <img src="/zoryn/assets/images/products/${filename}" 
                                 alt="${item.product_name}" 
                                 class="order-item-image"
                                 onerror="this.onerror=null; this.src='../assets/zoryn/logo.png';">
                            <div class="order-item-name">${item.product_name}</div>
                            <div class="order-quantity-controls">
                                <button class="order-qty-btn order-minus">
                                    <i class="fas fa-minus fa-sm"></i>
                                </button>
                                <span class="order-qty-display">${item.quantity}</span>
                                <button class="order-qty-btn order-plus">
                                    <i class="fas fa-plus fa-sm"></i>
                                </button>
                                <button class="order-delete-btn">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });

                // Add the add order button at the end
                orderItemsHTML += addOrderButton;
                
                // Set the complete HTML
                orderItemsList.innerHTML = orderItemsHTML;

                // Update payment details
                let total = 0;
                let paymentDetailsHTML = '<h3 class="order-payment-title">Payment Details</h3>';
                
                data.items.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    paymentDetailsHTML += `
                        <div class="order-item-row">
                            <div>
                                <span class="order-item-quantity">${item.quantity}</span>
                                <span>${item.product_name}</span>
                            </div>
                            <div class="order-item-price">P- ${itemTotal}</div>
                        </div>
                    `;
                });

                paymentDetailsHTML += `
                    <div class="order-total-row">
                        <div>Total</div>
                        <div class="order-item-price">P- ${total}</div>
                    </div>
                `;

                orderPaymentDetails.innerHTML = paymentDetailsHTML;
                
                // Reattach event listeners
                attachEventListeners();
            })
            .catch(error => {
                console.error('Error loading order details:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load order details. Please try again later.',
                    icon: 'error'
                });
            });
        }

        // Function to attach event listeners
        function attachEventListeners() {
            // Quantity buttons functionality
            document.querySelectorAll('.order-qty-btn').forEach(button => {
            button.addEventListener('click', function() {
                const quantityDisplay = this.parentElement.querySelector('.order-qty-display');
                let quantity = parseInt(quantityDisplay.textContent);
                const productId = this.closest('.order-item-card').dataset.productId;
                
                if (this.classList.contains('order-minus') && quantity > 1) {
                    const newQuantity = quantity - 1;
                    // Update UI first
                    quantityDisplay.textContent = newQuantity;
                    // Then send to server
                    updateOrderQuantity(productId, newQuantity);
                } else if (this.classList.contains('order-plus')) {
                    const newQuantity = quantity + 1;
                    // Update UI first
                    quantityDisplay.textContent = newQuantity;
                    // Then send to server
                    updateOrderQuantity(productId, newQuantity);
                }
            });
        });
            // Delete buttons functionality
            document.querySelectorAll('.order-delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const itemCard = this.closest('.order-item-card');
                    const productId = itemCard.dataset.productId;
                    const itemName = itemCard.querySelector('.order-item-name').textContent;
                    
                    Swal.fire({
                        title: 'Remove Item?',
                        text: `Remove ${itemName} from your order?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#5d4037',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, remove it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            removeOrderItem(productId);
                        }
                    });
                });
            });
        }

        // Function to update order quantity
        function updateOrderQuantity(productId, quantity) {
            // Disable buttons during the update to prevent multiple clicks
            const itemCard = document.querySelector(`.order-item-card[data-product-id="${productId}"]`);
            if (itemCard) {
                const buttons = itemCard.querySelectorAll('button');
                buttons.forEach(btn => btn.disabled = true);
            }
            
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                // Re-enable buttons
                if (itemCard) {
                    const buttons = itemCard.querySelectorAll('button');
                    buttons.forEach(btn => btn.disabled = false);
                }
                
                if (data.error) {
                    console.error('Error updating order:', data.error);
                    Swal.fire({
                        title: 'Error',
                        text: data.error,
                        icon: 'error'
                    });
                } else {
                    // Update payment details
                    updatePaymentDetails(data);
                }
            })
            .catch(error => {
                // Re-enable buttons on error
                if (itemCard) {
                    const buttons = itemCard.querySelectorAll('button');
                    buttons.forEach(btn => btn.disabled = false);
                }
                
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to update quantity. Please try again.',
                    icon: 'error'
                });
            });
        }

        // Function to update payment details without reloading the page
        function updatePaymentDetails(data) {
            if (!data.items || data.items.length === 0) {
                return;
            }
            
            const orderPaymentDetails = document.querySelector('.order-payment-details');
            let total = 0;
            let paymentDetailsHTML = '<h3 class="order-payment-title">Payment Details</h3>';
            
            data.items.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                paymentDetailsHTML += `
                    <div class="order-item-row">
                        <div>
                            <span class="order-item-quantity">${item.quantity}</span>
                            <span>${item.product_name}</span>
                        </div>
                        <div class="order-item-price">P- ${itemTotal}</div>
                    </div>
                `;
            });

            paymentDetailsHTML += `
                <div class="order-total-row">
                    <div>Total</div>
                    <div class="order-item-price">P- ${total}</div>
                </div>
            `;

            orderPaymentDetails.innerHTML = paymentDetailsHTML;
        }

        // Function to remove order item
        function removeOrderItem(productId) {
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_item&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error removing item:', data.error);
                } else {
                    loadOrderDetails();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Load order details when page loads
        document.addEventListener('DOMContentLoaded', loadOrderDetails);
        
        // Update the order confirmation code
        document.querySelector('.order-confirm-btn').addEventListener('click', function() {
            const customerName = document.getElementById('customer-name').textContent || document.getElementById('customer-name').value;
            const orderType = document.getElementById('order-type') ? document.getElementById('order-type').value : 'account-order';
            
            // First show payment modal
            Swal.fire({
                title: 'Payment Details',
                html: `
                    <div class="payment-options">
                        <div class="payment-option" onclick="selectPaymentOption(this, 'cash')">
                            <input type="radio" name="payment_type" id="cash" value="cash">
                            <label for="cash">Cash Payment</label>
                        </div>
                        <div class="payment-option" onclick="selectPaymentOption(this, 'online')">
                            <input type="radio" name="payment_type" id="online" value="online">
                            <label for="online">Online Payment</label>
                        </div>
                    </div>
                    <div class="payment-upload" id="paymentUpload">
                        <div class="upload-btn" onclick="document.getElementById('proofOfPayment').click()">
                            Upload Proof of Payment
                        </div>
                        <input type="file" id="proofOfPayment" accept="image/*" onchange="previewPaymentProof(this)">
                        <div class="upload-preview" id="uploadPreview">
                            <img id="previewImage" src="" alt="Payment Proof Preview">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Proceed to Order',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3c2415',
                cancelButtonColor: '#6c757d',
                customClass: {
                    popup: 'payment-modal'
                },
                didOpen: () => {
                    // Initialize payment form
                    document.querySelector('.payment-option').click();
                },
                preConfirm: () => {
                    const paymentType = document.querySelector('input[name="payment_type"]:checked')?.value;
                    if (!paymentType) {
                        Swal.showValidationMessage('Please select a payment method');
                        return false;
                    }
                    
                    if (paymentType === 'online' && !document.getElementById('proofOfPayment').files[0]) {
                        Swal.showValidationMessage('Please upload proof of payment');
                        return false;
                    }
                    
                    return {
                        payment_type: paymentType,
                        proof_of_payment: document.getElementById('proofOfPayment').files[0]
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create FormData for payment and order
                    const formData = new FormData();
                    formData.append('action', 'create_order_with_payment');
                    formData.append('customer_name', customerName);
                    formData.append('order_type', orderType);
                    formData.append('user_id', '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>');
                    formData.append('payment_type', result.value.payment_type);
                    
                    if (result.value.proof_of_payment) {
                        formData.append('proof_of_payment', result.value.proof_of_payment);
                    }
                    
                    // Send request to create order with payment
                    fetch('../backend/order_manager.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Order Placed Successfully!',
                                text: 'Thank you for your order!',
                                icon: 'success',
                                background: '#f5f0e6',
                                confirmButtonColor: '#3c2415'
                            }).then(() => {
                                // Clear the current order and reload the order details
                                fetch('../backend/order_manager.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'action=clear_order'
                                }).then(() => {
                                    // Reload order details instead of redirecting
                                    loadOrderDetails();
                                });
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Failed to process your order. Please try again later.',
                                icon: 'error',
                                background: '#f5f0e6'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while processing your order',
                            icon: 'error',
                            background: '#f5f0e6'
                        });
                    });
                }
            });
        });

        // Add payment option selection function
        window.selectPaymentOption = function(element, type) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
                opt.querySelector('input[type="radio"]').checked = false;
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            
            // Show/hide upload section
            const uploadSection = document.getElementById('paymentUpload');
            if (type === 'online') {
                uploadSection.classList.add('active');
            } else {
                uploadSection.classList.remove('active');
            }
        }

        // Add payment proof preview function
        window.previewPaymentProof = function(input) {
            const preview = document.getElementById('uploadPreview');
            const previewImage = document.getElementById('previewImage');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>