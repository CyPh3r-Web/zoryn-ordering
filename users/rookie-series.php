<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Rookie Series</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/choco.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <style>
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding: 0 20px 30px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .action-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .next-btn {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .next-btn:hover {
            background-color: #e0e0e0;
        }
        
        .confirm-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .confirm-btn:hover {
            background-color: #45a049;
        }

        /* Custom styles for SweetAlert */
        .swal2-popup {
            font-family: 'Poppins', sans-serif;
        }

        /* Not Available Badge */
        .not-available-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ff4444;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            z-index: 1;
        }

        .product-card {
            position: relative;
        }

        .product-card.not-available .plus {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<?php include ("../navigation/navbar.php"); ?>
    <div class="tabs">
        <button class="tab active" onclick="window.location.href='rookie-series.php'">Non-Coffee</button>
        <button class="tab" onclick="window.location.href='coffee-series.php'">Coffee Series</button>
    </div>

    <main class="content">
        <div class="showcase">
            <h1>ROOKIE SERIES</h1>
            <img src="../assets/zoryn/rookie_series/rookie.png" alt="Rookie Series Drinks" class="showcase-image">
        </div>

        <div class="products" id="products-container">
            <!-- Products will be dynamically loaded here -->
        </div>
        
        <div class="action-buttons">
            <button class="action-btn next-btn">Next</button>
            <button class="action-btn confirm-btn">Confirm Order</button>
        </div>
    </main>

    <script>
        // Function to load products
        function loadProducts() {
            return fetch('../backend/fetch_rookie_products.php')
                .then(response => response.json())
                .then(products => {
                    const container = document.getElementById('products-container');
                    container.innerHTML = ''; // Clear existing content
                    
                    products.forEach(product => {
                        const productCard = document.createElement('div');
                        productCard.className = 'product-card';
                        productCard.dataset.productId = product.product_id;
                        
                        // Handle image path
                        let imagePath = product.image_path;
                        if (!imagePath) {
                            imagePath = '../assets/zoryn/rookie_series/rookie.png';
                        }
                        
                        productCard.innerHTML = `
                            <div class="product-image-container">
                                <img src="${imagePath}" 
                                     alt="${product.product_name}" 
                                     class="product-image"
                                     onerror="this.onerror=null; this.src='../assets/zoryn/rookie_series/rookie.png';">
                            </div>
                            <div class="product-info">
                                <h3>${product.product_name}</h3>
                                <p class="price">${product.price} Pesos</p>
                                <div class="quantity-selector">
                                    <button class="qty-btn minus">
                                        <i class="fas fa-minus fa-sm"></i>
                                    </button>
                                    <span>0</span>
                                    <button class="qty-btn plus">
                                        <i class="fas fa-plus fa-sm"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        container.appendChild(productCard);
                    });
                    
                    // Check ingredient availability for each product
                    checkAllProductsAvailability();
                    
                    // Reattach event listeners to quantity buttons
                    attachQuantityListeners();
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

        // Function to check ingredient availability
        function checkProductAvailability(productId) {
            return fetch('../backend/check_ingredient_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
                if (productCard) {
                    if (!data.is_available) {
                        productCard.classList.add('not-available');
                        const badge = document.createElement('div');
                        badge.className = 'not-available-badge';
                        badge.textContent = 'Not Available';
                        productCard.appendChild(badge);
                        
                        // Disable plus button
                        const plusBtn = productCard.querySelector('.plus');
                        if (plusBtn) {
                            plusBtn.disabled = true;
                            plusBtn.style.cursor = 'not-allowed';
                        }
                    }
                }
                return data;
            })
            .catch(error => {
                console.error('Error checking availability:', error);
            });
        }

        // Function to check availability for all products
        function checkAllProductsAvailability() {
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                const productId = card.dataset.productId;
                checkProductAvailability(productId);
            });
        }

        // Function to attach quantity button listeners
        function attachQuantityListeners() {
            document.querySelectorAll('.qty-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const quantitySpan = this.parentElement.querySelector('span');
                    let quantity = parseInt(quantitySpan.textContent);
                    const productCard = this.closest('.product-card');
                    const productId = productCard.dataset.productId;
                    
                    if (this.classList.contains('minus') && quantity > 0) {
                        quantity--;
                    } else if (this.classList.contains('plus')) {
                        quantity++;
                    } else {
                        return; // No change needed
                    }
                    
                    updateOrder(productId, quantity).then(() => {
                        quantitySpan.textContent = quantity;
                    }).catch(() => {
                        // If there's an error, reload the current quantities
                        loadCurrentQuantities();
                    });
                });
            });
        }

        // Function to update order in backend
        function updateOrder(productId, quantity) {
            return fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error updating order:', data.error);
                    throw new Error(data.error);
                }
                return data;
            })
            .catch(error => {
                console.error('Error:', error);
                throw error;
            });
        }

        // Function to load current quantities
        function loadCurrentQuantities() {
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_order'
            })
            .then(response => response.json())
            .then(data => {
                if (data.items) {
                    data.items.forEach(item => {
                        const productCard = document.querySelector(`.product-card[data-product-id="${item.product_id}"]`);
                        if (productCard) {
                            const quantitySpan = productCard.querySelector('.quantity-selector span');
                            if (quantitySpan) {
                                quantitySpan.textContent = item.quantity;
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading quantities:', error);
            });
        }
        
        // Load products and quantities when page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadProducts().then(() => {
                loadCurrentQuantities();
            });
        });
        
        // Next button with SweetAlert
        const nextBtn = document.querySelector('.next-btn');
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Continue to Milky Series?',
                    text: "Let's check out our Milky Series menu!",
                    imageUrl: '../assets/zoryn/milky_series/milky_series.png', 
                    imageWidth: 150, 
                    imageHeight: 100, 
                    showCancelButton: true,
                    confirmButtonColor: '#4CAF50',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, take me there!',
                    cancelButtonText: 'Stay on this page'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'milky-series.php';
                    }
                });
            });
        }
        
        // Confirm button functionality
        const confirmBtn = document.querySelector('.confirm-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                // Check if any items have been selected
                const quantities = document.querySelectorAll('.quantity-selector span');
                let hasSelectedItems = false;
                
                quantities.forEach(span => {
                    if (parseInt(span.textContent) > 0) {
                        hasSelectedItems = true;
                    }
                });
                
                if (!hasSelectedItems) {
                    Swal.fire({
                        title: 'No Items Selected',
                        text: 'Please select at least one item before confirming your order.',
                        icon: 'warning',
                        confirmButtonColor: '#4CAF50'
                    });
                    return;
                }
                
                // If items are selected, proceed to order details
                window.location.href = 'order-details.php';
            });
        }
    </script>
</body>
</html>