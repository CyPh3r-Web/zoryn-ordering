<?php
$current_page = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}

function isFinancePage() {
    global $current_page;
    return in_array($current_page, ['reports.php', 'balance-sheet.php', 'purchase-orders.php']);
}

function renderSidebarIcon($name) {
    $baseClass = 'h-[18px] w-[18px] stroke-[1.85] text-current';

    $icons = [
        'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><rect x="3.75" y="3.75" width="7.5" height="7.5" rx="2"></rect><rect x="12.75" y="3.75" width="7.5" height="4.5" rx="2"></rect><rect x="12.75" y="11.25" width="7.5" height="9" rx="2"></rect><rect x="3.75" y="12.75" width="7.5" height="7.5" rx="2"></rect></svg>',
        'products' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.25h9l2.25 3.375-6.75 4.125-6.75-4.125L7.5 5.25Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.625V17.25A1.5 1.5 0 0 0 6.75 18.75h10.5a1.5 1.5 0 0 0 1.5-1.5V8.625"></path><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6"></path></svg>',
        'orders' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15"></path><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 4.5h10.5a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 10.5h7.5"></path><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 14.25h5.25"></path></svg>',
        'inventory' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5 12 3.75l7.5 3.75L12 11.25 4.5 7.5Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12 12 15.75 19.5 12"></path><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 16.5 12 20.25l7.5-3.75"></path></svg>',
        'finance' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"></path></svg>',
        'reports' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 17.25V10.5"></path><path stroke-linecap="round" stroke-linejoin="round" d="M12 17.25V6.75"></path><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 17.25v-4.5"></path><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25h15"></path></svg>',
        'balance_sheet' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"></path></svg>',
        'purchase_orders' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25h13.5v9.75a1.5 1.5 0 0 1-1.5 1.5H4.5a1.5 1.5 0 0 1-1.5-1.5V8.25Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 11.25h3l2.25 3v3a1.5 1.5 0 0 1-1.5 1.5H18"></path><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M18 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path></svg>',
        'chevron' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-4 w-4 stroke-[2] text-current transition-transform duration-300" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"></path></svg>',
        'users' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.125a7.5 7.5 0 0 1 15 0"></path></svg>',
        'logout' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="' . $baseClass . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6.75V5.625A1.875 1.875 0 0 1 12.375 3.75h5.25A1.875 1.875 0 0 1 19.5 5.625v12.75a1.875 1.875 0 0 1-1.875 1.875h-5.25A1.875 1.875 0 0 1 10.5 18.375V17.25"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4.5"></path><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 8.25-3.75 3.75 3.75 3.75"></path></svg>',
    ];

    return $icons[$name] ?? '';
}
?>
<div class="sidebar fixed top-0 left-0 h-screen w-[260px] bg-[#0D0D0D]/90 backdrop-blur-xl border-r border-[#D4AF37]/10 pt-20 z-[80] transition-all duration-300 flex flex-col" id="sidebar" style="font-family: 'Poppins', sans-serif;">
    <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#D4AF37] via-[#F5D76E] to-[#D4AF37]"></div>
    <div class="flex-1 overflow-y-auto py-4 px-3">
        <p class="px-4 mb-3 text-[10px] font-semibold uppercase tracking-[3px] text-[#D4AF37]/50">Menu</p>
        <ul class="space-y-1">
            <li class="sidebar-item <?= isActive('dashboard.php') ?>">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/10 transition-all duration-200 group">
                    <div class="sidebar-icon-wrap w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#D4AF37]/12 to-[#B8921E]/5 text-[#D4AF37]/75 ring-1 ring-[#D4AF37]/10 group-hover:from-[#D4AF37]/20 group-hover:to-[#F5D76E]/10 group-hover:text-[#F5D76E] group-hover:shadow-[0_0_18px_rgba(212,175,55,0.16)] transition-all duration-300"><?= renderSidebarIcon('dashboard') ?></div>
                    <span class="sidebar-text font-medium">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item <?= isActive('products.php') ?>">
                <a href="products.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/10 transition-all duration-200 group">
                    <div class="sidebar-icon-wrap w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#D4AF37]/12 to-[#B8921E]/5 text-[#D4AF37]/75 ring-1 ring-[#D4AF37]/10 group-hover:from-[#D4AF37]/20 group-hover:to-[#F5D76E]/10 group-hover:text-[#F5D76E] group-hover:shadow-[0_0_18px_rgba(212,175,55,0.16)] transition-all duration-300"><?= renderSidebarIcon('products') ?></div>
                    <span class="sidebar-text font-medium">Products</span>
                </a>
            </li>
            <li class="sidebar-item <?= isActive('orders.php') ?>">
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/10 transition-all duration-200 group">
                    <div class="sidebar-icon-wrap w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#D4AF37]/12 to-[#B8921E]/5 text-[#D4AF37]/75 ring-1 ring-[#D4AF37]/10 group-hover:from-[#D4AF37]/20 group-hover:to-[#F5D76E]/10 group-hover:text-[#F5D76E] group-hover:shadow-[0_0_18px_rgba(212,175,55,0.16)] transition-all duration-300"><?= renderSidebarIcon('orders') ?></div>
                    <span class="sidebar-text font-medium">Orders</span>
                </a>
            </li>
            <li class="sidebar-item <?= isActive('sales.php') ?>">
                <a href="sales.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/10 transition-all duration-200 group">
                    <div class="sidebar-icon-wrap w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#D4AF37]/12 to-[#B8921E]/5 text-[#D4AF37]/75 ring-1 ring-[#D4AF37]/10 group-hover:from-[#D4AF37]/20 group-hover:to-[#F5D76E]/10 group-hover:text-[#F5D76E] group-hover:shadow-[0_0_18px_rgba(212,175,55,0.16)] transition-all duration-300"><?= renderSidebarIcon('reports') ?></div>
                    <span class="sidebar-text font-medium">Sales Reading</span>
                </a>
            </li>
            <li class="sidebar-item <?= isActive('inventory.php') ?>">
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/10 transition-all duration-200 group">
                    <div class="sidebar-icon-wrap w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#D4AF37]/12 to-[#B8921E]/5 text-[#D4AF37]/75 ring-1 ring-[#D4AF37]/10 group-hover:from-[#D4AF37]/20 group-hover:to-[#F5D76E]/10 group-hover:text-[#F5D76E] group-hover:shadow-[0_0_18px_rgba(212,175,55,0.16)] transition-all duration-300"><?= renderSidebarIcon('inventory') ?></div>
                    <span class="sidebar-text font-medium">Inventory</span>
                </a>
            </li>
            <li class="sidebar-item sidebar-dropdown <?= isFinancePage() ? 'active open' : '' ?>">
                <button onclick="toggleFinanceDropdown()" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/10 transition-all duration-200 group">
                    <div class="sidebar-icon-wrap w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#D4AF37]/12 to-[#B8921E]/5 text-[#D4AF37]/75 ring-1 ring-[#D4AF37]/10 group-hover:from-[#D4AF37]/20 group-hover:to-[#F5D76E]/10 group-hover:text-[#F5D76E] group-hover:shadow-[0_0_18px_rgba(212,175,55,0.16)] transition-all duration-300"><?= renderSidebarIcon('finance') ?></div>
                    <span class="sidebar-text font-medium flex-1 text-left">Finance</span>
                    <span id="financeChevron" class="<?= isFinancePage() ? 'rotate-180' : '' ?>"><?= renderSidebarIcon('chevron') ?></span>
                </button>
                <ul id="financeSubmenu" class="sidebar-submenu mt-1 ml-[52px] space-y-0.5 overflow-hidden transition-all duration-300" style="<?= isFinancePage() ? '' : 'max-height:0;opacity:0;' ?>">
                    <li class="<?= isActive('reports.php') ? 'sub-active' : '' ?>">
                        <a href="reports.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/8 transition-all duration-200">
                            <span class="w-5 h-5 flex items-center justify-center"><?= renderSidebarIcon('reports') ?></span>
                            <span>Financial Reports</span>
                        </a>
                    </li>
                    <li class="<?= isActive('balance-sheet.php') ? 'sub-active' : '' ?>">
                        <a href="balance-sheet.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/8 transition-all duration-200">
                            <span class="w-5 h-5 flex items-center justify-center"><?= renderSidebarIcon('balance_sheet') ?></span>
                            <span>Balance Sheet</span>
                        </a>
                    </li>
                    <li class="<?= isActive('purchase-orders.php') ? 'sub-active' : '' ?>">
                        <a href="purchase-orders.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/8 transition-all duration-200">
                            <span class="w-5 h-5 flex items-center justify-center"><?= renderSidebarIcon('purchase_orders') ?></span>
                            <span>Purchase Orders</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="sidebar-item <?= isActive('users.php') ?>">
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-[#F5D76E] hover:bg-[#D4AF37]/10 transition-all duration-200 group">
                    <div class="sidebar-icon-wrap w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#D4AF37]/12 to-[#B8921E]/5 text-[#D4AF37]/75 ring-1 ring-[#D4AF37]/10 group-hover:from-[#D4AF37]/20 group-hover:to-[#F5D76E]/10 group-hover:text-[#F5D76E] group-hover:shadow-[0_0_18px_rgba(212,175,55,0.16)] transition-all duration-300"><?= renderSidebarIcon('users') ?></div>
                    <span class="sidebar-text font-medium">Users</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Footer -->
    <div class="p-3 border-t border-[#D4AF37]/10">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-[#B0B0B0] hover:text-red-400 hover:bg-red-500/10 transition-all duration-200 group">
            <div class="w-9 h-9 flex items-center justify-center rounded-xl bg-gradient-to-br from-red-500/10 to-red-500/5 text-red-400/75 ring-1 ring-red-500/10 group-hover:from-red-500/20 group-hover:to-red-500/10 group-hover:text-red-300 transition-all duration-300">
                <?= renderSidebarIcon('logout') ?>
            </div>
            <span class="sidebar-text font-medium">Logout</span>
        </a>
    </div>
</div>

<style>
.sidebar.collapsed { width: 0; overflow: hidden; padding: 0; border: none; }
.sidebar-item.active > a,
.sidebar-item.active > button { background: rgba(212,175,55,0.12) !important; color: #F5D76E !important; }
.sidebar-item.active > a > .sidebar-icon-wrap,
.sidebar-item.active > button > .sidebar-icon-wrap {
    background: linear-gradient(135deg, rgba(212,175,55,0.22), rgba(245,215,110,0.08)) !important;
    box-shadow: 0 0 18px rgba(212,175,55,0.18) !important;
    color: #F5D76E !important;
    border-color: rgba(212,175,55,0.18) !important;
}
.sidebar-item.active > a::before,
.sidebar-item.sidebar-dropdown.active > button::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 24px; background: linear-gradient(180deg, #D4AF37, #F5D76E); border-radius: 0 4px 4px 0; }
.sidebar-item > a,
.sidebar-item > button { position: relative; }
.sidebar-submenu li.sub-active > a { color: #F5D76E !important; background: rgba(212,175,55,0.08) !important; }
.sidebar-submenu li.sub-active > a::before { content: ''; position: absolute; left: -8px; top: 50%; transform: translateY(-50%); width: 4px; height: 4px; background: #F5D76E; border-radius: 50%; }
.sidebar-submenu li > a { position: relative; }
@media (max-width: 1024px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.collapsed { transform: translateX(0); width: 260px; }
}
</style>
<script>
function toggleFinanceDropdown() {
    const submenu = document.getElementById('financeSubmenu');
    const chevron = document.getElementById('financeChevron');
    const isOpen = submenu.style.maxHeight !== '0px' && submenu.style.maxHeight !== '';
    if (isOpen) {
        submenu.style.maxHeight = '0px';
        submenu.style.opacity = '0';
        chevron.classList.remove('rotate-180');
    } else {
        submenu.style.maxHeight = submenu.scrollHeight + 'px';
        submenu.style.opacity = '1';
        chevron.classList.add('rotate-180');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const submenu = document.getElementById('financeSubmenu');
    if (submenu && submenu.style.maxHeight !== '0px' && submenu.style.opacity !== '0') {
        submenu.style.maxHeight = submenu.scrollHeight + 'px';
        submenu.style.opacity = '1';
    }
});
</script>
