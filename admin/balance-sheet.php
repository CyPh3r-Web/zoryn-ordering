<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
if (isset($_SESSION['2fa_pending']) && $_SESSION['2fa_pending']) {
    header("Location: 2fa.php");
    exit();
}
require_once '../backend/dbconn.php';

$stmt = $conn->prepare("SELECT two_factor_enabled FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
if (!$admin) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
if ((int) $admin['two_factor_enabled'] === 1 && !isset($_SESSION['2fa_verified'])) {
    header("Location: 2fa.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Balance Sheet & Journal Entries</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/active-page.js"></script>
    <style>
        body {
            background:
                radial-gradient(circle at top, rgba(212, 175, 55, 0.12), transparent 28%),
                linear-gradient(180deg, #050505 0%, #0a0a0a 45%, #111827 100%);
            color: #ffffff;
        }
        .main-content { margin-left: 260px; padding: 24px; transition: margin-left 0.3s ease; }
        .main-content.expanded { margin-left: 0; }
        .glass-card {
            background: rgba(17, 24, 39, 0.78);
            border: 1px solid rgba(212, 175, 55, 0.18);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(18px);
        }
        .bs-section-title {
            font-size: 11px; font-weight: 700; letter-spacing: 0.22em;
            text-transform: uppercase; color: rgba(212, 175, 55, 0.65); padding: 12px 20px 6px;
        }
        .bs-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 20px; border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.15s;
        }
        .bs-row:hover { background: rgba(212,175,55,0.04); }
        .bs-row-name { font-size: 14px; color: #d1d5db; }
        .bs-row-amount { font-size: 14px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums; }
        .bs-total-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 20px; border-top: 2px solid rgba(212,175,55,0.25);
            background: rgba(212,175,55,0.06);
        }
        .bs-total-row .bs-row-name { font-weight: 700; color: #F5D76E; }
        .bs-total-row .bs-row-amount { font-weight: 700; color: #F5D76E; font-size: 16px; }

        .je-table { width: 100%; border-collapse: collapse; }
        .je-table thead th {
            position: sticky; top: 0; background: rgba(10, 10, 10, 0.95);
            color: #f5d76e; font-size: 11px; letter-spacing: 0.08em;
            text-transform: uppercase; padding: 12px 14px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.16); text-align: left;
        }
        .je-table tbody td {
            padding: 12px 14px; border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            color: #d1d5db; font-size: 13px;
        }
        .je-table tbody tr:hover td { background: rgba(212, 175, 55, 0.05); color: #ffffff; }

        .badge-posted { background: rgba(0,184,148,0.12); color: #00B894; border: 1px solid rgba(0,184,148,0.25); }
        .badge-draft { background: rgba(245,215,110,0.12); color: #F5D76E; border: 1px solid rgba(245,215,110,0.25); }
        .badge-void { background: rgba(255,118,117,0.12); color: #FF7675; border: 1px solid rgba(255,118,117,0.25); }

        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.72); backdrop-filter: blur(4px);
            z-index: 200; display: none; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: #0f1219; border: 1px solid rgba(212,175,55,0.22); border-radius: 24px;
            width: 95%; max-width: 820px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6);
        }

        .form-input {
            width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(212,175,55,0.2);
            border-radius: 12px; padding: 10px 14px; color: #fff; font-size: 14px;
            outline: none; transition: border-color 0.2s;
        }
        .form-input:focus { border-color: rgba(212,175,55,0.55); }
        .form-input option { background: #111827; color: #fff; }

        .line-row { display: grid; grid-template-columns: 1fr 2fr 1fr 1fr 40px; gap: 8px; align-items: center; padding: 6px 0; }

        .btn-gold {
            background: linear-gradient(135deg, #f4d26b, #c99b2a); color: #050505;
            font-weight: 600; border: none; border-radius: 14px; padding: 10px 22px;
            cursor: pointer; transition: all 0.2s; font-size: 14px;
        }
        .btn-gold:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(212,175,55,0.25); }

        .btn-outline {
            background: transparent; border: 1px solid rgba(212,175,55,0.25); color: #F5D76E;
            border-radius: 14px; padding: 10px 22px; cursor: pointer; font-weight: 600;
            transition: all 0.2s; font-size: 14px;
        }
        .btn-outline:hover { border-color: rgba(212,175,55,0.5); background: rgba(212,175,55,0.08); }

        .btn-sm {
            padding: 6px 14px; font-size: 12px; border-radius: 10px;
        }

        .btn-red-outline {
            background: transparent; border: 1px solid rgba(255,118,117,0.3); color: #FF7675;
            border-radius: 10px; padding: 6px 14px; cursor: pointer; font-size: 12px;
            transition: all 0.2s;
        }
        .btn-red-outline:hover { border-color: rgba(255,118,117,0.6); background: rgba(255,118,117,0.1); }

        .remove-line-btn {
            width: 28px; height: 28px; border-radius: 8px; border: 1px solid rgba(255,118,117,0.3);
            background: transparent; color: #FF7675; cursor: pointer; display: flex;
            align-items: center; justify-content: center; transition: all 0.15s;
        }
        .remove-line-btn:hover { background: rgba(255,118,117,0.15); border-color: rgba(255,118,117,0.5); }

        .debit-credit-summary {
            display: flex; gap: 24px; padding: 14px 20px;
            background: rgba(0,0,0,0.3); border-radius: 14px; border: 1px solid rgba(212,175,55,0.12);
        }
        .dc-block label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.15em; color: #9CA3AF; }
        .dc-block .value { font-size: 18px; font-weight: 700; color: #fff; margin-top: 2px; }
        .dc-block .value.balanced { color: #00B894; }
        .dc-block .value.unbalanced { color: #FF7675; }

        .tab-active {
            background: linear-gradient(135deg, #f4d26b, #c99b2a) !important;
            color: #050505 !important;
            box-shadow: 0 10px 24px rgba(212, 175, 55, 0.2);
        }

        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 16px; }
            .line-row { grid-template-columns: 1fr; }
        }
        @media print {
            nav, .sidebar, .print-hide { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-black text-white" style="font-family:'Poppins',sans-serif;">
    <?php include("../navigation/admin-navbar.php"); ?>
    <?php include("../navigation/admin-sidebar.php"); ?>

    <main class="main-content">
        <section class="mx-auto max-w-7xl space-y-6">
            <div class="glass-card relative overflow-hidden p-6">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.18),transparent_30%),radial-gradient(circle_at_bottom_left,rgba(212,175,55,0.12),transparent_32%)]"></div>
                <div class="relative flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex items-center rounded-full border border-yellow-500/20 bg-yellow-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.32em] text-yellow-300">Finance Module</span>
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-white md:text-4xl">Balance Sheet & Journal</h1>
                            <p class="mt-1.5 max-w-2xl text-sm text-gray-300">View the balance sheet position and manage journal entries with full double-entry bookkeeping.</p>
                        </div>
                    </div>
                    <div class="print-hide flex gap-3">
                        <button onclick="openCreateModal()" class="btn-gold inline-flex items-center gap-2">
                            <i data-lucide="plus" class="h-4 w-4"></i> New Journal Entry
                        </button>
                    </div>
                </div>
            </div>

            <div class="print-hide flex gap-2 flex-wrap">
                <button class="tab-btn tab-active rounded-2xl px-5 py-2.5 text-sm font-semibold transition-all duration-300 border border-yellow-500/20 bg-black/35" data-tab="balance_sheet" onclick="switchTab('balance_sheet', this)">
                    <span class="inline-flex items-center gap-2"><i data-lucide="scale" class="h-4 w-4"></i>Balance Sheet</span>
                </button>
                <button class="tab-btn rounded-2xl px-5 py-2.5 text-sm font-semibold transition-all duration-300 border border-yellow-500/20 bg-black/35 text-gray-300" data-tab="journal" onclick="switchTab('journal', this)">
                    <span class="inline-flex items-center gap-2"><i data-lucide="book-open" class="h-4 w-4"></i>Journal Entries</span>
                </button>
            </div>

            <!-- Balance Sheet Tab -->
            <div id="tab_balance_sheet">
                <div id="bsSummaryGrid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6"></div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="glass-card overflow-hidden">
                        <div class="p-5 border-b border-yellow-500/10">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                <i data-lucide="trending-up" class="h-5 w-5 text-emerald-400"></i> Assets
                            </h3>
                        </div>
                        <div id="bsAssetsBody"></div>
                        <div id="bsAssetsTotal" class="bs-total-row"></div>
                    </div>

                    <div class="space-y-6">
                        <div class="glass-card overflow-hidden">
                            <div class="p-5 border-b border-yellow-500/10">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <i data-lucide="arrow-down-right" class="h-5 w-5 text-red-400"></i> Liabilities
                                </h3>
                            </div>
                            <div id="bsLiabilitiesBody"></div>
                            <div id="bsLiabilitiesTotal" class="bs-total-row"></div>
                        </div>

                        <div class="glass-card overflow-hidden">
                            <div class="p-5 border-b border-yellow-500/10">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <i data-lucide="landmark" class="h-5 w-5 text-yellow-400"></i> Equity
                                </h3>
                            </div>
                            <div id="bsEquityBody"></div>
                            <div id="bsEquityTotal" class="bs-total-row"></div>
                        </div>
                    </div>
                </div>

                <div id="bsEquationCard" class="glass-card p-5 mt-6"></div>
            </div>

            <!-- Journal Entries Tab -->
            <div id="tab_journal" style="display:none;">
                <div class="glass-card p-5 mb-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex gap-3 flex-wrap">
                            <input type="text" id="jeSearch" placeholder="Search entries..." class="form-input max-w-xs" oninput="debounceLoadEntries()">
                            <select id="jeStatusFilter" class="form-input max-w-[160px]" onchange="loadJournalEntries()">
                                <option value="">All Status</option>
                                <option value="posted">Posted</option>
                                <option value="draft">Draft</option>
                                <option value="void">Void</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="glass-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="je-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Memo</th>
                                    <th>Reference</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="jeTableBody">
                                <tr><td colspan="9" class="text-center py-8 text-gray-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="jePagination" class="flex items-center justify-between p-4 border-t border-white/5"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Create Journal Entry Modal -->
    <div id="createModal" class="modal-overlay" onclick="if(event.target===this)closeCreateModal()">
        <div class="modal-content">
            <div class="p-6 border-b border-yellow-500/15">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">New Journal Entry</h2>
                    <button onclick="closeCreateModal()" class="w-8 h-8 flex items-center justify-center rounded-lg border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition-all">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <form id="jeForm" onsubmit="submitJournalEntry(event)" class="p-6 space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Entry Date *</label>
                        <input type="date" id="jeDate" class="form-input" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Status</label>
                        <select id="jeStatus" class="form-input">
                            <option value="posted">Posted</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Reference Type</label>
                        <input type="text" id="jeRefType" class="form-input" placeholder="e.g., manual, adjustment, purchase">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Memo</label>
                        <input type="text" id="jeMemo" class="form-input" placeholder="Description of this entry">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-400">Journal Lines</label>
                        <button type="button" onclick="addLine()" class="btn-outline btn-sm inline-flex items-center gap-1.5">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i> Add Line
                        </button>
                    </div>

                    <div class="hidden md:grid line-row text-[10px] font-bold uppercase tracking-wider text-gray-500 px-1 mb-1">
                        <span>Account</span><span>Description</span><span>Debit</span><span>Credit</span><span></span>
                    </div>

                    <div id="jeLinesContainer" class="space-y-2"></div>

                    <div class="debit-credit-summary mt-4">
                        <div class="dc-block">
                            <label>Total Debits</label>
                            <div id="totalDebits" class="value">₱0.00</div>
                        </div>
                        <div class="dc-block">
                            <label>Total Credits</label>
                            <div id="totalCredits" class="value">₱0.00</div>
                        </div>
                        <div class="dc-block">
                            <label>Difference</label>
                            <div id="totalDiff" class="value balanced">₱0.00</div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" onclick="closeCreateModal()" class="btn-outline">Cancel</button>
                    <button type="submit" id="jeSubmitBtn" class="btn-gold inline-flex items-center gap-2">
                        <i data-lucide="check" class="h-4 w-4"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Journal Entry Modal -->
    <div id="viewModal" class="modal-overlay" onclick="if(event.target===this)closeViewModal()">
        <div class="modal-content">
            <div class="p-6 border-b border-yellow-500/15">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">Journal Entry Details</h2>
                    <button onclick="closeViewModal()" class="w-8 h-8 flex items-center justify-center rounded-lg border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition-all">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>
            <div id="viewModalBody" class="p-6"></div>
        </div>
    </div>

    <script>
    const API = '../backend/journal_entries.php';
    let accountsCache = [];
    let jeCurrentPage = 1;
    let debounceTimer = null;

    function fmt(v) {
        return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(Number(v || 0));
    }

    function switchTab(tab, btn) {
        document.getElementById('tab_balance_sheet').style.display = tab === 'balance_sheet' ? '' : 'none';
        document.getElementById('tab_journal').style.display = tab === 'journal' ? '' : 'none';
        document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('tab-active'); b.classList.add('text-gray-300'); });
        btn.classList.add('tab-active');
        btn.classList.remove('text-gray-300');
        if (tab === 'journal') loadJournalEntries();
        if (tab === 'balance_sheet') loadBalanceSheet();
    }

    // ── Balance Sheet ──
    async function loadBalanceSheet() {
        try {
            const res = await fetch(`${API}?action=balance_sheet`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            renderBalanceSheet(json.data);
        } catch (e) {
            console.error(e);
        }
    }

    function renderBsRows(containerId, items) {
        const el = document.getElementById(containerId);
        el.innerHTML = '';
        Object.entries(items).forEach(([name, amount]) => {
            el.innerHTML += `<div class="bs-row"><span class="bs-row-name">${name}</span><span class="bs-row-amount">${fmt(amount)}</span></div>`;
        });
    }

    function renderBalanceSheet(data) {
        renderBsRows('bsAssetsBody', data.assets);
        renderBsRows('bsLiabilitiesBody', data.liabilities);
        renderBsRows('bsEquityBody', data.equity);

        const t = data.totals;
        document.getElementById('bsAssetsTotal').innerHTML = `<span class="bs-row-name">Total Assets</span><span class="bs-row-amount">${fmt(t.total_assets)}</span>`;
        document.getElementById('bsLiabilitiesTotal').innerHTML = `<span class="bs-row-name">Total Liabilities</span><span class="bs-row-amount">${fmt(t.total_liabilities)}</span>`;
        document.getElementById('bsEquityTotal').innerHTML = `<span class="bs-row-name">Total Equity</span><span class="bs-row-amount">${fmt(t.total_equity)}</span>`;

        const eqCard = document.getElementById('bsEquationCard');
        const validClass = t.balanced ? 'border-emerald-500/30 bg-emerald-500/8' : 'border-red-500/30 bg-red-500/8';
        const validText = t.balanced ? 'text-emerald-300' : 'text-red-300';
        const icon = t.balanced ? 'check-circle' : 'alert-triangle';
        eqCard.className = `glass-card p-5 mt-6 border ${validClass}`;
        eqCard.innerHTML = `
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <i data-lucide="${icon}" class="h-6 w-6 ${validText}"></i>
                    <div>
                        <p class="text-sm font-semibold ${validText}">${t.balanced ? 'Accounting Equation Balanced' : 'Equation Imbalance Detected'}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Assets = Liabilities + Equity</p>
                    </div>
                </div>
                <div class="flex gap-6 text-sm">
                    <div><span class="text-gray-400">Assets:</span> <span class="font-semibold text-white">${fmt(t.total_assets)}</span></div>
                    <div><span class="text-gray-400">L + E:</span> <span class="font-semibold text-white">${fmt(t.total_liabilities_equity)}</span></div>
                    <div><span class="text-gray-400">Variance:</span> <span class="font-semibold ${validText}">${fmt(t.variance)}</span></div>
                </div>
            </div>`;
        lucide.createIcons();

        const grid = document.getElementById('bsSummaryGrid');
        const cards = [
            { label: 'Total Assets', value: fmt(t.total_assets), icon: 'trending-up', color: 'emerald' },
            { label: 'Total Liabilities', value: fmt(t.total_liabilities), icon: 'arrow-down-right', color: 'red' },
            { label: 'Total Equity', value: fmt(t.total_equity), icon: 'landmark', color: 'yellow' },
            { label: 'Equation Status', value: t.balanced ? 'Balanced' : 'Imbalanced', icon: t.balanced ? 'check-circle' : 'alert-triangle', color: t.balanced ? 'emerald' : 'red' },
        ];
        grid.innerHTML = cards.map(c => `
            <article class="glass-card group p-5 transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-400">${c.label}</p>
                        <h3 class="mt-3 text-2xl font-bold text-white break-words">${c.value}</h3>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-${c.color}-500/25 to-${c.color}-600/15 text-${c.color}-300 ring-1 ring-${c.color}-500/20">
                        <i data-lucide="${c.icon}" class="h-5 w-5"></i>
                    </div>
                </div>
            </article>
        `).join('');
        lucide.createIcons();
    }

    // ── Journal Entries List ──
    function debounceLoadEntries() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { jeCurrentPage = 1; loadJournalEntries(); }, 350);
    }

    async function loadJournalEntries() {
        const search = document.getElementById('jeSearch').value;
        const status = document.getElementById('jeStatusFilter').value;
        const params = new URLSearchParams({ action: 'list', page: jeCurrentPage, search, status });
        try {
            const res = await fetch(`${API}?${params}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            renderJournalTable(json.data);
        } catch (e) {
            document.getElementById('jeTableBody').innerHTML = `<tr><td colspan="9" class="text-center py-8 text-red-400">${e.message}</td></tr>`;
        }
    }

    function badgeClass(status) {
        return status === 'posted' ? 'badge-posted' : status === 'draft' ? 'badge-draft' : 'badge-void';
    }

    function renderJournalTable(data) {
        const tbody = document.getElementById('jeTableBody');
        if (!data.entries.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-gray-500">No journal entries found</td></tr>';
            document.getElementById('jePagination').innerHTML = '';
            return;
        }
        tbody.innerHTML = data.entries.map(e => `
            <tr>
                <td class="font-mono text-xs text-yellow-300/70">#${e.entry_id}</td>
                <td class="whitespace-nowrap">${e.entry_date}</td>
                <td class="max-w-[200px] truncate">${e.memo || '<span class="text-gray-600">—</span>'}</td>
                <td>${e.reference_type || '<span class="text-gray-600">—</span>'}</td>
                <td class="font-mono">${fmt(e.total_debit)}</td>
                <td class="font-mono">${fmt(e.total_credit)}</td>
                <td><span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold ${badgeClass(e.status)}">${e.status}</span></td>
                <td>${e.created_by_name || '—'}</td>
                <td>
                    <div class="flex gap-2">
                        <button onclick="viewEntry(${e.entry_id})" class="btn-outline btn-sm">View</button>
                        ${e.status !== 'void' ? `<button onclick="voidEntry(${e.entry_id})" class="btn-red-outline">Void</button>` : ''}
                    </div>
                </td>
            </tr>
        `).join('');

        const pag = document.getElementById('jePagination');
        pag.innerHTML = `
            <span class="text-sm text-gray-400">Page ${data.page} of ${data.pages} (${data.total} entries)</span>
            <div class="flex gap-2">
                <button onclick="goPage(${data.page - 1})" class="btn-outline btn-sm" ${data.page <= 1 ? 'disabled style="opacity:0.4;pointer-events:none;"' : ''}>Prev</button>
                <button onclick="goPage(${data.page + 1})" class="btn-outline btn-sm" ${data.page >= data.pages ? 'disabled style="opacity:0.4;pointer-events:none;"' : ''}>Next</button>
            </div>`;
    }

    function goPage(p) { jeCurrentPage = p; loadJournalEntries(); }

    // ── View Entry ──
    async function viewEntry(id) {
        try {
            const res = await fetch(`${API}?action=view&id=${id}`);
            const json = await res.json();
            if (!json.success || !json.data) throw new Error(json.message || 'Entry not found');
            renderViewModal(json.data);
        } catch (e) {
            Swal.fire({ title: 'Error', text: e.message, icon: 'error', confirmButtonColor: '#D4AF37' });
        }
    }

    function renderViewModal(entry) {
        const body = document.getElementById('viewModalBody');
        body.innerHTML = `
            <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                <div><span class="text-gray-400">Entry ID:</span> <span class="font-semibold text-yellow-300">#${entry.entry_id}</span></div>
                <div><span class="text-gray-400">Date:</span> <span class="font-semibold">${entry.entry_date}</span></div>
                <div><span class="text-gray-400">Status:</span> <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold ${badgeClass(entry.status)}">${entry.status}</span></div>
                <div><span class="text-gray-400">Created By:</span> <span class="font-semibold">${entry.created_by_name || '—'}</span></div>
                <div class="col-span-2"><span class="text-gray-400">Memo:</span> <span class="font-semibold">${entry.memo || '—'}</span></div>
                ${entry.reference_type ? `<div class="col-span-2"><span class="text-gray-400">Reference:</span> <span class="font-semibold">${entry.reference_type}</span></div>` : ''}
            </div>
            <table class="je-table">
                <thead><tr><th>Account</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead>
                <tbody>
                    ${entry.lines.map(l => `
                        <tr>
                            <td><span class="text-yellow-300/70 font-mono text-xs">${l.account_code}</span> ${l.account_name}</td>
                            <td>${l.description || '—'}</td>
                            <td class="font-mono">${l.debit_amount > 0 ? fmt(l.debit_amount) : ''}</td>
                            <td class="font-mono">${l.credit_amount > 0 ? fmt(l.credit_amount) : ''}</td>
                        </tr>
                    `).join('')}
                    <tr class="border-t-2 border-yellow-500/20">
                        <td colspan="2" class="font-bold text-yellow-300">Totals</td>
                        <td class="font-mono font-bold text-yellow-300">${fmt(entry.lines.reduce((s, l) => s + Number(l.debit_amount), 0))}</td>
                        <td class="font-mono font-bold text-yellow-300">${fmt(entry.lines.reduce((s, l) => s + Number(l.credit_amount), 0))}</td>
                    </tr>
                </tbody>
            </table>`;
        document.getElementById('viewModal').classList.add('active');
        lucide.createIcons();
    }

    function closeViewModal() { document.getElementById('viewModal').classList.remove('active'); }

    // ── Void Entry ──
    async function voidEntry(id) {
        const result = await Swal.fire({
            title: 'Void this entry?',
            text: 'This will mark the journal entry as void. The entry will remain visible but will no longer affect account balances.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#D4AF37',
            cancelButtonColor: '#555',
            confirmButtonText: 'Yes, void it'
        });
        if (!result.isConfirmed) return;
        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'void', entry_id: id })
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            Swal.fire({ title: 'Voided', text: json.message, icon: 'success', confirmButtonColor: '#D4AF37' });
            loadJournalEntries();
            loadBalanceSheet();
        } catch (e) {
            Swal.fire({ title: 'Error', text: e.message, icon: 'error', confirmButtonColor: '#D4AF37' });
        }
    }

    // ── Create Journal Entry ──
    async function loadAccounts() {
        if (accountsCache.length) return;
        const res = await fetch(`${API}?action=accounts`);
        const json = await res.json();
        if (json.success) accountsCache = json.data;
    }

    function buildAccountOptions() {
        const grouped = {};
        accountsCache.forEach(a => {
            const type = a.account_type.charAt(0).toUpperCase() + a.account_type.slice(1);
            if (!grouped[type]) grouped[type] = [];
            grouped[type].push(a);
        });
        let html = '<option value="">Select Account</option>';
        Object.entries(grouped).forEach(([type, accs]) => {
            html += `<optgroup label="${type}">`;
            accs.forEach(a => { html += `<option value="${a.account_id}">${a.account_code} - ${a.account_name}</option>`; });
            html += '</optgroup>';
        });
        return html;
    }

    let lineIndex = 0;

    function addLine() {
        const container = document.getElementById('jeLinesContainer');
        const optionsHtml = buildAccountOptions();
        const div = document.createElement('div');
        div.className = 'line-row';
        div.dataset.idx = lineIndex;
        div.innerHTML = `
            <select class="form-input line-account" required>${optionsHtml}</select>
            <input type="text" class="form-input line-desc" placeholder="Line description">
            <input type="number" step="0.01" min="0" class="form-input line-debit" placeholder="0.00" oninput="onDebitCreditInput(this, 'debit')">
            <input type="number" step="0.01" min="0" class="form-input line-credit" placeholder="0.00" oninput="onDebitCreditInput(this, 'credit')">
            <button type="button" class="remove-line-btn" onclick="removeLine(this)"><i data-lucide="x" class="h-3.5 w-3.5"></i></button>`;
        container.appendChild(div);
        lineIndex++;
        lucide.createIcons();
        recalcTotals();
    }

    function removeLine(btn) {
        btn.closest('.line-row').remove();
        recalcTotals();
    }

    function onDebitCreditInput(input, type) {
        const row = input.closest('.line-row');
        const val = parseFloat(input.value) || 0;
        if (val > 0) {
            const other = row.querySelector(type === 'debit' ? '.line-credit' : '.line-debit');
            other.value = '';
        }
        recalcTotals();
    }

    function recalcTotals() {
        let debits = 0, credits = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            debits += parseFloat(row.querySelector('.line-debit')?.value || 0);
            credits += parseFloat(row.querySelector('.line-credit')?.value || 0);
        });
        document.getElementById('totalDebits').textContent = fmt(debits);
        document.getElementById('totalCredits').textContent = fmt(credits);
        const diff = Math.abs(debits - credits);
        const diffEl = document.getElementById('totalDiff');
        diffEl.textContent = fmt(diff);
        diffEl.className = 'value ' + (diff < 0.01 ? 'balanced' : 'unbalanced');
    }

    async function openCreateModal() {
        await loadAccounts();
        document.getElementById('jeDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('jeStatus').value = 'posted';
        document.getElementById('jeRefType').value = '';
        document.getElementById('jeMemo').value = '';
        document.getElementById('jeLinesContainer').innerHTML = '';
        lineIndex = 0;
        addLine();
        addLine();
        document.getElementById('createModal').classList.add('active');
        lucide.createIcons();
    }

    function closeCreateModal() { document.getElementById('createModal').classList.remove('active'); }

    async function submitJournalEntry(e) {
        e.preventDefault();
        const btn = document.getElementById('jeSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const lines = [];
        document.querySelectorAll('#jeLinesContainer .line-row').forEach(row => {
            lines.push({
                account_id: row.querySelector('.line-account').value,
                description: row.querySelector('.line-desc').value,
                debit: parseFloat(row.querySelector('.line-debit').value || 0),
                credit: parseFloat(row.querySelector('.line-credit').value || 0),
            });
        });

        const payload = {
            action: 'create',
            entry_date: document.getElementById('jeDate').value,
            status: document.getElementById('jeStatus').value,
            reference_type: document.getElementById('jeRefType').value,
            memo: document.getElementById('jeMemo').value,
            lines
        };

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            closeCreateModal();
            Swal.fire({
                title: 'Success',
                text: `Journal entry #${json.entry_id} created successfully`,
                icon: 'success',
                confirmButtonColor: '#D4AF37'
            });
            loadBalanceSheet();
            loadJournalEntries();
        } catch (e) {
            Swal.fire({ title: 'Error', text: e.message, icon: 'error', confirmButtonColor: '#D4AF37' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check" class="h-4 w-4"></i> Save Entry';
            lucide.createIcons();
        }
    }

    // ── Init ──
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        loadBalanceSheet();
        loadAccounts();
    });
    </script>
</body>
</html>
