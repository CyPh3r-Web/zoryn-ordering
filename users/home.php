<?php
session_start();
if (isset($_SESSION['user_id']) && in_array(strtolower($_SESSION['role'] ?? ''), ['kitchen', 'crew'], true)) {
    header('Location: ../admin/orders.php');
    exit();
}
require_once '../navigation/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn – Home</title>
    <link rel="icon" type="image/jpeg" href="../assets/zoryn/zoryn.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at 15% 20%, rgba(212,175,55,0.18), transparent 45%),
                radial-gradient(circle at 85% 0%, rgba(212,175,55,0.12), transparent 40%),
                linear-gradient(145deg, #0D0D0D 0%, #1a1204 38%, #0D0D0D 100%);
            margin: 0;
            color: #fff;
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
        .main-content {
            min-height: calc(100vh - 64px);
            padding: 24px 16px 40px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <?php $isCashier = isset($_SESSION['role']) && $_SESSION['role'] === 'cashier'; ?>

    <div class="main-content">
        <div class="mx-auto max-w-7xl">
            <div class="py-10 text-center md:py-14">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-300/90">Zoryn Ordering</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white md:text-4xl">Explore Our Menu</h1>
                <p class="mx-auto mt-3 max-w-2xl text-sm text-neutral-300 md:text-base">
                    Discover curated food and drink categories designed to make ordering faster and more enjoyable.
                </p>
            </div>

            <div
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-5 lg:grid-cols-3 xl:grid-cols-4"
                id="categories-container"
                aria-live="polite"
            >
                <div class="col-span-full flex flex-col items-center justify-center py-16 text-neutral-300">
                    <span class="h-8 w-8 animate-spin rounded-full border-2 border-neutral-600 border-t-neutral-200"></span>
                    <p class="mt-4 text-sm">Loading categories...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fallbackImage = '../assets/zoryn/zoryn_logo.jpg';

        function createCategoryCard(category) {
            const card = document.createElement('a');
            card.className = 'group relative block overflow-hidden rounded-2xl border border-[#2E2E2E]/50 bg-gradient-to-b from-[#1F1F1F] to-[#121212] shadow-md shadow-black/20 transition duration-300 hover:-translate-y-1 hover:border-[#D4AF37]/40 hover:shadow-xl hover:shadow-black/35 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D4AF37] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0D0D0D]';
            card.href = 'category-products.php?category_id=' + encodeURIComponent(category.category_id);
            card.setAttribute('aria-label', 'View ' + (category.category_name || 'category') + ' products');

            const categoryName = category.category_name || 'Category';
            const imgSrc = category.image_path || fallbackImage;

            card.innerHTML =
                '<div class="relative w-full overflow-hidden bg-[#0D0D0D]">' +
                    '<img class="w-full h-auto transition duration-500 ease-out group-hover:scale-105 group-hover:brightness-110 group-focus-visible:scale-105" ' +
                        'src="' + imgSrc + '" alt="' + categoryName + '" loading="lazy" decoding="async" ' +
                        'onerror="this.onerror=null;this.src=\'' + fallbackImage + '\';">' +
                    '<div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/55 via-black/15 to-transparent"></div>' +
                    '<span class="absolute left-3 top-3 max-w-[calc(100%-24px)] truncate rounded-full border border-black/90 bg-gradient-to-r from-[#D4AF37] to-[#F5D76E] px-3.5 py-1.5 text-xs font-extrabold uppercase tracking-[0.14em] text-[#0D0D0D] opacity-0 shadow-md shadow-black/45 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100 group-focus-visible:opacity-100 -translate-y-1">' + categoryName + '</span>' +
                '</div>';

            return card;
        }

        fetch('../backend/fetch_product_categories.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('categories-container');
                container.innerHTML = '';

                if (data.message || data.error || !Array.isArray(data) || data.length === 0) {
                    container.innerHTML =
                        '<p class="col-span-full py-14 text-center text-neutral-300">No categories available.</p>';
                    return;
                }

                data.forEach(category => {
                    container.appendChild(createCategoryCard(category));
                });
            })
            .catch(error => {
                console.error('Error loading categories:', error);
                document.getElementById('categories-container').innerHTML =
                    '<p class="col-span-full py-14 text-center text-red-400">Failed to load categories. Please refresh the page.</p>';
            });
    });
    </script>
</body>
</html>
