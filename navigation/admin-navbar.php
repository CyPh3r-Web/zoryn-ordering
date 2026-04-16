<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
?>
<link rel="icon" type="image/jpeg" href="../assets/zoryn/zoryn.jpg">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                z: {
                    black: '#0D0D0D', dark: '#121212', gray: '#1F1F1F',
                    'gray-light': '#2A2A2A', border: '#2E2E2E',
                    gold: '#D4AF37', 'gold-light': '#F5D76E',
                    'gold-muted': '#B8921E', 'gold-pale': '#F4D26B',
                }
            },
            fontFamily: { poppins: ['Poppins', 'sans-serif'] },
        }
    }
}
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<nav class="fixed top-0 left-0 right-0 z-[100] bg-[#0D0D0D]/90 backdrop-blur-xl border-b border-[#D4AF37]/10 shadow-lg shadow-black/30" style="font-family: 'Poppins', sans-serif;">
    <div class="absolute bottom-0 left-0 right-0 h-[1px] bg-gradient-to-r from-transparent via-[#D4AF37]/30 to-transparent"></div>
    <div class="flex items-center justify-between px-4 lg:px-6 h-16">
        <div class="flex items-center gap-3">
            <button id="sidebarToggle" class="w-9 h-9 flex items-center justify-center rounded-lg text-[#D4AF37] hover:bg-[#D4AF37]/10 transition-all duration-200"><i class="fas fa-bars text-sm"></i></button>
            <div class="flex items-center gap-3">
                <img src="../assets/zoryn/zoryn.jpg" alt="Zoryn" class="h-9 w-9 rounded-lg object-cover ring-2 ring-[#D4AF37]/30">
                <span class="text-[#D4AF37] font-bold text-sm tracking-wider hidden sm:block">ZORYN ADMIN</span>
            </div>
        </div>

        <!-- Center: Search -->
        <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
            <div class="relative w-full">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#D4AF37]/40 text-xs"></i>
                <input type="text" placeholder="Search orders, products..." class="w-full pl-9 pr-4 py-2 bg-[#1F1F1F] border border-[#2E2E2E] rounded-xl text-sm text-[#B0B0B0] placeholder-[#555] focus:border-[#D4AF37] focus:shadow-[0_0_0_3px_rgba(212,175,55,0.1)] outline-none transition-all duration-200">
            </div>
        </div>

        <!-- Right -->
        <div class="flex items-center gap-3">
            <!-- Notifications -->
            <div class="relative group">
                <button class="w-9 h-9 flex items-center justify-center rounded-lg text-[#D4AF37]/70 hover:text-[#D4AF37] hover:bg-[#D4AF37]/10 transition-all duration-200 relative">
                    <i class="fas fa-bell text-sm"></i>
                    <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-[#0D0D0D]"></span>
                </button>
                <div class="hidden group-hover:block absolute right-0 top-full mt-2 w-80 bg-[#1F1F1F] border border-[#D4AF37]/10 rounded-xl shadow-2xl overflow-hidden z-[1000]" style="animation: slideDown 0.2s ease;">
                    <div class="px-4 py-3 border-b border-[#2E2E2E] flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-[#D4AF37]">Notifications</h3>
                        <span class="badge-pending text-[10px]">2 new</span>
                    </div>
                    <div class="p-2">
                        <div class="px-3 py-2.5 rounded-lg hover:bg-[#D4AF37]/5 transition flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#D4AF37]/10 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-shopping-bag text-[10px] text-[#D4AF37]"></i></div>
                            <div><p class="text-sm text-[#B0B0B0]">New order received</p><span class="text-xs text-[#666]">5 minutes ago</span></div>
                        </div>
                        <div class="px-3 py-2.5 rounded-lg hover:bg-[#D4AF37]/5 transition flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-exclamation-triangle text-[10px] text-red-400"></i></div>
                            <div><p class="text-sm text-[#B0B0B0]">Inventory low alert</p><span class="text-xs text-[#666]">30 minutes ago</span></div>
                        </div>
                    </div>
                    <a href="notifications.php" class="block text-center py-2.5 text-xs text-[#D4AF37] hover:bg-[#D4AF37]/5 border-t border-[#2E2E2E] transition">View All Notifications</a>
                </div>
            </div>

            <!-- Admin Profile -->
            <div class="relative group">
                <button class="flex items-center gap-2.5 bg-[#D4AF37]/8 hover:bg-[#D4AF37]/15 px-3 py-1.5 rounded-full transition-all duration-200 cursor-pointer border border-[#D4AF37]/10 hover:border-[#D4AF37]/20">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#D4AF37] to-[#B8921E] flex items-center justify-center">
                        <i class="fas fa-user-shield text-xs text-[#0D0D0D]"></i>
                    </div>
                    <span class="hidden sm:block text-xs font-medium text-[#D4AF37]"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                    <i class="fas fa-chevron-down text-[10px] text-[#D4AF37]/50"></i>
                </button>
                <div class="hidden group-hover:block absolute right-0 top-full mt-2 w-56 bg-[#1F1F1F] border border-[#D4AF37]/10 rounded-xl shadow-2xl overflow-hidden z-[1000]" style="animation: slideDown 0.2s ease;">
                    <div class="px-4 py-3 border-b border-[#2E2E2E] flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#D4AF37] to-[#B8921E] flex items-center justify-center"><i class="fas fa-user-shield text-sm text-[#0D0D0D]"></i></div>
                        <div><p class="text-sm font-semibold text-[#D4AF37]"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></p><p class="text-[10px] text-[#666]">Administrator</p></div>
                    </div>
                    <div class="py-2">
                        <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-[#B0B0B0] hover:text-[#D4AF37] hover:bg-[#D4AF37]/5 transition"><i class="fas fa-user w-4 text-center text-[#D4AF37]/50"></i>My Profile</a>
                        <a href="settings-2fa.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-[#B0B0B0] hover:text-[#D4AF37] hover:bg-[#D4AF37]/5 transition"><i class="fas fa-cog w-4 text-center text-[#D4AF37]/50"></i>Settings</a>
                        <div class="mx-3 my-1 border-t border-[#2E2E2E]"></div>
                        <a href="#" id="logoutLink" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/5 transition"><i class="fas fa-sign-out-alt w-4 text-center"></i>Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="h-16"></div>

<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    const logoutLink = document.getElementById('logoutLink');

    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed && sidebar) {
        sidebar.classList.add('collapsed');
        if (mainContent) mainContent.classList.add('expanded');
    }

    logoutLink.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Logout?', text: 'You will be logged out of your account', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#2A2A2A', confirmButtonText: 'Logout'
        }).then(result => { if (result.isConfirmed) window.location.href = 'logout.php'; });
    });

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
});
</script>
