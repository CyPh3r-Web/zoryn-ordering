<?php
session_start();
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
if ($category_id <= 0) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <style>
        body {
            background:
                radial-gradient(circle at 15% 20%, rgba(212,175,55,0.18), transparent 45%),
                radial-gradient(circle at 85% 0%, rgba(212,175,55,0.12), transparent 40%),
                linear-gradient(145deg, #0D0D0D 0%, #1a1204 38%, #0D0D0D 100%);
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.42);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            pointer-events: none;
            z-index: 0;
        }
        .content {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
<?php include ("../navigation/navbar.php"); ?>

    <main class="content">
        <div class="showcase">
            <h1 id="category-title">Loading&hellip;</h1>
            <span class="product-count" id="product-count"></span>
        </div>

        <!-- Category Navigation -->
        <div class="category-nav-wrapper">
            <button class="category-nav-arrow left" id="scrollLeft" aria-label="Scroll left">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="category-nav" id="category-nav">
                <!-- Populated by JS -->
            </div>
            <button class="category-nav-arrow right" id="scrollRight" aria-label="Scroll right">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div class="products" id="products-container">
            <!-- Skeleton loaders while fetching -->
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-text"></div><div class="skeleton-text short"></div><div class="skeleton-text xs"></div></div>
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-text"></div><div class="skeleton-text short"></div><div class="skeleton-text xs"></div></div>
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-text"></div><div class="skeleton-text short"></div><div class="skeleton-text xs"></div></div>
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-text"></div><div class="skeleton-text short"></div><div class="skeleton-text xs"></div></div>
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-text"></div><div class="skeleton-text short"></div><div class="skeleton-text xs"></div></div>
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-text"></div><div class="skeleton-text short"></div><div class="skeleton-text xs"></div></div>
        </div>

        <div class="action-buttons">
            <button class="action-btn back-btn" id="back-btn">
                <i class="fas fa-arrow-left fa-sm"></i>Back
            </button>
            <button class="action-btn next-btn" id="next-btn">
                Next<i class="fas fa-arrow-right fa-sm"></i>
            </button>
            <button class="action-btn confirm-btn">
                <i class="fas fa-check fa-sm"></i>Confirm Order
            </button>
        </div>
    </main>

    <script>
        const currentCategoryId = <?php echo $category_id; ?>;
        let allCategories = [];
        let defaultImage = '../assets/zoryn/zoryn_logo.jpg';

        function loadCategories() {
            return fetch('../backend/fetch_product_categories.php')
                .then(response => response.json())
                .then(categories => {
                    if (Array.isArray(categories)) {
                        allCategories = categories;
                        renderCategoryNav(categories);
                    }
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                });
        }

        function renderCategoryNav(categories) {
            const nav = document.getElementById('category-nav');
            nav.innerHTML = '';

            categories.forEach(cat => {
                const pill = document.createElement('div');
                pill.className = 'category-pill' + (cat.category_id === currentCategoryId ? ' active' : '');
                pill.dataset.categoryId = cat.category_id;

                const img = document.createElement('img');
                img.className = 'category-pill-img';
                img.src = cat.image_path || defaultImage;
                img.alt = cat.category_name;
                img.loading = 'lazy';
                img.onerror = function() { this.onerror = null; this.src = defaultImage; };

                const name = document.createElement('span');
                name.className = 'category-pill-name';
                name.textContent = cat.category_name;

                pill.appendChild(img);
                pill.appendChild(name);

                pill.addEventListener('click', function() {
                    if (cat.category_id === currentCategoryId) return;
                    showCategoryModal(cat);
                });

                nav.appendChild(pill);
            });

            scrollActivePillIntoView();
            updateScrollArrows();
        }

        function showCategoryModal(cat) {
            const imgSrc = cat.image_path || defaultImage;
            const desc = cat.description
                ? '<p class="category-modal-desc">' + cat.description + '</p>'
                : '';

            Swal.fire({
                html:
                    '<img class="category-modal-img" src="' + imgSrc + '" ' +
                        'alt="' + cat.category_name + '" ' +
                        'onerror="this.onerror=null; this.src=\'' + defaultImage + '\';">' +
                    '<p class="category-modal-name">' + cat.category_name + '</p>' +
                    desc,
                showCancelButton: true,
                confirmButtonColor: '#D4AF37',
                cancelButtonColor: '#2A2A2A',
                confirmButtonText: '<i class="fas fa-utensils" style="margin-right:6px"></i>View Menu',
                cancelButtonText: 'Stay here',
                customClass: {
                    popup: 'category-modal-popup'
                }
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = 'category-products.php?category_id=' + cat.category_id;
                }
            });
        }

        function scrollActivePillIntoView() {
            const nav = document.getElementById('category-nav');
            const activePill = nav.querySelector('.category-pill.active');
            if (activePill) {
                const navRect = nav.getBoundingClientRect();
                const pillRect = activePill.getBoundingClientRect();
                const offset = pillRect.left - navRect.left - (navRect.width / 2) + (pillRect.width / 2);
                nav.scrollLeft += offset;
            }
        }

        function updateScrollArrows() {
            const nav = document.getElementById('category-nav');
            const leftBtn = document.getElementById('scrollLeft');
            const rightBtn = document.getElementById('scrollRight');

            const atStart = nav.scrollLeft <= 4;
            const atEnd = nav.scrollLeft + nav.clientWidth >= nav.scrollWidth - 4;

            leftBtn.classList.toggle('hidden', atStart);
            rightBtn.classList.toggle('hidden', atEnd);
        }

        (function setupScrollArrows() {
            const nav = document.getElementById('category-nav');
            document.getElementById('scrollLeft').addEventListener('click', () => {
                nav.scrollBy({ left: -200, behavior: 'smooth' });
            });
            document.getElementById('scrollRight').addEventListener('click', () => {
                nav.scrollBy({ left: 200, behavior: 'smooth' });
            });
            nav.addEventListener('scroll', updateScrollArrows);
            window.addEventListener('resize', updateScrollArrows);
        })();

        function loadProducts() {
            return fetch('../backend/fetch_products_by_category.php?category_id=' + currentCategoryId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    const categoryTitle = document.getElementById('category-title');
                    if (data.category && data.category.category_name) {
                        categoryTitle.textContent = data.category.category_name.toUpperCase();
                    }

                    if (data.products && data.products.length > 0 && data.products[0].image_path) {
                        defaultImage = data.products[0].image_path;
                    }

                    const container = document.getElementById('products-container');
                    container.innerHTML = '';

                    if (!data.products || data.products.length === 0) {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-box-open"></i><p>No products available in this category yet.</p></div>';
                        document.getElementById('product-count').textContent = '';
                        return;
                    }

                    const count = data.products.length;
                    document.getElementById('product-count').textContent = count + ' item' + (count !== 1 ? 's' : '') + ' available';

                    data.products.forEach((product, idx) => {
                        const productCard = document.createElement('div');
                        productCard.className = 'product-card';
                        productCard.dataset.productId = product.product_id;
                        productCard.style.animationDelay = (idx * 0.06) + 's';

                        let imagePath = product.image_path || defaultImage;

                        productCard.innerHTML =
                            '<div class="product-image-container">' +
                                '<img src="' + imagePath + '" ' +
                                     'alt="' + product.product_name + '" ' +
                                     'class="product-image" ' +
                                     'loading="lazy" ' +
                                     'onerror="this.onerror=null; this.src=\'' + defaultImage + '\';">' +
                            '</div>' +
                            '<div class="product-info">' +
                                '<h3>' + product.product_name + '</h3>' +
                                '<p class="price">' + product.price + ' Pesos</p>' +
                                '<div class="quantity-selector">' +
                                    '<button class="qty-btn minus" aria-label="Decrease">' +
                                        '<i class="fas fa-minus fa-xs"></i>' +
                                    '</button>' +
                                    '<span>0</span>' +
                                    '<button class="qty-btn plus" aria-label="Increase">' +
                                        '<i class="fas fa-plus fa-xs"></i>' +
                                    '</button>' +
                                '</div>' +
                            '</div>';

                        container.appendChild(productCard);
                    });

                    checkAllProductsAvailability();
                    loadCurrentQuantities();
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

        function checkProductAvailability(productId) {
            return fetch('../backend/check_ingredient_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                const productCard = document.querySelector('.product-card[data-product-id="' + productId + '"]');
                if (productCard) {
                    if (!data.is_available) {
                        productCard.classList.add('not-available');
                        const badge = document.createElement('div');
                        badge.className = 'not-available-badge';
                        badge.textContent = 'Not Available';
                        productCard.appendChild(badge);

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

        function checkAllProductsAvailability() {
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                const productId = card.dataset.productId;
                checkProductAvailability(productId);
            });
        }

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
                        return;
                    }

                    updateOrder(productId, quantity).then(() => {
                        quantitySpan.textContent = quantity;
                        if (quantity > 0) {
                            quantitySpan.classList.add('has-qty');
                        } else {
                            quantitySpan.classList.remove('has-qty');
                        }
                    }).catch(() => {
                        loadCurrentQuantities();
                    });
                });
            });
        }

        function updateOrder(productId, quantity) {
            return fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_quantity&product_id=' + productId + '&quantity=' + quantity
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
                        const productCard = document.querySelector('.product-card[data-product-id="' + item.product_id + '"]');
                        if (productCard) {
                            const quantitySpan = productCard.querySelector('.quantity-selector span');
                            if (quantitySpan) {
                                quantitySpan.textContent = item.quantity;
                                if (parseInt(item.quantity) > 0) {
                                    quantitySpan.classList.add('has-qty');
                                }
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading quantities:', error);
            });
        }

        function getNextCategory() {
            const currentIndex = allCategories.findIndex(c => c.category_id === currentCategoryId);
            if (currentIndex >= 0 && currentIndex < allCategories.length - 1) {
                return allCategories[currentIndex + 1];
            }
            return null;
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadCategories().then(() => {
                loadProducts();

                const nextBtn = document.getElementById('next-btn');
                const nextCategory = getNextCategory();
                if (nextCategory) {
                    nextBtn.addEventListener('click', function() {
                        Swal.fire({
                            title: 'Continue to ' + nextCategory.category_name + '?',
                            text: "Let's check out our " + nextCategory.category_name + ' menu!',
                            imageUrl: nextCategory.image_path || defaultImage,
                            imageWidth: 150,
                            imageHeight: 150,
                            showCancelButton: true,
                            confirmButtonColor: '#D4AF37',
                            cancelButtonColor: '#2A2A2A',
                            confirmButtonText: 'Yes, take me there!',
                            cancelButtonText: 'Stay here'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'category-products.php?category_id=' + nextCategory.category_id;
                            }
                        });
                    });
                } else {
                    nextBtn.style.display = 'none';
                }
            });
        });

        document.getElementById('back-btn').addEventListener('click', function() {
            window.location.href = 'home.php';
        });

        document.querySelector('.confirm-btn').addEventListener('click', function() {
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
                    confirmButtonColor: '#D4AF37'
                });
                return;
            }

            window.location.href = 'order-details.php';
        });
    </script>
</body>
</html>
