<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
echo '<link rel="icon" type="image/jpeg" href="../assets/zoryn/zoryn.jpg">';
$canValidateAuth = session_status() === PHP_SESSION_ACTIVE;
if ($canValidateAuth) {
    if (isset($_SESSION['2fa_pending']) && $_SESSION['2fa_pending']) {
        // Don't redirect if in 2FA verification process
    } else if (!isset($_SESSION['user_id'])) {
        if (!headers_sent()) {
            header("Location: ../index.php");
            exit();
        }
    }
}

require_once '../backend/dbconn.php';
require_once '../backend/shift_access.php';
$profilePicture = null;
$userEmail = null;
$cashierShiftAccess = null;

if (isset($_SESSION['user_id']) && strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier') {
    $shiftAccess = zoryn_get_cashier_shift_access($conn, (int) $_SESSION['user_id']);
    $cashierShiftAccess = $shiftAccess;
    if (!$shiftAccess['is_within_shift'] && !$shiftAccess['is_grace_period']) {
        $_SESSION = [];
        if (session_id() !== '' || isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
        if (!headers_sent()) {
            header('Location: ../index.php');
            exit();
        }
    } else {
        $_SESSION['active_shift_id'] = $shiftAccess['active_shift_id'];
    }
}

if (isset($_SESSION['user_id'])) {
try {
    $stmt = $conn->prepare("SELECT username, profile_picture, email FROM users WHERE user_id = ?");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    $result = $stmt->get_result();
    if (!$result) throw new Exception("Get result failed: " . $stmt->error);
    $user = $result->fetch_assoc();
    if ($user) {
        $_SESSION['username'] = $user['username'];
        $profilePicture = $user['profile_picture'] ?? null;
        $userEmail = $user['email'] ?? null;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}
}
?>
<!-- Tailwind CDN + Config -->
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/zoryn-theme.css">

<nav class="fixed top-0 left-0 right-0 z-[100] bg-[#121212]/95 backdrop-blur-xl border-b border-[#2E2E2E]/50 shadow-lg shadow-black/30" style="font-family: 'Poppins', sans-serif;">
    <div class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-[#D4AF37]/30 to-transparent"></div>
    <div class="flex items-center justify-between px-4 lg:px-6 h-16">
        <!-- Left -->
        <div class="flex items-center gap-3">
            <a href="../users/home.php" class="flex items-center gap-3">
                <img src="../assets/zoryn/zoryn.jpg" alt="Zoryn" class="h-12 w-12 object-cover">
                <span class="hidden sm:block text-z-gold font-bold text-sm tracking-wider">ZORYN Restaurant</span>
            </a>
        </div>

        <!-- Right -->
        <div class="flex items-center gap-2 lg:gap-4">
            <!-- Nav Icons -->
            <div class="flex items-center gap-1">
                <!-- Notifications -->
                <div class="relative" id="notifContainer">
                    <button id="notificationIcon" class="w-9 h-9 flex items-center justify-center rounded-lg text-z-gold/70 hover:text-z-gold hover:bg-z-gold/10 transition-all relative">
                        <i class="fas fa-bell text-sm"></i>
                        <span class="notification-badge absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center" id="notificationBadge" style="display: none;">0</span>
                    </button>

                    <!-- Notification Panel -->
                    <div class="hidden absolute right-0 top-full mt-2 w-[380px] bg-z-gray border border-z-border rounded-2xl shadow-2xl shadow-black/40 max-h-[500px] overflow-hidden z-[1000]" id="notificationPanel" style="animation: slideDown 0.3s ease;">
                        <div class="sticky top-0 bg-z-gray/95 backdrop-blur-lg px-5 py-4 border-b border-z-border flex items-center justify-between rounded-t-2xl">
                            <h3 class="text-base font-bold text-z-gold">Notifications</h3>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer hover:text-z-gold transition">
                                    <input type="checkbox" id="selectAllNotifications" class="w-3.5 h-3.5 rounded accent-[#D4AF37]">
                                    <span>All</span>
                                </label>
                                <button class="hidden text-xs bg-red-500/15 text-red-400 px-2.5 py-1 rounded-lg hover:bg-red-500/25 transition font-medium" id="bulkDeleteBtn">Delete</button>
                                <button class="close-notifications w-7 h-7 flex items-center justify-center rounded-lg text-gray-500 hover:text-white hover:bg-white/5 transition text-lg">&times;</button>
                            </div>
                        </div>
                        <div class="overflow-y-auto max-h-[400px] p-3" id="notificationList"></div>
                    </div>
                </div>

                <!-- Cart -->
                <a href="order-details.php" class="w-9 h-9 flex items-center justify-center rounded-lg text-z-gold/70 hover:text-z-gold hover:bg-z-gold/10 transition-all" title="Cart">
                    <i class="fas fa-shopping-cart text-sm"></i>
                </a>

                <?php if (($_SESSION['role'] ?? '') === 'cashier'): ?>
                <!-- Orders (cashier only) -->
                <a href="orders.php" id="navOrdersLink" class="relative w-9 h-9 flex items-center justify-center rounded-lg text-z-gold/70 hover:text-z-gold hover:bg-z-gold/10 transition-all" title="Orders">
                    <i class="fas fa-receipt text-sm"></i>
                    <span id="pendingOrdersBadge" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center" style="display:none;">0</span>
                </a>
                <?php endif; ?>

                <!-- Home -->
                <a href="home.php" class="w-9 h-9 flex items-center justify-center rounded-lg text-z-gold/70 hover:text-z-gold hover:bg-z-gold/10 transition-all" title="Home">
                    <i class="fas fa-home text-sm"></i>
                </a>
            </div>

            <!-- User Profile -->
            <div class="relative" id="userProfile">
                <button class="flex items-center gap-2.5 bg-z-gold/8 hover:bg-z-gold/15 px-3 py-1.5 rounded-full transition-all cursor-pointer border border-z-gold/10">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-z-gold to-z-gold-muted ring-2 ring-z-border overflow-hidden flex items-center justify-center">
                        <?php if ($profilePicture): ?>
                            <img src="../uploads/profile_pictures/<?= htmlspecialchars($profilePicture) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-xs text-z-black"></i>
                        <?php endif; ?>
                    </div>
                    <span class="hidden sm:block text-xs font-medium text-z-gold"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <i class="fas fa-chevron-down text-[10px] text-z-gold/50"></i>
                </button>

                <!-- Profile Dropdown -->
                <div class="hidden absolute right-0 top-full mt-2 w-72 bg-z-gray border border-z-border rounded-2xl shadow-2xl shadow-black/40 overflow-hidden z-[1000]" id="profileDropdown" style="animation: slideDown 0.3s ease;">
                    <div class="px-5 py-4 bg-z-gray-light/50 border-b border-z-border">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-z-gold to-z-gold-muted ring-2 ring-z-border overflow-hidden flex items-center justify-center">
                                <?php if ($profilePicture): ?>
                                    <img src="../uploads/profile_pictures/<?= htmlspecialchars($profilePicture) ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-user text-sm text-z-black"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white"><?= htmlspecialchars($_SESSION['username']) ?></p>
                                <p class="text-xs text-gray-400"><?= $userEmail ? htmlspecialchars($userEmail) : '' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="py-2">
                        <a href="profile.php" class="flex items-center gap-3 px-5 py-2.5 text-sm text-gray-300 hover:text-z-gold hover:bg-z-gold/5 transition-all">
                            <i class="fas fa-user w-4 text-center text-z-gold/50"></i>Profile Settings
                        </a>
                        <button type="button" id="openChangePasswordModal" class="flex items-center gap-3 px-5 py-2.5 text-sm text-gray-300 hover:text-z-gold hover:bg-z-gold/5 transition-all w-full text-left border-0 bg-transparent cursor-pointer font-[inherit]">
                            <i class="fas fa-key w-4 text-center text-z-gold/50"></i>Change Password
                        </button>
                        <div class="mx-4 my-1 border-t border-z-border"></div>
                        <a href="#" onclick="confirmLogout(event)" class="flex items-center gap-3 px-5 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/5 transition-all">
                            <i class="fas fa-sign-out-alt w-4 text-center"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Spacer for fixed navbar -->
<div class="h-16"></div>

<!-- Change password modal (stays on current page) -->
<div id="changePasswordModal" class="fixed inset-0 z-[1050] hidden flex items-center justify-center p-4 bg-black/65 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="cpModalTitle">
    <div class="relative w-full max-w-md rounded-2xl border border-z-border bg-[#1F1F1F] shadow-2xl shadow-black/50 max-h-[90vh] overflow-y-auto">
        <button type="button" id="closeChangePasswordModal" class="absolute top-3 right-3 z-10 w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 text-xl leading-none" aria-label="Close">&times;</button>
        <div class="p-6 pt-9">
            <h2 id="cpModalTitle" class="text-lg font-bold text-z-gold mb-1">Change Password</h2>
            <p class="text-xs text-gray-400 mb-5">Enter your current password, then a new one (at least 8 characters).</p>
            <form id="changePasswordModalForm" class="space-y-3">
                <div>
                    <label for="cp_current_password" class="block text-[11px] font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">Current password</label>
                    <input type="password" id="cp_current_password" required autocomplete="current-password" class="w-full px-3 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all">
                </div>
                <div>
                    <label for="cp_new_password" class="block text-[11px] font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">New password</label>
                    <input type="password" id="cp_new_password" required autocomplete="new-password" minlength="8" placeholder="Minimum 8 characters" class="w-full px-3 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all">
                </div>
                <div>
                    <label for="cp_confirm_password" class="block text-[11px] font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">Confirm new password</label>
                    <input type="password" id="cp_confirm_password" required autocomplete="new-password" minlength="8" class="w-full px-3 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all">
                </div>
                <button type="submit" class="w-full py-2.5 mt-2 rounded-xl font-semibold text-sm text-z-black bg-gradient-to-r from-[#F4D26B] to-[#C99B2A] hover:from-[#FFDF7D] hover:to-[#D3A533] transition shadow-lg shadow-z-gold/15">Update password</button>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.notification-item { transition: all 0.2s ease; }
.notification-item:hover { background: rgba(212,175,55,0.05); }
.notification-item.unread { border-left: 3px solid #D4AF37; background: rgba(212,175,55,0.05); }
.notification-item.read { border-left: 3px solid transparent; }

/* Payment modal styles */
.payment-options { display: flex; flex-direction: column; gap: 12px; margin: 16px 0; }
.payment-option { display: flex; align-items: center; gap: 10px; padding: 14px; border: 1px solid #2E2E2E; border-radius: 12px; cursor: pointer; transition: all 0.2s; background: #1F1F1F; }
.payment-option:hover { border-color: #D4AF37; }
.payment-option.selected { border-color: #D4AF37; background: rgba(212,175,55,0.08); }
.payment-option input[type="radio"] { accent-color: #D4AF37; width: 18px; height: 18px; }
.payment-option label { cursor: pointer; font-weight: 500; color: #B0B0B0; }
.payment-upload { display: none; margin-top: 12px; padding: 16px; border: 2px dashed #2E2E2E; border-radius: 12px; text-align: center; background: #121212; }
.payment-upload.active { display: block; }
.upload-btn { display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #D4AF37, #B8921E); color: #0D0D0D; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; }
.upload-btn:hover { transform: translateY(-1px); }
.payment-upload input[type="file"] { display: none; }
.upload-preview { margin-top: 10px; max-width: 200px; display: none; margin: 10px auto 0; }
.upload-preview img { width: 100%; border-radius: 8px; border: 1px solid #2E2E2E; }
</style>

<script>
window.selectPaymentOption = function(element, type) {
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.querySelector('input[type="radio"]').checked = false;
    });
    element.classList.add('selected');
    element.querySelector('input[type="radio"]').checked = true;
    const uploadSection = document.getElementById('paymentUpload');
    if (type === 'online') uploadSection.classList.add('active');
    else uploadSection.classList.remove('active');
}

window.previewPaymentProof = function(input) {
    const preview = document.getElementById('uploadPreview');
    const previewImage = document.getElementById('previewImage');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    const closeNotifications = document.querySelector('.close-notifications');
    const selectAllCheckbox = document.getElementById('selectAllNotifications');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const userProfile = document.getElementById('userProfile');
    const profileDropdown = document.getElementById('profileDropdown');
    const cashierShiftAccess = <?php echo json_encode($cashierShiftAccess ?? null); ?>;

    const playShiftReminderSound = () => {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const oscillator = ctx.createOscillator();
            const gainNode = ctx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, ctx.currentTime);
            gainNode.gain.setValueAtTime(0.0001, ctx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.08, ctx.currentTime + 0.02);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.28);

            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);

            oscillator.start();
            oscillator.stop(ctx.currentTime + 0.3);
        } catch (e) {
            // Silent fallback if browser blocks autoplay/audio context.
        }
    };

    if (cashierShiftAccess && cashierShiftAccess.status === 'active') {
        const alertMinutes = [30, 20, 10, 5];
        const shiftId = Number(cashierShiftAccess.active_shift_id || 0);
        const keyPrefix = `shift_warn_${shiftId}_`;
        const showWarning = (mins) => {
            playShiftReminderSound();
            Swal.fire({
                icon: 'warning',
                title: 'Shift Reminder',
                text: `Your shift will end in ${mins} minute${mins === 1 ? '' : 's'}.`,
                confirmButtonColor: '#D4AF37',
                background: '#1F1F1F',
                color: '#E5E5E5'
            });
        };

        const evaluateAlerts = () => {
            const totalSec = Number(cashierShiftAccess.seconds_until_end || 0);
            const elapsed = Math.floor((Date.now() - pageLoadMs) / 1000);
            const remainingSec = Math.max(0, totalSec - elapsed);
            alertMinutes.forEach((m) => {
                const threshold = m * 60;
                if (remainingSec <= threshold) {
                    const key = keyPrefix + String(m);
                    if (localStorage.getItem(key) !== '1') {
                        localStorage.setItem(key, '1');
                        showWarning(m);
                    }
                }
            });
        };
        const pageLoadMs = Date.now();
        evaluateAlerts();
        setInterval(evaluateAlerts, 15000);
    }

    if (cashierShiftAccess && cashierShiftAccess.status === 'grace') {
        playShiftReminderSound();
        Swal.fire({
            icon: 'info',
            title: 'Shift Ended',
            text: 'Your shift has ended. You have 5 minutes to submit your cash count.',
            confirmButtonColor: '#D4AF37',
            background: '#1F1F1F',
            color: '#E5E5E5'
        });
    }

    function addNotification(notification) {
        const notificationItem = document.createElement('div');
        notificationItem.className = `notification-item ${notification.is_read ? 'read' : 'unread'} rounded-xl p-3 mb-2`;
        notificationItem.dataset.notificationId = notification.id;
        const actionsHtml = `
            <div class="flex flex-wrap gap-2 mt-2">
                ${!notification.is_read ? `<button class="text-xs bg-z-gold/15 text-z-gold px-2.5 py-1 rounded-lg hover:bg-z-gold/25 transition font-medium" onclick="markNotificationAsRead(${notification.id}, this)">Mark Read</button>` : ''}
                ${notification.message.includes('is now being prepared') && notification.payment_status !== 'verified' ? `<button class="text-xs bg-green-500/15 text-green-400 px-2.5 py-1 rounded-lg hover:bg-green-500/25 transition font-medium" onclick="processPayment(${notification.order_id})">Pay Now</button>` : ''}
                ${notification.message.includes('has been completed') ? `<button class="text-xs bg-green-500/15 text-green-400 px-2.5 py-1 rounded-lg hover:bg-green-500/25 transition font-medium" onclick="checkFeedbackAndRedirect(${notification.order_id})">Feedback</button>` : ''}
                <button class="text-xs bg-red-500/15 text-red-400 px-2.5 py-1 rounded-lg hover:bg-red-500/25 transition font-medium" onclick="deleteNotification(${notification.id}, this)">Delete</button>
            </div>
        `;
        notificationItem.innerHTML = `
            <div class="flex items-start gap-3">
                <input type="checkbox" class="notification-checkbox w-3.5 h-3.5 rounded accent-[#D4AF37] mt-1" onchange="updateBulkDeleteButton()">
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-300 leading-relaxed">${notification.message}</p>
                    <p class="text-xs text-gray-500 mt-1">${new Date(notification.created_at).toLocaleString()}</p>
                    ${actionsHtml}
                </div>
            </div>
        `;
        notificationList.appendChild(notificationItem);
    }

    window.markNotificationAsRead = function(notificationId, buttonElement) {
        fetch('../backend/notification_functions.php', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_notification_read&notification_id=${notificationId}`
        }).then(r => r.json()).then(data => {
            if (data.success) {
                const item = buttonElement.closest('.notification-item');
                item.classList.remove('unread');
                item.classList.add('read');
                buttonElement.remove();
                updateBadgeCount();
            }
        }).catch(e => console.error('Error:', e));
    }

    function updateBadgeCount() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        notificationBadge.textContent = unreadCount;
        notificationBadge.style.display = unreadCount > 0 ? 'flex' : 'none';
    }

    function checkNotifications() {
        fetch('../backend/notification_functions.php', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_notifications'
        }).then(r => r.json()).then(data => {
            if (data.success) {
                notificationList.innerHTML = '';
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<div class="text-center py-10 text-gray-500"><i class="fas fa-bell-slash text-3xl text-z-gold/20 mb-3 block"></i><p class="text-sm">No notifications yet</p></div>';
                } else {
                    const sorted = data.notifications.slice().sort((a, b) => {
                        const ta = new Date(a.created_at).getTime();
                        const tb = new Date(b.created_at).getTime();
                        if (ta !== tb) return ta - tb;
                        return (Number(a.id) || 0) - (Number(b.id) || 0);
                    });
                    sorted.forEach(n => addNotification(n));
                }
                updateBadgeCount();
            }
        }).catch(e => console.error('Error:', e));
    }

    // Cashier-only: live pending-orders badge on the navbar Orders icon
    const pendingBadge = document.getElementById('pendingOrdersBadge');
    if (pendingBadge) {
        function refreshPendingOrdersBadge() {
            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_order_counts'
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const active = (parseInt(data.counts.pending || 0, 10)) + (parseInt(data.counts.preparing || 0, 10));
                pendingBadge.textContent = active > 99 ? '99+' : active;
                pendingBadge.style.display = active > 0 ? 'flex' : 'none';
            }).catch(() => { /* silent */ });
        }
        refreshPendingOrdersBadge();
        setInterval(refreshPendingOrdersBadge, 15000);
    }

    notificationIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        const isVisible = !notificationPanel.classList.contains('hidden');
        notificationPanel.classList.toggle('hidden');
        profileDropdown.classList.add('hidden');
        if (!isVisible) checkNotifications();
    });

    document.addEventListener('click', function(e) {
        if (!notificationPanel.contains(e.target) && e.target !== notificationIcon) notificationPanel.classList.add('hidden');
    });

    closeNotifications.addEventListener('click', () => notificationPanel.classList.add('hidden'));
    setInterval(checkNotifications, 30000);
    checkNotifications();

    window.checkFeedbackAndRedirect = function(orderId) {
        fetch('../backend/order_functions.php', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=check_feedback_exists&order_id=${orderId}`
        }).then(r => r.json()).then(data => {
            if (data.has_feedback) {
                Swal.fire({ title: 'Feedback Submitted', text: 'You already submitted feedback for this order.', icon: 'info', confirmButtonColor: '#D4AF37' });
            } else {
                window.location.href = `../users/feedback.php?order_id=${orderId}`;
            }
        }).catch(e => Swal.fire({ title: 'Error', text: 'Failed to check feedback status', icon: 'error', confirmButtonColor: '#D4AF37' }));
    }

    window.deleteNotification = function(notificationId, buttonElement) {
        Swal.fire({
            title: 'Delete Notification?', icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#2A2A2A', confirmButtonText: 'Delete'
        }).then(result => {
            if (result.isConfirmed) {
                fetch('../backend/notification_functions.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_notification&notification_id=${notificationId}`
                }).then(r => r.json()).then(data => {
                    if (data.success) { buttonElement.closest('.notification-item').remove(); updateBadgeCount(); }
                }).catch(e => console.error('Error:', e));
            }
        });
    }

    window.updateBulkDeleteButton = function() {
        const checked = document.querySelectorAll('.notification-checkbox:checked');
        bulkDeleteBtn.style.display = checked.length > 0 ? 'block' : 'none';
    }

    selectAllCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.notification-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkDeleteButton();
    });

    bulkDeleteBtn.addEventListener('click', function() {
        const ids = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.closest('.notification-item').dataset.notificationId);
        if (!ids.length) return;
        Swal.fire({
            title: `Delete ${ids.length} notification(s)?`, icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#2A2A2A', confirmButtonText: 'Delete All'
        }).then(result => {
            if (result.isConfirmed) {
                Promise.all(ids.map(id => fetch('../backend/notification_functions.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_notification&notification_id=${id}`
                }).then(r => r.json()))).then(() => {
                    ids.forEach(id => { const el = document.querySelector(`.notification-item[data-notification-id="${id}"]`); if (el) el.remove(); });
                    updateBadgeCount(); selectAllCheckbox.checked = false; bulkDeleteBtn.style.display = 'none';
                });
            }
        });
    });

    userProfile.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('hidden');
        notificationPanel.classList.add('hidden');
    });
    document.addEventListener('click', function(e) {
        if (!profileDropdown.contains(e.target) && !userProfile.contains(e.target)) profileDropdown.classList.add('hidden');
    });

    window.confirmLogout = function(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Logout?', text: 'Are you sure you want to logout?', icon: 'question',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#2A2A2A', confirmButtonText: 'Logout'
        }).then(result => { if (result.isConfirmed) window.location.href = '../users/logout.php'; });
    }

    window.processPayment = function(orderId) {
        Swal.fire({
            title: 'Payment', html: `
                <div class="payment-options">
                    <div class="payment-option" onclick="selectPaymentOption(this, 'cash')"><input type="radio" name="payment_type" value="cash"><label>Cash Payment</label></div>
                    <div class="payment-option" onclick="selectPaymentOption(this, 'online')"><input type="radio" name="payment_type" value="online"><label>Online Payment</label></div>
                </div>
                <div class="payment-upload" id="paymentUpload">
                    <div class="upload-btn" onclick="document.getElementById('proofOfPayment').click()"><i class="fas fa-upload mr-2"></i>Upload Proof</div>
                    <input type="file" id="proofOfPayment" accept="image/*" onchange="previewPaymentProof(this)">
                    <div class="upload-preview" id="uploadPreview"><img id="previewImage" src="" alt="Preview"></div>
                </div>`,
            showCancelButton: true, confirmButtonText: 'Pay Now', cancelButtonText: 'Cancel',
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
                const fd = new FormData();
                fd.append('action', 'process_payment'); fd.append('order_id', orderId);
                fd.append('payment_type', result.value.payment_type);
                if (result.value.proof_of_payment) fd.append('proof_of_payment', result.value.proof_of_payment);
                fetch('../backend/payment_functions.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(data => {
                    if (data.success) { Swal.fire({ title: 'Success!', text: 'Payment processed', icon: 'success', confirmButtonColor: '#D4AF37' }).then(() => checkNotifications()); }
                    else Swal.fire({ title: 'Error', text: data.message || 'Payment failed', icon: 'error', confirmButtonColor: '#D4AF37' });
                }).catch(() => Swal.fire({ title: 'Error', text: 'Payment error occurred', icon: 'error', confirmButtonColor: '#D4AF37' }));
            }
        });
    }

    const changePasswordModal = document.getElementById('changePasswordModal');
    const openCpBtn = document.getElementById('openChangePasswordModal');
    const closeCpBtn = document.getElementById('closeChangePasswordModal');
    const cpForm = document.getElementById('changePasswordModalForm');
    function closeChangePasswordModalFn() {
        if (!changePasswordModal) return;
        changePasswordModal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    function openChangePasswordModalFn() {
        if (!changePasswordModal) return;
        changePasswordModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        profileDropdown.classList.add('hidden');
        notificationPanel.classList.add('hidden');
    }
    if (openCpBtn) {
        openCpBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openChangePasswordModalFn();
        });
    }
    if (closeCpBtn) closeCpBtn.addEventListener('click', closeChangePasswordModalFn);
    if (changePasswordModal) {
        changePasswordModal.addEventListener('click', function(e) {
            if (e.target === changePasswordModal) closeChangePasswordModalFn();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && changePasswordModal && !changePasswordModal.classList.contains('hidden')) closeChangePasswordModalFn();
    });
    if (cpForm) {
        cpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const current_password = document.getElementById('cp_current_password').value;
            const new_password = document.getElementById('cp_new_password').value;
            const confirm_password = document.getElementById('cp_confirm_password').value;
            fetch('../backend/change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password, new_password, confirm_password })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Updated', text: data.message || 'Password updated', confirmButtonColor: '#D4AF37' });
                    cpForm.reset();
                    closeChangePasswordModalFn();
                } else {
                    Swal.fire({ icon: 'error', title: 'Could not update', text: data.message || 'Try again', confirmButtonColor: '#D4AF37' });
                }
            }).catch(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed', confirmButtonColor: '#D4AF37' });
            });
        });
    }
});
</script>
