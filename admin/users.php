<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Users</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/users.css">
    <link rel="stylesheet" href="../assets/css/zoryn-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Active Page Detection -->
    <script src="js/active-page.js"></script>
</head>
<body>
    <?php
    require_once '../backend/dbconn.php';

    function mask_email($email) {
        $em   = explode("@", $email);
        $name = $em[0];
        $len  = strlen($name);
        if ($len <= 2) {
            $masked = str_repeat('*', $len);
        } else {
            $masked = substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1);
        }
        return $masked . '@' . $em[1];
    }

    // Fetch all users
    $query = "SELECT user_id, username, full_name, email, role, created_at, updated_at, account_status 
              FROM users 
              ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    ?>

    <?php include("../navigation/admin-navbar.php");?>
    <?php include("../navigation/admin-sidebar.php");?>
    
    <div class="main-content">
        <div class="users-container">
            <div class="users-header">
                <h1>Users Management</h1>
                <div class="users-filter">
                    <select id="userFilter">
                        <option value="all">All Users</option>
                        <option value="admin">Admins</option>
                        <option value="cashier">Cashiers</option>
                        <option value="waiter">Waiters</option>
                        <option value="kitchen">Kitchen Crew</option>
                    </select>
                    <select id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="users-table-container">
                <div class="users-table-header">
                    <h2>User List</h2>
                    <button id="addUser" class="action-btn view">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <div class="email-container">
                                        <span class="masked-email"><?php echo htmlspecialchars(mask_email($user['email'])); ?></span>
                                        <span class="actual-email" style="display: none;"><?php echo htmlspecialchars($user['email']); ?></span>
                                        <button class="toggle-email" data-action="toggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['account_status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['account_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (in_array(strtolower($user['role']), ['cashier', 'admin'], true)): ?>
                                        <button class="action-btn view set-pin" data-user-id="<?php echo $user['user_id']; ?>" data-user-role="<?php echo htmlspecialchars(strtolower($user['role'])); ?>" title="Set PIN">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (strtolower($user['role']) === 'cashier'): ?>
                                        <button class="action-btn view set-shift" data-user-id="<?php echo $user['user_id']; ?>" data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>" title="Assign Shift">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="action-btn view edit-user" data-user-id="<?php echo $user['user_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn view delete-user" data-user-id="<?php echo $user['user_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="users-table-container" style="margin-top:16px;">
                <div class="users-table-header">
                    <h2>Cashier Shift Monitoring</h2>
                    <button id="refreshShiftList" class="action-btn view">
                        <i class="fas fa-rotate"></i> Refresh
                    </button>
                </div>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th>Shift Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Cash Count</th>
                            <th>Breakdown</th>
                            <th>Recorded At</th>
                        </tr>
                    </thead>
                    <tbody id="shiftListBody">
                        <tr><td colspan="8" style="text-align:center;">Loading shifts...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" required>
                            <option value="waiter">Waiter</option>
                            <option value="cashier">Cashier</option>
                            <option value="kitchen">Kitchen Crew</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <p class="text-sm text-gray-500" style="margin-top: 12px; line-height: 1.5;">
                        New accounts use the default password <strong>zoryn123</strong>. Ask the user to sign in and change it from the profile menu (Change Password).
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel">Cancel</button>
                <button class="btn btn-primary" id="submitAddUser">Add User</button>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id">
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_full_name">Full Name</label>
                        <input type="text" id="edit_full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" required>
                            <option value="waiter">Waiter</option>
                            <option value="cashier">Cashier</option>
                            <option value="kitchen">Kitchen Crew</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel">Cancel</button>
                <button class="btn btn-primary" id="submitEditUser">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete User</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel">Cancel</button>
                <button class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
    
    <style>
    .email-container {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .toggle-email {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--primary-bg);
        padding: 4px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .toggle-email:hover {
        background-color: rgba(99, 72, 50, 0.1);
    }

    .toggle-email.active {
        color: var(--success-color);
    }
    .zoryn-swal-popup {
        border: 1px solid rgba(212, 175, 55, 0.28) !important;
        border-radius: 16px !important;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35) !important;
    }
    .zoryn-swal-title {
        color: #D4AF37 !important;
        font-weight: 700 !important;
    }
    .zoryn-swal-html {
        color: #E5E5E5 !important;
    }
    .zoryn-cash-breakdown {
        text-align: left;
        margin-top: 4px;
        font-size: 14px;
    }
    .zoryn-cash-meta,
    .zoryn-cash-row,
    .zoryn-cash-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        margin-bottom: 8px;
        border: 1px solid rgba(212, 175, 55, 0.18);
        background: rgba(18, 18, 18, 0.65);
    }
    .zoryn-cash-meta span,
    .zoryn-cash-row span,
    .zoryn-cash-total span {
        color: #B0B0B0;
    }
    .zoryn-cash-meta strong,
    .zoryn-cash-row strong {
        color: #F5D76E;
        font-weight: 600;
    }
    .zoryn-cash-total {
        margin-top: 10px;
        border-color: rgba(212, 175, 55, 0.34);
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.22), rgba(184, 146, 30, 0.2));
    }
    .zoryn-cash-total strong {
        color: #111;
        font-size: 16px;
        font-weight: 700;
    }
    .zoryn-shift-form {
        text-align: left;
        margin-top: 4px;
    }
    .zoryn-shift-field {
        margin-bottom: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid rgba(212, 175, 55, 0.18);
        background: rgba(18, 18, 18, 0.65);
    }
    .zoryn-shift-field label {
        display: block;
        margin-bottom: 6px;
        color: #F5D76E;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .zoryn-shift-input {
        width: 100%;
        padding: 9px 10px;
        border-radius: 8px;
        border: 1px solid #2E2E2E;
        background: #1F1F1F;
        color: #E5E5E5;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        outline: none;
        box-sizing: border-box;
    }
    .zoryn-shift-input:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.18);
    }
    </style>

    <script>
    function toggleEmail(button) {
        const container = button.closest('.email-container');
        const maskedEmail = container.querySelector('.masked-email');
        const actualEmail = container.querySelector('.actual-email');
        
        if (maskedEmail.style.display !== 'none') {
            maskedEmail.style.display = 'none';
            actualEmail.style.display = 'inline';
            button.innerHTML = '<i class="fas fa-eye-slash"></i>';
            button.classList.add('active');
        } else {
            maskedEmail.style.display = 'inline';
            actualEmail.style.display = 'none';
            button.innerHTML = '<i class="fas fa-eye"></i>';
            button.classList.remove('active');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Modal Elements
        const addUserModal = document.getElementById('addUserModal');
        const editUserModal = document.getElementById('editUserModal');
        const deleteUserModal = document.getElementById('deleteUserModal');
        const closeButtons = document.querySelectorAll('.close-modal');
        const cancelButtons = document.querySelectorAll('.btn-cancel');

        // Show Add User Modal
        document.getElementById('addUser').addEventListener('click', function() {
            addUserModal.style.display = 'flex';
        });

        // Show Edit User Modal
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                
                // Fetch user data
                fetch(`../backend/get_user.php?id=${userId}`)
                    .then(response => response.json())
                    .then(user => {
                        document.getElementById('edit_user_id').value = user.user_id;
                        document.getElementById('edit_username').value = user.username;
                        document.getElementById('edit_full_name').value = user.full_name;
                        document.getElementById('edit_email').value = user.email;
                        document.getElementById('edit_role').value = user.role;
                        document.getElementById('edit_status').value = user.account_status;
                        
                        editUserModal.style.display = 'flex';
                    })
                    .catch(error => {
                        console.error('Error fetching user data:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch user data',
                            confirmButtonColor: '#D4AF37'
                        });
                    });
            });
        });

        // Show Delete User Modal
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                document.getElementById('confirmDelete').setAttribute('data-user-id', userId);
                deleteUserModal.style.display = 'flex';
            });
        });

        document.querySelectorAll('.set-pin').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userRole = this.getAttribute('data-user-role');
                const pinLabel = userRole === 'admin' ? 'Admin Override PIN' : 'Cashier PIN';

                Swal.fire({
                    title: `Set ${pinLabel}`,
                    html: `
                        <input id="newPin" class="swal2-input" type="password" inputmode="numeric" placeholder="Enter 4-8 digits" maxlength="8">
                        <input id="confirmPin" class="swal2-input" type="password" inputmode="numeric" placeholder="Confirm PIN" maxlength="8">
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Save PIN',
                    confirmButtonColor: '#D4AF37',
                    preConfirm: () => {
                        const pin = document.getElementById('newPin').value.trim();
                        const confirmPin = document.getElementById('confirmPin').value.trim();
                        if (!/^\d{4,8}$/.test(pin)) {
                            Swal.showValidationMessage('PIN must be 4 to 8 digits');
                            return false;
                        }
                        if (pin !== confirmPin) {
                            Swal.showValidationMessage('PIN does not match');
                            return false;
                        }
                        return { pin };
                    }
                }).then((result) => {
                    if (!result.isConfirmed || !result.value) return;
                    fetch('../backend/set_user_pin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: userId,
                            pin: result.value.pin
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Saved' : 'Error',
                            text: data.message || (data.success ? 'PIN updated' : 'Failed to save PIN'),
                            confirmButtonColor: '#D4AF37'
                        });
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to save PIN',
                            confirmButtonColor: '#D4AF37'
                        });
                    });
                });
            });
        });

        function loadShiftList() {
            fetch('../backend/shift_functions.php?action=get_admin_shift_list')
                .then(response => response.json())
                .then(data => {
                    const body = document.getElementById('shiftListBody');
                    if (!data.success) {
                        body.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#c0392b;">Failed to load shifts</td></tr>';
                        return;
                    }
                    if (!data.shifts || !data.shifts.length) {
                        body.innerHTML = '<tr><td colspan="8" style="text-align:center;">No shifts yet</td></tr>';
                        return;
                    }
                    body.innerHTML = data.shifts.map(shift => {
                        const hasCashCount = !!shift.cash_count_id;
                        const cashText = hasCashCount ? `P ${Number(shift.total_cash || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'Not submitted';
                        const breakdownBtn = hasCashCount
                            ? `<button class="action-btn view view-cash-breakdown"
                                 data-cashier="${(shift.cashier_name || '').replace(/"/g, '&quot;')}"
                                 data-shift-date="${shift.shift_date || ''}"
                                 data-1000="${Number(shift.count_1000 || 0)}"
                                 data-500="${Number(shift.count_500 || 0)}"
                                 data-100="${Number(shift.count_100 || 0)}"
                                 data-50="${Number(shift.count_50 || 0)}"
                                 data-20="${Number(shift.count_20 || 0)}"
                                 data-total="${Number(shift.total_cash || 0)}">
                                 <i class="fas fa-eye"></i> View
                               </button>`
                            : '<span style="color:#999;">-</span>';
                        return `
                            <tr>
                                <td>${shift.cashier_name || '-'}</td>
                                <td>${shift.shift_date || '-'}</td>
                                <td>${shift.start_time || '-'}</td>
                                <td>${shift.end_time || '-'}</td>
                                <td>${shift.status || 'scheduled'}</td>
                                <td>${cashText}</td>
                                <td>${breakdownBtn}</td>
                                <td>${shift.recorded_at || '-'}</td>
                            </tr>
                        `;
                    }).join('');

                    body.querySelectorAll('.view-cash-breakdown').forEach(button => {
                        button.addEventListener('click', function() {
                            const cashier = this.getAttribute('data-cashier') || 'Cashier';
                            const shiftDate = this.getAttribute('data-shift-date') || '-';
                            const c1000 = Number(this.getAttribute('data-1000') || 0);
                            const c500 = Number(this.getAttribute('data-500') || 0);
                            const c100 = Number(this.getAttribute('data-100') || 0);
                            const c50 = Number(this.getAttribute('data-50') || 0);
                            const c20 = Number(this.getAttribute('data-20') || 0);
                            const total = Number(this.getAttribute('data-total') || 0);

                            Swal.fire({
                                title: 'Cash Count Breakdown',
                                background: '#1F1F1F',
                                color: '#E5E5E5',
                                customClass: {
                                    popup: 'zoryn-swal-popup',
                                    title: 'zoryn-swal-title',
                                    htmlContainer: 'zoryn-swal-html'
                                },
                                html: `
                                    <div class="zoryn-cash-breakdown">
                                        <div class="zoryn-cash-meta"><span>Cashier</span><strong>${cashier}</strong></div>
                                        <div class="zoryn-cash-meta"><span>Shift Date</span><strong>${shiftDate}</strong></div>
                                        <div class="zoryn-cash-row"><span>P 1000 bills</span><strong>${c1000}</strong></div>
                                        <div class="zoryn-cash-row"><span>P 500 bills</span><strong>${c500}</strong></div>
                                        <div class="zoryn-cash-row"><span>P 100 bills</span><strong>${c100}</strong></div>
                                        <div class="zoryn-cash-row"><span>P 50 bills</span><strong>${c50}</strong></div>
                                        <div class="zoryn-cash-row"><span>P 20 bills</span><strong>${c20}</strong></div>
                                        <div class="zoryn-cash-total">
                                            <span>Total Cash</span>
                                            <strong>P ${total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                                        </div>
                                    </div>
                                `,
                                confirmButtonColor: '#D4AF37'
                            });
                        });
                    });
                })
                .catch(() => {
                    document.getElementById('shiftListBody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#c0392b;">Failed to load shifts</td></tr>';
                });
        }

        document.querySelectorAll('.set-shift').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const fullName = this.getAttribute('data-full-name') || 'Cashier';
                const today = new Date().toISOString().slice(0, 10);

                Swal.fire({
                    title: `Assign Shift: ${fullName}`,
                    background: '#1F1F1F',
                    color: '#E5E5E5',
                    customClass: {
                        popup: 'zoryn-swal-popup',
                        title: 'zoryn-swal-title',
                        htmlContainer: 'zoryn-swal-html'
                    },
                    html: `
                        <div class="zoryn-shift-form">
                            <div class="zoryn-shift-field">
                                <label for="shiftDate">Shift Date</label>
                                <input id="shiftDate" class="zoryn-shift-input" type="date" value="${today}">
                            </div>
                            <div class="zoryn-shift-field">
                                <label for="shiftStart">Start Time</label>
                                <input id="shiftStart" class="zoryn-shift-input" type="time" value="08:00">
                            </div>
                            <div class="zoryn-shift-field">
                                <label for="shiftEnd">End Time</label>
                                <input id="shiftEnd" class="zoryn-shift-input" type="time" value="17:00">
                            </div>
                            <div class="zoryn-shift-field">
                                <label for="shiftNotes">Notes (Optional)</label>
                                <input id="shiftNotes" class="zoryn-shift-input" type="text" placeholder="Add notes">
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Save Shift',
                    confirmButtonColor: '#D4AF37',
                    preConfirm: () => {
                        const shift_date = document.getElementById('shiftDate').value;
                        const start_time = document.getElementById('shiftStart').value;
                        const end_time = document.getElementById('shiftEnd').value;
                        const notes = document.getElementById('shiftNotes').value;
                        if (!shift_date || !start_time || !end_time) {
                            Swal.showValidationMessage('Date, start, and end time are required');
                            return false;
                        }
                        if (start_time >= end_time) {
                            Swal.showValidationMessage('End time must be later than start time');
                            return false;
                        }
                        return { user_id: userId, shift_date, start_time, end_time, notes };
                    }
                }).then((result) => {
                    if (!result.isConfirmed || !result.value) return;
                    fetch('../backend/shift_functions.php?action=assign_shift', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(result.value)
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Shift Saved' : 'Error',
                            text: data.message || 'Failed to save shift',
                            confirmButtonColor: '#D4AF37'
                        });
                        if (data.success) loadShiftList();
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save shift', confirmButtonColor: '#D4AF37' });
                    });
                });
            });
        });

        document.getElementById('refreshShiftList').addEventListener('click', loadShiftList);
        loadShiftList();

        // Close Modals
        function closeAllModals() {
            addUserModal.style.display = 'none';
            editUserModal.style.display = 'none';
            deleteUserModal.style.display = 'none';
        }

        closeButtons.forEach(button => {
            button.addEventListener('click', closeAllModals);
        });

        cancelButtons.forEach(button => {
            button.addEventListener('click', closeAllModals);
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                closeAllModals();
            }
        });

        // Add User
        document.getElementById('submitAddUser').addEventListener('click', function() {
            const username = document.getElementById('username').value;
            const full_name = document.getElementById('full_name').value;
            const email = document.getElementById('email').value;
            const role = document.getElementById('role').value;

            if (!username || !full_name || !email || !role) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'Please fill in all fields',
                    confirmButtonColor: '#D4AF37'
                });
                return;
            }

            fetch('../backend/add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username, full_name, email, role })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeAllModals();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'User added successfully',
                        confirmButtonColor: '#D4AF37'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to add user',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add user',
                    confirmButtonColor: '#D4AF37'
                });
            });
        });

        // Edit User
        document.getElementById('submitEditUser').addEventListener('click', function() {
            const userId = document.getElementById('edit_user_id').value;
            const username = document.getElementById('edit_username').value;
            const full_name = document.getElementById('edit_full_name').value;
            const email = document.getElementById('edit_email').value;
            const role = document.getElementById('edit_role').value;
            const status = document.getElementById('edit_status').value;

            if (!username || !full_name || !email || !role || !status) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'Please fill in all fields',
                    confirmButtonColor: '#D4AF37'
                });
                return;
            }

            fetch('../backend/update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    username,
                    full_name,
                    email,
                    role,
                    status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeAllModals();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'User updated successfully',
                        confirmButtonColor: '#D4AF37'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update user',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update user',
                    confirmButtonColor: '#D4AF37'
                });
            });
        });

        // Delete User
        document.getElementById('confirmDelete').addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');

            fetch('../backend/delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeAllModals();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'User deleted successfully',
                        confirmButtonColor: '#D4AF37'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete user',
                        confirmButtonColor: '#D4AF37'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete user',
                    confirmButtonColor: '#D4AF37'
                });
            });
        });

        // Filter functionality
        document.getElementById('userFilter').addEventListener('change', function() {
            filterUsers();
        });
        
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterUsers();
        });
        
        function filterUsers() {
            const roleFilter = document.getElementById('userFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const roleBadge = row.querySelector('td:nth-child(4) .status-badge');
                const statusBadge = row.querySelector('td:nth-child(5) .status-badge');
                
                const role = roleBadge ? roleBadge.textContent.trim().toLowerCase() : '';
                const status = statusBadge ? statusBadge.textContent.trim().toLowerCase() : '';
                
                const roleMatch = roleFilter === 'all' || role === roleFilter;
                const statusMatch = statusFilter === 'all' || status === statusFilter;
                
                row.style.display = roleMatch && statusMatch ? '' : 'none';
            });
        }

        // Add event delegation for email toggle buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.toggle-email')) {
                toggleEmail(e.target.closest('.toggle-email'));
            }
        });
    });
    </script>
</body>
</html>