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