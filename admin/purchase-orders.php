<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zoryn · Purchase Orders</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/zoryn-theme.css">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
<script src="js/active-page.js"></script>
<style>
    body { font-family:'Poppins',sans-serif; background:radial-gradient(circle at top,rgba(212,175,55,.12),transparent 28%),linear-gradient(180deg,#050505 0%,#0a0a0a 45%,#111827 100%); color:#fff; min-height:100vh; }
    .main-content { margin-left:260px; padding:24px; transition:margin-left .3s ease; }
    .main-content.expanded { margin-left:0; }
    .glass { background:rgba(17,24,39,.78); border:1px solid rgba(212,175,55,.18); border-radius:24px; box-shadow:0 20px 60px rgba(0,0,0,.35); backdrop-filter:blur(18px); }
    .gold-btn { background:linear-gradient(135deg,#f4d26b,#c99b2a); color:#050505; font-weight:600; border-radius:14px; transition:transform .25s ease, box-shadow .25s ease; }
    .gold-btn:hover { transform:translateY(-1px); box-shadow:0 10px 24px rgba(212,175,55,.25); }
    .po-table { width:100%; border-collapse:collapse; }
    .po-table thead th { position:sticky; top:0; background:rgba(10,10,10,.95); color:#f5d76e; font-size:12px; letter-spacing:.08em; text-transform:uppercase; padding:14px 16px; border-bottom:1px solid rgba(212,175,55,.16); text-align:left; }
    .po-table tbody td { padding:14px 16px; border-bottom:1px solid rgba(255,255,255,.06); color:#d1d5db; vertical-align:top; }
    .po-table tbody tr:hover td { background:rgba(212,175,55,.05); color:#fff; }
    .badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:9999px; font-size:11px; font-weight:600; }
    .badge-received { background:rgba(16,185,129,.12); color:#34d399; border:1px solid rgba(16,185,129,.25); }
    .badge-draft    { background:rgba(245,158,11,.12); color:#fbbf24; border:1px solid rgba(245,158,11,.25); }
    .badge-cancel   { background:rgba(239,68,68,.12); color:#f87171; border:1px solid rgba(239,68,68,.25); }
    .po-input { width:100%; border-radius:12px; border:1px solid rgba(212,175,55,.25); background:rgba(0,0,0,.45); color:#fff; padding:10px 14px; font-size:14px; outline:none; transition:border-color .2s ease; }
    .po-input:focus { border-color:#f4d26b; }
    .po-input::placeholder { color:rgba(255,255,255,.35); }
    @media (max-width:1024px) { .main-content { margin-left:0; padding:16px; } }
</style>
</head>
<body>
<?php include("../navigation/admin-navbar.php"); ?>
<?php include("../navigation/admin-sidebar.php"); ?>

<main class="main-content">
    <section class="mx-auto max-w-7xl space-y-6">
        <div class="glass p-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <span class="inline-flex items-center rounded-full border border-yellow-500/20 bg-yellow-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.32em] text-yellow-300">Back Office</span>
                <h1 class="mt-3 text-3xl font-bold tracking-tight md:text-4xl">Purchase Orders</h1>
                <p class="mt-1 max-w-2xl text-sm text-gray-400">Record ingredient purchases. Saving a PO auto-increases stock, writes an expense, and updates unit costs.</p>
            </div>
            <button id="newPoBtn" class="gold-btn inline-flex items-center gap-2 px-5 py-3 text-sm shadow-lg">
                <i data-lucide="plus" class="h-4 w-4"></i> New Purchase Order
            </button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="glass p-5"><p class="text-xs uppercase tracking-[0.22em] text-yellow-300/80">Total POs</p><h2 class="mt-3 text-3xl font-bold" id="statTotalPO">0</h2></article>
            <article class="glass p-5"><p class="text-xs uppercase tracking-[0.22em] text-yellow-300/80">Received</p><h2 class="mt-3 text-3xl font-bold text-emerald-300" id="statReceived">0</h2></article>
            <article class="glass p-5"><p class="text-xs uppercase tracking-[0.22em] text-yellow-300/80">Spend (period)</p><h2 class="mt-3 text-3xl font-bold text-yellow-300" id="statSpend">₱0.00</h2></article>
            <article class="glass p-5"><p class="text-xs uppercase tracking-[0.22em] text-yellow-300/80">Ingredients Purchased</p><h2 class="mt-3 text-3xl font-bold" id="statQty">0</h2></article>
        </div>

        <div class="glass p-6">
            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-xl font-semibold">Purchase Order History</h3>
                    <p class="text-sm text-gray-400">All orders are posted to finance automatically.</p>
                </div>
                <input id="searchInput" class="po-input max-w-xs" placeholder="Search PO # or supplier…">
            </div>
            <div class="overflow-x-auto">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th>PO #</th><th>Date</th><th>Supplier</th><th>Items</th><th class="text-right">Total</th><th>Status</th><th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="poTableBody"></tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<!-- Create PO Modal -->
<div id="poModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-black/70 backdrop-blur-sm p-4">
    <div class="glass w-full max-w-4xl max-h-[92vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Record New Purchase Order</h2>
            <button class="text-gray-400 hover:text-white" onclick="closePoModal()"><i data-lucide="x" class="h-6 w-6"></i></button>
        </div>
        <form id="poForm" class="mt-5 space-y-5">
            <div class="grid gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">PO Date</span>
                    <input type="date" name="po_date" class="po-input" required>
                </label>
                <div class="block md:col-span-2">
                    <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Supplier</span>
                        <button type="button" id="openSupplierModalBtn" class="rounded-lg border border-yellow-500/35 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-yellow-200 hover:bg-yellow-500/10">
                            + Add supplier
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <select name="supplier_id" id="supplierSelect" class="po-input min-w-0 flex-1"><option value="">— Select supplier —</option></select>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-yellow-500/20 bg-black/30 p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-yellow-300/80">Items</h3>
                    <button type="button" id="addLineBtn" class="rounded-xl border border-yellow-500/30 px-3 py-1.5 text-xs font-semibold text-yellow-200 hover:bg-yellow-500/10">
                        <i data-lucide="plus" class="inline h-3.5 w-3.5"></i> Add line
                    </button>
                </div>
                <div id="poItemsBody" class="space-y-2"></div>
                <div class="mt-4 flex justify-end text-lg font-semibold text-yellow-300">Total: <span class="ml-2" id="poTotalPreview">₱0.00</span></div>
            </div>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Notes</span>
                <textarea name="notes" rows="2" class="po-input" placeholder="Optional internal notes"></textarea>
            </label>

            <div class="flex justify-end gap-3 pt-3">
                <button type="button" class="rounded-xl border border-yellow-500/25 px-5 py-2.5 text-sm font-semibold text-gray-200 hover:bg-white/5" onclick="closePoModal()">Cancel</button>
                <button type="submit" class="gold-btn px-6 py-2.5 text-sm">Save PO &amp; Receive Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Add supplier modal -->
<div id="supplierModal" class="fixed inset-0 z-[1100] hidden items-center justify-center bg-black/75 backdrop-blur-sm p-4">
    <div class="glass w-full max-w-lg max-h-[92vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Add supplier</h2>
            <button type="button" class="text-gray-400 hover:text-white" onclick="closeSupplierModal()" aria-label="Close"><i data-lucide="x" class="h-6 w-6"></i></button>
        </div>
        <p class="mt-2 text-sm text-gray-400">Creates a row in suppliers and selects it for this purchase order.</p>
        <form id="supplierForm" class="mt-5 space-y-4">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Supplier name *</span>
                <input type="text" name="supplier_name" class="po-input" required maxlength="120" placeholder="e.g. Fresh Farms Co.">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Contact person</span>
                <input type="text" name="contact_person" class="po-input" maxlength="100" placeholder="Optional">
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Phone</span>
                    <input type="text" name="phone" class="po-input" maxlength="30" placeholder="Optional">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Email</span>
                    <input type="email" name="email" class="po-input" maxlength="120" placeholder="Optional">
                </label>
            </div>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Address</span>
                <textarea name="address" rows="2" class="po-input" placeholder="Optional"></textarea>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-yellow-300/80">Status</span>
                <select name="status" class="po-input">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="rounded-xl border border-yellow-500/25 px-5 py-2.5 text-sm font-semibold text-gray-200 hover:bg-white/5" onclick="closeSupplierModal()">Cancel</button>
                <button type="submit" class="gold-btn px-6 py-2.5 text-sm">Save supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
let supplierList = [];
let ingredientList = [];
let poCache = [];

const formatCurrency = v => new Intl.NumberFormat('en-PH', { style:'currency', currency:'PHP' }).format(Number(v||0));
const statusBadge = s => `<span class="badge badge-${s === 'received' ? 'received' : (s === 'draft' ? 'draft' : 'cancel')}"><i data-lucide="${s==='received'?'check-circle':(s==='draft'?'clock':'x-circle')}" class="h-3 w-3"></i>${s}</span>`;

async function fetchOptions() {
    const res = await fetch('../backend/purchase_order_manager.php?action=options');
    const j = await res.json();
    if (!j.success) throw new Error(j.error);
    supplierList   = j.data.suppliers || [];
    ingredientList = j.data.ingredients || [];
    const sel = document.getElementById('supplierSelect');
    const prev = sel.value;
    sel.innerHTML = '<option value="">— Select supplier —</option>' + supplierList.map(s => `<option value="${s.supplier_id}">${s.supplier_name}</option>`).join('');
    if (prev && [...sel.options].some(o => o.value === prev)) sel.value = prev;
}

function openSupplierModal() {
    const m = document.getElementById('supplierModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.getElementById('supplierForm').reset();
    document.querySelector('#supplierForm select[name="status"]').value = 'active';
    lucide.createIcons();
}
function closeSupplierModal() {
    const m = document.getElementById('supplierModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

document.getElementById('openSupplierModalBtn').addEventListener('click', openSupplierModal);

document.getElementById('supplierModal').addEventListener('click', function(e) {
    if (e.target === this) closeSupplierModal();
});

document.getElementById('supplierForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = {
        supplier_name: (fd.get('supplier_name') || '').trim(),
        contact_person: (fd.get('contact_person') || '').trim(),
        phone: (fd.get('phone') || '').trim(),
        email: (fd.get('email') || '').trim(),
        address: (fd.get('address') || '').trim(),
        status: fd.get('status') || 'active'
    };
    const res = await fetch('../backend/purchase_order_manager.php?action=create_supplier', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const j = await res.json();
    if (!j.success) {
        return Swal.fire({ title: 'Could not save', text: j.error || 'Error', icon: 'error', confirmButtonColor: '#D4AF37' });
    }
    await fetchOptions();
    document.getElementById('supplierSelect').value = String(j.supplier_id);
    closeSupplierModal();
    Swal.fire({ title: 'Supplier added', text: j.supplier_name + ' is selected for this PO.', icon: 'success', confirmButtonColor: '#D4AF37' });
});

async function loadPOs() {
    const res = await fetch('../backend/purchase_order_manager.php?action=list');
    const j = await res.json();
    if (!j.success) { Swal.fire({ title:'Error', text:j.error, icon:'error', confirmButtonColor:'#D4AF37' }); return; }
    poCache = j.data;
    renderStats();
    renderTable();
}

function renderStats() {
    document.getElementById('statTotalPO').textContent = poCache.length;
    document.getElementById('statReceived').textContent = poCache.filter(p => p.status === 'received').length;
    const spend = poCache.filter(p => p.status !== 'cancelled').reduce((s,p) => s + Number(p.total_amount||0), 0);
    document.getElementById('statSpend').textContent = formatCurrency(spend);
    const qty = poCache.filter(p => p.status !== 'cancelled').reduce((s,p) => s + Number(p.total_qty||0), 0);
    document.getElementById('statQty').textContent = qty.toFixed(2);
}

function renderTable() {
    const body = document.getElementById('poTableBody');
    const q = (document.getElementById('searchInput').value || '').toLowerCase();
    const rows = poCache.filter(p => !q || `${p.po_number} ${p.supplier_name}`.toLowerCase().includes(q));
    if (!rows.length) { body.innerHTML = '<tr><td colspan="7" class="text-center text-gray-500 py-8">No purchase orders yet</td></tr>'; return; }
    body.innerHTML = rows.map(p => `
        <tr>
            <td class="font-semibold text-yellow-300">${p.po_number}</td>
            <td>${p.po_date}</td>
            <td>${p.supplier_name}</td>
            <td>${p.item_count}</td>
            <td class="text-right">${formatCurrency(p.total_amount)}</td>
            <td>${statusBadge(p.status)}</td>
            <td class="text-right">
                <button class="rounded-xl border border-yellow-500/25 px-3 py-1.5 text-xs font-semibold text-yellow-200 hover:bg-yellow-500/10" onclick="viewPO(${p.po_id})"><i data-lucide="eye" class="inline h-3.5 w-3.5"></i></button>
                ${p.status !== 'cancelled' ? `<button class="rounded-xl border border-red-500/25 px-3 py-1.5 text-xs font-semibold text-red-300 hover:bg-red-500/10 ml-1" onclick="cancelPO(${p.po_id})"><i data-lucide="x" class="inline h-3.5 w-3.5"></i></button>` : ''}
            </td>
        </tr>`).join('');
    lucide.createIcons();
}

async function viewPO(id) {
    const res = await fetch('../backend/purchase_order_manager.php?action=detail&po_id=' + id);
    const j = await res.json();
    if (!j.success) return Swal.fire({ title:'Error', text:j.error, icon:'error', confirmButtonColor:'#D4AF37' });
    const p = j.data;
    const rows = p.items.map(i => `<tr><td style="padding:6px 8px">${i.ingredient_name}</td><td style="text-align:right;padding:6px 8px">${Number(i.quantity).toFixed(2)} ${i.unit}</td><td style="text-align:right;padding:6px 8px">${formatCurrency(i.unit_cost)}</td><td style="text-align:right;padding:6px 8px">${formatCurrency(i.subtotal)}</td></tr>`).join('');
    Swal.fire({
        title: p.po_number,
        html: `
            <div style="text-align:left;font-size:13px;color:#d1d5db;">
                <div style="margin-bottom:10px;"><strong>Supplier:</strong> ${p.supplier_name}<br><strong>Date:</strong> ${p.po_date}<br><strong>Status:</strong> ${p.status}</div>
                <table style="width:100%;border-collapse:collapse;"><thead><tr style="color:#f5d76e;font-size:11px;text-transform:uppercase;"><th style="text-align:left;padding:6px 8px;">Ingredient</th><th style="text-align:right;padding:6px 8px;">Qty</th><th style="text-align:right;padding:6px 8px;">Unit Cost</th><th style="text-align:right;padding:6px 8px;">Subtotal</th></tr></thead><tbody>${rows}</tbody></table>
                <div style="text-align:right;margin-top:12px;font-size:15px;color:#f5d76e;font-weight:700;">Total: ${formatCurrency(p.total_amount)}</div>
                ${p.notes ? `<div style="margin-top:12px;padding:10px;border:1px solid rgba(212,175,55,.15);border-radius:10px;"><strong>Notes:</strong><br>${p.notes}</div>` : ''}
            </div>`,
        confirmButtonColor: '#D4AF37', width: 680, background: '#111827', color: '#fff'
    });
}

function cancelPO(id) {
    Swal.fire({ title:'Cancel PO?', text:'Marks the order cancelled. Stock/expense already posted will not be reversed automatically.', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc2626', cancelButtonColor:'#374151' })
        .then(async r => {
            if (!r.isConfirmed) return;
            const res = await fetch('../backend/purchase_order_manager.php?action=cancel&po_id=' + id);
            const j = await res.json();
            if (j.success) { Swal.fire({ title:'Cancelled', icon:'success', confirmButtonColor:'#D4AF37' }); loadPOs(); }
        });
}

function openPoModal() {
    document.getElementById('poModal').classList.remove('hidden');
    document.getElementById('poModal').classList.add('flex');
    document.querySelector('input[name="po_date"]').value = new Date().toISOString().split('T')[0];
    document.getElementById('poItemsBody').innerHTML = '';
    addLine();
    recomputeTotal();
}
function closePoModal() {
    document.getElementById('poModal').classList.add('hidden');
    document.getElementById('poModal').classList.remove('flex');
    document.getElementById('poForm').reset();
}

function addLine() {
    const row = document.createElement('div');
    row.className = 'grid gap-2 md:grid-cols-12 items-center po-line';
    row.innerHTML = `
        <select class="po-input md:col-span-5 line-ingredient">
            <option value="">— Ingredient —</option>
            ${ingredientList.map(i => `<option value="${i.ingredient_id}" data-unit="${i.unit}" data-cost="${i.default_unit_cost}">${i.ingredient_name} (stock ${Number(i.stock).toFixed(2)} ${i.unit})</option>`).join('')}
        </select>
        <input type="number" step="0.01" min="0" placeholder="Qty" class="po-input md:col-span-2 line-qty">
        <input type="text"   class="po-input md:col-span-2 line-unit" placeholder="Unit">
        <input type="number" step="0.01" min="0" placeholder="Unit ₱" class="po-input md:col-span-2 line-cost">
        <button type="button" class="md:col-span-1 rounded-xl border border-red-500/30 px-2 py-2 text-red-300 hover:bg-red-500/10"><i data-lucide="trash-2" class="h-4 w-4"></i></button>`;
    document.getElementById('poItemsBody').appendChild(row);

    const sel = row.querySelector('.line-ingredient');
    const unitEl = row.querySelector('.line-unit');
    const costEl = row.querySelector('.line-cost');
    sel.addEventListener('change', () => {
        const opt = sel.selectedOptions[0];
        if (opt && opt.dataset.unit) unitEl.value = opt.dataset.unit;
        if (opt && opt.dataset.cost && !costEl.value) costEl.value = Number(opt.dataset.cost).toFixed(2);
        recomputeTotal();
    });
    row.querySelector('.line-qty').addEventListener('input', recomputeTotal);
    row.querySelector('.line-cost').addEventListener('input', recomputeTotal);
    row.querySelector('button').addEventListener('click', () => { row.remove(); recomputeTotal(); });
    lucide.createIcons();
}

function recomputeTotal() {
    let total = 0;
    document.querySelectorAll('.po-line').forEach(r => {
        total += (parseFloat(r.querySelector('.line-qty').value || 0)) * (parseFloat(r.querySelector('.line-cost').value || 0));
    });
    document.getElementById('poTotalPreview').textContent = formatCurrency(total);
}

document.getElementById('newPoBtn').addEventListener('click', openPoModal);
document.getElementById('addLineBtn').addEventListener('click', addLine);
document.getElementById('searchInput').addEventListener('input', renderTable);

document.getElementById('poForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const items = [];
    document.querySelectorAll('.po-line').forEach(r => {
        const id = r.querySelector('.line-ingredient').value;
        const qty = parseFloat(r.querySelector('.line-qty').value || 0);
        const unit = r.querySelector('.line-unit').value.trim();
        const cost = parseFloat(r.querySelector('.line-cost').value || 0);
        if (id && qty > 0) items.push({ ingredient_id: Number(id), quantity: qty, unit, unit_cost: cost });
    });
    if (!items.length) return Swal.fire({ title:'Add items', text:'At least one item is required.', icon:'warning', confirmButtonColor:'#D4AF37' });
    const payload = { supplier_id: fd.get('supplier_id') || null, po_date: fd.get('po_date'), notes: fd.get('notes') || '', items };
    const res = await fetch('../backend/purchase_order_manager.php?action=create', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    });
    const j = await res.json();
    if (!j.success) return Swal.fire({ title:'Error', text:j.error, icon:'error', confirmButtonColor:'#D4AF37' });
    closePoModal();
    Swal.fire({ title:'PO Recorded', text:`${j.po_number} saved · Stock-in posted · ${formatCurrency(j.total)} expense recorded.`, icon:'success', confirmButtonColor:'#D4AF37' });
    loadPOs();
});

document.addEventListener('DOMContentLoaded', async () => {
    lucide.createIcons();
    await fetchOptions();
    await loadPOs();
});
</script>
</body>
</html>
