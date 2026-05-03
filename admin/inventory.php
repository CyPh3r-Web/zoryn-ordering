<?php
require_once '../backend/dbconn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Inventory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/inventory.css">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <!-- Active Page Detection -->
    <script src="js/active-page.js"></script>
</head>
<body>
    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    
    <div class="main-content">
        <div class="inventory-container">
            <div class="inventory-header">
                <h1>Inventory Management</h1>
                <div class="inventory-tabs">
                    <button class="tab-btn active" data-tab="stock">Stock Management</button>
                    <button class="tab-btn" data-tab="usage">Usage Analysis</button>
                </div>
                <div class="inventory-filters">
                    <select class="filter-select" id="categoryFilter">
                        <option value="">All Categories</option>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="critical">Critical</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            
            <!-- Stock Management Tab -->
            <div class="tab-content active" id="stockTab">
                <div class="inventory-table-container">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Ingredient</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ingredientsTableBody">
                            <!-- Ingredients will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Usage Analysis Tab -->
            <div class="tab-content" id="usageTab">
                <div class="inventory-table-container">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Ingredient</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Total Used</th>
                                <th>Remaining</th>
                                <th>Usage Status</th>
                            </tr>
                        </thead>
                        <tbody id="usageTableBody">
                            <!-- Usage data will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Update Modal -->
    <div class="stock-update-modal" id="stockUpdateModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h2>Update Stock</h2>
            </div>
            <form class="stock-update-form" id="stockUpdateForm">
                <input type="hidden" id="ingredientId">
                <div class="form-group">
                    <label for="currentStock">Current Stock</label>
                    <input type="text" id="currentStock" readonly>
                </div>
                <div class="form-group">
                    <label for="updateAmount">Update Amount</label>
                    <input type="number" id="updateAmount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="updateType">Update Type</label>
                    <select id="updateType" required>
                        <option value="add">Add Stock</option>
                        <option value="remove">Remove Stock</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Update Stock</button>
            </form>
        </div>
    </div>

    <style>
    /* Inline page-specific overrides - base styles in inventory.css */
    tr.ing-highlight { outline: 2px solid rgba(212, 175, 55, 0.9); outline-offset: 3px; border-radius: 8px; transition: outline-color 0.25s ease; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all DOM elements
            const ingredientsTableBody = document.getElementById('ingredientsTableBody');
            const stockUpdateModal = document.getElementById('stockUpdateModal');
            const stockUpdateForm = document.getElementById('stockUpdateForm');
            const closeModal = document.querySelector('.close-modal');
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('statusFilter');

            // Check if all required elements exist
            if (!ingredientsTableBody || !stockUpdateModal || !stockUpdateForm || !closeModal || !categoryFilter || !statusFilter) {
                console.error('One or more required elements not found');
                return;
            }

            // Load ingredients
            function loadIngredients() {
                fetch('../backend/inventory_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_ingredients'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        displayIngredients(data.data);
                    } else {
                        console.error('Invalid data format:', data);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to load ingredients: Invalid data format',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to load ingredients',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }

            // Display ingredients
            function displayIngredients(ingredients) {
                if (!ingredientsTableBody) {
                    console.error('Ingredients table body not found');
                    return;
                }
                
                ingredientsTableBody.innerHTML = '';
                
                const categoryFilterValue = categoryFilter ? categoryFilter.value : '';
                const statusFilterValue = statusFilter ? statusFilter.value : '';
                
                if (!Array.isArray(ingredients)) {
                    console.error('Ingredients is not an array:', ingredients);
                    return;
                }
                
                ingredients.forEach(ingredient => {
                    if (categoryFilterValue && String(ingredient.category_id) !== categoryFilterValue) return;
                    
                    // Convert stock to number
                    const stock = parseFloat(ingredient.stock);
                    const totalUsed = parseFloat(ingredient.total_used) || 0;
                    // stock is already the remaining stock (DB already deducts usage)
                    const originalStock = stock + totalUsed;
                    const usagePercent = originalStock > 0
                        ? Math.min(100, Math.round((totalUsed / originalStock) * 100))
                        : 0;
                    const remainingPercent = 100 - usagePercent;
                    const barClass = usagePercent >= 80 ? 'critical' : usagePercent >= 50 ? 'medium' : 'high';
                    
                    const stockStatus = getStockStatus(stock, ingredient.unit);
                    if (statusFilterValue && stockStatus !== statusFilterValue) return;
                    
                    const row = document.createElement('tr');
                    row.className = 'ingredient-row';
                    
                    row.innerHTML = `
                        <td>
                            <div class="ingredient-info">
                                <img src="../${ingredient.image_path || ''}" alt="${ingredient.ingredient_name}" class="ingredient-image">
                                <span class="ingredient-name">${ingredient.ingredient_name}</span>
                            </div>
                        </td>
                        <td>${ingredient.category_name}</td>
                        <td>
                            <div class="stock-info">
                                <div class="stock-amount-container">
                                    <span class="stock-amount">${stock.toFixed(2)}</span>
                                    <span class="stock-unit">${ingredient.unit}</span>
                                </div>
                                <div class="stock-level-container">
                                    <div class="stock-level-bar-container">
                                        <div class="stock-level-bar ${barClass}" 
                                             style="width: ${remainingPercent}%"></div>
                                    </div>
                                    <span class="stock-level-percentage">${remainingPercent}%</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${getStockStatus(stock, ingredient.unit)}">
                                ${getStockStatus(stock, ingredient.unit).toUpperCase()}
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button class="action-btn update" onclick="openUpdateModal(${ingredient.ingredient_id}, ${stock})">
                                <i class="fas fa-edit"></i>
                                Update
                            </button>
                        </td>
                    `;
                    
                    row.id = 'ing-row-' + ingredient.ingredient_id;
                    ingredientsTableBody.appendChild(row);
                });
                scrollToIngredientFromHash();
            }

            function scrollToIngredientFromHash() {
                const m = /^#(\d+)$/.exec(window.location.hash || '');
                if (!m) return;
                const el = document.getElementById('ing-row-' + m[1]);
                if (!el) return;
                requestAnimationFrame(function() {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('ing-highlight');
                    setTimeout(function() { el.classList.remove('ing-highlight'); }, 4500);
                });
            }

            // Get stock status based on unit type and quantity
            function getStockStatus(stock, unit) {
                unit = unit.toLowerCase();
                
                // Define thresholds based on unit type
                let criticalThreshold, lowThreshold, mediumThreshold;
                
                switch(unit) {
                    case 'kg':
                    case 'liters':
                    case 'l':
                        criticalThreshold = 0.2;
                        lowThreshold = 0.5;
                        mediumThreshold = 1.0;
                        break;
                    case 'pcs':
                        criticalThreshold = 10;
                        lowThreshold = 20;
                        mediumThreshold = 40;
                        break;
                    default:
                        criticalThreshold = 0.2;
                        lowThreshold = 0.5;
                        mediumThreshold = 1.0;
                }
                
                if (stock <= criticalThreshold) return 'critical';
                if (stock <= lowThreshold) return 'low';
                if (stock <= mediumThreshold) return 'medium';
                return 'high';
            }

            // Get stock level for visual display
            function getStockLevel(stock, unit) {
                return getStockStatus(stock, unit);
            }

            /**
             * Bar fill is purely from current stock vs a capacity (100% = full bar).
             * - Default: 10 kg/L or 100 pcs = 100% (so 5 kg ≈ 50%, 2.5 kg ≈ 25%).
             * - If reorder_level > 0: 100% at 2× reorder (restock target is half the bar).
             */
            function calculatePercentage(stock, unit, reorderLevelRaw) {
                const s = parseFloat(stock);
                const stockNum = Number.isFinite(s) ? s : 0;
                const reorder = parseFloat(reorderLevelRaw);
                const u = (unit || '').toLowerCase();

                let cap100;
                switch (u) {
                    case 'kg':
                    case 'liters':
                    case 'l':
                        cap100 = reorder > 0 ? Math.max(reorder * 2, 0.01) : 10;
                        break;
                    case 'pcs':
                        cap100 = reorder > 0 ? Math.max(reorder * 2, 1) : 100;
                        break;
                    default:
                        cap100 = reorder > 0 ? Math.max(reorder * 2, 0.01) : 10;
                }

                if (cap100 <= 0) {
                    return 0;
                }
                return Math.max(0, Math.min(100, Math.round((stockNum / cap100) * 100)));
            }

            // Category dropdown: same `categories` table as Products / Usage Analysis (fetch_data.php)
            function populateCategoryFilter() {
                if (!categoryFilter) {
                    console.error('Category filter element not found');
                    return Promise.resolve();
                }
                const previous = categoryFilter.value;
                return fetch('../backend/fetch_data.php?action=ingredient-categories')
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success || !Array.isArray(data.data)) {
                            console.error('Invalid ingredient categories response:', data);
                            return;
                        }
                        categoryFilter.innerHTML = '<option value="">All Categories</option>';
                        data.data.forEach(cat => {
                            const option = document.createElement('option');
                            option.value = String(cat.category_id);
                            option.textContent = cat.category_name;
                            categoryFilter.appendChild(option);
                        });
                        if (previous && [...categoryFilter.options].some(o => o.value === previous)) {
                            categoryFilter.value = previous;
                        }
                    })
                    .catch(err => {
                        console.error('Error loading categories:', err);
                    });
            }

            // Open update modal
            window.openUpdateModal = function(ingredientId, currentStock) {
                if (!stockUpdateModal) return;
                document.getElementById('ingredientId').value = ingredientId;
                document.getElementById('currentStock').value = currentStock;
                stockUpdateModal.style.display = 'flex';
            }

            // Close modal
            closeModal.onclick = function() {
                if (!stockUpdateModal) return;
                stockUpdateModal.style.display = 'none';
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == stockUpdateModal) {
                    stockUpdateModal.style.display = 'none';
                }
            }

            // Handle stock update
            stockUpdateForm.onsubmit = function(e) {
                e.preventDefault();
                
                const ingredientId = document.getElementById('ingredientId').value;
                const updateAmount = parseFloat(document.getElementById('updateAmount').value);
                const updateType = document.getElementById('updateType').value;
                
                fetch('../backend/update_ingredient_stock.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ingredient_id=${ingredientId}&amount=${updateAmount}&type=${updateType}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success',
                            text: data.message || 'Stock updated successfully',
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        }).then(() => {
                            stockUpdateModal.style.display = 'none';
                            loadIngredients();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Failed to update stock',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to update stock',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }

            // Add event listeners for filters
            categoryFilter.addEventListener('change', loadIngredients);
            statusFilter.addEventListener('change', loadIngredients);

            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    button.classList.add('active');
                    const tabId = button.getAttribute('data-tab') + 'Tab';
                    document.getElementById(tabId).classList.add('active');

                    // Load data for the active tab
                    if (button.getAttribute('data-tab') === 'usage') {
                        loadUsageData();
                    } else {
                        loadIngredients();
                    }
                });
            });

            // Function to load usage data
            function loadUsageData() {
                fetch('../backend/fetch_ingredient_usage.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayUsageData(data.data);
                        } else {
                            console.error('Error loading usage data:', data.error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to load usage data',
                                icon: 'error',
                                confirmButtonColor: '#D4AF37'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to load usage data',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    });
            }

            // Function to display usage data
            function displayUsageData(ingredients) {
                const usageTableBody = document.getElementById('usageTableBody');
                if (!usageTableBody) return;

                usageTableBody.innerHTML = '';

                ingredients.forEach(ingredient => {
                    // Ensure values are numbers
                    const currentStock = parseFloat(ingredient.current_stock) || 0;
                    const totalUsed = parseFloat(ingredient.total_used) || 0;
                    // currentStock is already the remaining stock (DB already deducts usage)
                    // Original stock = what we had before any orders consumed it
                    const originalStock = currentStock + totalUsed;
                    const usagePercent = originalStock > 0
                        ? Math.min(100, Math.round((totalUsed / originalStock) * 100))
                        : 0;
                    const usageStatus = getUsageStatus(currentStock, ingredient.unit);
                    const barClass = usagePercent >= 80 ? 'critical' : usagePercent >= 50 ? 'medium' : 'high';

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <div class="ingredient-info">
                                <img src="../${ingredient.image_path || ''}" alt="${ingredient.ingredient_name}" class="ingredient-image">
                                <span class="ingredient-name">${ingredient.ingredient_name}</span>
                            </div>
                        </td>
                        <td>${ingredient.category_name}</td>
                        <td>${currentStock.toFixed(2)} ${ingredient.unit}</td>
                        <td>${totalUsed.toFixed(2)} ${ingredient.unit}</td>
                        <td>
                            <div class="stock-info">
                                <div class="stock-amount-container">
                                    <span class="stock-amount">${currentStock.toFixed(2)}</span>
                                    <span class="stock-unit">${ingredient.unit}</span>
                                </div>
                                <div class="stock-level-container">
                                    <div class="stock-level-bar-container">
                                        <div class="stock-level-bar ${barClass}" 
                                             style="width: ${100 - usagePercent}%"></div>
                                    </div>
                                    <span class="stock-level-percentage">${100 - usagePercent}%</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="usage-status ${usageStatus.toLowerCase()}">
                                ${usageStatus}
                            </span>
                        </td>
                    `;
                    usageTableBody.appendChild(row);
                });
            }

            // Function to determine usage status based on remaining stock
            function getUsageStatus(remaining, unit) {
                unit = unit.toLowerCase();
                
                let criticalThreshold, lowThreshold, mediumThreshold;
                
                switch(unit) {
                    case 'kg':
                    case 'liters':
                    case 'l':
                        criticalThreshold = 0.2;
                        lowThreshold = 0.5;
                        mediumThreshold = 1.0;
                        break;
                    case 'pcs':
                        criticalThreshold = 10;
                        lowThreshold = 20;
                        mediumThreshold = 40;
                        break;
                    default:
                        criticalThreshold = 0.2;
                        lowThreshold = 0.5;
                        mediumThreshold = 1.0;
                }
                
                if (remaining <= criticalThreshold) return 'CRITICAL';
                if (remaining <= lowThreshold) return 'WARNING';
                if (remaining <= mediumThreshold) return 'MEDIUM';
                return 'GOOD';
            }

            // Initial load: categories from DB, then stock rows (matches Usage Analysis category names)
            populateCategoryFilter().then(() => loadIngredients());
        });
    </script>
</body>
</html>