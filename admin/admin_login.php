<?php
session_start();

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once '../backend/dbconn.php';

// Initialize variables
$error = '';
$email = '';
$password = '';
$login_attempts = 0;
$last_attempt = 0;
$cooldown_time = 0;

// Check for brute force protection
if (isset($_SESSION['login_attempts'])) {
    $login_attempts = $_SESSION['login_attempts'];
    $last_attempt = $_SESSION['last_attempt'];
    $cooldown_time = 15; // 15 seconds cooldown after 3 failed attempts
    
    if ($login_attempts >= 3 && (time() - $last_attempt) < $cooldown_time) {
        $error = "Too many login attempts. Please wait " . ($cooldown_time - (time() - $last_attempt)) . " seconds.";
    }
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Check if user exists and is an admin
        $stmt = $conn->prepare("SELECT user_id, full_name, password, two_factor_enabled FROM users WHERE email = ? AND role = 'admin'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                // Password is correct
                $_SESSION['admin_id'] = $admin['user_id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                // Check if 2FA is enabled
                if ($admin['two_factor_enabled'] == 1) {
                    // Generate 6-digit code
                    $verification_code = sprintf('%06d', mt_rand(0, 999999));
                    
                    // Set expiration time (10 minutes from now)
                    $expires = time() + (10 * 60);
                    
                    // Store code in database
                    $stmt = $conn->prepare("UPDATE users SET twofa_code = ?, twofa_expires = FROM_UNIXTIME(?), last_2fa_sent = NOW(), two_factor_attempts = 0 WHERE user_id = ?");
                    $stmt->bind_param("sii", $verification_code, $expires, $admin['user_id']);
                    
                    if ($stmt->execute()) {
                        // Set session variables
                        $_SESSION['2fa_expires'] = $expires;
                        $_SESSION['2fa_pending'] = true;
                        
                        // Send verification email
                        require_once '../users/email_functions.php';
                        if (sendVerificationEmail($admin['full_name'], $email, $verification_code)) {
                            header("Location: 2fa.php");
                            exit();
                        } else {
                            $error = "Failed to send verification email. Please try again.";
                        }
                    } else {
                        $error = "Failed to set up 2FA verification. Please try again.";
                    }
                } else {
                    // No 2FA required, proceed to dashboard
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = "Invalid email or password";
                $_SESSION['login_attempts'] = $login_attempts + 1;
                $_SESSION['last_attempt'] = time();
            }
        } else {
            $error = "Invalid email or password";
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_attempt'] = time();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Zoryn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
    <style>
    .password-container {
        position: relative;
        width: 100%;
    }

    .password-container input {
        width: 100%;
        padding: 12px 40px 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }

    .password-container input:focus {
        border-color: #6F4E37;
        outline: none;
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #666;
        padding: 5px;
        z-index: 2;
    }

    .toggle-password:hover {
        color: #333;
    }

    .toggle-password i {
        font-size: 16px;
    }

    .countdown-timer {
        background-color: #f8d7da;
        color: #721c24;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: 500;
    }
    .login-btn:disabled, input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .error-message {
        color: #721c24;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h1>Welcome back Admin!</h1>
            <p class="subtitle">Please enter your details</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($login_attempts >= 3): ?>
                <div class="countdown-timer" id="countdown">
                    Account locked for <span id="countdown-number"><?php echo $cooldown_time - (time() - $last_attempt); ?></span> seconds
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" required <?php echo $login_attempts >= 3 ? 'disabled' : ''; ?>>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required <?php echo $login_attempts >= 3 ? 'disabled' : ''; ?>>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="options-row">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" <?php echo $login_attempts >= 3 ? 'disabled' : ''; ?>>
                        <label for="remember">Remember for 30 days</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Forget password</a>
                </div>
                
                <button type="submit" class="login-btn" <?php echo $login_attempts >= 3 ? 'disabled' : ''; ?>>Login</button>
                
                <button type="button" class="google-btn" <?php echo $login_attempts >= 3 ? 'disabled' : ''; ?>>
                    <img src="../assets/zoryn/google.png" alt="Google" class="google-icon">
                    Sign in with Google
                </button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="admin_signup.php">Register here</a></p>
            </div>
        </div>
        
        <div class="brand-container">
            <div class="logo-container">
                <img src="../assets/zoryn/logo.png" alt="Zoryn Logo" class="logo">
            </div>
            <h2 class="brand-name">ZORYN</h2>
            <img src="../assets/zoryn/login_header.png" alt="Coffee Illustration" class="coffee-illustration">
        </div>
    </div>
    <script>
        <?php if ($login_attempts >= 3): ?>
        // Countdown timer functionality
        let timeLeft = <?php echo $cooldown_time - (time() - $last_attempt); ?>;
        const countdownElement = document.getElementById('countdown-number');
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const rememberCheckbox = document.getElementById('remember');
        const loginButton = document.querySelector('.login-btn');
        const googleButton = document.querySelector('.google-btn');
        
        const countdown = setInterval(function() {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                document.querySelector('.countdown-timer').style.display = 'none';
                
                // Enable all form elements
                emailInput.disabled = false;
                passwordInput.disabled = false;
                rememberCheckbox.disabled = false;
                loginButton.disabled = false;
                googleButton.disabled = false;
            }
        }, 1000);
        <?php endif; ?>

        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleButton = passwordInput.nextElementSibling;
            const icon = toggleButton.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>