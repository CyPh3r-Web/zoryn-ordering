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
    /* Add these styles to your existing CSS */
    .inventory-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .tab-btn {
        padding: 0.5rem 1rem;
        border: none;
        background: #f5f5f5;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        background: #634832;
        color: white;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .usage-status {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .usage-status.critical {
        background: #fee2e2;
        color: #dc2626;
    }

    .usage-status.warning {
        background: #fef3c7;
        color: #d97706;
    }

    .usage-status.good {
        background: #dcfce7;
        color: #16a34a;
    }
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
                        populateCategoryFilter(data.data);
                    } else {
                        console.error('Invalid data format:', data);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to load ingredients: Invalid data format',
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to load ingredients',
                        icon: 'error',
                        confirmButtonColor: '#634832'
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
                    if (categoryFilterValue && ingredient.category_id != categoryFilterValue) return;
                    
                    // Convert stock to number
                    const stock = parseFloat(ingredient.stock);
                    
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
                                        <div class="stock-level-bar ${getStockLevel(stock, ingredient.unit)}" 
                                             style="width: ${calculatePercentage(stock, ingredient.unit)}%"></div>
                                    </div>
                                    <span class="stock-level-percentage">${calculatePercentage(stock, ingredient.unit)}%</span>
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
                    
                    ingredientsTableBody.appendChild(row);
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

            // Calculate percentage for progress bar based on medium threshold
            function calculatePercentage(stock, unit) {
                let mediumThreshold;
                
                switch(unit.toLowerCase()) {
                    case 'kg':
                    case 'liters':
                    case 'l':
                        mediumThreshold = 1.0; // Medium threshold is 1.0 kg/liters
                        break;
                    case 'pcs':
                        mediumThreshold = 40; // Medium threshold is 40 pieces
                        break;
                    default:
                        mediumThreshold = 1.0;
                }
                
                // Calculate percentage based on medium threshold
                // If stock is above medium, show 100%
                // If stock is below medium, show percentage of medium
                const percentage = Math.round((stock / mediumThreshold) * 100);
                return Math.min(percentage, 100);
            }

            // Populate category filter
            function populateCategoryFilter(ingredients) {
                if (!categoryFilter || !Array.isArray(ingredients)) {
                    console.error('Category filter element not found or invalid ingredients data');
                    return;
                }
                
                // Define the categories with their IDs and names
                const categories = [
                    { id: 1, name: 'Coffee' },
                    { id: 2, name: 'Syrup' },
                    { id: 3, name: 'Powder' },
                    { id: 4, name: 'Dairy' },
                    { id: 5, name: 'Topping' },
                    { id: 6, name: 'Other' }
                ];
                
                categoryFilter.innerHTML = '<option value="">All Categories</option>';
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    categoryFilter.appendChild(option);
                });
            }

            // Open update modal
            window.openUpdateModal = function(ingredientId, currentStock) {
                if (!stockUpdateModal) return;
                document.getElementById('ingredientId').value = ingredientId;
                document.getElementById('currentStock').value = currentStock;
                stockUpdateModal.style.display = 'block';
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
                            confirmButtonColor: '#634832'
                        }).then(() => {
                            stockUpdateModal.style.display = 'none';
                            loadIngredients();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Failed to update stock',
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to update stock',
                        icon: 'error',
                        confirmButtonColor: '#634832'
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
                                confirmButtonColor: '#634832'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to load usage data',
                            icon: 'error',
                            confirmButtonColor: '#634832'
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
                    const remaining = currentStock - totalUsed;
                    const usageStatus = getUsageStatus(remaining, ingredient.unit);

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
                        <td>${remaining.toFixed(2)} ${ingredient.unit}</td>
                        <td>
                            <span class="usage-status ${usageStatus.toLowerCase()}">
                                ${usageStatus}
                            </span>
                        </td>
                    `;
                    usageTableBody.appendChild(row);
                });
            }

            // Function to determine usage status
            function getUsageStatus(remaining, unit) {
                unit = unit.toLowerCase();
                
                let criticalThreshold, lowThreshold;
                
                switch(unit) {
                    case 'kg':
                    case 'liters':
                    case 'l':
                        criticalThreshold = 0.2;
                        lowThreshold = 0.5;
                        break;
                    case 'pcs':
                        criticalThreshold = 10;
                        lowThreshold = 20;
                        break;
                    default:
                        criticalThreshold = 0.2;
                        lowThreshold = 0.5;
                }
                
                if (remaining <= criticalThreshold) return 'CRITICAL';
                if (remaining <= lowThreshold) return 'WARNING';
                return 'GOOD';
            }

            // Initial load
            loadIngredients();
        });
    </script>
</body>
</html>