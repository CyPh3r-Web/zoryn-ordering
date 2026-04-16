<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
function isActiveCashier($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'cashier'):
?>

<div id="sidebar" class="fixed top-0 left-0 h-screen w-[260px] bg-[#121212]/95 backdrop-blur-xl border-r border-[#2E2E2E]/50 pt-20 z-[80] transition-all duration-300 flex flex-col" style="font-family: 'Poppins', sans-serif;">
    <div class="absolute top-0 left-0 w-[3px] h-full bg-gradient-to-b from-[#D4AF37] via-[#F5D76E] to-[#B8921E] opacity-40"></div>
    <div class="px-5 py-4 border-b border-[#2E2E2E]/50">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#D4AF37]/20 to-[#D4AF37]/5 flex items-center justify-center border border-[#D4AF37]/20">
                <i class="fas fa-cash-register text-sm text-[#D4AF37]"></i>
            </div>
            <div><h3 class="text-sm font-bold text-[#D4AF37] tracking-wider">CASHIER</h3><p class="text-[10px] text-[#666]">Point of Sale</p></div>
        </div>
    </div>

    <!-- Menu -->
    <div class="flex-1 overflow-y-auto py-4 px-3 scrollbar-thin">
        <ul class="space-y-1">
            <li class="cashier-item <?= isActiveCashier('order-details.php') ?>">
                <a href="order-details.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-[#B0B0B0] hover:text-[#D4AF37] hover:bg-[#D4AF37]/8 transition-all duration-200 group">
                    <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#D4AF37]/5 group-hover:bg-[#D4AF37]/15 transition-all duration-200">
                        <i class="fas fa-plus-circle text-xs text-[#D4AF37]/60 group-hover:text-[#D4AF37] transition-colors"></i>
                    </div>
                    <span class="font-medium">New Order</span>
                </a>
            </li>
            <li class="cashier-item <?= isActiveCashier('orders.php') ?>">
                <a href="orders.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-[#B0B0B0] hover:text-[#D4AF37] hover:bg-[#D4AF37]/8 transition-all duration-200 group">
                    <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#D4AF37]/5 group-hover:bg-[#D4AF37]/15 transition-all duration-200">
                        <i class="fas fa-shopping-cart text-xs text-[#D4AF37]/60 group-hover:text-[#D4AF37] transition-colors"></i>
                    </div>
                    <span class="font-medium">Orders</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Footer -->
    <div class="px-3 py-4 border-t border-[#2E2E2E]/50">
        <a href="#" id="cashierLogoutLink" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-red-400/70 hover:text-red-400 hover:bg-red-500/8 transition-all duration-200 group">
            <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/5 group-hover:bg-red-500/15 transition-all duration-200">
                <i class="fas fa-sign-out-alt text-xs"></i>
            </div>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</div>

<style>
#sidebar.collapsed { transform: translateX(-260px); }
.cashier-item.active > a { background: rgba(212,175,55,0.1) !important; color: #D4AF37 !important; }
.cashier-item.active > a > div:first-child { background: rgba(212,175,55,0.2) !important; }
.cashier-item.active > a > div:first-child i { color: #D4AF37 !important; }
.cashier-item.active > a::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 60%; background: #D4AF37; border-radius: 0 4px 4px 0; }
.main-content { margin-left: 260px; transition: all 0.3s ease; }
.main-content.expanded { margin-left: 0; }
@media (max-width: 768px) {
    #sidebar { transform: translateX(-100%); }
    #sidebar.collapsed { transform: translateX(0); }
    .main-content { margin-left: 0; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    if (isSidebarCollapsed) {
        sidebar.classList.add('collapsed');
        if (mainContent) mainContent.classList.add('expanded');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }

    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                if (mainContent) mainContent.classList.add('expanded');
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        });
    });
});
</script>

<?php endif; ?>
