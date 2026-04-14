<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow access during 2FA verification
if (isset($_SESSION['2fa_pending']) && $_SESSION['2fa_pending']) {
    // Don't redirect if in 2FA verification process
} else if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

// Get user profile picture
require_once '../backend/dbconn.php';
$profilePicture = null;
$userEmail = null;

try {
    $stmt = $conn->prepare("SELECT username, profile_picture, email FROM users WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }
    
    $user = $result->fetch_assoc();
    if ($user) {
        $_SESSION['username'] = $user['username']; // Set username in session
        $profilePicture = $user['profile_picture'] ?? null;
        $userEmail = $user['email'] ?? null;
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}
?>
<nav class="navbar">
    <div class="nav-left">
        <!-- Add sidebar toggle button here -->
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <img src="../assets/zoryn/logo.png" alt="Zoryn Logo" class="logo">
    </div>
    <div class="nav-right">
        <div class="nav-icons">
            <div class="notification-container">
                <img src="../assets/zoryn/bell.png" alt="Notifications" class="icon notification-icon" id="notificationIcon">
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                <div class="notification-panel" id="notificationPanel">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <div class="notification-actions-header">
                            <label class="select-all-container">
                                <input type="checkbox" id="selectAllNotifications">
                                <span>Select All</span>
                            </label>
                            <button class="bulk-delete-btn" id="bulkDeleteBtn" style="display: none;">Delete Selected</button>
                            <span class="close-notifications">&times;</span>
                        </div>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <!-- Notifications will be added here dynamically -->
                    </div>
                </div>
            </div>
            <a href="order-details.php" class="icon-link" title="Cart">
            <img src="../assets/zoryn/cart.png" alt="Cart" class="icon">
            </a>

            <a href="home.php" class="icon-link" title="Back to Home">
                <img src="../assets/zoryn/home.png" alt="Home" class="icon">
            </a>
        </div>
        <div class="user-profile" id="userProfile">
            <div class="profile-pic">
                <?php if ($profilePicture): ?>
                    <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
                <?php endif; ?>
            </div>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <div class="profile-info">
                        <div class="profile-pic-large">
                            <?php if ($profilePicture): ?>
                                <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="email"><?php echo $userEmail ? htmlspecialchars($userEmail) : ''; ?></span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        Profile Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="settings-2fa.php" class="settings-link">
                        <i class="fas fa-shield-alt"></i> Two-Factor Authentication
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item text-danger" onclick="confirmLogout(event)">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
/* Reset any conflicting styles */
.navbar {
    background-color: #A68B6F;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.sidebar-toggle {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    margin-right: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.sidebar-toggle:hover {
    transform: scale(1.1);
}

.sidebar-toggle i {
    filter: brightness(0) invert(1);
}

.nav-left {
    display: flex;
    align-items: center;
}

.logo {
    height: 40px;
    width: auto;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.nav-icons {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.icon {
    width: 24px;
    height: 24px;
    cursor: pointer;
    transition: transform 0.3s ease;
    filter: brightness(0) invert(1);
}

.icon:hover {
    transform: scale(1.1);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    transition: all 0.2s ease;
}

.user-profile:hover {
    background-color: rgba(255, 255, 255, 0.25);
}

.user-profile span {
    color: #ffffff;
    font-weight: 500;
}

.profile-pic {
    width: 35px;
    height: 35px;
    background-color: #ffffff;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

/* Keep the notification styles */
.notification-container {
    position: relative;
    display: inline-block;
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: linear-gradient(135deg, #ff4b4b, #dc3545);
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: 600;
    display: none;
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
    border: 1.5px solid white;
}

.notification-panel {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    width: 400px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    z-index: 1000;
    max-height: 600px;
    overflow-y: auto;
    margin-top: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    animation: slideIn 0.3s ease-out;
}

.notification-header {
    padding: 20px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    background: rgba(255,255,255,0.95);
    border-radius: 16px 16px 0 0;
    backdrop-filter: blur(10px);
}

.notification-header h3 {
    margin: 0;
    font-size: 20px;
    color: #1a1a1a;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.notification-actions-header {
    display: flex;
    align-items: center;
    gap: 16px;
}

.select-all-container {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #4a4a4a;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.2s;
    background: rgba(0,0,0,0.03);
}

.select-all-container:hover {
    background: rgba(0,0,0,0.06);
}

.select-all-container input[type="checkbox"] {
    margin: 0;
    cursor: pointer;
    width: 18px;
    height: 18px;
    accent-color: #3c2415;
    border-radius: 4px;
}

.bulk-delete-btn {
    background: linear-gradient(135deg, #ff4b4b, #dc3545);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(220, 53, 69, 0.2);
}

.bulk-delete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
}

.close-notifications {
    cursor: pointer;
    font-size: 22px;
    color: #4a4a4a;
    padding: 6px;
    border-radius: 50%;
    transition: all 0.2s;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-notifications:hover {
    background: rgba(0,0,0,0.06);
    color: #1a1a1a;
}

.notification-list {
    padding: 12px;
}

.notification-item {
    padding: 16px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    font-size: 14px;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    border-radius: 12px;
    margin: 6px 0;
    background: white;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-checkbox {
    margin-top: 2px;
    width: 16px;
    height: 16px;
    accent-color: #3c2415;
    border-radius: 4px;
}

.notification-content {
    flex: 1;
}

.notification-item.unread {
    background: linear-gradient(to right, rgba(60, 36, 21, 0.05), transparent);
    font-weight: 600;
    border-left: 4px solid #3c2415;
}

.notification-item.read {
    background: white;
    color: #4a4a4a;
}

.notification-item:hover {
    background: rgba(0,0,0,0.02);
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.notification-actions {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.mark-read-btn {
    background: linear-gradient(135deg, #3c2415, #5d4037);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(60, 36, 21, 0.2);
}

.mark-read-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(60, 36, 21, 0.3);
}

.feedback-btn {
    background: linear-gradient(135deg, #28a745, #218838);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(40, 167, 69, 0.2);
}

.feedback-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(40, 167, 69, 0.3);
}

.delete-btn {
    background: linear-gradient(135deg, #ff4b4b, #dc3545);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(220, 53, 69, 0.2);
}

.delete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
}

.notification-time {
    font-size: 13px;
    color: #666;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.notification-time::before {
    content: "•";
    color: #ccc;
}

.message {
    margin-bottom: 10px;
    line-height: 1.5;
    color: #1a1a1a;
}

.no-notifications {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
    background: rgba(0,0,0,0.02);
    border-radius: 12px;
    margin: 12px;
    font-size: 15px;
}

/* Custom scrollbar for notification panel */
.notification-panel::-webkit-scrollbar {
    width: 8px;
}

.notification-panel::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.02);
    border-radius: 4px;
}

.notification-panel::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 4px;
}

.notification-panel::-webkit-scrollbar-thumb:hover {
    background: rgba(0,0,0,0.2);
}

/* Animation for notification panel */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 320px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(60, 36, 21, 0.15);
    display: none;
    z-index: 1000;
    margin-top: 12px;
    overflow: hidden;
    padding: 0;
    animation: slideDown 0.3s ease-out;
}

.profile-dropdown.active {
    display: block;
}

.dropdown-header {
    padding: 24px 24px 12px 24px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.profile-pic-large {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
    background: #e9ecef;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.profile-pic-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-details .username {
    font-weight: 600;
    color: #2d3436;
    font-size: 1.1rem;
}

.user-details .email {
    color: #636e72;
    font-size: 0.95rem;
}

.dropdown-menu {
    padding: 8px 0;
    background: #fff;
}

.dropdown-item,
.settings-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 24px;
    color: #2d3436;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
    font-size: 1rem;
    cursor: pointer;
}

.dropdown-item:hover,
.settings-link:hover {
    background: #f8f9fa;
    color: #3c2415;
    text-decoration: none;
}

.dropdown-item i,
.settings-link i {
    width: 20px;
    color: #636e72;
    font-size: 1.1rem;
}

.dropdown-divider {
    height: 1px;
    background: #eee;
    margin: 0 24px;
}

.text-danger {
    color: #dc3545 !important;
}

.text-danger i {
    color: #dc3545 !important;
}

.profile-pic img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* Payment Modal Styles */
.payment-modal {
    max-width: 500px !important;
}

.payment-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 20px 0;
}

.payment-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-option:hover {
    border-color: #3c2415;
    background-color: #f8f9fa;
}

.payment-option.selected {
    border-color: #3c2415;
    background-color: #f8f9fa;
}

.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
    accent-color: #3c2415;
}

.payment-option label {
    flex: 1;
    cursor: pointer;
    font-weight: 500;
}

.payment-upload {
    display: none;
    margin-top: 15px;
    padding: 15px;
    border: 1px dashed #ddd;
    border-radius: 8px;
    text-align: center;
}

.payment-upload.active {
    display: block;
}

.payment-upload input[type="file"] {
    display: none;
}

.upload-btn {
    display: inline-block;
    padding: 10px 20px;
    background: #3c2415;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-btn:hover {
    background: #5d4037;
}

.upload-preview {
    margin-top: 10px;
    max-width: 200px;
    display: none;
}

.upload-preview img {
    width: 100%;
    border-radius: 4px;
}

.pay-btn {
    background: linear-gradient(135deg, #3c2415, #5d4037);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 20px;
}

.pay-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(60, 36, 21, 0.2);
}

.pay-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
</style>

<script>
// Move these functions outside of DOMContentLoaded
window.selectPaymentOption = function(element, type) {
    // Remove selected class from all options
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.querySelector('input[type="radio"]').checked = false;
    });
    
    // Add selected class to clicked option
    element.classList.add('selected');
    element.querySelector('input[type="radio"]').checked = true;
    
    // Show/hide upload section
    const uploadSection = document.getElementById('paymentUpload');
    if (type === 'online') {
        uploadSection.classList.add('active');
    } else {
        uploadSection.classList.remove('active');
    }
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
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    // Sidebar toggle functionality
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        // Store the state in localStorage
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });

    // Function to add a new notification
    function addNotification(notification) {
        const notificationItem = document.createElement('div');
        notificationItem.className = `notification-item ${notification.is_read ? 'read' : 'unread'}`;
        notificationItem.dataset.notificationId = notification.id;
        
        const actionsHtml = `
            <div class="notification-actions">
                ${!notification.is_read ? 
                    `<button class="mark-read-btn" onclick="markNotificationAsRead(${notification.id}, this)">Mark as Read</button>` 
                    : ''}
                ${notification.message.includes('is now being prepared') && notification.payment_status !== 'verified' ? 
                    `<button class="feedback-btn" onclick="processPayment(${notification.order_id})">Proceed to Payment</button>` 
                    : ''}
                ${notification.message.includes('has been completed') ? 
                    `<button class="feedback-btn" onclick="checkFeedbackAndRedirect(${notification.order_id})">Give Feedback</button>` 
                    : ''}
                <button class="delete-btn" onclick="deleteNotification(${notification.id}, this)">Delete</button>
            </div>
        `;
        
        notificationItem.innerHTML = `
            <input type="checkbox" class="notification-checkbox" onchange="updateBulkDeleteButton()">
            <div class="notification-content">
                <div class="message">${notification.message}</div>
                <div class="notification-time">${new Date(notification.created_at).toLocaleString()}</div>
                ${actionsHtml}
            </div>
        `;
        
        notificationList.insertBefore(notificationItem, notificationList.firstChild);
    }

    // Function to mark a notification as read
    window.markNotificationAsRead = function(notificationId, buttonElement) {
        fetch('../backend/notification_functions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_notification_read&notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the notification item UI
                const notificationItem = buttonElement.closest('.notification-item');
                notificationItem.classList.remove('unread');
                notificationItem.classList.add('read');
                
                // Only remove the mark as read button
                const markReadBtn = buttonElement.closest('.mark-read-btn');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
                
                // Update badge count
                updateBadgeCount();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    // Function to update badge count
    function updateBadgeCount() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        notificationBadge.textContent = unreadCount;
        notificationBadge.style.display = unreadCount > 0 ? 'block' : 'none';
    }

    // Function to check for new notifications
    function checkNotifications() {
        fetch('../backend/notification_functions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_notifications'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear existing notifications
                notificationList.innerHTML = '';
                
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<div class="no-notifications">No notifications yet</div>';
                } else {
                    // Add new notifications
                    data.notifications.forEach(notification => {
                        addNotification(notification);
                    });
                }
                
                // Update badge count
                updateBadgeCount();
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
        });
    }

    // Toggle notification panel
    notificationIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationPanel.style.display = notificationPanel.style.display === 'block' ? 'none' : 'block';
        if (notificationPanel.style.display === 'block') {
            checkNotifications();
        }
    });

    // Close notification panel when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationPanel.contains(e.target) && e.target !== notificationIcon) {
            notificationPanel.style.display = 'none';
        }
    });

    // Close button functionality
    closeNotifications.addEventListener('click', function() {
        notificationPanel.style.display = 'none';
    });

    // Check for notifications every 30 seconds
    setInterval(checkNotifications, 30000);
    
    // Initial check
    checkNotifications();

    // Add this function before the closing script tag
    window.checkFeedbackAndRedirect = function(orderId) {
        fetch('../backend/order_functions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=check_feedback_exists&order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.has_feedback) {
                Swal.fire({
                    title: 'Feedback Already Submitted',
                    text: 'You have already submitted feedback for this order.',
                    icon: 'info',
                    confirmButtonColor: '#5d4037'
                });
            } else {
                window.location.href = `../users/feedback.php?order_id=${orderId}`;
            }
        })
        .catch(error => {
            console.error('Error checking feedback status:', error);
            Swal.fire({
                title: 'Error',
                text: 'Failed to check feedback status',
                icon: 'error',
                confirmButtonColor: '#5d4037'
            });
        });
    }

    // Add this function before the closing script tag
    window.deleteNotification = function(notificationId, buttonElement) {
        Swal.fire({
            title: 'Delete Notification',
            text: 'Are you sure you want to delete this notification?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../backend/notification_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_notification&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the notification item from the UI
                        const notificationItem = buttonElement.closest('.notification-item');
                        notificationItem.remove();
                        
                        // Update badge count
                        updateBadgeCount();
                        
                        // Show success message
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Notification has been deleted.',
                            icon: 'success',
                            confirmButtonColor: '#5d4037'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to delete notification',
                            icon: 'error',
                            confirmButtonColor: '#5d4037'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error deleting notification:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to delete notification',
                        icon: 'error',
                        confirmButtonColor: '#5d4037'
                    });
                });
            }
        });
    }

    // Function to update bulk delete button visibility
    window.updateBulkDeleteButton = function() {
        const checkedBoxes = document.querySelectorAll('.notification-checkbox:checked');
        bulkDeleteBtn.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.notification-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkDeleteButton();
    });

    // Bulk delete functionality
    bulkDeleteBtn.addEventListener('click', function() {
        const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked'))
            .map(checkbox => checkbox.closest('.notification-item').dataset.notificationId);

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Delete Notifications',
            text: `Are you sure you want to delete ${selectedIds.length} notification(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Delete notifications one by one
                const deletePromises = selectedIds.map(id => 
                    fetch('../backend/notification_functions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete_notification&notification_id=${id}`
                    }).then(response => response.json())
                );

                Promise.all(deletePromises)
                    .then(results => {
                        const success = results.every(result => result.success);
                        if (success) {
                            // Remove all selected notifications from UI
                            selectedIds.forEach(id => {
                                const item = document.querySelector(`.notification-item[data-notification-id="${id}"]`);
                                if (item) item.remove();
                            });
                            
                            // Update badge count
                            updateBadgeCount();
                            
                            // Reset select all checkbox
                            selectAllCheckbox.checked = false;
                            
                            // Hide bulk delete button
                            bulkDeleteBtn.style.display = 'none';
                            
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Selected notifications have been deleted.',
                                icon: 'success',
                                confirmButtonColor: '#5d4037'
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: 'Some notifications could not be deleted.',
                                icon: 'error',
                                confirmButtonColor: '#5d4037'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting notifications:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to delete notifications',
                            icon: 'error',
                            confirmButtonColor: '#5d4037'
                        });
                    });
            }
        });
    });

    userProfile.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('active');
    });

    document.addEventListener('click', function(e) {
        if (!profileDropdown.contains(e.target) && e.target !== userProfile) {
            profileDropdown.classList.remove('active');
        }
    });

    // Add logout confirmation function
    window.confirmLogout = function(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Logout Confirmation',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, logout!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../users/logout.php';
            }
        });
    }

    // Add this function to handle payment
    window.processPayment = function(orderId) {
        Swal.fire({
            title: 'Payment Details',
            html: `
                <div class="payment-options">
                    <div class="payment-option" onclick="selectPaymentOption(this, 'cash')">
                        <input type="radio" name="payment_type" id="cash" value="cash">
                        <label for="cash">Cash Payment</label>
                    </div>
                    <div class="payment-option" onclick="selectPaymentOption(this, 'online')">
                        <input type="radio" name="payment_type" id="online" value="online">
                        <label for="online">Online Payment</label>
                    </div>
                </div>
                <div class="payment-upload" id="paymentUpload">
                    <div class="upload-btn" onclick="document.getElementById('proofOfPayment').click()">
                        Upload Proof of Payment
                    </div>
                    <input type="file" id="proofOfPayment" accept="image/*" onchange="previewPaymentProof(this)">
                    <div class="upload-preview" id="uploadPreview">
                        <img id="previewImage" src="" alt="Payment Proof Preview">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Proceed to Payment',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3c2415',
            cancelButtonColor: '#6c757d',
            customClass: {
                popup: 'payment-modal'
            },
            didOpen: () => {
                // Initialize payment form
                document.querySelector('.payment-option').click();
            },
            preConfirm: () => {
                const paymentType = document.querySelector('input[name="payment_type"]:checked')?.value;
                if (!paymentType) {
                    Swal.showValidationMessage('Please select a payment method');
                    return false;
                }
                
                if (paymentType === 'online' && !document.getElementById('proofOfPayment').files[0]) {
                    Swal.showValidationMessage('Please upload proof of payment');
                    return false;
                }
                
                return {
                    payment_type: paymentType,
                    proof_of_payment: document.getElementById('proofOfPayment').files[0]
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'process_payment');
                formData.append('order_id', orderId);
                formData.append('payment_type', result.value.payment_type);
                if (result.value.proof_of_payment) {
                    formData.append('proof_of_payment', result.value.proof_of_payment);
                }
                
                fetch('../backend/payment_functions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Payment has been processed successfully',
                            icon: 'success',
                            confirmButtonColor: '#3c2415'
                        }).then(() => {
                            // Refresh notifications
                            checkNotifications();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to process payment',
                            icon: 'error',
                            confirmButtonColor: '#3c2415'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while processing payment',
                        icon: 'error',
                        confirmButtonColor: '#3c2415'
                    });
                });
            }
        });
    }
});
</script>