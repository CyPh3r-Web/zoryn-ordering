<?php
function renderProductsIcon($name, $class = 'h-4 w-4') {
    $baseClass = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    $icons = [
        'menu' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 7.5h15"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 12h9"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 16.5h6"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 4.5h12a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z"></path></svg>',
        'ingredients' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 6.75h9"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3.75h10.5l.75 6.75a6 6 0 1 1-12 0l.75-6.75Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 11.25h6"></path></svg>',
        'warning' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m10.34 3.94-7.5 13A1.5 1.5 0 0 0 4.14 19.5h15.72a1.5 1.5 0 0 0 1.3-2.56l-7.5-13a1.5 1.5 0 0 0-2.6 0Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16.5h.008v.008H12V16.5Z"></path></svg>',
        'plus' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5.25v13.5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 12h13.5"></path></svg>',
        'edit' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.862 4.487 2.651 2.651"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 6.75 9 17.25l-4.5 1.5L6 14.25 16.5 3.75a2.121 2.121 0 0 1 3 3Z"></path></svg>',
        'view' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6S2.25 12 2.25 12Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14.25A2.25 2.25 0 1 0 12 9.75a2.25 2.25 0 0 0 0 4.5Z"></path></svg>',
        'delete' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 7.5h15"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 10.5v5.25"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.25 10.5v5.25"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5 7.5 18A1.5 1.5 0 0 0 9 19.5h6a1.5 1.5 0 0 0 1.5-1.5l.75-10.5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 4.5h4.5"></path></svg>',
        'search' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35"></path><circle cx="11" cy="11" r="6" stroke-width="1.8"></circle></svg>',
        'stock' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 7.5 12 3.75l7.5 3.75L12 11.25 4.5 7.5Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 12 12 15.75 19.5 12"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 16.5 12 20.25l7.5-3.75"></path></svg>',
        'empty' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 5.25h9l2.25 3.375-6.75 4.125-6.75-4.125L7.5 5.25Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 8.625V17.25A1.5 1.5 0 0 0 6.75 18.75h10.5a1.5 1.5 0 0 0 1.5-1.5V8.625"></path></svg>',
    ];

    return $icons[$name] ?? '';
}
?>
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
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
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
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        #selectIngredientsModal { z-index: 2000; }
        .vat-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .vat-badge-taxable {
            background: rgba(212,175,55,0.12);
            color: #D4AF37;
            border: 1px solid rgba(212,175,55,0.25);
        }
        .vat-badge-exempt {
            background: rgba(100,100,100,0.15);
            color: #888;
            border: 1px solid rgba(100,100,100,0.25);
        }
        .ingredient-unit-cost {
            font-size: 12px;
            color: #9a9a9a;
            margin-top: 4px;
        }
        .recipe-cost-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #c9a227;
            margin: 4px 0 0;
        }
        .modal-content {
            background-color: #1F1F1F;
            border: 1px solid rgba(212,175,55,0.15);
            padding: 32px;
            border-radius: 20px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            z-index: 2001;
            box-shadow: 0 16px 48px rgba(0,0,0,0.4);
        }
    </style>
</head>
<body>
    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    
    <div class="main-content">
        <div class="products-container">
            <div class="page-header">
                <h1><span class="premium-inline-icon"><?= renderProductsIcon('menu') ?></span>Products Management</h1>
                <div class="filter-bar">
                    <input type="text" id="searchProduct" placeholder="Search products...">
                    <select id="categoryFilter">
                        <option value="all">All Categories</option>
                        <option value="milky">Milky Series</option>
                        <option value="rookie">Rookie Series</option>
                        <option value="choco">Choco-ey Series</option>
                        <option value="cold">Beverages</option>
                        <option value="gold">Gold Series</option>
                    </select>
                </div>
            </div>
            
            <!-- Products Summary Panels -->
            <div class="products-summary">
                <div class="stat-card" id="totalProductsPanel">
                    <div class="stat-icon stat-icon-gold"><?= renderProductsIcon('menu', 'h-[18px] w-[18px]') ?></div>
                    <p class="stat-label">Total Products</p>
                    <p class="stat-value panel-count">24</p>
                </div>
                <div class="stat-card" id="totalIngredientsPanel">
                    <div class="stat-icon stat-icon-info"><?= renderProductsIcon('ingredients', 'h-[18px] w-[18px]') ?></div>
                    <p class="stat-label">Total Ingredients</p>
                    <p class="stat-value panel-count">18</p>
                </div>
                <div class="stat-card" id="lowStockPanel">
                    <div class="stat-icon stat-icon-danger"><?= renderProductsIcon('warning', 'h-[18px] w-[18px]') ?></div>
                    <p class="stat-label">Low Stock Items</p>
                    <p class="stat-value panel-count">3</p>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="products">Menu Products</button>
                    <button class="tab-btn" data-tab="ingredients">Ingredients</button>
                </div>
                
                <div class="tab-content">
                    <!-- Products Tab Content -->
                    <div class="tab-pane active" id="products-tab">
                        <div class="action-bar">
                            <button class="btn-gold" id="addProductBtn">
                                <span class="premium-btn-icon"><?= renderProductsIcon('plus') ?></span> Add New Product
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
                                        <th>Est. COGS</th>
                                        <th>VAT</th>
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
                            <button class="btn-gold" id="addIngredientBtn">
                                <span class="premium-btn-icon"><?= renderProductsIcon('plus') ?></span> Add New Ingredient
                            </button>
                        </div>
                        
                        <div class="ingredients-table-container">
                            <table class="ingredients-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Stock</th>
                                        <th>Unit cost</th>
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
                                <option value="coffee">Beverages</option>
                                <option value="gold">Gold Series</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="productPrice">Price (₱) <span style="font-size:11px;color:#888;">VAT-inclusive</span></label>
                            <input type="number" id="productPrice" name="productPrice" min="0" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="productTaxRate">VAT Rate (%)</label>
                            <select id="productTaxRate" name="productTaxRate" required>
                                <option value="12.00" selected>12% (Standard VAT)</option>
                                <option value="0.00">0% (VAT-Exempt)</option>
                                <option value="5.00">5% (Custom)</option>
                            </select>
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
                                <span class="premium-btn-icon"><?= renderProductsIcon('plus') ?></span> Select Ingredients
                            </button>
                        </div>
                    </div>

                    <div class="form-group recipe-cost-summary">
                        <label>Est. ingredient cost (COGS)</label>
                        <p class="recipe-cost-value" id="addRecipeCostValue">₱0.00</p>
                        <p style="font-size:11px;color:#888;margin-top:4px;">Uses each ingredient’s current unit cost (updated when you receive purchase orders).</p>
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
                                <option value="coffee">Beverage Base</option>
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
                                <option value="coffee">Beverages</option>
                                <option value="gold">Gold Series</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editProductPrice">Price (₱) <span style="font-size:11px;color:#888;">VAT-inclusive</span></label>
                            <input type="number" id="editProductPrice" name="productPrice" min="0" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="editProductTaxRate">VAT Rate (%)</label>
                            <select id="editProductTaxRate" name="productTaxRate" required>
                                <option value="12.00">12% (Standard VAT)</option>
                                <option value="0.00">0% (VAT-Exempt)</option>
                                <option value="5.00">5% (Custom)</option>
                            </select>
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
                                <span class="premium-btn-icon"><?= renderProductsIcon('plus') ?></span> Select Ingredients
                            </button>
                        </div>
                    </div>

                    <div class="form-group recipe-cost-summary">
                        <label>Est. ingredient cost (COGS)</label>
                        <p class="recipe-cost-value" id="editRecipeCostValue">₱0.00</p>
                        <p style="font-size:11px;color:#888;margin-top:4px;">Uses each ingredient’s current unit cost (updated when you receive purchase orders).</p>
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
            let selectedProductIngredients = [];
            let isEditingProduct = false;

            function convertQtyToStockUnitClient(qty, fromUnit, toUnit) {
                const from = String(fromUnit).toLowerCase().trim();
                const to = String(toUnit).toLowerCase().trim();
                const q = parseFloat(qty) || 0;
                if (from === to) return q;
                const weightToG = { kg: 1000, g: 1, mg: 0.001, oz: 28.3495, lb: 453.592 };
                const volToMl = { liters: 1000, l: 1000, ml: 1, cup: 236.588, tbsp: 14.7868, tsp: 4.92892, 'fl oz': 29.5735 };
                const countUnits = ['pcs', 'pieces', 'units'];
                if (weightToG[from] !== undefined && weightToG[to] !== undefined) {
                    return q * weightToG[from] / weightToG[to];
                }
                if (volToMl[from] !== undefined && volToMl[to] !== undefined) {
                    return q * volToMl[from] / volToMl[to];
                }
                if (countUnits.includes(from) && countUnits.includes(to)) return q;
                return q;
            }

            function recipeLineCostClient(ing) {
                const full = ingredients.find(i => String(i.ingredient_id) === String(ing.id));
                if (!full) return 0;
                const qty = convertQtyToStockUnitClient(ing.quantity, ing.unit, full.unit);
                return Math.round(qty * parseFloat(full.default_unit_cost || 0) * 100) / 100;
            }

            function totalRecipeCostClient(arr) {
                if (!arr || !arr.length) return 0;
                return Math.round(arr.reduce((s, x) => s + recipeLineCostClient(x), 0) * 100) / 100;
            }

            function updateProductRecipeSummaries() {
                const t = totalRecipeCostClient(selectedProductIngredients);
                const addEl = document.getElementById('addRecipeCostValue');
                const editEl = document.getElementById('editRecipeCostValue');
                if (addEl) addEl.textContent = '₱' + t.toFixed(2);
                if (editEl) editEl.textContent = '₱' + t.toFixed(2);
            }

            function premiumIcon(name, className = 'h-4 w-4') {
                const baseClass = className;
                const icons = {
                    edit: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="${baseClass}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.862 4.487 2.651 2.651"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 6.75 9 17.25l-4.5 1.5L6 14.25 16.5 3.75a2.121 2.121 0 0 1 3 3Z"></path></svg>`,
                    view: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="${baseClass}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6S2.25 12 2.25 12Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14.25A2.25 2.25 0 1 0 12 9.75a2.25 2.25 0 0 0 0 4.5Z"></path></svg>`,
                    delete: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="${baseClass}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 7.5h15"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 10.5v5.25"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.25 10.5v5.25"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5 7.5 18A1.5 1.5 0 0 0 9 19.5h6a1.5 1.5 0 0 0 1.5-1.5l.75-10.5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 4.5h4.5"></path></svg>`,
                    search: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="${baseClass}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35"></path><circle cx="11" cy="11" r="6" stroke-width="1.8"></circle></svg>`,
                    stock: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="${baseClass}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 7.5 12 3.75l7.5 3.75L12 11.25 4.5 7.5Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 12 12 15.75 19.5 12"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 16.5 12 20.25l7.5-3.75"></path></svg>`,
                    empty: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="${baseClass}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 5.25h9l2.25 3.375-6.75 4.125-6.75-4.125L7.5 5.25Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 8.625V17.25A1.5 1.5 0 0 0 6.75 18.75h10.5a1.5 1.5 0 0 0 1.5-1.5V8.625"></path></svg>`
                };
                return icons[name] || '';
            }

            function resetIngredientSelectionModal() {
                document.querySelectorAll('#selectIngredientsModal .ingredient-item').forEach(item => {
                    item.classList.remove('selected');
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    const measurementInput = item.querySelector('.ingredient-measurement');
                    const unitSelect = item.querySelector('.ingredient-unit');

                    if (checkbox) checkbox.checked = false;
                    if (measurementInput) measurementInput.value = '';
                    if (unitSelect) unitSelect.selectedIndex = 0;
                });

                document.getElementById('selectedIngredientsPreview').innerHTML = '';
            }

            function renderSelectedIngredientTags(containerId, selectedIngredients) {
                const container = document.getElementById(containerId);
                if (!container) return;

                container.innerHTML = '';
                selectedIngredients.forEach(ingredient => {
                    const tag = document.createElement('div');
                    tag.className = 'ingredient-tag';
                    const line = recipeLineCostClient(ingredient);
                    tag.innerHTML = `
                        <span>${ingredient.name} (${ingredient.quantity} ${ingredient.unit}) · ₱${line.toFixed(2)}</span>
                        <span class="remove-btn">&times;</span>
                    `;
                    container.appendChild(tag);
                });
                updateProductRecipeSummaries();
            }

            function syncIngredientModalSelections(selectedIngredients) {
                resetIngredientSelectionModal();

                selectedIngredients.forEach(ingredient => {
                    const checkbox = document.querySelector(`#selectIngredientsModal input[type="checkbox"][value="${ingredient.id}"]`);
                    if (!checkbox) return;

                    checkbox.checked = true;
                    const ingredientItem = checkbox.closest('.ingredient-item');
                    const measurementInput = ingredientItem.querySelector('.ingredient-measurement');
                    const unitSelect = ingredientItem.querySelector('.ingredient-unit');

                    ingredientItem.classList.add('selected');
                    if (measurementInput) measurementInput.value = ingredient.quantity;
                    if (unitSelect) unitSelect.value = ingredient.unit;
                });

                updateSelectedIngredientsPreview();
            }

            function syncProductIngredientsToForm(formId, selectedIngredients) {
                const form = document.getElementById(formId);
                if (!form) return;

                let hiddenInput = form.querySelector('input[name="ingredients"]');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'ingredients';
                    form.appendChild(hiddenInput);
                }

                hiddenInput.value = JSON.stringify(selectedIngredients);
            }

            function openIngredientModalForCreate() {
                const form = document.getElementById('addIngredientForm');
                form.reset();
                document.getElementById('ingredientId').value = '';
                document.getElementById('addIngredientModal').style.display = 'flex';
                document.querySelector('#addIngredientModal .modal-header h2').textContent = 'Add New Ingredient';
                document.querySelector('#addIngredientForm .save-btn').textContent = 'Save Ingredient';
            }

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
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching ingredient categories:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch ingredient categories. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
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
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching product categories:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch product categories. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
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
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching ingredients:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch ingredients. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
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
                            <td>₱${parseFloat(ingredient.default_unit_cost || 0).toFixed(2)} <span style="color:#888;font-size:12px">/${ingredient.unit}</span></td>
                            <td><span class="status-badge ${ingredient.status}">${ingredient.status}</span></td>
                            <td class="action-buttons">
                                <button class="action-btn edit" data-ingredient-id="${ingredient.ingredient_id}" title="Edit Ingredient">
                                    ${premiumIcon('edit')}
                                </button>
                                <button class="action-btn view" data-ingredient-id="${ingredient.ingredient_id}" title="View Details">
                                    ${premiumIcon('view')}
                                </button>
                                <button class="action-btn delete" data-ingredient-id="${ingredient.ingredient_id}" title="Delete Ingredient">
                                    ${premiumIcon('delete')}
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    // Show a message when no ingredients are available
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td colspan="6" class="text-center">No ingredients available</td>
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
                            <span class="premium-empty-icon">${premiumIcon('search', 'h-5 w-5')}</span>
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
                                    <span class="premium-inline-icon premium-stock-icon">${premiumIcon('stock')}</span>
                                    <span>Current Stock: ${ingredient.stock} ${ingredient.unit}</span>
                                </div>
                                <div class="ingredient-unit-cost">Unit cost: ₱${parseFloat(ingredient.default_unit_cost || 0).toFixed(2)} / ${ingredient.unit}</div>
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
                        <span class="premium-empty-icon">${premiumIcon('empty', 'h-5 w-5')}</span>
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
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching products:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch products. Please try again later.',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
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
                            <td colspan="9" class="text-center">
                                <span class="premium-empty-icon">${premiumIcon('search', 'h-5 w-5')}</span>
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
                        const taxRate = parseFloat(product.tax_rate || 12);
                        const taxLabel = taxRate > 0 ? `${taxRate}%` : 'Exempt';
                        const taxBadgeClass = taxRate > 0 ? 'vat-badge-taxable' : 'vat-badge-exempt';
                        row.innerHTML = `
                            <td class="product-image-cell">
                                <div class="product-thumb">
                                    <img src="../${product.image_path || 'assets/images/products/default.jpg'}" 
                                         alt="${product.product_name}" 
                                         class="product-img"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='../assets/images/products/default.jpg';">
                                </div>
                            </td>
                            <td>${product.product_name}</td>
                            <td>${product.category_name}</td>
                            <td>₱${parseFloat(product.price).toFixed(2)}</td>
                            <td title="Sum of recipe quantities × current ingredient unit costs">${product.ingredients && product.ingredients.length ? '₱' + parseFloat(product.recipe_cost || 0).toFixed(2) : '—'}</td>
                            <td><span class="vat-badge ${taxBadgeClass}">${taxLabel}</span></td>
                            <td>
                                <div class="ingredients-list">
                                    ${product.ingredients ? product.ingredients.map(ing => 
                                        `<span class="ingredient-tag">${ing.ingredient_name} (${ing.quantity} ${ing.unit})${ing.line_cost != null ? ` · ₱${parseFloat(ing.line_cost).toFixed(2)}` : ''}</span>`
                                    ).join('') : 'No ingredients'}
                                </div>
                            </td>
                            <td><span class="status-badge ${product.status}">${product.status}</span></td>
                            <td class="action-buttons">
                                <button class="action-btn edit" data-product-id="${product.product_id}" title="Edit Product">
                                    ${premiumIcon('edit')}
                                </button>
                                <button class="action-btn view" data-product-id="${product.product_id}" title="View Details">
                                    ${premiumIcon('view')}
                                </button>
                                <button class="action-btn delete" data-product-id="${product.product_id}" title="Delete Product">
                                    ${premiumIcon('delete')}
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                } else {
                    const row = document.createElement('tr');
                    row.className = 'no-results-message';
                    row.innerHTML = `
                        <td colspan="9" class="text-center">
                            <span class="premium-empty-icon">${premiumIcon('empty', 'h-5 w-5')}</span>
                            <p>No products available</p>
                        </td>
                    `;
                    tbody.appendChild(row);
                }

                // Update the total products count
                updateTotalProductsCount();
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
                            document.getElementById('editProductTaxRate').value = parseFloat(product.tax_rate || 12).toFixed(2);
                            document.getElementById('editProductDescription').value = product.description;
                            document.getElementById('editProductStatus').value = product.status;

                            selectedProductIngredients = (product.ingredients || []).map(ingredient => ({
                                id: ingredient.ingredient_id,
                                name: ingredient.ingredient_name,
                                quantity: parseFloat(ingredient.quantity),
                                unit: ingredient.unit
                            }));
                            isEditingProduct = true;

                            renderSelectedIngredientTags('editSelectedIngredients', selectedProductIngredients);
                            syncProductIngredientsToForm('editProductForm', selectedProductIngredients);
                            syncIngredientModalSelections(selectedProductIngredients);

                            document.getElementById('editProductModal').style.display = 'flex';
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.error,
                                icon: 'error',
                                confirmButtonColor: '#D4AF37'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch product details',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    });
            }

            function handleIngredientAction(action, ingredientId) {
                if (action === 'edit') {
                    editIngredient(ingredientId);
                } else if (action === 'view') {
                    viewIngredient(ingredientId);
                } else if (action === 'delete') {
                    deleteIngredient(ingredientId);
                }
            }

            function editIngredient(ingredientId) {
                fetch(`../backend/fetch_data.php?action=ingredient&id=${ingredientId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to fetch ingredient details');
                        }

                        const ingredient = data.data;
                        document.getElementById('ingredientId').value = ingredient.ingredient_id;
                        document.getElementById('ingredientName').value = ingredient.ingredient_name;
                        document.getElementById('ingredientCategory').value = ingredient.category_id;
                        document.getElementById('ingredientStock').value = ingredient.stock;
                        document.getElementById('ingredientUnit').value = ingredient.unit;
                        document.getElementById('ingredientStatus').value = ingredient.status;
                        document.getElementById('addIngredientModal').style.display = 'flex';
                        document.querySelector('#addIngredientModal .modal-header h2').textContent = 'Edit Ingredient';
                        document.querySelector('#addIngredientForm .save-btn').textContent = 'Update Ingredient';
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error',
                            text: error.message,
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    });
            }

            function viewIngredient(ingredientId) {
                fetch(`../backend/fetch_data.php?action=ingredient&id=${ingredientId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to fetch ingredient details');
                        }

                        const ingredient = data.data;
                        const imagePath = ingredient.image_path ? `../${ingredient.image_path}` : '../assets/images/ingredients/default.jpg';
                        Swal.fire({
                            title: 'Ingredient Details',
                            html: `
                                <div class="product-view-modal">
                                    <div class="product-view-hero">
                                        <div class="product-view-image-wrap">
                                            <img src="${imagePath}" alt="${ingredient.ingredient_name}" class="product-view-image" onerror="this.onerror=null;this.src='../assets/images/ingredients/default.jpg';">
                                        </div>
                                        <div class="product-view-hero-copy">
                                            <span class="product-view-badge">${ingredient.category_name}</span>
                                            <h2 class="product-view-title">${ingredient.ingredient_name}</h2>
                                            <div class="product-view-meta">
                                                <div class="product-view-stat">
                                                    <span class="product-view-label">Stock</span>
                                                    <strong>${ingredient.stock} ${ingredient.unit}</strong>
                                                </div>
                                                <div class="product-view-stat">
                                                    <span class="product-view-label">Status</span>
                                                    <span class="status-badge ${ingredient.status}">${ingredient.status}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `,
                            width: '680px',
                            showCloseButton: true,
                            confirmButtonText: 'Close',
                            confirmButtonColor: '#D4AF37',
                            customClass: {
                                popup: 'swal-modern-product-popup',
                                title: 'swal-modern-product-title',
                                htmlContainer: 'swal-modern-product-html',
                                confirmButton: 'swal-modern-product-confirm',
                                closeButton: 'swal-modern-product-close'
                            }
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error',
                            text: error.message,
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    });
            }

            function deleteIngredient(ingredientId) {
                Swal.fire({
                    title: 'Delete Ingredient?',
                    text: 'Are you sure you want to delete this ingredient? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#2E2E2E',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    fetch('../backend/delete_ingredient.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ ingredient_id: ingredientId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to delete ingredient');
                        }

                        Swal.fire({
                            title: 'Deleted!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        }).then(() => fetchIngredients());
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error',
                            text: error.message,
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
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
                    const line = recipeLineCostClient(ingredient);
                    tag.innerHTML = `
                        <span>${ingredient.name} (${ingredient.quantity} ${ingredient.unit}) · ₱${line.toFixed(2)}</span>
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
                            const ingredientsMarkup = product.ingredients && product.ingredients.length
                                ? product.ingredients.map(ing =>
                                    `<span class="view-ingredient-tag">${ing.ingredient_name} (${ing.quantity} ${ing.unit})${ing.line_cost != null ? ` · ₱${parseFloat(ing.line_cost).toFixed(2)}` : ''}</span>`
                                ).join('')
                                : '<span class="view-empty-note">No ingredients</span>';
                            const rc = parseFloat(product.recipe_cost || 0);
                            const gm = product.gross_margin != null ? parseFloat(product.gross_margin) : (parseFloat(product.price) - rc);

                            Swal.fire({
                                title: 'Product Details',
                                html: `
                                    <div class="product-view-modal">
                                        <div class="product-view-hero">
                                            <div class="product-view-image-wrap">
                                                <img src="../${product.image_path || 'assets/images/products/default.jpg'}" 
                                                     alt="${product.product_name}"
                                                     class="product-view-image"
                                                     onerror="this.onerror=null;this.src='../assets/images/products/default.jpg';">
                                            </div>
                                            <div class="product-view-hero-copy">
                                                <span class="product-view-badge">${product.category_name}</span>
                                                <h2 class="product-view-title">${product.product_name}</h2>
                                                <div class="product-view-meta">
                                                    <div class="product-view-stat">
                                                        <span class="product-view-label">Price (VAT-incl.)</span>
                                                        <strong>₱${parseFloat(product.price).toFixed(2)}</strong>
                                                    </div>
                                                    <div class="product-view-stat">
                                                        <span class="product-view-label">Est. COGS</span>
                                                        <strong>₱${rc.toFixed(2)}</strong>
                                                    </div>
                                                    <div class="product-view-stat">
                                                        <span class="product-view-label">Gross margin</span>
                                                        <strong>₱${gm.toFixed(2)}</strong>
                                                    </div>
                                                    <div class="product-view-stat">
                                                        <span class="product-view-label">VAT Rate</span>
                                                        <strong>${parseFloat(product.tax_rate || 0) > 0 ? parseFloat(product.tax_rate).toFixed(0) + '%' : 'Exempt'}</strong>
                                                    </div>
                                                    <div class="product-view-stat">
                                                        <span class="product-view-label">Status</span>
                                                        <span class="status-badge ${product.status}">${product.status}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="product-view-grid">
                                            <div class="product-view-card">
                                                <p class="product-view-section-label">Description</p>
                                                <p class="product-view-description">${product.description || 'No description available for this product.'}</p>
                                            </div>

                                            <div class="product-view-card">
                                                <p class="product-view-section-label">Ingredients</p>
                                                <div class="product-view-ingredients">
                                                    ${ingredientsMarkup}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `,
                                width: '720px',
                                showCloseButton: true,
                                confirmButtonText: 'Close',
                                confirmButtonColor: '#D4AF37',
                                customClass: {
                                    popup: 'swal-modern-product-popup',
                                    title: 'swal-modern-product-title',
                                    htmlContainer: 'swal-modern-product-html',
                                    confirmButton: 'swal-modern-product-confirm',
                                    closeButton: 'swal-modern-product-close'
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.error,
                                icon: 'error',
                                confirmButtonColor: '#D4AF37'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch product details',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
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
                    cancelButtonColor: '#2E2E2E',
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
                                    confirmButtonColor: '#D4AF37'
                                }).then(() => {
                                    // Refresh the page
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.error,
                                    icon: 'error',
                                    confirmButtonColor: '#D4AF37'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to delete product',
                                icon: 'error',
                                confirmButtonColor: '#D4AF37'
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
                    confirmButtonColor: '#D4AF37'
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
                openIngredientModalForCreate();
            });
            
            document.getElementById('selectIngredientsBtn').addEventListener('click', function() {
                isEditingProduct = false;
                syncIngredientModalSelections(selectedProductIngredients);
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
                isEditingProduct = true;
                syncIngredientModalSelections(selectedProductIngredients);
                document.getElementById('selectIngredientsModal').style.display = 'flex';
            });

            // Update the confirm ingredients selection function
            document.getElementById('confirmIngredientsBtn').addEventListener('click', function() {
                // Get the selected ingredients from the preview
                const hiddenInput = document.querySelector('#selectedIngredientsPreview input[name="ingredients"]');
                if (!hiddenInput) return;
                
                const selectedIngredients = JSON.parse(hiddenInput.value);
                
                // Update the selected ingredients display in the product form
                selectedProductIngredients = selectedIngredients;
                const targetContainer = isEditingProduct ? 'editSelectedIngredients' : 'selectedIngredients';
                const targetForm = isEditingProduct ? 'editProductForm' : 'addProductForm';

                renderSelectedIngredientTags(targetContainer, selectedIngredients);
                syncProductIngredientsToForm(targetForm, selectedIngredients);
                
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
                            confirmButtonColor: '#D4AF37'
                        }).then(() => {
                            // Close the modal and refresh the products table
                            document.getElementById('addProductModal').style.display = 'none';
                            this.reset();
                            document.getElementById('selectedIngredients').innerHTML = '';
                            selectedProductIngredients = [];
                            document.getElementById('addRecipeCostValue').textContent = '₱0.00';
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
                        confirmButtonColor: '#D4AF37'
                    });
                }
            });

            document.getElementById('editProductForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                try {
                    const formData = new FormData(this);
                    const ingredientsInput = this.querySelector('input[name="ingredients"]');
                    if (!ingredientsInput || !ingredientsInput.value) {
                        throw new Error('Please select at least one ingredient');
                    }

                    const response = await fetch('../backend/update_product.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to update product');
                    }

                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#D4AF37'
                    }).then(() => {
                        document.getElementById('editProductModal').style.display = 'none';
                        fetchProducts();
                    });
                } catch (error) {
                    Swal.fire({
                        title: 'Error!',
                        text: error.message,
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            });

            // Add event listener for remove ingredient buttons
            document.addEventListener('click', function(e) {
                const actionButton = e.target.closest('.action-btn');
                if (actionButton) {
                    e.stopPropagation();
                    const action = actionButton.classList.contains('edit') ? 'edit'
                        : actionButton.classList.contains('view') ? 'view'
                        : 'delete';

                    if (actionButton.hasAttribute('data-product-id')) {
                        handleProductAction(action, actionButton.getAttribute('data-product-id'));
                        return;
                    }

                    if (actionButton.hasAttribute('data-ingredient-id')) {
                        handleIngredientAction(action, actionButton.getAttribute('data-ingredient-id'));
                        return;
                    }
                }

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
                    const ingredientId = (formData.get('ingredientId') || '').toString().trim();
                    const isEdit = ingredientId !== '';
                    
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
                            confirmButtonColor: '#D4AF37'
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
                        confirmButtonColor: '#D4AF37'
                    });
                }
            });
            
        });
    </script>
</body>
</html>
