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
        /* Match users/home.php background; class + pseudo beats body:not(.auth-page)::before from app.css */
        body.page-category-products {
            background:
                radial-gradient(circle at 15% 20%, rgba(212,175,55,0.18), transparent 45%),
                radial-gradient(circle at 85% 0%, rgba(212,175,55,0.12), transparent 40%),
                linear-gradient(145deg, #0D0D0D 0%, #1a1204 38%, #0D0D0D 100%);
            margin: 0;
            color: #fff;
            min-height: 100vh;
        }
        body.page-category-products::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.42);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            pointer-events: none;
            z-index: 0;
        }
        body.page-category-products .content {
            position: relative;
            z-index: 1;
            /* Space for fixed action bar (selection strip + buttons) + safe area */
            padding-bottom: calc(152px + env(safe-area-inset-bottom, 0px));
        }

        body.page-category-products .action-buttons {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 90;
            margin-top: 0;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            padding: 12px max(20px, env(safe-area-inset-left, 0px))
                calc(12px + env(safe-area-inset-bottom, 0px))
                max(20px, env(safe-area-inset-right, 0px));
            border-top: 1px solid rgba(42, 42, 42, 0.95);
            background: rgba(13, 13, 13, 0.94);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.45);
        }

        body.page-category-products .selection-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #2E2E2E;
            border-radius: 12px;
            background: rgba(212, 175, 55, 0.06);
            color: #ccc;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            cursor: pointer;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        body.page-category-products .selection-strip:hover {
            border-color: rgba(212, 175, 55, 0.35);
            background: rgba(212, 175, 55, 0.1);
        }

        body.page-category-products .selection-strip:focus-visible {
            outline: 2px solid rgba(212, 175, 55, 0.6);
            outline-offset: 2px;
        }

        body.page-category-products .selection-strip.selection-strip--empty {
            color: #666;
            background: rgba(255, 255, 255, 0.02);
        }

        body.page-category-products .selection-strip-count {
            font-weight: 700;
            color: #D4AF37;
            font-size: 15px;
            font-variant-numeric: tabular-nums;
        }

        body.page-category-products .selection-strip--empty .selection-strip-count {
            color: #555;
        }

        body.page-category-products .selection-strip-pieces {
            font-size: 12px;
            color: #888;
        }

        body.page-category-products .selection-strip-chevron {
            margin-left: auto;
            color: #D4AF37;
            font-size: 12px;
            opacity: 0.85;
        }

        body.page-category-products .action-buttons-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            width: 100%;
        }

        @media (max-width: 768px) {
            body.page-category-products .action-buttons {
                padding-left: max(14px, env(safe-area-inset-left, 0px));
                padding-right: max(14px, env(safe-area-inset-right, 0px));
            }
        }

        /* “Your selection” modal list (SweetAlert html) */
        .selection-lines {
            list-style: none;
            margin: 0;
            padding: 0;
            text-align: left;
            max-height: min(45vh, 320px);
            overflow-y: auto;
        }

        .selection-line {
            border-bottom: 1px solid #2E2E2E;
            padding: 10px 0;
        }

        .selection-line:last-child {
            border-bottom: none;
        }

        .selection-line-body {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .selection-line-thumb {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            border-radius: 8px;
            overflow: hidden;
            background: #161616;
        }

        .selection-line-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .selection-line-text {
            flex: 1;
            min-width: 0;
        }

        .selection-line-name {
            font-weight: 600;
            color: #fff;
            font-size: 14px;
            line-height: 1.3;
        }

        .selection-line-meta {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .selection-line-total {
            font-weight: 600;
            color: #D4AF37;
            font-size: 14px;
            flex-shrink: 0;
        }

        .selection-subtotal {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #2E2E2E;
            text-align: right;
            font-size: 14px;
            color: #aaa;
        }

        .selection-subtotal strong {
            color: #D4AF37;
            margin-left: 8px;
        }

        .selection-hint {
            margin-top: 12px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body class="page-category-products">
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

        <nav class="action-buttons" aria-label="Order navigation">
            <button type="button" class="selection-strip selection-strip--empty" id="selection-summary-btn" aria-label="View selected products">
                <span>Products selected</span>
                <span class="selection-strip-count" id="selection-product-count">0</span>
                <span class="selection-strip-pieces" id="selection-pieces-label"></span>
                <i class="fas fa-chevron-up selection-strip-chevron" aria-hidden="true"></i>
            </button>
            <div class="action-buttons-row">
                <button type="button" class="action-btn back-btn" id="back-btn">
                    <i class="fas fa-arrow-left fa-sm"></i>Back
                </button>
                <button type="button" class="action-btn next-btn" id="next-btn">
                    Next<i class="fas fa-arrow-right fa-sm"></i>
                </button>
                <button type="button" class="action-btn confirm-btn">
                    <i class="fas fa-check fa-sm"></i>Confirm Order
                </button>
            </div>
        </nav>
    </main>

    <script>
        const currentCategoryId = <?php echo (int) $category_id; ?>;

        /** JSON from PHP often has category_id as string; use this for all comparisons */
        function sameCategoryId(a, b) {
            return Number(a) === Number(b);
        }

        let allCategories = [];
        let defaultImage = '../assets/zoryn/zoryn_logo.jpg';

        function escapeHtml(s) {
            if (s == null || s === '') {
                return '';
            }
            const el = document.createElement('div');
            el.textContent = s;
            return el.innerHTML;
        }

        function fetchOrderItems() {
            return fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_order'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    return Array.isArray(data.items) ? data.items : [];
                });
        }

        function refreshSelectionSummary() {
            return fetchOrderItems()
                .then(items => {
                    const active = items.filter(function(it) {
                        return parseInt(it.quantity, 10) > 0;
                    });
                    let totalPieces = 0;
                    active.forEach(function(it) {
                        totalPieces += parseInt(it.quantity, 10) || 0;
                    });
                    const lineCount = active.length;

                    const countEl = document.getElementById('selection-product-count');
                    const piecesEl = document.getElementById('selection-pieces-label');
                    const btn = document.getElementById('selection-summary-btn');
                    if (!countEl || !piecesEl || !btn) {
                        return;
                    }

                    countEl.textContent = String(lineCount);
                    if (lineCount === 0) {
                        piecesEl.textContent = '';
                        btn.classList.add('selection-strip--empty');
                    } else {
                        const p = totalPieces === 1 ? 'piece' : 'pieces';
                        piecesEl.textContent = ' · ' + totalPieces + ' ' + p + ' total';
                        btn.classList.remove('selection-strip--empty');
                    }
                })
                .catch(function() {
                    /* ignore; cart may be unreachable briefly */
                });
        }

        function showSelectionModal() {
            fetchOrderItems()
                .then(function(items) {
                    const active = items.filter(function(it) {
                        return parseInt(it.quantity, 10) > 0;
                    });

                    if (active.length === 0) {
                        return Swal.fire({
                            title: 'Your selection',
                            html: '<p style="margin:16px 0;color:#aaa;font-size:14px;">Nothing selected yet. Add products from the menu.</p>',
                            confirmButtonColor: '#D4AF37',
                            confirmButtonText: 'OK'
                        });
                    }

                    let rows = '';
                    let sub = 0;
                    active.forEach(function(it) {
                        const q = parseInt(it.quantity, 10) || 0;
                        const price = parseFloat(it.price) || 0;
                        const line = price * q;
                        sub += line;
                        const name = escapeHtml(it.product_name || 'Product');
                        const rawImg = it.image_path || '';
                        const imgSrc = rawImg ? escapeHtml(rawImg) : '';
                        const thumb = imgSrc
                            ? '<div class="selection-line-thumb"><img src="' + imgSrc + '" alt="" class="selection-line-img" onerror="this.parentNode.style.display=\'none\'"></div>'
                            : '';

                        rows +=
                            '<li class="selection-line">' +
                                '<div class="selection-line-body">' +
                                    thumb +
                                    '<div class="selection-line-text">' +
                                        '<div class="selection-line-name">' + name + '</div>' +
                                        '<div class="selection-line-meta">' + q + ' × ' + price.toFixed(2) + ' Pesos</div>' +
                                    '</div>' +
                                    '<div class="selection-line-total">' + line.toFixed(2) + '</div>' +
                                '</div>' +
                            '</li>';
                    });

                    const html =
                        '<ul class="selection-lines">' + rows + '</ul>' +
                        '<div class="selection-subtotal">Subtotal <strong>' + sub.toFixed(2) + ' Pesos</strong></div>' +
                        '<p class="selection-hint">You can adjust quantities on the menu. Full checkout is on the next step.</p>';

                    return Swal.fire({
                        title: 'Your selection',
                        html: html,
                        confirmButtonColor: '#D4AF37',
                        confirmButtonText: 'Close',
                        width: 'min(480px, 92vw)',
                        customClass: {
                            popup: 'selection-swal-popup'
                        }
                    });
                })
                .catch(function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Could not load your selection.',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
        }

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
                pill.className = 'category-pill' + (sameCategoryId(cat.category_id, currentCategoryId) ? ' active' : '');
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
                    if (sameCategoryId(cat.category_id, currentCategoryId)) return;
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
                        refreshSelectionSummary();
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
                        refreshSelectionSummary();
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
                return refreshSelectionSummary();
            })
            .catch(error => {
                console.error('Error loading quantities:', error);
            });
        }

        function getNextCategory() {
            const currentIndex = allCategories.findIndex(c => sameCategoryId(c.category_id, currentCategoryId));
            if (currentIndex >= 0 && currentIndex < allCategories.length - 1) {
                return allCategories[currentIndex + 1];
            }
            return null;
        }

        document.addEventListener('DOMContentLoaded', () => {
            refreshSelectionSummary();
            document.getElementById('selection-summary-btn').addEventListener('click', showSelectionModal);

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
            fetchOrderItems()
                .then(function(items) {
                    const hasSelected = items.some(function(it) {
                        return parseInt(it.quantity, 10) > 0;
                    });
                    if (!hasSelected) {
                        Swal.fire({
                            title: 'No Items Selected',
                            text: 'Please select at least one item before confirming your order.',
                            icon: 'warning',
                            confirmButtonColor: '#D4AF37'
                        });
                        return;
                    }
                    window.location.href = 'order-details.php';
                })
                .catch(function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Could not verify your order. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
        });
    </script>
</body>
</html>
