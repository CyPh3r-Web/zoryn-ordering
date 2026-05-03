<?php
session_start();
// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Orders tracking page is cashier-only. Waiters and everyone else are redirected.
if (($_SESSION['role'] ?? '') !== 'cashier') {
    header("Location: order-details.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Orders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <script src="js/active-page.js"></script>
    <style>
        /* ---------- Base (self-contained; immune to Tailwind Preflight reset) ---------- */
        body.zoryn-orders-page {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(212,175,55,0.16), transparent 42%),
                radial-gradient(circle at 88% 0%, rgba(212,175,55,0.10), transparent 38%),
                linear-gradient(145deg, #0D0D0D 0%, #1a1204 38%, #0D0D0D 100%);
            color: #fff;
            min-height: 100vh;
        }
        body.zoryn-orders-page * { box-sizing: border-box; }
        .zoryn-orders-page .main-content {
            margin-left: 260px;
            padding: 96px 24px 32px;
            transition: margin-left 0.3s ease;
        }
        .zoryn-orders-page .main-content.expanded { margin-left: 0; }
        @media (max-width: 1024px) {
            .zoryn-orders-page .main-content { margin-left: 0; padding: 88px 16px 24px; }
        }

        /* ---------- Container / header / filters ---------- */
        .zoryn-orders-page .orders-container { max-width: 1400px; margin: 0 auto; }
        .zoryn-orders-page .orders-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; padding: 22px 26px; flex-wrap: wrap; gap: 16px;
            background: rgba(31, 31, 31, 0.85);
            border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .zoryn-orders-page .orders-header h1 {
            font-size: 1.5rem; font-weight: 700;
            color: #D4AF37; margin: 0; letter-spacing: 0.3px;
        }
        .zoryn-orders-page .orders-filter { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .zoryn-orders-page .filter-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .zoryn-orders-page input[type="date"],
        .zoryn-orders-page .filter-select {
            padding: 9px 14px;
            border: 1px solid #2E2E2E;
            border-radius: 10px;
            background: #1F1F1F;
            color: #E5E5E5;
            font-size: 13px;
            min-width: 150px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }
        .zoryn-orders-page input[type="date"] { min-width: 160px; color-scheme: dark; }
        .zoryn-orders-page input[type="date"]:focus,
        .zoryn-orders-page .filter-select:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.18);
        }

        /* ---------- Table ---------- */
        .zoryn-orders-page .orders-table-container {
            background: rgba(31, 31, 31, 0.85);
            border: 1px solid rgba(212,175,55,0.18);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
        }
        .zoryn-orders-page .orders-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .zoryn-orders-page .orders-table thead th {
            background: #161616;
            color: #F5D76E;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #2E2E2E;
        }
        .zoryn-orders-page .orders-table thead th:nth-child(1),
        .zoryn-orders-page .orders-table tbody td:nth-child(1) { width: 13%; }
        .zoryn-orders-page .orders-table thead th:nth-child(2),
        .zoryn-orders-page .orders-table tbody td:nth-child(2) { width: 20%; }
        .zoryn-orders-page .orders-table thead th:nth-child(3),
        .zoryn-orders-page .orders-table tbody td:nth-child(3) { width: 14%; }
        .zoryn-orders-page .orders-table thead th:nth-child(4),
        .zoryn-orders-page .orders-table tbody td:nth-child(4) { width: 10%; }
        .zoryn-orders-page .orders-table thead th:nth-child(5),
        .zoryn-orders-page .orders-table tbody td:nth-child(5) { width: 23%; }
        .zoryn-orders-page .orders-table thead th:nth-child(6),
        .zoryn-orders-page .orders-table tbody td:nth-child(6) { width: 20%; }
        .zoryn-orders-page .orders-table tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid #2E2E2E;
            color: #D1D1D1;
            font-size: 14px;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 72px;
        }
        .zoryn-orders-page .orders-table tbody td:nth-child(5),
        .zoryn-orders-page .orders-table tbody td:nth-child(6) {
            overflow: visible;
            text-overflow: clip;
        }
        .zoryn-orders-page .orders-table tbody tr:hover td { background: rgba(212,175,55,0.05); color: #fff; }
        .zoryn-orders-page .orders-table tbody tr:last-child td { border-bottom: none; }
        .zoryn-orders-page .orders-table tbody tr.no-orders td {
            text-align: center; color: #7a7a7a; padding: 40px 16px; font-style: italic;
        }

        /* ---------- Buttons / badges / actions ---------- */
        .zoryn-orders-page .action-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        .zoryn-orders-page .action-btn {
            padding: 7px 13px; border-radius: 9px; font-size: 12px; cursor: pointer; border: none;
            font-weight: 600; transition: all 0.2s; font-family: 'Poppins', sans-serif;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .zoryn-orders-page .action-btn.view { background: rgba(212,175,55,0.15); color: #F5D76E; border: 1px solid rgba(212,175,55,0.25); }
        .zoryn-orders-page .action-btn.view:hover { background: rgba(212,175,55,0.25); transform: translateY(-1px); }

        .zoryn-orders-page .status-btn {
            padding: 7px 14px; border: none; border-radius: 9px; cursor: pointer;
            transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; font-family: 'Poppins', sans-serif;
        }
        .zoryn-orders-page .status-btn.preparing { background: rgba(116,185,255,0.18); color: #8fcbff; border: 1px solid rgba(116,185,255,0.28); }
        .zoryn-orders-page .status-btn.preparing:hover { background: rgba(116,185,255,0.28); transform: translateY(-1px); }
        .zoryn-orders-page .status-btn.completed { background: rgba(0,184,148,0.18); color: #78ebca; border: 1px solid rgba(0,184,148,0.28); }
        .zoryn-orders-page .status-btn.completed:hover { background: rgba(0,184,148,0.28); transform: translateY(-1px); }

        .zoryn-orders-page .status-badge {
            padding: 5px 12px; border-radius: 9999px; font-size: 11px;
            font-weight: 600; display: inline-flex; align-items: center; gap: 5px;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .zoryn-orders-page .status-badge.completed { background: rgba(0,184,148,0.15); color: #78ebca; }
        .zoryn-orders-page .status-badge.cancelled {
            background: rgba(220,53,69,0.15); color: #ff8b92;
        }
        .zoryn-orders-page .status-badge i { font-size: 10px; }

        /* Order-type badges */
        .zoryn-orders-page .type-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 9999px;
            font-size: 11px; font-weight: 600; letter-spacing: 0.3px;
            text-transform: uppercase; white-space: nowrap;
        }
        .zoryn-orders-page .type-badge i { font-size: 10px; }
        .zoryn-orders-page .type-badge.dine-in       { background: rgba(212,175,55,0.14); color: #f4d26b; border: 1px solid rgba(212,175,55,0.28); }
        .zoryn-orders-page .type-badge.take-out      { background: rgba(116,185,255,0.15); color: #8fcbff; border: 1px solid rgba(116,185,255,0.28); }
        .zoryn-orders-page .type-badge.walk-in       { background: rgba(0,184,148,0.14); color: #78ebca; border: 1px solid rgba(0,184,148,0.28); }
        .zoryn-orders-page .type-badge.account-order { background: rgba(162,155,254,0.16); color: #c5bdff; border: 1px solid rgba(162,155,254,0.28); }
        .zoryn-orders-page .type-badge .table-tag {
            margin-left: 6px; padding: 1px 6px; border-radius: 6px;
            background: rgba(0,0,0,0.35); color: #fff; font-size: 10px; letter-spacing: 0.5px;
        }

        /* Payment badges */
        .zoryn-orders-page .payment-status {
            padding: 5px 12px; border-radius: 9999px; font-size: 11px;
            font-weight: 600; display: inline-flex; align-items: center; gap: 5px;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .zoryn-orders-page .payment-status.pending  { background: rgba(253,203,110,0.15); color: #FDCB6E; }
        .zoryn-orders-page .payment-status.verified { background: rgba(0,184,148,0.15);   color: #78ebca; }
        .zoryn-orders-page .payment-status.unpaid      { background: rgba(220,53,69,0.15);   color: #ff8b92; }
        .zoryn-orders-page .payment-status.charge_corp { background: rgba(162,155,254,0.16); color: #c5bdff; }
        .zoryn-orders-page .payment-status i { font-size: 10px; }
        .zoryn-orders-page .payment-select:disabled,
        .zoryn-orders-page .filter-select:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .zoryn-orders-page .payment-controls {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            min-width: 0;
            width: 100%;
        }
        .zoryn-orders-page .payment-control-select {
            width: calc(50% - 3px);
            min-width: 0;
            padding: 7px 10px;
            border: 1px solid #2E2E2E;
            border-radius: 9px;
            background: #1F1F1F;
            color: #E5E5E5;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            outline: none;
        }
        .zoryn-orders-page .payment-control-select:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.18);
        }

        /* Mark-as-paid button + verify + image proof */
        .zoryn-orders-page .mark-paid-btn {
            background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D; border: none;
            padding: 8px 14px; border-radius: 9px; cursor: pointer; display: inline-flex;
            align-items: center; gap: 6px; font-size: 12px; font-weight: 700;
            margin-top: 10px; font-family: 'Poppins', sans-serif; transition: all 0.2s;
        }
        .zoryn-orders-page .mark-paid-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-1px); }
        .zoryn-orders-page .payment-select {
            width: 100%;
            margin-top: 12px;
            background: rgba(255,255,255,0.06);
            color: #f1f1f1;
            border: 1px solid rgba(212,175,55,0.28);
            border-radius: 10px;
            padding: 10px 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
        }

        .zoryn-orders-page .verify-btn {
            margin-top: 15px; padding: 10px 20px;
            background: linear-gradient(135deg, #F4D26B, #C99B2A); color: #0D0D0D;
            border: none; border-radius: 10px; cursor: pointer; font-weight: 700;
            transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;
            font-family: 'Poppins', sans-serif;
        }
        .zoryn-orders-page .verify-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); transform: translateY(-2px); }

        /* Swal overrides (scoped) */
        .payment-proof-modal { max-width: 90vw !important; max-height: 90vh !important; }
        .payment-proof-modal .swal2-image { max-width: 100%; max-height: 80vh; object-fit: contain; }

        /* Details modal helpers */
        .zoryn-orders-page .text-success { color: #78ebca !important; }
        .zoryn-orders-page .text-warning { color: #FDCB6E !important; }

        /* ==========================================================
         * Shared "View Order" modal — same look as admin/orders.php
         * These rules are NOT scoped to .zoryn-orders-page because
         * the Swal popup is portaled to <body> outside the page scope.
         * ========================================================== */
        .swal-modern-product-popup {
            background:
                radial-gradient(circle at top right, rgba(212, 175, 55, 0.14), transparent 28%),
                linear-gradient(180deg, #191919 0%, #111111 100%) !important;
            border: 1px solid rgba(212, 175, 55, 0.18) !important;
            border-radius: 24px !important;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45) !important;
            color: #f5f5f5 !important;
            padding: 1.25rem !important;
        }
        .product-modal-title { color: #f8e7a4 !important; font-size: 1.1rem !important; font-weight: 700 !important; letter-spacing: 0.01em; }
        .product-modal-content { margin: 0 !important; padding-top: 0.5rem !important; }
        .swal-modern-product-confirm,
        .swal-modern-product-deny { border-radius: 12px !important; padding: 0.75rem 1.35rem !important; font-weight: 700 !important; }
        .swal-modern-product-confirm { color: #111 !important; box-shadow: 0 10px 24px rgba(212, 175, 55, 0.22) !important; }
        .swal-modern-product-deny    { box-shadow: 0 10px 24px rgba(220, 53, 69, 0.20) !important; }
        .swal-modern-product-close   { color: #d7b75b !important; }

        .product-view-modal { display: flex; flex-direction: column; gap: 18px; text-align: left; }

        .product-modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding-bottom: 6px; }
        .product-modal-header-info h2 { margin: 0; color: #fff; font-size: 1.45rem; font-weight: 700; }
        .product-modal-subtitle { color: #9ca3af; font-size: 13px; margin-top: 4px; }
        .product-modal-status {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 6px; padding: 5px 12px; border-radius: 999px;
            font-size: 11px; font-weight: 700; text-transform: capitalize; margin-top: 10px;
        }
        .product-modal-status.pending   { background: rgba(255,193,7,0.16);  color: #ffd666; }
        .product-modal-status.preparing { background: rgba(116,185,255,0.18); color: #8fcbff; }
        .product-modal-status.completed { background: rgba(0,184,148,0.18);   color: #78ebca; }
        .product-modal-status.cancelled  { background: rgba(220,53,69,0.18);   color: #ff8b92; }

        .product-info-section { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .product-info-card,
        .product-section-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.12);
            border-radius: 18px;
            padding: 16px;
        }
        .product-info-card { display: flex; align-items: center; gap: 14px; }
        .product-info-icon {
            width: 46px; height: 46px; border-radius: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.18);
            color: #f4d26b; font-weight: 700; flex-shrink: 0;
        }
        .product-info-content { min-width: 0; }
        .product-info-label,
        .product-section-title {
            color: #9d9d9d; font-size: 11px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .product-info-value { color: #fff; font-size: 14px; font-weight: 600; margin-top: 6px; word-break: break-word; }
        .product-section-title { margin: 0 0 12px; }

        .order-items-list { display: flex; flex-direction: column; gap: 12px; }
        .order-item-card {
            display: flex; align-items: center; gap: 14px; padding: 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(212, 175, 55, 0.1);
            position: relative;
        }
        .order-item-remove-btn {
            flex-shrink: 0; align-self: flex-start;
            width: 36px; height: 36px; border-radius: 10px; border: 1px solid rgba(220,53,69,0.35);
            background: rgba(220,53,69,0.12); color: #ff8b92; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            transition: background 0.2s, transform 0.2s;
        }
        .order-item-remove-btn:hover { background: rgba(220,53,69,0.22); transform: translateY(-1px); }
        .order-item-image {
            width: 74px; height: 74px; border-radius: 16px;
            overflow: hidden; flex-shrink: 0;
            background: linear-gradient(180deg, #222, #141414);
            border: 1px solid rgba(212, 175, 55, 0.12);
        }
        .order-item-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .order-item-details { flex: 1; min-width: 0; }
        .order-item-name { margin: 0 0 6px; color: #fff; font-size: 15px; font-weight: 600; }
        .order-item-meta { display: flex; flex-wrap: wrap; gap: 8px; }
        .order-item-pill {
            display: inline-flex; align-items: center;
            padding: 6px 10px; border-radius: 999px;
            background: rgba(212, 175, 55, 0.1);
            color: #f6e2a2; font-size: 12px; font-weight: 600;
        }
        .order-total-bar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-top: 16px; padding-top: 16px;
            border-top: 1px solid rgba(212, 175, 55, 0.12);
        }
        .order-total-label { color: #9d9d9d; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
        .order-total-value { color: #f4d26b; font-size: 1.2rem; font-weight: 700; }

        /* Modal-scoped payment extras (user side only) */
        .product-section-card .proof-of-payment { margin-top: 16px; text-align: center; }
        .product-section-card .proof-of-payment h4 {
            margin: 0 0 12px; color: #9d9d9d; font-size: 11px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .product-section-card .proof-image {
            max-width: 320px; margin: 0 auto; border: 1px solid rgba(212,175,55,0.15);
            border-radius: 12px; overflow: hidden; cursor: pointer;
            transition: transform 0.25s ease, border-color 0.25s ease;
        }
        .product-section-card .proof-image:hover { transform: scale(1.02); border-color: rgba(212,175,55,0.4); }
        .product-section-card .proof-image img { width: 100%; height: auto; display: block; }

        .view-empty-note { color: #9d9d9d; font-size: 13px; }
        .add-order-picker {
            max-height: 52vh;
            overflow-y: auto;
            padding-right: 4px;
        }
        .add-order-search {
            width: 100%;
            margin-bottom: 12px;
            background: #141414;
            border: 1px solid #2e2e2e;
            border-radius: 10px;
            color: #efefef;
            padding: 10px 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
        }
        .add-order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
        }
        .add-order-card {
            background: linear-gradient(165deg, #181818 0%, #101010 100%);
            border: 1px solid #333;
            border-radius: 14px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .add-order-card.active {
            border-color: #d4af37;
            box-shadow: 0 0 0 2px rgba(212,175,55,0.35), 0 10px 24px rgba(212,175,55,0.16);
            transform: translateY(-2px);
            background: linear-gradient(165deg, #211a09 0%, #151207 100%);
        }
        .add-order-card.active::after {
            content: '\f00c';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f4d26b, #c99b2a);
            color: #111;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: 0 6px 16px rgba(212,175,55,0.35);
        }
        .add-order-card img {
            width: 100%;
            height: 120px;
            object-fit: contain;
            border-radius: 10px;
            background: rgba(255,255,255,0.02);
        }
        .add-order-card .name {
            color: #f6e2a2;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            min-height: 34px;
        }
        .add-order-card .price {
            color: #d4af37;
            font-size: 12px;
            font-weight: 700;
            margin-top: 4px;
        }
        .add-order-card .qty {
            margin-top: 8px;
            width: 72px;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            background: #0f0f0f;
            color: #fff;
            text-align: center;
            padding: 6px;
        }
        .swal-blackgold-popup {
            background:
                radial-gradient(circle at top right, rgba(212,175,55,0.14), transparent 28%),
                linear-gradient(180deg, #191919 0%, #111111 100%) !important;
            border: 1px solid rgba(212,175,55,0.2) !important;
            border-radius: 20px !important;
            color: #f5f5f5 !important;
        }
        .swal-blackgold-title { color: #f8e7a4 !important; }
        .swal-blackgold-html { color: #ddd !important; }
        .swal-blackgold-confirm {
            background: linear-gradient(135deg, #f4d26b, #c99b2a) !important;
            color: #111 !important;
            border-radius: 10px !important;
            font-weight: 700 !important;
        }
        .swal-blackgold-cancel {
            background: #2a2a2a !important;
            color: #ddd !important;
            border: 1px solid #3a3a3a !important;
            border-radius: 10px !important;
        }

        @media (max-width: 680px) {
            .product-modal-header    { flex-direction: column; }
            .product-info-section    { grid-template-columns: 1fr; }
            .order-item-card         { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body class="zoryn-orders-page">
    <?php include("../navigation/navbar.php");?>
    <?php include("../navigation/cashier-sidebar.php");?>
    <div class="main-content">
        <div class="orders-container">
            <div class="orders-header">
                <h1>Orders Management</h1>
                <div class="orders-filter">
                    <div class="filter-group">
                        <input type="date" id="orderDateFilter" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Filter by date" title="Showing today by default; pick another date to view that day">
                        <select id="paymentStatusFilter" class="filter-select">
                            <option value="">All Payment Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Paid</option>
                            <option value="charge_corp">Charge to corp</option>
                        </select>
                        <select id="orderStatusFilter" class="filter-select">
                            <option value="">All Order Status</option>
                            <option value="pending">Pending</option>
                            <option value="preparing">Preparing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select id="orderTypeFilter" class="filter-select">
                            <option value="">All Order Types</option>
                            <option value="dine-in">Dine-in</option>
                            <option value="take-out">Take-out</option>
                            <option value="walk-in">Walk-in</option>
                            <option value="account-order">Account Order</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <!-- Orders will be loaded here dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    function todayLocalYYYYMMDD() {
        const d = new Date();
        return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
    }
    function money(n) {
        return parseFloat(n || 0).toFixed(2);
    }
    function computeVatBreakdown(items) {
        let vatableSales = 0, vatAmount = 0, vatExemptSales = 0, total = 0;
        (items || []).forEach(i => {
            const lineTotal = parseFloat(i.price || 0) * parseInt(i.quantity || 0, 10);
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
    function printOrderReceipt(order) {
        if (!order || !Array.isArray(order.items) || order.items.length === 0) {
            Swal.fire({ title: 'No items to print', text: 'This order has no printable line items.', icon: 'warning', confirmButtonColor: '#D4AF37' });
            return;
        }
        const createdAt = order.created_at ? new Date(order.created_at) : new Date();
        const dateStr = createdAt.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
        const timeStr = createdAt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
        let itemsHtml = '';
        order.items.forEach(i => {
            const qty = parseInt(i.quantity || 0, 10);
            const price = parseFloat(i.price || 0);
            const subtotal = price * qty;
            itemsHtml += `<tr><td style="text-align:left">${i.product_name}</td><td style="text-align:center">${qty}</td><td style="text-align:right">₱${money(price)}</td><td style="text-align:right">₱${money(subtotal)}</td></tr>`;
        });
        const vat = computeVatBreakdown(order.items);
        const vatHtml = `${vat.vatableSales > 0 ? `<div class="receipt-vat-row"><span>VATable Sales</span><span>₱${money(vat.vatableSales)}</span></div><div class="receipt-vat-row"><span>VAT (12%)</span><span>₱${money(vat.vatAmount)}</span></div>` : ''}${vat.vatExemptSales > 0 ? `<div class="receipt-vat-row"><span>VAT-Exempt Sales</span><span>₱${money(vat.vatExemptSales)}</span></div>` : ''}`;
        const logoUrl = new URL('../assets/zoryn/zoryn_logo.jpg', window.location.href).href;
        const orderTypeLabel = (order.order_type || 'walk-in').toString().replace(/\b\w/g, c => c.toUpperCase());
        const receiptHtml = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Receipt #${order.order_id}</title><style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Poppins',sans-serif;background:#fff;color:#1a1a1a}.receipt{width:320px;margin:0 auto;padding:24px 20px}.receipt-logo{display:block;width:80px;height:80px;object-fit:cover;border-radius:50%;margin:0 auto 8px;border:2px solid #D4AF37}.receipt-brand{text-align:center;font-size:16px;font-weight:700;letter-spacing:1px;margin-bottom:2px}.receipt-tagline{text-align:center;font-size:10px;color:#888;margin-bottom:14px;letter-spacing:.5px}.receipt-divider{border:none;border-top:1px dashed #ccc;margin:10px 0}.receipt-meta{font-size:11px;color:#555;margin-bottom:3px;display:flex;justify-content:space-between}.receipt-table{width:100%;border-collapse:collapse;margin:10px 0;font-size:11px}.receipt-table th{text-align:left;font-weight:600;font-size:10px;text-transform:uppercase;color:#888;letter-spacing:.5px;padding:4px 0;border-bottom:1px solid #ddd}.receipt-table td{padding:5px 0;vertical-align:top}.receipt-vat-row{display:flex;justify-content:space-between;font-size:10px;color:#666;padding:2px 0}.receipt-total{display:flex;justify-content:space-between;font-size:15px;font-weight:700;padding:8px 0;border-top:2px solid #1a1a1a;margin-top:4px}.receipt-footer{text-align:center;margin-top:16px;font-size:10px;color:#999;line-height:1.6}.receipt-footer strong{color:#D4AF37;font-size:11px}@media print{@page{size:80mm auto;margin:0}.receipt{width:100%;padding:10px 8px}.no-print{display:none!important}}</style></head><body><div style="text-align:center;padding:16px 0" class="no-print"><button onclick="window.print()" style="padding:10px 28px;background:#D4AF37;color:#0D0D0D;border:none;border-radius:8px;font-weight:600;font-family:Poppins,sans-serif;cursor:pointer;font-size:14px;margin-right:8px"><i class="fas fa-print" style="margin-right:6px"></i>Print</button><button onclick="window.close()" style="padding:10px 28px;background:#2A2A2A;color:#B0B0B0;border:1px solid #ddd;border-radius:8px;font-weight:500;font-family:Poppins,sans-serif;cursor:pointer;font-size:14px">Close</button></div><div class="receipt"><img src="${logoUrl}" class="receipt-logo" alt="Zoryn" onerror="this.style.display='none'"><div class="receipt-brand">ZORYN RESTAURANT</div><div class="receipt-tagline">Taste the Excellence</div><hr class="receipt-divider"><div class="receipt-meta"><span>Date: ${dateStr}</span><span>${timeStr}</span></div><div class="receipt-meta"><span>Order #${order.order_id}</span></div><div class="receipt-meta"><span>Customer: ${order.customer_name || 'Guest'}</span></div><div class="receipt-meta"><span>Type: ${orderTypeLabel}</span>${order.table_number ? `<span>Table: ${order.table_number}</span>` : ''}</div><hr class="receipt-divider"><table class="receipt-table"><thead><tr><th style="text-align:left">Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Total</th></tr></thead><tbody>${itemsHtml}</tbody></table><hr class="receipt-divider">${vatHtml}<div class="receipt-total"><span>TOTAL</span><span>₱${money(vat.total)}</span></div><hr class="receipt-divider"><div class="receipt-footer"><strong>Thank you for dining with us!</strong><br>Please come again.<br>&mdash; Zoryn Restaurant &mdash;</div></div><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></body></html>`;
        const receiptWindow = window.open('', '_blank', 'width=420,height=760');
        if (!receiptWindow) return;
        receiptWindow.document.write(receiptHtml);
        receiptWindow.document.close();
    }

    // Move these functions outside of DOMContentLoaded
    window.verifyPayment = function(orderId, buttonElement) {
        Swal.fire({
            title: 'Verify Payment',
            text: 'Are you sure you want to verify this payment?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#D4AF37',
            cancelButtonColor: '#2E2E2E',
            confirmButtonText: 'Yes, verify it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=verify_payment&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        Swal.fire({
                            title: 'Verified!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#D4AF37',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            if (typeof window.loadOrders === 'function') {
                                window.loadOrders();
                            } else if (data.should_reload) {
                                window.location.href = window.location.href.split('#')[0] + '?t=' + new Date().getTime();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to verify payment',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while verifying payment',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }
        });
    }

    window.showFullImage = function(src) {
        Swal.fire({
            imageUrl: src,
            imageAlt: 'Proof of Payment',
            width: 'auto',
            padding: '1em',
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {
                popup: 'payment-proof-modal'
            }
        });
    }

    function canEditOrderLines(orderStatus) {
        const s = (orderStatus || '').toLowerCase();
        return s === 'pending' || s === 'preparing';
    }

    window.removeOrderLine = function(orderId, orderItemId) {
        Swal.fire({
            title: 'Remove this item?',
            html: `
                <p style="margin-bottom:10px;">It will be taken off the order and stock will be restored.</p>
                <input id="adminPinInput" type="password" class="swal2-input" inputmode="numeric" maxlength="8" placeholder="Enter admin PIN">
            `,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#2E2E2E',
            denyButtonColor: '#2A2A2A',
            confirmButtonText: 'Yes, remove it',
            denyButtonText: 'No admin PIN set?',
            preConfirm: () => {
                const pin = (document.getElementById('adminPinInput')?.value || '').trim();
                if (!/^\d{4,8}$/.test(pin)) {
                    Swal.showValidationMessage('Admin PIN is required (4-8 digits).');
                    return false;
                }
                return pin;
            }
        }).then((result) => {
            if (result.isDenied) {
                Swal.fire({
                    title: 'Setup Required',
                    html: `
                        <p style="margin-bottom:8px;">An admin must set an override PIN first.</p>
                        <p style="font-size:13px; color:#b0b0b0; margin:0;">
                            Go to <strong style="color:#D4AF37;">Admin &gt; Users</strong> and click the <strong style="color:#D4AF37;">key icon</strong> on an admin account to save a PIN.
                        </p>
                    `,
                    icon: 'info',
                    confirmButtonColor: '#D4AF37'
                });
                return;
            }
            if (!result.isConfirmed) return;
            const adminPin = result.value;
            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=remove_order_item&order_id=${orderId}&order_item_id=${orderItemId}&admin_pin=${encodeURIComponent(adminPin)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.close();
                    if (typeof window.loadOrders === 'function') window.loadOrders();
                    Swal.fire({
                        title: data.order_empty ? 'Order cancelled' : 'Updated',
                        text: data.message || 'Item removed',
                        icon: 'success',
                        confirmButtonColor: '#D4AF37'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Could not remove item',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'Request failed',
                    icon: 'error',
                    confirmButtonColor: '#D4AF37'
                });
            });
        });
    };

    window.cancelWholeOrder = function(orderId) {
        Swal.fire({
            title: 'Cancel this order?',
            html: `
                <p style="margin-bottom:10px;">The full order will be marked cancelled and ingredients will be restocked.</p>
                <input id="adminPinCancelInput" type="password" class="swal2-input" inputmode="numeric" maxlength="8" placeholder="Enter admin PIN">
            `,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#2E2E2E',
            denyButtonColor: '#2A2A2A',
            confirmButtonText: 'Yes, cancel order',
            denyButtonText: 'No admin PIN set?',
            preConfirm: () => {
                const pin = (document.getElementById('adminPinCancelInput')?.value || '').trim();
                if (!/^\d{4,8}$/.test(pin)) {
                    Swal.showValidationMessage('Admin PIN is required (4-8 digits).');
                    return false;
                }
                return pin;
            }
        }).then((result) => {
            if (result.isDenied) {
                Swal.fire({
                    title: 'Setup Required',
                    html: `
                        <p style="margin-bottom:8px;">An admin must set an override PIN first.</p>
                        <p style="font-size:13px; color:#b0b0b0; margin:0;">
                            Go to <strong style="color:#D4AF37;">Admin &gt; Users</strong> and click the <strong style="color:#D4AF37;">key icon</strong> on an admin account to save a PIN.
                        </p>
                    `,
                    icon: 'info',
                    confirmButtonColor: '#D4AF37'
                });
                return;
            }
            if (!result.isConfirmed) return;
            const adminPin = result.value;
            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=cancel_order&order_id=${orderId}&admin_pin=${encodeURIComponent(adminPin)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.close();
                    if (typeof window.loadOrders === 'function') window.loadOrders();
                    Swal.fire({
                        title: 'Cancelled',
                        text: data.message || 'Order cancelled',
                        icon: 'success',
                        confirmButtonColor: '#D4AF37'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Could not cancel order',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'Request failed',
                    icon: 'error',
                    confirmButtonColor: '#D4AF37'
                });
            });
        });
    };

    const PAYMENT_STATUS_LABELS = { paid: 'Paid', unpaid: 'Unpaid', pending: 'Pending', charge_corp: 'Charge to corp', verified: 'Paid' };

    window.updatePaymentStatus = function(orderId, paymentStatus) {
        const statusLabel = PAYMENT_STATUS_LABELS[paymentStatus] || paymentStatus;
        Swal.fire({
            title: 'Update payment status?',
            text: `Set payment status to "${statusLabel}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#D4AF37',
            cancelButtonColor: '#2E2E2E',
            confirmButtonText: 'Yes, update'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_payment_status&order_id=${orderId}&payment_status=${encodeURIComponent(paymentStatus)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Payment status updated',
                            icon: 'success',
                            confirmButtonColor: '#D4AF37'
                        }).then(() => {
                            if (typeof window.loadOrders === 'function') window.loadOrders();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to update payment status',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while updating payment status',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            }
        });
    }

    window.updatePaymentMethod = function(orderId, paymentType) {
        fetch('../backend/payment_functions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_payment_method&order_id=${orderId}&payment_type=${encodeURIComponent(paymentType)}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to update payment method',
                    icon: 'error',
                    confirmButtonColor: '#D4AF37'
                });
                if (typeof window.loadOrders === 'function') window.loadOrders();
            }
        })
        .catch(() => {
            Swal.fire({
                title: 'Error',
                text: 'Failed to update payment method',
                icon: 'error',
                confirmButtonColor: '#D4AF37'
            });
            if (typeof window.loadOrders === 'function') window.loadOrders();
        });
    };

    window.addAdditionalItem = function(orderId) {
        fetch('../backend/order_manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_products'
        })
        .then(response => response.json())
        .then(data => {
            const products = data.products || [];
            if (!products.length) {
                Swal.fire({
                    title: 'No products',
                    text: 'No active products available to add.',
                    icon: 'info',
                    confirmButtonColor: '#D4AF37'
                });
                return;
            }

            Swal.fire({
                title: 'Add Another Order Item',
                width: 960,
                html: `
                    <div class="add-order-picker">
                        <input id="addOrderSearchInput" class="add-order-search" placeholder="Search product name...">
                        <div class="add-order-grid" id="addOrderGrid">
                            ${products.map((p, idx) => `
                                <div class="add-order-card ${idx === 0 ? 'active' : ''}" data-product-id="${p.product_id}" data-product-name="${String(p.product_name || '').toLowerCase()}">
                                    <img src="${p.image_path || '../assets/zoryn/zoryn_logo.jpg'}" alt="${String(p.product_name || '').replace(/"/g, '&quot;')}" onerror="this.src='../assets/zoryn/zoryn_logo.jpg'">
                                    <div class="name">${p.product_name}</div>
                                    <div class="price">₱${parseFloat(p.price).toFixed(2)}</div>
                                    <input class="qty" type="number" min="1" max="50" value="1">
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Add to Order',
                confirmButtonColor: '#D4AF37',
                customClass: {
                    popup: 'swal-blackgold-popup',
                    title: 'swal-blackgold-title',
                    htmlContainer: 'swal-blackgold-html',
                    confirmButton: 'swal-blackgold-confirm',
                    cancelButton: 'swal-blackgold-cancel'
                },
                didOpen: () => {
                    const grid = document.getElementById('addOrderGrid');
                    const searchInput = document.getElementById('addOrderSearchInput');
                    const cards = Array.from(grid.querySelectorAll('.add-order-card'));

                    cards.forEach(card => {
                        card.addEventListener('click', (evt) => {
                            if (evt.target.classList.contains('qty')) return;
                            cards.forEach(c => c.classList.remove('active'));
                            card.classList.add('active');
                        });
                    });

                    searchInput.addEventListener('input', () => {
                        const term = searchInput.value.trim().toLowerCase();
                        cards.forEach(card => {
                            const name = card.getAttribute('data-product-name') || '';
                            card.style.display = term === '' || name.includes(term) ? '' : 'none';
                        });
                    });
                },
                preConfirm: () => {
                    const activeCard = document.querySelector('#addOrderGrid .add-order-card.active');
                    const productId = activeCard ? parseInt(activeCard.getAttribute('data-product-id'), 10) : 0;
                    const quantity = activeCard ? parseInt(activeCard.querySelector('.qty').value, 10) : 0;
                    if (!productId || !quantity || quantity < 1) {
                        Swal.showValidationMessage('Select product and valid quantity.');
                        return false;
                    }
                    return { productId, quantity };
                }
            }).then((result) => {
                if (!result.isConfirmed || !result.value) return;
                fetch('../backend/order_functions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=add_order_item&order_id=${orderId}&product_id=${result.value.productId}&quantity=${result.value.quantity}`
                })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        Swal.close();
                        if (typeof window.loadOrders === 'function') window.loadOrders();
                        if (typeof window.viewOrderDetails === 'function') {
                            window.viewOrderDetails(orderId);
                        }
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: res.message || 'Failed to add item',
                            icon: 'error',
                            confirmButtonColor: '#D4AF37'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to add item',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                });
            });
        })
        .catch(() => {
            Swal.fire({
                title: 'Error',
                text: 'Could not load products',
                icon: 'error',
                confirmButtonColor: '#D4AF37'
            });
        });
    };

    // Normalize order_type into label / icon / css class
    function orderTypeMeta(type) {
        const t = (type || '').toLowerCase();
        const map = {
            'dine-in':       { label: 'Dine-in',       icon: 'fa-utensils' },
            'take-out':      { label: 'Take-out',      icon: 'fa-bag-shopping' },
            'walk-in':       { label: 'Walk-in',       icon: 'fa-person-walking' },
            'account-order': { label: 'Account Order', icon: 'fa-user-circle' }
        };
        const meta = map[t] || { label: type || '—', icon: 'fa-tag' };
        meta.cls = t || 'walk-in';
        return meta;
    }
    function renderTypeBadge(type, tableNumber) {
        const m = orderTypeMeta(type);
        const tableTag = (tableNumber && String(tableNumber).trim() !== '')
            ? `<span class="table-tag">T# ${tableNumber}</span>` : '';
        return `<span class="type-badge ${m.cls}"><i class="fas ${m.icon}"></i>${m.label}${tableTag}</span>`;
    }

    let PAYMENT_METHODS = [];
    const DEFAULT_PAYMENT_METHODS = [
        { method_code: 'cash', method_name: 'Cash', requires_proof: 0 },
        { method_code: 'gcash', method_name: 'GCash', requires_proof: 1 },
        { method_code: 'maya', method_name: 'Maya', requires_proof: 1 },
        { method_code: 'card', method_name: 'Card', requires_proof: 0 },
        { method_code: 'bank_transfer', method_name: 'Bank Transfer', requires_proof: 1 }
    ];

    function normalizePaymentMethodCode(code) {
        const raw = String(code || '').trim().toLowerCase();
        if (!raw) return 'cash';
        if (raw === 'online') return 'gcash'; // Legacy compatibility
        return raw;
    }

    function paymentMethodOptionsHtml(selectedCode) {
        const normalizedSelected = normalizePaymentMethodCode(selectedCode);
        const list = PAYMENT_METHODS.length ? PAYMENT_METHODS : DEFAULT_PAYMENT_METHODS;
        const options = list.map(method => {
            const code = String(method.method_code || '').trim().toLowerCase();
            const name = String(method.method_name || method.method_code || 'Unknown').trim();
            const selected = code === normalizedSelected ? 'selected' : '';
            return `<option value="${code}" ${selected}>${name}</option>`;
        });

        const exists = list.some(method => String(method.method_code || '').trim().toLowerCase() === normalizedSelected);
        if (!exists && normalizedSelected) {
            const fallbackLabel = normalizedSelected.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            options.unshift(`<option value="${normalizedSelected}" selected>${fallbackLabel}</option>`);
        }

        return options.join('');
    }

    function loadPaymentMethods() {
        return fetch('../backend/payment_functions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_payment_methods'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.methods) && data.methods.length > 0) {
                PAYMENT_METHODS = data.methods;
            } else {
                PAYMENT_METHODS = DEFAULT_PAYMENT_METHODS;
            }
        })
        .catch(() => {
            PAYMENT_METHODS = DEFAULT_PAYMENT_METHODS;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPaymentMethods();
        // Load initial data
        loadOrders();
        
        // Add auto-reload functionality
        let autoReloadInterval = setInterval(loadOrders, 3000); // Reload every 3 seconds
        
        // Add event listeners for all filters
        const filters = ['orderDateFilter', 'paymentStatusFilter', 'orderStatusFilter', 'orderTypeFilter'];
        filters.forEach(filterId => {
            document.getElementById(filterId).addEventListener('change', function() {
                // Clear existing interval and start a new one when filters change
                clearInterval(autoReloadInterval);
                loadOrders();
                autoReloadInterval = setInterval(loadOrders, 3000);
            });
        });

        // Pause auto-reload when modal is open
        document.addEventListener('click', function(e) {
            if (e.target.closest('.action-btn')) {
                clearInterval(autoReloadInterval);
            }
        });

        // Resume auto-reload when modal is closed
        document.addEventListener('swal:close', function() {
            autoReloadInterval = setInterval(loadOrders, 3000);
        });
        
        // Update loadOrders function to handle all filters
        function loadOrders() {
            const dateEl = document.getElementById('orderDateFilter');
            let dateFilter = (dateEl.value || '').trim();
            if (!dateFilter) {
                dateFilter = todayLocalYYYYMMDD();
                dateEl.value = dateFilter;
            }
            const paymentStatus = document.getElementById('paymentStatusFilter').value;
            const orderStatus = document.getElementById('orderStatusFilter').value;
            const orderType = document.getElementById('orderTypeFilter').value;
            
            const params = new URLSearchParams({
                action: 'get_orders',
                date: dateFilter,
                payment_status: paymentStatus,
                order_status: orderStatus,
                order_type: orderType
            });

            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('ordersTableBody');
                    tbody.innerHTML = '';
                    
                    data.orders.forEach(order => {
                        const isCancelledOrder = (order.order_status || '').toLowerCase() === 'cancelled';
                        const ps = (order.payment_status || '').toLowerCase();
                        const normalizedPaymentStatus = (ps === 'verified' || ps === 'paid')
                            ? 'paid'
                            : (ps === 'charge_corp' ? 'charge_corp' : 'unpaid');
                        const normalizedPaymentType = normalizePaymentMethodCode(order.payment_type);
                        const paymentStatusDropdown = `
                            <div class="payment-controls">
                                <select class="payment-control-select" onchange="updatePaymentMethod(${order.order_id}, this.value)" ${isCancelledOrder ? 'disabled' : ''} title="${isCancelledOrder ? 'Payment method is locked for cancelled orders' : 'Change payment method'}">
                                    ${paymentMethodOptionsHtml(normalizedPaymentType)}
                                </select>
                                <select class="payment-control-select" onchange="updatePaymentStatus(${order.order_id}, this.value)" ${isCancelledOrder ? 'disabled' : ''} title="${isCancelledOrder ? 'Payment status is locked for cancelled orders' : 'Change payment status'}">
                                    <option value="paid" ${normalizedPaymentStatus === 'paid' ? 'selected' : ''}>Paid</option>
                                    <option value="unpaid" ${normalizedPaymentStatus === 'unpaid' ? 'selected' : ''}>Unpaid</option>
                                    <option value="charge_corp" ${normalizedPaymentStatus === 'charge_corp' ? 'selected' : ''}>Charge to corp</option>
                                </select>
                            </div>
                        `;

                        const row = document.createElement('tr');
                        row.className = `order-row`;
                        
                        row.innerHTML = `
                            <td>${order.customer_name}</td>
                            <td>${new Date(order.created_at).toLocaleString()}</td>
                            <td>${renderTypeBadge(order.order_type, order.table_number)}</td>
                            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td>${paymentStatusDropdown}</td>
                            <td class="action-buttons">
                                <button class="action-btn view" data-order-id="${order.order_id}" data-action="view" title="View Order Details">
                                    <i class="fas fa-eye"></i>
                                    <span>View Details</span>
                                </button>
                                ${order.order_status === 'cancelled' ?
                                    `<span class="status-badge cancelled">
                                        <i class="fas fa-ban"></i>
                                        <span>Cancelled</span>
                                    </span>` :
                                    order.order_status === 'pending' ?
                                    `<button class="status-btn preparing" data-order-id="${order.order_id}" title="Start Preparing Order">
                                        <i class="fas fa-utensils"></i>
                                        <span>Prepare</span>
                                    </button>` :
                                    order.order_status === 'preparing' ?
                                    `<button class="status-btn completed" data-order-id="${order.order_id}" title="Mark Order as Completed">
                                        <i class="fas fa-check"></i>
                                        <span>Complete</span>
                                    </button>` :
                                    `<span class="status-badge completed">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Completed</span>
                                    </span>`
                                }
                            </td>
                        `;
                        
                        tbody.appendChild(row);
                        
                        // Add event listener to view button
                        row.querySelector('.action-btn').addEventListener('click', function(e) {
                            e.stopPropagation();
                            const orderId = this.getAttribute('data-order-id');
                            viewOrderDetails(orderId);
                        });
                    });
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
            });
        }
        window.loadOrders = loadOrders;
        
        // Function to view order details
        function viewOrderDetails(orderId) {
            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_order_details&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const order = data.order;
                    const orderStatusLabel = order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1);
                    const statusIconClass = order.order_status === 'pending'
                        ? 'fa-clock'
                        : order.order_status === 'preparing'
                            ? 'fa-utensils'
                            : order.order_status === 'cancelled'
                                ? 'fa-ban'
                                : 'fa-check-circle';

                    const itemsHtml = order.items && order.items.length > 0
                        ? order.items.map(item => `
                            <div class="order-item-card">
                                <div class="order-item-image">
                                    <img src="../${item.image_path || 'assets/images/products/default.jpg'}" alt="${item.product_name}" onerror="this.onerror=null;this.src='../assets/images/products/default.jpg';">
                                </div>
                                <div class="order-item-details">
                                    <h4 class="order-item-name">${item.product_name}</h4>
                                    <div class="order-item-meta">
                                        <span class="order-item-pill">Qty: ${item.quantity}</span>
                                        <span class="order-item-pill">Price: ₱${parseFloat(item.price).toFixed(2)}</span>
                                        <span class="order-item-pill">Subtotal: ₱${parseFloat(item.price * item.quantity).toFixed(2)}</span>
                                    </div>
                                </div>
                                ${canEditOrderLines(order.order_status) ? `
                                <button type="button" class="order-item-remove-btn" onclick="removeOrderLine(${order.order_id}, ${item.order_item_id})" title="Remove this line">
                                    <i class="fas fa-times"></i>
                                </button>` : ''}
                            </div>
                        `).join('')
                        : '<span class="view-empty-note">No order items found.</span>';

                    Swal.fire({
                        title: `<div class="product-modal-header">
                            <div class="product-modal-header-info">
                                <h2>Order #${order.order_id}</h2>
                                <div class="product-modal-status ${order.order_status}">
                                    <i class="fas ${statusIconClass}"></i>
                                    ${orderStatusLabel}
                                </div>
                            </div>
                            <div class="product-modal-subtitle">${new Date(order.created_at).toLocaleString()}</div>
                        </div>`,
                        html: `
                            <div class="product-view-modal">
                                <div class="product-info-section">
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-user"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Customer</div>
                                            <div class="product-info-value">${order.customer_name}</div>
                                        </div>
                                    </div>
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-concierge-bell"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Waiter</div>
                                            <div class="product-info-value">${order.waiter_name || 'Not recorded'}</div>
                                        </div>
                                    </div>
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-cash-register"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Cashier</div>
                                            <div class="product-info-value">${order.cashier_name || 'Not recorded'}</div>
                                        </div>
                                    </div>
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas ${orderTypeMeta(order.order_type).icon}"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Order Type</div>
                                            <div class="product-info-value">${renderTypeBadge(order.order_type, null)}</div>
                                        </div>
                                    </div>
                                    ${order.table_number ? `
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-chair"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Table Number</div>
                                            <div class="product-info-value">${order.table_number}</div>
                                        </div>
                                    </div>` : ''}
                                    <div class="product-info-card">
                                        <div class="product-info-icon"><i class="fas fa-receipt"></i></div>
                                        <div class="product-info-content">
                                            <div class="product-info-label">Items</div>
                                            <div class="product-info-value">${order.item_count || (order.items ? order.items.length : 0)} item(s)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="product-section-card">
                                    <h3 class="product-section-title">Order Items</h3>
                                    <div class="order-items-list">${itemsHtml}</div>
                                    ${canEditOrderLines(order.order_status) ? `
                                    <div style="margin-top:12px;">
                                        <button type="button" class="mark-paid-btn" onclick="addAdditionalItem(${order.order_id})">
                                            <i class="fas fa-plus"></i> Add Item
                                        </button>
                                        <button type="button" class="mark-paid-btn" id="print-receipt-btn" style="margin-left:8px;">
                                            <i class="fas fa-print"></i> Print Receipt
                                        </button>
                                    </div>` : ''}
                                    ${!canEditOrderLines(order.order_status) ? `
                                    <div style="margin-top:12px;">
                                        <button type="button" class="mark-paid-btn" id="print-receipt-btn">
                                            <i class="fas fa-print"></i> Print Receipt
                                        </button>
                                    </div>` : ''}
                                    <div class="order-total-bar">
                                        <div class="order-total-label">Total Amount</div>
                                        <div class="order-total-value">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                    </div>
                                </div>
                            </div>
                        `,
                        width: '900px',
                        showCloseButton: true,
                        showConfirmButton: true,
                        showDenyButton: canEditOrderLines(order.order_status),
                        confirmButtonText: 'Close',
                        denyButtonText: 'Cancel order',
                        confirmButtonColor: '#D4AF37',
                        denyButtonColor: '#dc3545',
                        customClass: {
                            popup: 'swal-modern-product-popup',
                            title: 'product-modal-title',
                            htmlContainer: 'product-modal-content',
                            confirmButton: 'swal-modern-product-confirm',
                            denyButton: 'swal-modern-product-deny',
                            closeButton: 'swal-modern-product-close'
                        },
                        didOpen: () => {
                            const printBtn = document.getElementById('print-receipt-btn');
                            if (printBtn) {
                                printBtn.addEventListener('click', () => printOrderReceipt(order));
                            }
                        }
                    }).then((result) => {
                        if (result.isDenied) {
                            cancelWholeOrder(orderId);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading order details:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load order details',
                    icon: 'error',
                    confirmButtonColor: '#D4AF37'
                });
            });
        }
        window.viewOrderDetails = viewOrderDetails;
        
        // Add event listeners for status buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.status-btn')) {
                const button = e.target.closest('.status-btn');
                const orderId = button.getAttribute('data-order-id');
                
                updateOrderStatus(orderId);
            }
        });
        
        function updateOrderStatus(orderId) {
            fetch('../backend/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the orders to show updated status
                    loadOrders();
                    
                    // Show success message
                    Swal.fire({
                        title: 'Success',
                        text: 'Order status updated successfully',
                        icon: 'success',
                        confirmButtonColor: '#D4AF37'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to update order status',
                        icon: 'error',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while updating the order status',
                    icon: 'error',
                    confirmButtonColor: '#D4AF37'
                });
            });
        }
    });
    </script>
</body>
</html>