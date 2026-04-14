<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Products Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/products.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <!-- Active Page Detection -->
    <script src="js/active-page.js"></script>
    <style>

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        #selectIngredientsModal {
            z-index: 2000; 
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            z-index: 2001; 
        }
    </style>
</head>
<body>
    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    
    <div class="main-content">
        <div class="products-container">
            <div class="products-header">
                <h1>Products Management</h1>
                <div class="products-filter">
                    <input type="text" id="searchProduct" placeholder="Search products...">
                    <select id="categoryFilter">
                        <option value="all">All Categories</option>
                        <option value="milky">Milky Series</option>
                        <option value="rookie">Rookie Series</option>
                        <option value="choco">Choco-ey Series</option>
                        <option value="cold">Cold Coffee</option>
                        <option value="gold">Gold Series</option>
                    </select>
                </div>
            </div>
            
            <!-- Products Summary Panels -->
            <div class="products-summary">
                <div class="summary-panel total-products" id="totalProductsPanel">
                    <div class="panel-icon">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <div class="panel-info">
                        <h3>Total Products</h3>
                        <p class="panel-count">24</p>
                    </div>
                </div>
                
                <div class="summary-panel total-ingredients" id="totalIngredientsPanel">
                    <div class="panel-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="panel-info">
                        <h3>Total Ingredients</h3>
                        <p class="panel-count">18</p>
                    </div>
                </div>
                
                <div class="summary-panel low-stock" id="lowStockPanel">
                    <div class="panel-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="panel-info">
                        <h3>Low Stock Items</h3>
                        <p class="panel-count">3</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="products">Coffee Products</button>
                    <button class="tab-btn" data-tab="ingredients">Ingredients</button>
                </div>
                
                <div class="tab-content">
                    <!-- Products Tab Content -->
                    <div class="tab-pane active" id="products-tab">
                        <div class="action-bar">
                            <button class="add-btn" id="addProductBtn">
                                <i class="fas fa-plus"></i> Add New Product
                            </button>
                        </div>
                        
                        <div class="products-table-container">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Ingredients</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody">
                                    <!-- Products will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Ingredients Tab Content -->
                    <div class="tab-pane" id="ingredients-tab">
                        <div class="action-bar">
                            <button class="add-btn" id="addIngredientBtn">
                                <i class="fas fa-plus"></i> Add New Ingredient
                            </button>
                        </div>
                        
                        <div class="ingredients-table-container">
                            <table class="ingredients-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Ingredients will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div class="modal" id="addProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Product</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addProductForm">
                    <div class="form-group">
                        <label for="productName">Product Name</label>
                        <input type="text" id="productName" name="productName" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productCategory">Category</label>
                            <select id="productCategory" name="productCategory" required>
                                <option value="">Select Category</option>
                                <option value="milky">Milky Series</option>
                                <option value="rookie">Rookie Series</option>
                                <option value="choco-ey">Choco-ey Series</option>
                                <option value="coffee">Coffee Series</option>
                                <option value="gold">Gold Series</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="productPrice">Price (₱)</label>
                            <input type="number" id="productPrice" name="productPrice" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="productDescription">Description</label>
                        <textarea id="productDescription" name="productDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="productImage">Product Image</label>
                        <input type="file" id="productImage" name="productImage" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>Ingredients</label>
                        <div class="ingredients-selector">
                            <div class="selected-ingredients" id="selectedIngredients">
                                <!-- Selected ingredients will appear here -->
                            </div>
                            <button type="button" class="select-ingredients-btn" id="selectIngredientsBtn">
                                <i class="fas fa-plus"></i> Select Ingredients
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="productStatus">Status</label>
                        <select id="productStatus" name="productStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancelProductBtn">Cancel</button>
                        <button type="submit" class="save-btn">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Ingredient Modal -->
    <div class="modal" id="addIngredientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Ingredient</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addIngredientForm">
                    <input type="hidden" id="ingredientId" name="ingredientId">
                    
                    <div class="form-group">
                        <label for="ingredientName">Ingredient Name</label>
                        <input type="text" id="ingredientName" name="ingredientName" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ingredientCategory">Category</label>
                            <select id="ingredientCategory" name="ingredientCategory" required>
                                <option value="">Select Category</option>
                                <option value="coffee">Coffee</option>
                                <option value="syrup">Syrup</option>
                                <option value="powder">Powder</option>
                                <option value="dairy">Dairy</option>
                                <option value="topping">Topping</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="ingredientStock">Stock</label>
                            <input type="number" id="ingredientStock" name="ingredientStock" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="ingredientUnit">Unit</label>
                            <select id="ingredientUnit" name="ingredientUnit" required>
                                <option value="g">Grams (g)</option>
                                <option value="kg">Kilograms (kg)</option>
                                <option value="ml">Milliliters (ml)</option>
                                <option value="l">Liters (l)</option>
                                <option value="pcs">Pieces (pcs)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ingredientImage">Ingredient Image</label>
                        <input type="file" id="ingredientImage" name="ingredientImage" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="ingredientStatus">Status</label>
                        <select id="ingredientStatus" name="ingredientStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Ingredient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Select Ingredients Modal -->
    <div class="modal" id="selectIngredientsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Select Ingredients</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="ingredients-search">
                    <input type="text" id="searchIngredients" placeholder="Search ingredients...">
                </div>
                
                <div class="ingredients-list">
                    <!-- Ingredients will be populated dynamically -->
                </div>
                
                <div class="selected-ingredients-preview">
                    <h3>Selected Ingredients</h3>
                    <div class="selected-ingredients-list" id="selectedIngredientsPreview">
                        <!-- Selected ingredients will appear here -->
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelSelectIngredientsBtn">Cancel</button>
                    <button type="button" class="confirm-btn" id="confirmIngredientsBtn">Confirm Selection</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div class="modal" id="editProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Product</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editProductForm">
                    <input type="hidden" id="editProductId" name="productId">
                    <div class="form-group">
                        <label for="editProductName">Product Name</label>
                        <input type="text" id="editProductName" name="productName" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductCategory">Category</label>
                            <select id="editProductCategory" name="productCategory" required>
                                <option value="">Select Category</option>
                                <option value="milky">Milky Series</option>
                                <option value="rookie">Rookie Series</option>
                                <option value="choco-ey">Choco-ey Series</option>
                                <option value="coffee">Coffee Series</option>
                                <option value="gold">Gold Series</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editProductPrice">Price (₱)</label>
                            <input type="number" id="editProductPrice" name="productPrice" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editProductDescription">Description</label>
                        <textarea id="editProductDescription" name="productDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editProductImage">Product Image</label>
                        <input type="file" id="editProductImage" name="productImage" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>Ingredients</label>
                        <div class="ingredients-selector">
                            <div class="selected-ingredients" id="editSelectedIngredients">
                                <!-- Selected ingredients will appear here -->
                            </div>
                            <button type="button" class="select-ingredients-btn" id="editSelectIngredientsBtn">
                                <i class="fas fa-plus"></i> Select Ingredients
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editProductStatus">Status</label>
                        <select id="editProductStatus" name="productStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancelEditBtn">Cancel</button>
                        <button type="submit" class="save-btn">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let ingredientCategories = [];
            let productCategories = [];
            let ingredients = [];

            // Function to fetch ingredient categories
            async function fetchIngredientCategories() {
                try {
                    const response = await fetch('../backend/fetch_data.php?action=ingredient-categories');
                    const data = await response.json();
                    
                    if (data.success) {
                        ingredientCategories = data.data;
                        populateIngredientCategoryDropdown();
                    } else {
                        console.error('Error fetching ingredient categories:', data.error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch ingredient categories: ' + data.error,
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching ingredient categories:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch ingredient categories. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                }
            }

            // Function to fetch product categories
            async function fetchProductCategories() {
                try {
                    const response = await fetch('../backend/fetch_data.php?action=product-categories');
                    const data = await response.json();
                    
                    if (data.success) {
                        productCategories = data.data;
                        populateProductCategoryDropdowns();
                    } else {
                        console.error('Error fetching product categories:', data.error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch product categories: ' + data.error,
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching product categories:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch product categories. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                }
            }

            // Function to populate ingredient category dropdown
            function populateIngredientCategoryDropdown() {
                const dropdown = document.getElementById('ingredientCategory');
                if (!dropdown) return;

                // Clear existing options except the first one
                while (dropdown.options.length > 1) {
                    dropdown.remove(1);
                }
                
                // Add new options
                ingredientCategories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_id;
                    option.textContent = category.category_name;
                    dropdown.appendChild(option);
                });
            }

            // Function to populate product category dropdowns
            function populateProductCategoryDropdowns() {
                const dropdowns = [
                    document.getElementById('productCategory'),
                    document.getElementById('editProductCategory')
                ];

                dropdowns.forEach(dropdown => {
                    if (!dropdown) return;

                    // Clear existing options except the first one
                    while (dropdown.options.length > 1) {
                        dropdown.remove(1);
                    }
                    
                    // Add new options
                    productCategories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.category_id;
                        option.textContent = category.category_name;
                        dropdown.appendChild(option);
                    });
                });
            }

            // Function to fetch ingredients
            async function fetchIngredients() {
                try {
                    const response = await fetch('../backend/fetch_data.php?action=ingredients');
                    const data = await response.json();
                    
                    if (data.success) {
                        ingredients = data.data;
                        populateIngredientsTable();
                        populateIngredientsList();
                    } else {
                        console.error('Error fetching ingredients:', data.error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch ingredients: ' + data.error,
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching ingredients:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch ingredients. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                }
            }

            // Function to populate ingredients table
            function populateIngredientsTable() {
                const tbody = document.querySelector('.ingredients-table tbody');
                tbody.innerHTML = ''; // Clear existing rows

                if (ingredients && ingredients.length > 0) {
                    ingredients.forEach(ingredient => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${ingredient.ingredient_name}</td>
                            <td>${ingredient.category_name}</td>
                            <td>${ingredient.stock} ${ingredient.unit}</td>
                            <td><span class="status-badge ${ingredient.status}">${ingredient.status}</span></td>
                            <td class="action-buttons">
                                <button class="action-btn edit" data-ingredient-id="${ingredient.ingredient_id}" title="Edit Ingredient">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn view" data-ingredient-id="${ingredient.ingredient_id}" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn delete" data-ingredient-id="${ingredient.ingredient_id}" title="Delete Ingredient">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    // Show a message when no ingredients are available
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td colspan="5" class="text-center">No ingredients available</td>
                    `;
                    tbody.appendChild(row);
                }

                // Update the ingredients count in the summary panel
                document.querySelector('#totalIngredientsPanel .panel-count').textContent = ingredients ? ingredients.length : 0;
            }

            // Function to filter ingredients based on search query
            function filterIngredients(searchQuery) {
                const ingredientsList = document.querySelector('.ingredients-list');
                const items = ingredientsList.querySelectorAll('.ingredient-item');
                
                searchQuery = searchQuery.toLowerCase().trim();
                
                items.forEach(item => {
                    const ingredientName = item.querySelector('.ingredient-name').textContent.toLowerCase();
                    const ingredientCategory = item.querySelector('.ingredient-category').textContent.toLowerCase();
                    
                    if (ingredientName.includes(searchQuery) || ingredientCategory.includes(searchQuery)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Show "no results" message if no items are visible
                const visibleItems = ingredientsList.querySelectorAll('.ingredient-item[style=""]').length;
                let noResultsMessage = ingredientsList.querySelector('.no-results-message');
                
                if (visibleItems === 0) {
                    if (!noResultsMessage) {
                        noResultsMessage = document.createElement('div');
                        noResultsMessage.className = 'no-results-message';
                        noResultsMessage.innerHTML = `
                            <i class="fas fa-search"></i>
                            <p>No ingredients found matching "${searchQuery}"</p>
                        `;
                        ingredientsList.appendChild(noResultsMessage);
                    }
                } else if (noResultsMessage) {
                    noResultsMessage.remove();
                }
            }

            // Add event listener for search input
            document.getElementById('searchIngredients').addEventListener('input', function(e) {
                filterIngredients(e.target.value);
            });

            // Function to populate ingredients list in the select ingredients modal
            function populateIngredientsList() {
                const ingredientsList = document.querySelector('.ingredients-list');
                ingredientsList.innerHTML = ''; // Clear existing items

                if (ingredients && ingredients.length > 0) {
                    ingredients.forEach(ingredient => {
                        const item = document.createElement('div');
                        item.className = 'ingredient-item';
                        
                        // Create image element with error handling
                        const img = document.createElement('img');
                        img.className = 'ingredient-image';
                        img.alt = ingredient.ingredient_name;
                        
                        // Set image source with fallback
                        if (ingredient.image_path) {
                            img.src = '../' + ingredient.image_path;
                            img.onerror = function() {
                                this.src = '../assets/images/ingredients/default.jpg';
                            };
                        } else {
                            img.src = '../assets/images/ingredients/default.jpg';
                        }
                        
                        item.innerHTML = `
                            <div class="ingredient-content">
                                <div class="ingredient-header">
                                    <div class="ingredient-checkbox">
                                        <input type="checkbox" id="ing${ingredient.ingredient_id}" name="ingredients[]" value="${ingredient.ingredient_id}">
                                        <label for="ing${ingredient.ingredient_id}" class="ingredient-name">${ingredient.ingredient_name}</label>
                                    </div>
                                    <span class="ingredient-category">${ingredient.category_name}</span>
                                </div>
                                <div class="ingredient-stock">
                                    <i class="fas fa-box"></i>
                                    <span>Current Stock: ${ingredient.stock} ${ingredient.unit}</span>
                                </div>
                                <div class="ingredient-inputs">
                                    <div class="input-group">
                                        <label>Amount</label>
                                        <input type="number" class="ingredient-measurement" placeholder="0.00" min="0" step="0.01">
                                    </div>
                                    <div class="input-group">
                                        <label>Unit</label>
                                        <select class="ingredient-unit">
                                            <option value="${ingredient.unit}" selected>${ingredient.unit}</option>
                                            ${getUnitOptions(ingredient.unit)}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Insert image at the beginning of the card
                        item.insertBefore(img, item.firstChild);
                        
                        // Add change event to the checkbox
                        const checkbox = item.querySelector('input[type="checkbox"]');
                        checkbox.addEventListener('change', function() {
                            item.classList.toggle('selected', this.checked);
                            updateSelectedIngredientsPreview();
                        });
                        
                        // Add input event to the measurement field
                        const measurementInput = item.querySelector('.ingredient-measurement');
                        measurementInput.addEventListener('input', function() {
                            if (checkbox.checked) {
                                updateSelectedIngredientsPreview();
                            }
                        });
                        
                        // Add change event to the unit select
                        const unitSelect = item.querySelector('.ingredient-unit');
                        unitSelect.addEventListener('change', function() {
                            if (checkbox.checked) {
                                updateSelectedIngredientsPreview();
                            }
                        });
                        
                        ingredientsList.appendChild(item);
                    });
                } else {
                    const message = document.createElement('div');
                    message.className = 'no-ingredients';
                    message.innerHTML = `
                        <i class="fas fa-box-open"></i>
                        <p>No ingredients available</p>
                    `;
                    ingredientsList.appendChild(message);
                }
            }

            // Helper function to get unit options
            function getUnitOptions(currentUnit) {
                const units = ['g', 'kg', 'ml', 'l', 'pcs'];
                return units
                    .filter(unit => unit !== currentUnit)
                    .map(unit => `<option value="${unit}">${unit}</option>`)
                    .join('');
            }

            // Function to fetch products
            async function fetchProducts() {
                try {
                    const response = await fetch('../backend/fetch_data.php?action=products');
                    const data = await response.json();
                    
                    if (data.success) {
                        populateProductsTable(data.data);
                    } else {
                        console.error('Error fetching products:', data.error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch products: ' + data.error,
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching products:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch products. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                }
            }

            // Function to update total products count
            async function updateTotalProductsCount() {
                try {
                    const response = await fetch('../backend/fetch_data.php?action=total-products');
                    const data = await response.json();
                    
                    if (data.success) {
                        const totalProductsPanel = document.querySelector('#totalProductsPanel .panel-count');
                        if (totalProductsPanel) {
                            totalProductsPanel.textContent = data.data;
                        }
                    } else {
                        console.error('Error fetching total products count:', data.error);
                    }
                } catch (error) {
                    console.error('Error fetching total products count:', error);
                }
            }

            // Function to filter products based on search query and category
            function filterProducts(searchQuery, categoryFilter) {
                const tbody = document.getElementById('productsTableBody');
                const rows = tbody.querySelectorAll('tr');
                
                searchQuery = searchQuery.toLowerCase().trim();
                
                rows.forEach(row => {
                    const productName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const productCategory = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    
                    const matchesSearch = productName.includes(searchQuery);
                    const matchesCategory = categoryFilter === 'all' || productCategory.includes(categoryFilter.toLowerCase());
                    
                    if (matchesSearch && matchesCategory) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show "no results" message if no rows are visible
                const visibleRows = tbody.querySelectorAll('tr[style=""]').length;
                let noResultsRow = tbody.querySelector('.no-results-message');
                
                if (visibleRows === 0) {
                    if (!noResultsRow) {
                        noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results-message';
                        noResultsRow.innerHTML = `
                            <td colspan="7" class="text-center">
                                <i class="fas fa-search"></i>
                                <p>No products found matching your search criteria</p>
                            </td>
                        `;
                        tbody.appendChild(noResultsRow);
                    }
                } else if (noResultsRow) {
                    noResultsRow.remove();
                }
            }

            // Add event listeners for search and filter
            document.getElementById('searchProduct').addEventListener('input', function(e) {
                const searchQuery = e.target.value;
                const categoryFilter = document.getElementById('categoryFilter').value;
                filterProducts(searchQuery, categoryFilter);
            });

            document.getElementById('categoryFilter').addEventListener('change', function(e) {
                const categoryFilter = e.target.value;
                const searchQuery = document.getElementById('searchProduct').value;
                filterProducts(searchQuery, categoryFilter);
            });

            // Function to populate products table
            function populateProductsTable(products) {
                const tbody = document.getElementById('productsTableBody');
                tbody.innerHTML = ''; // Clear existing rows

                if (products && products.length > 0) {
                    products.forEach(product => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <img src="../${product.image_path || 'assets/images/products/default.jpg'}" 
                                     alt="${product.product_name}" 
                                     class="product-img">
                            </td>
                            <td>${product.product_name}</td>
                            <td>${product.category_name}</td>
                            <td>₱${parseFloat(product.price).toFixed(2)}</td>
                            <td>
                                <div class="ingredients-list">
                                    ${product.ingredients ? product.ingredients.map(ing => 
                                        `<span class="ingredient-tag">${ing.ingredient_name} (${ing.quantity} ${ing.unit})</span>`
                                    ).join('') : 'No ingredients'}
                                </div>
                            </td>
                            <td><span class="status-badge ${product.status}">${product.status}</span></td>
                            <td class="action-buttons">
                                <button class="action-btn edit" data-product-id="${product.product_id}" title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn view" data-product-id="${product.product_id}" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn delete" data-product-id="${product.product_id}" title="Delete Product">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Add event listeners after populating the table
                    setupActionButtons();
                } else {
                    const row = document.createElement('tr');
                    row.className = 'no-results-message';
                    row.innerHTML = `
                        <td colspan="7" class="text-center">
                            <i class="fas fa-box-open"></i>
                            <p>No products available</p>
                        </td>
                    `;
                    tbody.appendChild(row);
                }

                // Update the total products count
                updateTotalProductsCount();
            }

            // Function to setup action button event listeners
            function setupActionButtons() {
                document.querySelectorAll('.action-btn').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const action = this.classList.contains('edit') ? 'edit' : 
                                      this.classList.contains('view') ? 'view' : 'delete';
                        
                        if (this.hasAttribute('data-product-id')) {
                            const productId = this.getAttribute('data-product-id');
                            handleProductAction(action, productId);
                        }
                    });
                });
            }

            // Function to handle product actions
            function handleProductAction(action, productId) {
                if (action === 'edit') {
                    editProduct(productId);
                } else if (action === 'view') {
                    viewProduct(productId);
                } else if (action === 'delete') {
                    deleteProduct(productId);
                }
            }

            // Function to edit product
            function editProduct(productId) {
                fetch(`../backend/fetch_product_details.php?product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const product = data.data;
                            
                            // Populate the edit form
                            document.getElementById('editProductId').value = product.product_id;
                            document.getElementById('editProductName').value = product.product_name;
                            document.getElementById('editProductCategory').value = product.category_id;
                            document.getElementById('editProductPrice').value = product.price;
                            document.getElementById('editProductDescription').value = product.description;
                            document.getElementById('editProductStatus').value = product.status;
                            
                            // Store the current ingredients for later use
                            const currentIngredients = product.ingredients || [];
                            
                            // Show the edit modal
                            document.getElementById('editProductModal').style.display = 'flex';
                            
                            // Show the select ingredients modal
                            document.getElementById('selectIngredientsModal').style.display = 'flex';
                            
                            // Wait for the ingredients list to be populated
                            setTimeout(() => {
                                // Pre-populate the ingredients
                                currentIngredients.forEach(ingredient => {
                                    const checkbox = document.querySelector(`#selectIngredientsModal input[type="checkbox"][value="${ingredient.ingredient_id}"]`);
                                    if (checkbox) {
                                        // Check the checkbox
                                        checkbox.checked = true;
                                        
                                        // Get the ingredient item
                                        const ingredientItem = checkbox.closest('.ingredient-item');
                                        
                                        // Set the selected class
                                        ingredientItem.classList.add('selected');
                                        
                                        // Set the quantity and unit
                                        const measurementInput = ingredientItem.querySelector('.ingredient-measurement');
                                        const unitSelect = ingredientItem.querySelector('.ingredient-unit');
                                        
                                        if (measurementInput) measurementInput.value = ingredient.quantity;
                                        if (unitSelect) unitSelect.value = ingredient.unit;
                                    }
                                });
                                
                                // Update the preview
                                updateSelectedIngredientsPreview();
                            }, 100);
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.error,
                                icon: 'error',
                                confirmButtonColor: '#634832'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch product details',
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    });
            }

            // Function to update the selected ingredients preview
            function updateSelectedIngredientsPreview() {
                const selectedIngredients = [];
                const checkboxes = document.querySelectorAll('#selectIngredientsModal input[type="checkbox"]:checked');
                
                checkboxes.forEach(checkbox => {
                    const ingredientItem = checkbox.closest('.ingredient-item');
                    const measurement = ingredientItem.querySelector('.ingredient-measurement').value;
                    const unit = ingredientItem.querySelector('.ingredient-unit').value;
                    const ingredientId = checkbox.value;
                    const ingredient = ingredients.find(i => i.ingredient_id == ingredientId);
                    
                    if (measurement && unit && ingredient) {
                        selectedIngredients.push({
                            id: ingredientId,
                            name: ingredient.ingredient_name,
                            quantity: parseFloat(measurement),
                            unit: unit
                        });
                    }
                });
                
                // Update the preview
                const previewContainer = document.getElementById('selectedIngredientsPreview');
                previewContainer.innerHTML = '';
                
                selectedIngredients.forEach(ingredient => {
                    const tag = document.createElement('div');
                    tag.className = 'selected-ingredient-tag';
                    tag.innerHTML = `
                        <span>${ingredient.name} (${ingredient.quantity} ${ingredient.unit})</span>
                        <span class="remove-btn">&times;</span>
                    `;
                    previewContainer.appendChild(tag);
                });
                
                // Store the selected ingredients in a hidden input
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'ingredients';
                hiddenInput.value = JSON.stringify(selectedIngredients);
                previewContainer.appendChild(hiddenInput);
            }

            // Function to view product
            function viewProduct(productId) {
                fetch(`../backend/fetch_product_details.php?product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const product = data.data;
                            Swal.fire({
                                title: 'Product Details',
                                html: `
                                    <div class="product-details">
                                        <div class="product-image">
                                            <img src="../${product.image_path || 'assets/images/products/default.jpg'}" 
                                                 alt="${product.product_name}">
                                        </div>
                                        <div class="product-info">
                                            <p><strong>Name:</strong> ${product.product_name}</p>
                                            <p><strong>Category:</strong> ${product.category_name}</p>
                                            <p><strong>Price:</strong> ₱${parseFloat(product.price).toFixed(2)}</p>
                                            <p><strong>Description:</strong> ${product.description || 'No description'}</p>
                                            <p><strong>Status:</strong> ${product.status}</p>
                                            <p><strong>Ingredients:</strong></p>
                                            <div class="ingredients-list">
                                                ${product.ingredients ? product.ingredients.map(ing => 
                                                    `<span class="ingredient-tag">${ing.ingredient_name} (${ing.quantity} ${ing.unit})</span>`
                                                ).join('') : 'No ingredients'}
                                            </div>
                                        </div>
                                    </div>
                                `,
                                width: '600px',
                                confirmButtonColor: '#634832'
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.error,
                                icon: 'error',
                                confirmButtonColor: '#634832'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch product details',
                            icon: 'error',
                            confirmButtonColor: '#634832'
                        });
                    });
            }

            // Function to delete product
            function deleteProduct(productId) {
                Swal.fire({
                    title: 'Delete Product?',
                    text: 'Are you sure you want to delete this product? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#634832',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('product_id', productId);
                        
                        fetch('../backend/delete_product.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: '#634832'
                                }).then(() => {
                                    // Refresh the page
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.error,
                                    icon: 'error',
                                    confirmButtonColor: '#634832'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to delete product',
                                icon: 'error',
                                confirmButtonColor: '#634832'
                            });
                        });
                    }
                });
            }

            // Fetch data when the page loads
            fetchIngredientCategories();
            fetchProductCategories();
            fetchIngredients();
            fetchProducts();

            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding pane
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Panel click handlers
            document.getElementById('totalProductsPanel').addEventListener('click', function() {
                // Switch to products tab
                document.querySelector('.tab-btn[data-tab="products"]').click();
            });
            
            document.getElementById('totalIngredientsPanel').addEventListener('click', function() {
                // Switch to ingredients tab
                document.querySelector('.tab-btn[data-tab="ingredients"]').click();
            });
            
            document.getElementById('lowStockPanel').addEventListener('click', function() {
                // Switch to ingredients tab and filter for low stock
                document.querySelector('.tab-btn[data-tab="ingredients"]').click();
                // In a real application, this would filter the ingredients table
                Swal.fire({
                    title: 'Low Stock Items',
                    text: 'Showing ingredients with low stock levels',
                    icon: 'info',
                    confirmButtonColor: '#634832'
                });
            });
            
            // Modal functionality
            const modals = document.querySelectorAll('.modal');
            const closeButtons = document.querySelectorAll('.close-btn');
            const cancelButtons = document.querySelectorAll('.cancel-btn');
            
            // Open modals
            document.getElementById('addProductBtn').addEventListener('click', function() {
                document.getElementById('addProductModal').style.display = 'flex';
            });
            
            document.getElementById('addIngredientBtn').addEventListener('click', function() {
                document.getElementById('addIngredientModal').style.display = 'flex';
            });
            
            document.getElementById('selectIngredientsBtn').addEventListener('click', function() {
                document.getElementById('selectIngredientsModal').style.display = 'flex';
            });
            
            // Close modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });
            
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                modals.forEach(modal => {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });
            
            // Add event listener for edit select ingredients button
            document.getElementById('editSelectIngredientsBtn').addEventListener('click', function() {
                document.getElementById('selectIngredientsModal').style.display = 'flex';
            });

            // Update the confirm ingredients selection function
            document.getElementById('confirmIngredientsBtn').addEventListener('click', function() {
                // Get the selected ingredients from the preview
                const hiddenInput = document.querySelector('#selectedIngredientsPreview input[name="ingredients"]');
                if (!hiddenInput) return;
                
                const selectedIngredients = JSON.parse(hiddenInput.value);
                
                // Update the selected ingredients display in the product form
                const selectedIngredientsContainer = document.getElementById('selectedIngredients');
                selectedIngredientsContainer.innerHTML = '';
                
                selectedIngredients.forEach(ingredient => {
                    const tag = document.createElement('div');
                    tag.className = 'ingredient-tag';
                    tag.innerHTML = `
                        <span>${ingredient.name} (${ingredient.quantity} ${ingredient.unit})</span>
                        <span class="remove-btn">&times;</span>
                    `;
                    selectedIngredientsContainer.appendChild(tag);
                });
                
                // Store the ingredients data in a hidden input in the product form
                let hiddenProductInput = document.querySelector('#addProductForm input[name="ingredients"]');
                if (!hiddenProductInput) {
                    hiddenProductInput = document.createElement('input');
                    hiddenProductInput.type = 'hidden';
                    hiddenProductInput.name = 'ingredients';
                    document.getElementById('addProductForm').appendChild(hiddenProductInput);
                }
                hiddenProductInput.value = JSON.stringify(selectedIngredients);
                
                // Close the select ingredients modal
                document.getElementById('selectIngredientsModal').style.display = 'none';
            });
            
            // Update the add product form submission handler
            document.getElementById('addProductForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    // Get form data
                    const formData = new FormData(this);
                    
                    // Get selected ingredients
                    const ingredientsInput = this.querySelector('input[name="ingredients"]');
                    if (!ingredientsInput || !ingredientsInput.value) {
                        throw new Error('Please select at least one ingredient');
                    }
                    
                    // Send AJAX request
                    const response = await fetch('../backend/add_product.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#634832'
                        }).then(() => {
                            // Close the modal and refresh the products table
                            document.getElementById('addProductModal').style.display = 'none';
                            this.reset();
                            document.getElementById('selectedIngredients').innerHTML = '';
                            fetchProducts();
                        });
                    } else {
                        throw new Error(data.error || 'Failed to add product');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message,
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                }
            });

            // Add event listener for remove ingredient buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-btn')) {
                    const tag = e.target.closest('.ingredient-tag');
                    if (tag) {
                        tag.remove();
                        // Update the hidden input value
                        const container = tag.closest('.selected-ingredients');
                        if (container) {
                            const remainingTags = container.querySelectorAll('.ingredient-tag');
                            const ingredients = Array.from(remainingTags).map(tag => {
                                const text = tag.querySelector('span').textContent;
                                const match = text.match(/(.*?)\s*\(([\d.]+)\s*(.*?)\)/);
                                if (match) {
                                    return {
                                        name: match[1].trim(),
                                        quantity: parseFloat(match[2]),
                                        unit: match[3].trim()
                                    };
                                }
                            }).filter(Boolean);
                            
                            const hiddenInput = container.closest('form').querySelector('input[name="ingredients"]');
                            if (hiddenInput) {
                                hiddenInput.value = JSON.stringify(ingredients);
                            }
                        }
                    }
                }
            });

            // Add ingredient form submission handler
            document.getElementById('addIngredientForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    const formData = new FormData(this);
                    const isEdit = formData.get('ingredientId') !== null;
                    
                    // Send the form data to the appropriate endpoint
                    const response = await fetch(`../backend/${isEdit ? 'update' : 'add'}_ingredient.php`, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            title: 'Success',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#634832'
                        });
                        
                        // Close the modal and refresh the ingredients table
                        document.getElementById('addIngredientModal').style.display = 'none';
                        this.reset();
                        fetchIngredients();
                    } else {
                        throw new Error(data.error);
                    }
                } catch (error) {
                    console.error('Error saving ingredient:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to save ingredient. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#634832'
                    });
                }
            });
            
            // Add event listener for add ingredient button
            document.getElementById('addIngredientBtn').addEventListener('click', function() {
                // Reset the form and show the modal
                document.getElementById('addIngredientForm').reset();
                document.getElementById('addIngredientModal').style.display = 'flex';
                document.querySelector('#addIngredientModal .modal-header h2').textContent = 'Add New Ingredient';
                document.querySelector('#addIngredientForm .save-btn').textContent = 'Save Ingredient';
            });

            // Add event listener for modal close button
            document.querySelector('#addIngredientModal .close-btn').addEventListener('click', function() {
                document.getElementById('addIngredientModal').style.display = 'none';
            });
        });
    </script>
</body>
</html>
