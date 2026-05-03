<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn – Order Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body {
            background:
                radial-gradient(circle at 15% 20%, rgba(212,175,55,0.18), transparent 45%),
                radial-gradient(circle at 85% 0%, rgba(212,175,55,0.12), transparent 40%),
                linear-gradient(145deg, #0D0D0D 0%, #1a1204 38%, #0D0D0D 100%);
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
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .order-details-container {
            background: #1F1F1F;
            border: 1px solid #2E2E2E;
            border-radius: 16px;
            margin: 24px auto;
            padding: 24px;
            max-width: 860px;
        }

        .order-customer-info {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .order-info-field { flex: 1; }
        .order-info-field label { display: block; color: #888; font-size: 12px; font-weight: 500; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .order-value {
            background: #121212;
            border: 1px solid #2E2E2E;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 14px;
            color: #D4AF37;
            text-align: center;
        }
        .order-value input, .order-value select {
            width: 100%;
            padding: 0;
            border: none;
            background: transparent;
            font-size: 14px;
            color: #D4AF37;
            font-family: 'Poppins', sans-serif;
            text-align: center;
            outline: none;
        }

        .order-items-list {
            display: flex;
            overflow-x: auto;
            gap: 16px;
            padding: 16px 4px;
            margin-bottom: 24px;
            scrollbar-width: thin;
            scrollbar-color: #D4AF37 #1a1a1a;
        }
        .order-items-list::-webkit-scrollbar { height: 6px; }
        .order-items-list::-webkit-scrollbar-track { background: #121212; border-radius: 3px; }
        .order-items-list::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 3px; }

        .order-item-card {
            background: linear-gradient(165deg, #161616 0%, #121212 100%);
            border: 1px solid #333;
            border-radius: 18px;
            padding: 22px 18px 20px;
            min-width: 240px;
            flex: 0 0 auto;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 18px rgba(0,0,0,0.35);
        }
        .order-item-card:hover { transform: translateY(-4px); border-color: rgba(212,175,55,0.35); box-shadow: 0 12px 28px rgba(212,175,55,0.12); }

        .order-item-image { width: 168px; height: 196px; object-fit: contain; margin: 0 auto 14px; display: block; }
        .order-item-name { font-weight: 600; color: #D4AF37; margin-bottom: 12px; font-size: 0.95rem; line-height: 1.35; }

        .order-quantity-controls { display: flex; justify-content: center; align-items: center; gap: 10px; }
        .order-qty-btn {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #1F1F1F;
            border: 1px solid #2E2E2E;
            display: flex; justify-content: center; align-items: center;
            cursor: pointer;
            color: #D4AF37;
            transition: all 0.2s;
            font-size: 12px;
        }
        .order-qty-btn:hover { background: #D4AF37; color: #0D0D0D; }
        .order-qty-display { font-weight: 700; font-size: 1rem; min-width: 28px; text-align: center; color: #D4AF37; }
        .order-delete-btn { background: none; border: none; color: #666; cursor: pointer; font-size: 1rem; padding: 4px; transition: color 0.2s; }
        .order-delete-btn:hover { color: #FF6B6B; }

        .order-discount-panel {
            background: #121212;
            border: 1px solid #2E2E2E;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 16px;
        }
        .order-discount-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .order-discount-label { color: #B0B0B0; font-size: 13px; font-weight: 600; letter-spacing: 0.03em; }
        .discount-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }
        .discount-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
        .discount-toggle-track {
            width: 48px;
            height: 26px;
            border-radius: 999px;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            position: relative;
            transition: background 0.25s, border-color 0.25s;
        }
        .discount-toggle-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #888;
            transition: transform 0.25s, background 0.25s;
        }
        .discount-toggle input:checked + .discount-toggle-track {
            background: rgba(212,175,55,0.35);
            border-color: rgba(212,175,55,0.55);
        }
        .discount-toggle input:checked + .discount-toggle-track .discount-toggle-thumb {
            transform: translateX(22px);
            background: #D4AF37;
        }
        .discount-state-text { font-size: 12px; color: #888; font-weight: 600; min-width: 28px; }
        .discount-toggle input:checked ~ .discount-state-text { color: #D4AF37; }
        .discount-percent-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #2E2E2E;
            transition: opacity 0.2s;
        }
        .discount-percent-wrap.discount-inactive { opacity: 0.45; pointer-events: none; }
        .discount-percent-wrap label { color: #888; font-size: 12px; }
        .discount-percent-input {
            width: 100px;
            text-align: center;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid #2E2E2E;
            background: #1F1F1F;
            color: #D4AF37;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 14px;
        }
        .discount-percent-input:focus {
            outline: none;
            border-color: rgba(212,175,55,0.6);
            box-shadow: 0 0 0 2px rgba(212,175,55,0.12);
        }
        .discount-percent-suffix { color: #666; font-size: 13px; font-weight: 500; }
        .order-item-row.order-discount-row .order-discount-val { color: #7bdc9a; font-weight: 600; }

        .order-payment-details {
            background: #121212;
            border: 1px solid #2E2E2E;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .order-payment-title { color: #D4AF37; font-size: 1rem; font-weight: 600; margin-bottom: 16px; }
        .order-item-row { display: flex; justify-content: space-between; margin-bottom: 8px; color: #B0B0B0; font-size: 14px; }
        .order-item-quantity { font-weight: 500; margin-right: 8px; color: #D4AF37; }
        .order-item-price { font-weight: 600; color: #D4AF37; }
        .order-total-row { display: flex; justify-content: space-between; padding-top: 12px; border-top: 1px solid #2E2E2E; margin-top: 12px; font-weight: 700; color: #F4D26B; font-size: 1.1rem; }

        .order-confirm-btn {
            display: block;
            width: 100%;
            max-width: 220px;
            margin: 0 auto;
            padding: 12px;
            border-radius: 12px;
            background: linear-gradient(135deg, #D4AF37, #B8921E);
            color: #0D0D0D;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        .order-confirm-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(212,175,55,0.3); }

        .add-order-btn-container {
            background: transparent;
            border: 2px dashed rgba(212,175,55,0.3);
            border-radius: 18px;
            padding: 20px;
            min-width: 240px;
            flex: 0 0 auto;
            text-align: center;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 360px;
            transition: all 0.3s;
        }
        .add-order-btn-container:hover { background: rgba(212,175,55,0.05); border-color: #D4AF37; }
        .add-order-btn {
            background: linear-gradient(135deg, #D4AF37, #B8921E);
            color: #0D0D0D;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
        }
        .add-order-btn:hover { transform: translateY(-2px); }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.82); backdrop-filter: blur(6px); z-index: 1000; align-items: center; justify-content: center; padding: 12px; }
        .modal-content {
            background: #1F1F1F;
            border: 1px solid #3a3a3a;
            margin: 0 auto;
            padding: 0;
            border-radius: 20px;
            width: 96%;
            max-width: 1280px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.55);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            padding: 22px 26px 16px;
            border-bottom: 1px solid #2E2E2E;
        }
        .modal-header h2 { color: #D4AF37; font-size: 1.35rem; font-weight: 600; }
        .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 18px 26px 22px;
            -webkit-overflow-scrolling: touch;
        }
        .close-modal { font-size: 24px; cursor: pointer; color: #888; transition: color 0.2s; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
        .close-modal:hover { color: #fff; background: rgba(255,255,255,0.05); }

        .category-tabs { display: flex; gap: 10px; margin-bottom: 18px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: thin; }
        .category-tab {
            padding: 8px 16px;
            background: #121212;
            border: 1px solid #2E2E2E;
            border-radius: 9999px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s;
            color: #B0B0B0;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
        }
        .category-tab.active { background: linear-gradient(135deg, #D4AF37, #B8921E); color: #0D0D0D; border-color: transparent; font-weight: 600; }
        .category-tab:hover:not(.active) { border-color: #D4AF37; color: #D4AF37; }

        .products-search-wrap { margin-bottom: 16px; }
        .products-search-input {
            width: 100%;
            border: 1px solid #2E2E2E;
            background: #121212;
            color: #E6E6E6;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .products-search-input:focus {
            border-color: rgba(212,175,55,0.85);
            box-shadow: 0 0 0 2px rgba(212,175,55,0.15);
        }
        .products-search-input::placeholder { color: #777; }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .product-card {
            background: linear-gradient(165deg, #181818 0%, #101010 100%);
            border: 1px solid #333;
            border-radius: 18px;
            padding: 18px 14px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.35);
            position: relative;
        }
        .product-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 18px;
            pointer-events: none;
            opacity: 0;
            box-shadow: inset 0 0 0 1px rgba(212,175,55,0.25);
            transition: opacity 0.25s;
        }
        .product-card:hover { transform: translateY(-5px); border-color: rgba(212,175,55,0.45); box-shadow: 0 14px 32px rgba(0,0,0,0.45); }
        .product-card:hover::after { opacity: 1; }
        .product-card.selected {
            border: 2px solid #D4AF37;
            box-shadow: 0 0 0 1px rgba(212,175,55,0.2), 0 12px 36px rgba(212,175,55,0.18);
            background: linear-gradient(165deg, #1e1a10 0%, #141208 100%);
        }
        .product-card .product-card-img-wrap {
            width: 100%;
            min-height: 168px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            background: radial-gradient(ellipse at center, rgba(212,175,55,0.08) 0%, transparent 70%);
            border-radius: 14px;
        }
        .product-card img { width: 148px; height: 172px; max-width: 100%; object-fit: contain; display: block; }
        .product-card h3 { font-size: 14px; color: #E8C84A; margin-bottom: 6px; line-height: 1.35; font-weight: 600; }
        .product-card .price { font-weight: 700; color: #F5D76E; font-size: 15px; letter-spacing: 0.02em; }
        .product-quantity-controls {
            margin-top: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .product-quantity-input {
            width: 56px;
            border: 1px solid #3a3a3a;
            background: #0f0f0f;
            color: #E6E6E6;
            border-radius: 10px;
            text-align: center;
            padding: 6px 4px;
            font-size: 13px;
        }

        .modal-footer {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px 22px 22px;
            margin: 0 18px 18px;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(31,31,31,0.92) 0%, #1a1a1a 100%);
            border: 1px solid rgba(212,175,55,0.22);
            box-shadow: 0 -12px 40px rgba(0,0,0,0.55), 0 8px 28px rgba(0,0,0,0.35);
        }
        .confirm-order-btn {
            background: linear-gradient(135deg, #E8C84A, #B8921E);
            color: #0D0D0D;
            border: none;
            padding: 14px 36px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 6px 20px rgba(212,175,55,0.35);
            width: 100%;
            max-width: 420px;
        }
        .confirm-order-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(212,175,55,0.45); }

        /* SweetAlert dark theme */
        .swal2-popup { font-family: 'Poppins', sans-serif !important; background: #1F1F1F !important; border: 1px solid #2E2E2E !important; border-radius: 16px !important; color: #fff !important; }
        .swal2-title { color: #D4AF37 !important; }
        .swal2-html-container { color: #B0B0B0 !important; }
        .swal2-confirm { background: linear-gradient(135deg, #F4D26B, #C99B2A) !important; color: #0D0D0D !important; font-weight: 600 !important; border-radius: 10px !important; }
        .swal2-cancel { background: #2A2A2A !important; color: #B0B0B0 !important; border-radius: 10px !important; border: 1px solid #2E2E2E !important; }

        /* Payment styles */
        .payment-options { display: flex; flex-direction: column; gap: 12px; margin: 16px 0; }
        .payment-option { display: flex; align-items: center; gap: 10px; padding: 14px; border: 1px solid #2E2E2E; border-radius: 12px; cursor: pointer; transition: all 0.2s; background: #1F1F1F; }
        .payment-option:hover { border-color: #D4AF37; }
        .payment-option.selected { border-color: #D4AF37; background: rgba(212,175,55,0.08); }
        .payment-option input[type="radio"] { accent-color: #D4AF37; width: 18px; height: 18px; }
        .payment-option label { cursor: pointer; font-weight: 500; color: #B0B0B0; }
        .payment-upload { display: none; margin-top: 12px; padding: 16px; border: 2px dashed #2E2E2E; border-radius: 12px; text-align: center; background: #121212; }
        .payment-upload.active { display: block; }
        .upload-btn { display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #D4AF37, #B8921E); color: #0D0D0D; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 13px; }
        .payment-upload input[type="file"] { display: none; }
        .upload-preview { margin-top: 10px; max-width: 200px; display: none; margin: 10px auto 0; }
        .upload-preview img { width: 100%; border-radius: 8px; border: 1px solid #2E2E2E; }

        @media (max-width: 768px) {
            .order-customer-info { flex-direction: column; }
            .modal-content { width: 100%; max-height: 94vh; border-radius: 16px; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
            .product-card img { width: 120px; height: 140px; }
            .order-item-image { width: 140px; height: 164px; }
            .order-item-card { min-width: 200px; }
        }
    </style>
</head>
<body>
    <?php include("../navigation/navbar.php"); ?>

    <div class="main-content" style="padding: 24px;">
        <div class="order-details-container">
            <?php
                $zornStaffRole = $_SESSION['role'] ?? null;
                $zornIsStaff   = in_array($zornStaffRole, ['cashier', 'waiter'], true);
            ?>
            <div class="order-customer-info">
                <div class="order-info-field">
                    <label>Customer Name</label>
                    <?php if ($zornIsStaff): ?>
                        <div class="order-value"><input type="text" id="customer-name" value="" placeholder="Enter customer name"></div>
                    <?php else: ?>
                        <div class="order-value" id="customer-name"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest' ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($zornIsStaff): ?>
                    <div class="order-info-field">
                        <label>Order Type</label>
                        <div class="order-value">
                            <select id="order-type">
                                <option value="dine-in">Dine-in</option>
                                <option value="take-out">Take-out</option>
                                <option value="walk-in">Walk-in</option>
                            </select>
                        </div>
                    </div>
                    <div class="order-info-field" id="table-number-field">
                        <label>Table #</label>
                        <div class="order-value"><input type="text" id="table-number" placeholder="e.g. T-05" maxlength="20"></div>
                    </div>
                <?php else: ?>
                    <div class="order-info-field">
                        <label>Order Type</label>
                        <div class="order-value">Account-Order</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="order-items-list">
                <div class="add-order-btn-container" onclick="showProductsModal()">
                    <button class="add-order-btn"><i class="fas fa-plus"></i> Add Order</button>
                </div>
            </div>

            <div class="order-discount-panel" id="order-discount-panel">
                <div class="order-discount-head">
                    <span class="order-discount-label">Discount</span>
                    <label class="discount-toggle">
                        <input type="checkbox" id="discount-enabled-toggle">
                        <span class="discount-toggle-track"><span class="discount-toggle-thumb"></span></span>
                        <span class="discount-state-text" id="discount-state-text">Off</span>
                    </label>
                </div>
                <div class="discount-percent-wrap discount-inactive" id="discount-percent-wrap">
                    <label for="discount-percent-input">Discount rate</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="number" class="discount-percent-input" id="discount-percent-input" min="0" max="100" step="0.01" placeholder="0" title="Percentage off the order total">
                        <span class="discount-percent-suffix">%</span>
                    </div>
                </div>
            </div>

            <div class="order-payment-details">
                <h3 class="order-payment-title">Order Summary</h3>
                <p style="color:#888;text-align:center;padding:20px;">No items yet</p>
            </div>

            <button class="order-confirm-btn"><i class="fas fa-check-circle" style="margin-right:6px;"></i>Confirm Order</button>
        </div>
    </div>

    <!-- Products Modal -->
    <div id="productsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-basket-shopping" style="margin-right:8px;"></i>Select Products</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="category-tabs"></div>
                <div class="products-search-wrap">
                    <input type="text" id="productSearchInput" class="products-search-input" placeholder="Search product name...">
                </div>
                <div class="products-grid"></div>
            </div>
            <div class="modal-footer">
                <button class="confirm-order-btn" onclick="confirmSelectedProducts()"><i class="fas fa-check" style="margin-right:6px;"></i>Confirm Selection</button>
            </div>
        </div>
    </div>

    <script>
        const IS_CASHIER = <?= $zornIsStaff ? 'true' : 'false' ?>;
        const STAFF_ROLE = <?= json_encode($zornStaffRole ?? '') ?>;
        const USER_ID = '<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>';
        let selectedProducts = new Map();
        let currentCategory = null;
        let productSearchTerm = '';
        let categoriesData = [];
        let lastOrderData = null;
        let discountEnabled = false;
        let discountPercent = 0;
        let discountPersistTimer = null;
        function money(n) { return parseFloat(n).toFixed(2); }

        function computeDiscountSlice(gross, enabled, pctRaw) {
            const pct = enabled ? Math.min(100, Math.max(0, parseFloat(pctRaw) || 0)) : 0;
            let discAmt = 0;
            if (pct > 0 && gross > 0) {
                discAmt = Math.round(gross * (pct / 100) * 100) / 100;
                if (discAmt > gross) discAmt = gross;
            }
            const total = Math.round((gross - discAmt) * 100) / 100;
            return { pct, discAmt, total };
        }

        function syncDiscountControlsFromState() {
            const toggle = document.getElementById('discount-enabled-toggle');
            const wrap = document.getElementById('discount-percent-wrap');
            const inp = document.getElementById('discount-percent-input');
            const stateLbl = document.getElementById('discount-state-text');
            if (toggle) toggle.checked = discountEnabled;
            if (inp) inp.value = (discountPercent > 0 || discountEnabled) ? String(discountPercent) : '';
            if (wrap) wrap.classList.toggle('discount-inactive', !discountEnabled);
            if (stateLbl) stateLbl.textContent = discountEnabled ? 'On' : 'Off';
        }

        function persistDiscountToSession() {
            clearTimeout(discountPersistTimer);
            discountPersistTimer = setTimeout(() => {
                const body = new URLSearchParams();
                body.set('action', 'update_discount');
                body.set('discount_enabled', discountEnabled ? '1' : '0');
                body.set('discount_percent', String(discountPercent || 0));
                fetch('../backend/order_manager.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                }).catch(() => {});
            }, 200);
        }

        function resetDiscountSettings() {
            discountEnabled = false;
            discountPercent = 0;
            syncDiscountControlsFromState();
            persistDiscountToSession();
        }

        function showProductsModal() {
            document.getElementById('productsModal').style.display = 'flex';
            loadCategories();
        }

        document.querySelector('.close-modal').addEventListener('click', function() {
            document.getElementById('productsModal').style.display = 'none';
            selectedProducts.clear();
            productSearchTerm = '';
            const searchInput = document.getElementById('productSearchInput');
            if (searchInput) searchInput.value = '';
            updateProductSelection();
        });

        function loadCategories() {
            fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=get_categories' })
            .then(r => r.json()).then(data => {
                categoriesData = data.categories;
                const tabs = document.querySelector('.category-tabs');
                tabs.innerHTML = '';
                const allTab = document.createElement('div');
                allTab.className = 'category-tab active'; allTab.textContent = 'All';
                allTab.onclick = () => filterProducts(null); tabs.appendChild(allTab);
                data.categories.forEach(c => { const t = document.createElement('div'); t.className = 'category-tab'; t.textContent = c.category_name; t.onclick = () => filterProducts(c.category_id); tabs.appendChild(t); });
                loadProducts();
            }).catch(e => Swal.fire({ title: 'Error', text: 'Failed to load categories', icon: 'error', confirmButtonColor: '#D4AF37' }));
        }

        function loadProducts() {
            fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=get_products' })
            .then(r => r.json()).then(data => {
                const grid = document.querySelector('.products-grid');
                grid.innerHTML = '';
                data.products.forEach(p => {
                    const card = document.createElement('div');
                    card.className = 'product-card'; card.dataset.productId = p.product_id; card.dataset.categoryId = p.category_id;
                    card.dataset.productName = (p.product_name || '').toLowerCase();
                    const imgSrc = p.image_path || '../assets/zoryn/zoryn_logo.jpg';
                    card.innerHTML = `<div class="product-card-img-wrap"><img src="${imgSrc}" alt="${p.product_name}" onerror="this.src='../assets/zoryn/zoryn_logo.jpg';"></div><h3>${p.product_name}</h3><div class="price">₱${money(p.price)}</div><div class="product-quantity-controls"><button class="order-qty-btn modal-qty-btn modal-qty-minus" type="button"><i class="fas fa-minus fa-xs"></i></button><input class="product-quantity-input" type="number" min="1" value="1"><button class="order-qty-btn modal-qty-btn modal-qty-plus" type="button"><i class="fas fa-plus fa-xs"></i></button></div>`;
                    card.addEventListener('click', function(e) {
                        if (e.target.closest('.modal-qty-btn') || e.target.classList.contains('product-quantity-input')) return;
                        toggleProductSelection(p.product_id);
                    });
                    const qtyInput = card.querySelector('.product-quantity-input');
                    const minusBtn = card.querySelector('.modal-qty-minus');
                    const plusBtn = card.querySelector('.modal-qty-plus');
                    minusBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        setModalProductQuantity(p.product_id, parseInt(qtyInput.value, 10) - 1);
                    });
                    plusBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        setModalProductQuantity(p.product_id, parseInt(qtyInput.value, 10) + 1);
                    });
                    qtyInput.addEventListener('click', e => e.stopPropagation());
                    qtyInput.addEventListener('input', function(e) {
                        e.stopPropagation();
                        setModalProductQuantity(p.product_id, parseInt(qtyInput.value, 10), true);
                    });
                    grid.appendChild(card);
                });
                filterProducts(currentCategory);
            }).catch(e => Swal.fire({ title: 'Error', text: 'Failed to load products', icon: 'error', confirmButtonColor: '#D4AF37' }));
        }

        function filterProducts(categoryId) {
            currentCategory = categoryId;
            const normalizedSearch = productSearchTerm.trim().toLowerCase();
            document.querySelectorAll('.product-card').forEach(p => {
                const categoryMatch = categoryId === null || p.dataset.categoryId === categoryId.toString();
                const searchMatch = normalizedSearch === '' || (p.dataset.productName || '').includes(normalizedSearch);
                p.style.display = (categoryMatch && searchMatch) ? 'block' : 'none';
            });
            document.querySelectorAll('.category-tab').forEach(t => {
                t.classList.remove('active');
                if ((categoryId === null && t.textContent === 'All') || (categoryId !== null && t.textContent === categoriesData.find(c => c.category_id === categoryId)?.category_name)) t.classList.add('active');
            });
        }

        function toggleProductSelection(productId) {
            const pid = String(productId);
            const card = document.querySelector(`.product-card[data-product-id="${pid}"]`);
            if (selectedProducts.has(pid)) {
                selectedProducts.delete(pid);
                card.classList.remove('selected');
            } else {
                const qtyInput = card ? card.querySelector('.product-quantity-input') : null;
                const qty = qtyInput ? Math.max(1, parseInt(qtyInput.value, 10) || 1) : 1;
                selectedProducts.set(pid, qty);
                card.classList.add('selected');
            }
        }

        function setModalProductQuantity(productId, nextQty, preserveManualInput = false) {
            const pid = String(productId);
            const card = document.querySelector(`.product-card[data-product-id="${pid}"]`);
            if (!card) return;
            const qtyInput = card.querySelector('.product-quantity-input');
            let qty = parseInt(nextQty, 10);
            if (Number.isNaN(qty) || qty < 1) qty = 1;
            if (!preserveManualInput || qtyInput.value === '' || parseInt(qtyInput.value, 10) !== qty) qtyInput.value = qty;
            if (selectedProducts.has(pid)) selectedProducts.set(pid, qty);
        }

        function updateProductSelection() {
            document.querySelectorAll('.product-card').forEach(p => {
                const pid = p.dataset.productId;
                p.classList.toggle('selected', selectedProducts.has(pid));
                const qtyInput = p.querySelector('.product-quantity-input');
                if (!qtyInput) return;
                const selectedQty = selectedProducts.get(pid);
                qtyInput.value = selectedQty ? selectedQty : 1;
            });
        }

        function confirmSelectedProducts() {
            if (selectedProducts.size === 0) { Swal.fire({ title: 'No Products', text: 'Select at least one product', icon: 'warning', confirmButtonColor: '#D4AF37' }); return; }
            const selectedEntries = Array.from(selectedProducts.entries());
            const selectedProductIds = selectedEntries.map(([id]) => id);
            fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=add_items&product_ids=${JSON.stringify(selectedProductIds)}` })
            .then(r => r.json()).then(data => {
                if (data.error) { Swal.fire({ title: 'Error', text: data.error, icon: 'error', confirmButtonColor: '#D4AF37' }); }
                else {
                    const updates = selectedEntries
                        .filter(([, qty]) => qty > 1)
                        .map(([productId, qty]) => fetch('../backend/order_manager.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=update_quantity&product_id=${productId}&quantity=${qty}`
                        }).then(r => r.json()));
                    Promise.all(updates)
                        .then(() => {
                            document.getElementById('productsModal').style.display = 'none';
                            selectedProducts.clear();
                            productSearchTerm = '';
                            const searchInput = document.getElementById('productSearchInput');
                            if (searchInput) searchInput.value = '';
                            loadOrderDetails();
                            Swal.fire({ title: 'Added!', text: 'Products added to order', icon: 'success', confirmButtonColor: '#D4AF37' });
                        })
                        .catch(() => Swal.fire({ title: 'Error', text: 'Products were added but quantity update failed', icon: 'warning', confirmButtonColor: '#D4AF37' }));
                }
            }).catch(e => Swal.fire({ title: 'Error', text: 'Failed to add products', icon: 'error', confirmButtonColor: '#D4AF37' }));
        }

        function computeVatBreakdown(items) {
            let vatableSales = 0, vatAmount = 0, vatExemptSales = 0, total = 0;
            items.forEach(i => {
                const lineTotal = parseFloat(i.price) * i.quantity;
                const taxRate = parseFloat(i.tax_rate || 12);
                total += lineTotal;
                if (taxRate > 0) {
                    const vatable = lineTotal / (1 + taxRate / 100);
                    vatableSales += vatable;
                    vatAmount += lineTotal - vatable;
                } else {
                    vatExemptSales += lineTotal;
                }
            });
            return { vatableSales, vatAmount, vatExemptSales, total };
        }

        function buildPaymentSummaryHtml(items) {
            const vat = computeVatBreakdown(items);
            const gross = vat.total;
            const disc = computeDiscountSlice(gross, discountEnabled, discountPercent);
            let html = '<h3 class="order-payment-title">Order Summary</h3>';
            items.forEach(i => {
                const t = parseFloat(i.price) * i.quantity;
                html += `<div class="order-item-row"><div><span class="order-item-quantity">${i.quantity}</span><span>${i.product_name}</span></div><div class="order-item-price">₱${money(t)}</div></div>`;
            });
            html += `<div style="border-top:1px solid #2E2E2E;margin-top:12px;padding-top:10px;">`;
            if (vat.vatableSales > 0) {
                html += `<div class="order-item-row"><div style="color:#888;font-size:12px;">VATable Sales</div><div style="color:#888;font-size:12px;">₱${money(vat.vatableSales)}</div></div>`;
                html += `<div class="order-item-row"><div style="color:#888;font-size:12px;">VAT (12%)</div><div style="color:#888;font-size:12px;">₱${money(vat.vatAmount)}</div></div>`;
            }
            if (vat.vatExemptSales > 0) {
                html += `<div class="order-item-row"><div style="color:#888;font-size:12px;">VAT-Exempt Sales</div><div style="color:#888;font-size:12px;">₱${money(vat.vatExemptSales)}</div></div>`;
            }
            html += `</div>`;
            if (disc.discAmt > 0) {
                html += `<div class="order-item-row order-discount-row"><div>Discount (${money(disc.pct)}%)</div><div class="order-discount-val">−₱${money(disc.discAmt)}</div></div>`;
            }
            html += `<div class="order-total-row"><div>Total</div><div class="order-item-price">₱${money(disc.total)}</div></div>`;
            return html;
        }

        function loadOrderDetails() {
            fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=get_order' })
            .then(r => r.json()).then(data => {
                const list = document.querySelector('.order-items-list');
                const payment = document.querySelector('.order-payment-details');
                const addBtn = `<div class="add-order-btn-container" onclick="showProductsModal()"><button class="add-order-btn"><i class="fas fa-plus"></i> Add Order</button></div>`;
                if (data.error || !data.items || data.items.length === 0) {
                    list.innerHTML = addBtn;
                    payment.innerHTML = '<h3 class="order-payment-title">Order Summary</h3><p style="color:#888;text-align:center;padding:20px;">No items yet</p>';
                    lastOrderData = null;
                    resetDiscountSettings();
                    return;
                }
                if (typeof data.discount_enabled !== 'undefined') {
                    discountEnabled = parseInt(data.discount_enabled, 10) === 1;
                }
                if (typeof data.discount_percent !== 'undefined') {
                    discountPercent = parseFloat(data.discount_percent);
                    if (Number.isNaN(discountPercent)) discountPercent = 0;
                }
                syncDiscountControlsFromState();
                lastOrderData = data;
                let html = '';
                data.items.forEach(item => {
                    const imgSrc = item.image_path || '../assets/zoryn/zoryn_logo.jpg';
                    html += `<div class="order-item-card" data-product-id="${item.product_id}"><img src="${imgSrc}" alt="${item.product_name}" class="order-item-image" onerror="this.src='../assets/zoryn/zoryn_logo.jpg';"><div class="order-item-name">${item.product_name}</div><div class="order-quantity-controls"><button class="order-qty-btn order-minus"><i class="fas fa-minus fa-xs"></i></button><span class="order-qty-display">${item.quantity}</span><button class="order-qty-btn order-plus"><i class="fas fa-plus fa-xs"></i></button><button class="order-delete-btn"><i class="fas fa-trash-alt"></i></button></div></div>`;
                });
                list.innerHTML = html + addBtn;
                payment.innerHTML = buildPaymentSummaryHtml(data.items);
                attachEventListeners();
            }).catch(e => Swal.fire({ title: 'Error', text: 'Failed to load order', icon: 'error', confirmButtonColor: '#D4AF37' }));
        }

        function attachEventListeners() {
            document.querySelectorAll('.order-qty-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const qty = this.parentElement.querySelector('.order-qty-display');
                    let q = parseInt(qty.textContent);
                    const pid = this.closest('.order-item-card').dataset.productId;
                    if (this.classList.contains('order-minus') && q > 1) { qty.textContent = --q; updateOrderQuantity(pid, q); }
                    else if (this.classList.contains('order-plus')) { qty.textContent = ++q; updateOrderQuantity(pid, q); }
                });
            });
            document.querySelectorAll('.order-delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.order-item-card');
                    Swal.fire({ title: 'Remove Item?', text: `Remove ${card.querySelector('.order-item-name').textContent}?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#2A2A2A', confirmButtonText: 'Remove' })
                    .then(r => { if (r.isConfirmed) removeOrderItem(card.dataset.productId); });
                });
            });
        }

        function updateOrderQuantity(productId, quantity) {
            fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=update_quantity&product_id=${productId}&quantity=${quantity}` })
            .then(r => r.json()).then(data => { if (!data.error) { lastOrderData = data; updatePaymentDetails(data); } });
        }

        function updatePaymentDetails(data) {
            if (!data.items || !data.items.length) return;
            document.querySelector('.order-payment-details').innerHTML = buildPaymentSummaryHtml(data.items);
        }

        function removeOrderItem(productId) {
            fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=remove_item&product_id=${productId}` })
            .then(r => r.json()).then(data => { if (!data.error) loadOrderDetails(); });
        }

        function getCustomerName() {
            const el = document.getElementById('customer-name');
            if (!el) return 'Guest';
            return (el.value !== undefined ? el.value : el.textContent).trim() || 'Guest';
        }

        function submitOrder(customerName, orderType, paymentType, proofFile, tableNumber) {
            const fd = new FormData();
            fd.append('action', 'create_order_with_payment');
            fd.append('customer_name', customerName);
            fd.append('order_type', orderType);
            fd.append('user_id', USER_ID);
            fd.append('payment_type', paymentType);
            fd.append('discount_enabled', discountEnabled ? '1' : '0');
            fd.append('discount_percent', String(discountPercent || 0));
            if (tableNumber) fd.append('table_number', tableNumber);
            if (proofFile) fd.append('proof_of_payment', proofFile);
            return fetch('../backend/order_manager.php', { method: 'POST', body: fd }).then(r => r.json());
        }

        function getOrderType() {
            const el = document.getElementById('order-type');
            return el ? el.value : 'account-order';
        }
        function getTableNumber() {
            const el = document.getElementById('table-number');
            return el ? el.value.trim() : '';
        }

        function printReceipt(orderId, customerName, items, discountedTotal, orderType, tableNumber, discSlice) {
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

            let itemsHtml = '';
            items.forEach(i => {
                const subtotal = parseFloat(i.price) * i.quantity;
                itemsHtml += `<tr><td style="text-align:left">${i.product_name}</td><td style="text-align:center">${i.quantity}</td><td style="text-align:right">₱${money(i.price)}</td><td style="text-align:right">₱${money(subtotal)}</td></tr>`;
            });

            const vat = computeVatBreakdown(items);
            const disc = discSlice || computeDiscountSlice(vat.total, false, 0);

            let vatHtml = '';
            if (vat.vatableSales > 0) {
                vatHtml += `<div class="receipt-vat-row"><span>VATable Sales</span><span>₱${money(vat.vatableSales)}</span></div>`;
                vatHtml += `<div class="receipt-vat-row"><span>VAT (12%)</span><span>₱${money(vat.vatAmount)}</span></div>`;
            }
            if (vat.vatExemptSales > 0) {
                vatHtml += `<div class="receipt-vat-row"><span>VAT-Exempt Sales</span><span>₱${money(vat.vatExemptSales)}</span></div>`;
            }
            let discHtml = '';
            if (disc.discAmt > 0) {
                discHtml += `<div class="receipt-vat-row"><span>Discount (${money(disc.pct)}%)</span><span>−₱${money(disc.discAmt)}</span></div>`;
            }

            const logoUrl = new URL('../assets/zoryn/zoryn_logo.jpg', window.location.href).href;

            const receiptHtml = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Receipt #${orderId}</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Poppins',sans-serif; background:#fff; color:#1a1a1a; padding:0; }
.receipt { width:320px; margin:0 auto; padding:24px 20px; }
.receipt-logo { display:block; width:80px; height:80px; object-fit:cover; border-radius:50%; margin:0 auto 8px; border:2px solid #D4AF37; }
.receipt-brand { text-align:center; font-size:16px; font-weight:700; color:#1a1a1a; letter-spacing:1px; margin-bottom:2px; }
.receipt-tagline { text-align:center; font-size:10px; color:#888; margin-bottom:14px; letter-spacing:0.5px; }
.receipt-divider { border:none; border-top:1px dashed #ccc; margin:10px 0; }
.receipt-meta { font-size:11px; color:#555; margin-bottom:3px; display:flex; justify-content:space-between; }
.receipt-table { width:100%; border-collapse:collapse; margin:10px 0; font-size:11px; }
.receipt-table th { text-align:left; font-weight:600; font-size:10px; text-transform:uppercase; color:#888; letter-spacing:0.5px; padding:4px 0; border-bottom:1px solid #ddd; }
.receipt-table td { padding:5px 0; vertical-align:top; }
.receipt-vat-row { display:flex; justify-content:space-between; font-size:10px; color:#666; padding:2px 0; }
.receipt-total { display:flex; justify-content:space-between; font-size:15px; font-weight:700; color:#1a1a1a; padding:8px 0; border-top:2px solid #1a1a1a; margin-top:4px; }
.receipt-footer { text-align:center; margin-top:16px; font-size:10px; color:#999; line-height:1.6; }
.receipt-footer strong { color:#D4AF37; font-size:11px; }
@media print {
    @page { size:80mm auto; margin:0; }
    body { padding:0; }
    .receipt { width:100%; padding:10px 8px; }
    .no-print { display:none !important; }
}
</style></head><body>
<div style="text-align:center;padding:16px 0" class="no-print">
    <button onclick="window.print()" style="padding:10px 28px;background:#D4AF37;color:#0D0D0D;border:none;border-radius:8px;font-weight:600;font-family:Poppins,sans-serif;cursor:pointer;font-size:14px;margin-right:8px"><i class="fas fa-print" style="margin-right:6px"></i>Print</button>
    <button onclick="window.close()" style="padding:10px 28px;background:#2A2A2A;color:#B0B0B0;border:1px solid #ddd;border-radius:8px;font-weight:500;font-family:Poppins,sans-serif;cursor:pointer;font-size:14px">Close</button>
</div>
<div class="receipt">
    <img src="${logoUrl}" class="receipt-logo" alt="Zoryn" onerror="this.style.display='none'">
    <div class="receipt-brand">ZORYN RESTAURANT</div>
    <div class="receipt-tagline">Taste the Excellence</div>
    <hr class="receipt-divider">
    <div class="receipt-meta"><span>Date: ${dateStr}</span><span>${timeStr}</span></div>
    <div class="receipt-meta"><span>Order #${orderId}</span></div>
    <div class="receipt-meta"><span>Customer: ${customerName}</span></div>
    <div class="receipt-meta"><span>Type: ${(orderType || 'walk-in').toString().replace(/\b\w/g, c => c.toUpperCase())}</span>${tableNumber ? `<span>Table: ${tableNumber}</span>` : ''}</div>
    <hr class="receipt-divider">
    <table class="receipt-table">
        <thead><tr><th style="text-align:left">Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>${itemsHtml}</tbody>
    </table>
    <hr class="receipt-divider">
    ${vatHtml}
    ${discHtml}
    <div class="receipt-total"><span>TOTAL</span><span>₱${money(discountedTotal)}</span></div>
    <hr class="receipt-divider">
    <div class="receipt-footer">
        <strong>Thank you for dining with us!</strong><br>
        Please come again.<br>
        &mdash; Zoryn Restaurant &mdash;
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body></html>`;

            const receiptWindow = window.open('', '_blank', 'width=400,height=700');
            receiptWindow.document.write(receiptHtml);
            receiptWindow.document.close();
        }

        function cashierConfirmOrder() {
            const customerName = getCustomerName();
            const orderType = getOrderType();
            const tableNumber = getTableNumber();

            if (orderType === 'dine-in' && !tableNumber) {
                Swal.fire({ title: 'Table # Required', text: 'Please enter the table number for dine-in orders.', icon: 'warning', confirmButtonColor: '#D4AF37' });
                return;
            }
            if (!lastOrderData || !lastOrderData.items || lastOrderData.items.length === 0) {
                Swal.fire({ title: 'No Items', text: 'Add items to the order first.', icon: 'warning', confirmButtonColor: '#D4AF37' });
                return;
            }

            const items = lastOrderData.items;
            let gross = 0;
            items.forEach(i => { gross += parseFloat(i.price) * i.quantity; });
            gross = parseFloat(gross.toFixed(2));
            const discConfirm = computeDiscountSlice(gross, discountEnabled, discountPercent);
            const total = discConfirm.total;

            Swal.fire({
                title: 'Confirm Order',
                html: `<p style="color:#B0B0B0;font-size:14px;">Place order for <strong style="color:#D4AF37">${customerName}</strong>?</p>
                       <p style="color:#F4D26B;font-size:1.2rem;font-weight:700;margin-top:8px;">Total: ₱${money(total)}</p>${discConfirm.discAmt > 0 ? `<p style="color:#7bdc9a;font-size:13px;margin-top:6px;">Including ${money(discConfirm.pct)}% discount (−₱${money(discConfirm.discAmt)})</p>` : ''}`,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check" style="margin-right:6px"></i>Place Order',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#D4AF37',
                cancelButtonColor: '#2A2A2A'
            }).then(result => {
                if (!result.isConfirmed) return;
                submitOrder(customerName, orderType, 'cash', null, tableNumber).then(data => {
                    if (!data.success) {
                        Swal.fire({ title: 'Error', text: data.message || data.error || 'Order failed', icon: 'error', confirmButtonColor: '#D4AF37' });
                        return;
                    }
                    const orderId = data.order_id;
                    Swal.fire({
                        title: 'Order Placed!',
                        html: `<p style="color:#B0B0B0;margin-bottom:4px;">Order <strong style="color:#D4AF37">#${orderId}</strong> for <strong style="color:#D4AF37">${customerName}</strong></p>
                               <p style="color:#F4D26B;font-size:1.1rem;font-weight:700;">₱${money(total)}</p>`,
                        icon: 'success',
                        showDenyButton: true,
                        showCancelButton: false,
                        confirmButtonText: '<i class="fas fa-print" style="margin-right:6px"></i>Print Receipt',
                        denyButtonText: '<i class="fas fa-plus" style="margin-right:6px"></i>New Order',
                        confirmButtonColor: '#D4AF37',
                        denyButtonColor: '#2A2A2A',
                        allowOutsideClick: false
                    }).then(choice => {
                        if (choice.isConfirmed) {
                            printReceipt(orderId, customerName, items, total, orderType, tableNumber, discConfirm);
                        }
                        fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=clear_order' })
                        .then(() => {
                            loadOrderDetails();
                            const nameInput = document.getElementById('customer-name');
                            if (nameInput && nameInput.tagName === 'INPUT') nameInput.value = '';
                            const tableInput = document.getElementById('table-number');
                            if (tableInput) tableInput.value = '';
                        });
                    });
                }).catch(() => Swal.fire({ title: 'Error', text: 'Order processing error', icon: 'error', confirmButtonColor: '#D4AF37' }));
            });
        }

        function userConfirmOrder() {
            const customerName = getCustomerName();
            Swal.fire({
                title: 'Payment', html: `
                    <div class="payment-options">
                        <div class="payment-option" onclick="selectPaymentOption(this, 'cash')"><input type="radio" name="payment_type" value="cash"><label>Cash Payment</label></div>
                        <div class="payment-option" onclick="selectPaymentOption(this, 'online')"><input type="radio" name="payment_type" value="online"><label>Online Payment</label></div>
                    </div>
                    <div class="payment-upload" id="paymentUpload">
                        <div class="upload-btn" onclick="document.getElementById('proofOfPayment').click()"><i class="fas fa-upload" style="margin-right:6px;"></i>Upload Proof</div>
                        <input type="file" id="proofOfPayment" accept="image/*" onchange="previewPaymentProof(this)">
                        <div class="upload-preview" id="uploadPreview"><img id="previewImage" src="" alt="Preview"></div>
                    </div>`,
                showCancelButton: true, confirmButtonText: 'Place Order', cancelButtonText: 'Cancel',
                confirmButtonColor: '#D4AF37', cancelButtonColor: '#2A2A2A',
                didOpen: () => document.querySelector('.payment-option')?.click(),
                preConfirm: () => {
                    const pt = document.querySelector('input[name="payment_type"]:checked')?.value;
                    if (!pt) { Swal.showValidationMessage('Select payment method'); return false; }
                    if (pt === 'online' && !document.getElementById('proofOfPayment').files[0]) { Swal.showValidationMessage('Upload proof of payment'); return false; }
                    return { payment_type: pt, proof_of_payment: document.getElementById('proofOfPayment').files[0] };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    submitOrder(customerName, 'account-order', result.value.payment_type, result.value.proof_of_payment).then(data => {
                        if (data.success) {
                            Swal.fire({ title: 'Order Placed!', text: 'Thank you!', icon: 'success', confirmButtonColor: '#D4AF37' })
                            .then(() => fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=clear_order' }).then(() => loadOrderDetails()));
                        } else Swal.fire({ title: 'Error', text: data.message || 'Order failed', icon: 'error', confirmButtonColor: '#D4AF37' });
                    }).catch(() => Swal.fire({ title: 'Error', text: 'Order processing error', icon: 'error', confirmButtonColor: '#D4AF37' }));
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadOrderDetails();
            const typeSel = document.getElementById('order-type');
            const tableField = document.getElementById('table-number-field');
            const searchInput = document.getElementById('productSearchInput');
            if (typeSel && tableField) {
                const syncTableVisibility = () => {
                    tableField.style.display = typeSel.value === 'dine-in' ? '' : 'none';
                };
                typeSel.addEventListener('change', syncTableVisibility);
                syncTableVisibility();
            }
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    productSearchTerm = this.value || '';
                    filterProducts(currentCategory);
                });
            }

            const discToggle = document.getElementById('discount-enabled-toggle');
            const discInp = document.getElementById('discount-percent-input');
            if (discToggle) {
                discToggle.addEventListener('change', function() {
                    discountEnabled = discToggle.checked;
                    syncDiscountControlsFromState();
                    if (lastOrderData && lastOrderData.items && lastOrderData.items.length) {
                        updatePaymentDetails(lastOrderData);
                    }
                    persistDiscountToSession();
                });
            }
            if (discInp) {
                discInp.addEventListener('input', function() {
                    const v = parseFloat(this.value);
                    discountPercent = Number.isFinite(v) ? Math.min(100, Math.max(0, v)) : 0;
                    if (lastOrderData && lastOrderData.items && lastOrderData.items.length) {
                        updatePaymentDetails(lastOrderData);
                    }
                    persistDiscountToSession();
                });
            }
        });

        document.querySelector('.order-confirm-btn').addEventListener('click', function() {
            IS_CASHIER ? cashierConfirmOrder() : userConfirmOrder();
        });

        window.selectPaymentOption = function(el, type) {
            document.querySelectorAll('.payment-option').forEach(o => { o.classList.remove('selected'); o.querySelector('input').checked = false; });
            el.classList.add('selected'); el.querySelector('input').checked = true;
            const up = document.getElementById('paymentUpload');
            type === 'online' ? up.classList.add('active') : up.classList.remove('active');
        }
        window.previewPaymentProof = function(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { document.getElementById('previewImage').src = e.target.result; document.getElementById('uploadPreview').style.display = 'block'; };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
