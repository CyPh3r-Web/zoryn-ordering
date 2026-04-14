<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$verification_sent = false;

// First, we need to add PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Function to generate random verification code
function generateVerificationCode() {
    return rand(1000, 9999); // 4-digit code
}

// Function to send verification email
function sendVerificationEmail($email, $full_name, $verification_code) {
    // Load Composer's autoloader
    require '../vendor/autoload.php';

    // Instantiate PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'zoryn@gmail.com'; 
        $mail->Password   = 'gvlg skgp lcwk zdzf'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('zoryn@gmail.com', 'Zoryn');
        $mail->addAddress($email, $full_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Zoryn - Email Verification Code';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #6F4E37; color: white; padding: 20px; text-align: center;'>
                    <h1>Zoryn</h1>
                </div>
                <div style='padding: 20px; border: 1px solid #e0e0e0; border-top: none;'>
                    <h2>Email Verification</h2>
                    <p>Hello $full_name,</p>
                    <p>Thank you for registering with Zoryn. To complete your registration, please use the verification code below:</p>
                    <div style='background-color: #f8f8f8; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0;'>
                        $verification_code
                    </div>
                    <p>This code will expire in 30 minutes.</p>
                    <p>If you did not request this verification, please ignore this email.</p>
                    <p>Best regards,<br>Zoryn Team</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #777;'>
                    &copy; " . date('Y') . " Zoryn. All rights reserved.
                </div>
            </div>
        ";
        $mail->AltBody = "Hello $full_name,\n\nYour verification code is: $verification_code\n\nThis code will expire in 30 minutes.\n\nIf you did not request this verification, please ignore this email.\n\nBest regards,\nZoryn Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../backend/dbconn.php';
    
    // Check if it's the verification form
    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        // Debug: Log verification attempt
        error_log("Verification attempt - POST data: " . print_r($_POST, true));
        
        // Verify the code
        if (isset($_SESSION['temp_user_data']) && isset($_POST['verification_code'])) {
            $temp_data = $_SESSION['temp_user_data'];
            $entered_code = trim($_POST['verification_code']);
            $user_id = $temp_data['user_id'];
            
            // Debug: Log verification data
            error_log("Verification data - User ID: " . $user_id);
            error_log("Entered code: " . $entered_code);
            
            // Get the stored verification code from database
            $stmt = $conn->prepare("SELECT verification_code, verification_expires FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Debug: Log database verification data
                error_log("Database verification data: " . print_r($user, true));
                
                if ($entered_code == $user['verification_code']) {
                    // Check if code is expired
                    if (strtotime($user['verification_expires']) < time()) {
                        $error = 'Verification code has expired. Please request a new one.';
                        $verification_sent = true;
                    } else {
                        // Activate the account
                        $stmt = $conn->prepare("UPDATE users SET account_status = 'active' WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        
                        if ($stmt->execute()) {
                            // Debug: Log successful activation
                            error_log("Account activated successfully for user ID: " . $user_id);
                            
                            // Clear session data
                            unset($_SESSION['temp_user_data']);
                            $success = 'Account verified and activated successfully! You can now login.';
                            
                            // Show success message and redirect to login page
                            echo '<div class="success-message" style="text-align: center; padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 20px 0;">
                                    <h3>Verification Successful!</h3>
                                    <p>Your account has been verified and activated. You will be redirected to the login page in 3 seconds.</p>
                                    <p>If you are not redirected, <a href="login.php">click here</a> to login.</p>
                                  </div>';
                            
                            // Redirect to login page after 3 seconds
                            header("refresh:3;url=login.php");
                            exit();
                        } else {
                            $error = 'Account activation failed: ' . $stmt->error;
                            error_log("Account activation failed: " . $stmt->error);
                        }
                    }
                } else {
                    $error = 'Invalid verification code. Please try again.';
                    $verification_sent = true;
                    error_log("Invalid verification code entered. Expected: " . $user['verification_code'] . ", Got: " . $entered_code);
                }
            } else {
                $error = 'User not found in database.';
                error_log("User not found in database. User ID: " . $user_id);
                $verification_sent = true;
            }
        } else {
            $error = 'Session expired or verification code missing. Please register again.';
            error_log("Session expired during verification or code missing");
        }
    } 
    // Handle resend verification code request
    elseif (isset($_POST['action']) && $_POST['action'] === 'resend') {
        if (isset($_SESSION['temp_user_data'])) {
            $temp_data = $_SESSION['temp_user_data'];
            $email = $temp_data['email'];
            $full_name = $temp_data['full_name'];
            $user_id = $temp_data['user_id'];
            
            // Generate new verification code
            $verification_code = generateVerificationCode();
            $verification_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            
            // Update verification code in database
            $stmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_expires = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $verification_code, $verification_expires, $user_id);
            
            if ($stmt->execute()) {
                // Update session data
                $temp_data['verification_code'] = $verification_code;
                $_SESSION['temp_user_data'] = $temp_data;
                
                // Send new verification email
                if (sendVerificationEmail($email, $full_name, $verification_code)) {
                    $success = 'Verification code has been resent to your email.';
                    error_log("Verification code resent successfully to: " . $email . ", Code: " . $verification_code);
                } else {
                    $error = 'Failed to send verification email. Please try again.';
                    error_log("Failed to resend verification email to: " . $email);
                }
            } else {
                $error = 'Failed to update verification code. Please try again.';
                error_log("Failed to update verification code in database: " . $stmt->error);
            }
            
            $verification_sent = true;
        } else {
            $error = 'Session expired. Please register again.';
            error_log("Session expired during resend attempt");
        }
    }
    // Handle initial registration form
    else {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate input
        if (empty($username) || empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            // Check if email or username already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email or username already registered';
            } else {
                // Generate verification code
                $verification_code = generateVerificationCode();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $current_time = date('Y-m-d H:i:s');
                $verification_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $role = "user";
                $account_status = "pending";
                
                // Insert user with pending status
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role, created_at, updated_at, verification_code, verification_expires, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $username, $full_name, $email, $hashed_password, $role, $current_time, $current_time, $verification_code, $verification_expires, $account_status);
                
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    error_log("User registered successfully. ID: " . $user_id . ", Email: " . $email . ", Code: " . $verification_code);
                    
                    // Store user data in session for verification
                    $_SESSION['temp_user_data'] = [
                        'user_id' => $user_id,
                        'email' => $email,
                        'full_name' => $full_name,
                        'verification_code' => $verification_code
                    ];
                    
                    // Send verification email
                    if (sendVerificationEmail($email, $full_name, $verification_code)) {
                        $verification_sent = true;
                        $success = 'A verification code has been sent to your email. Please check your inbox.';
                    } else {
                        $error = 'Failed to send verification email. Please try again.';
                        error_log("Failed to send verification email to: " . $email);
                    }
                } else {
                    $error = 'Registration failed: ' . $stmt->error;
                    error_log("Registration failed: " . $stmt->error);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Zoryn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/register.css">
    <style>
        /* Add additional styles for verification form */
        .verification-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .verification-code {
            letter-spacing: 8px;
            font-size: 24px;
            padding: 10px;
            width: 160px;
            text-align: center;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .resend-link {
            display: block;
            margin-top: 10px;
            color: #6F4E37;
            text-decoration: none;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
        .timer {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
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
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-form">
            <?php if ($verification_sent): ?>
                <!-- Verification Form -->
                <h1>Verify Email</h1>
                <p class="subtitle">Enter the 4-digit code sent to <?php echo isset($_SESSION['temp_user_data']['email']) ? htmlspecialchars($_SESSION['temp_user_data']['email']) : ''; ?></p>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="verification-container">
                        <input type="text" class="verification-code" name="verification_code" maxlength="4" pattern="[0-9]{4}" required autofocus>
                        <div class="timer" id="timer">Resend available in <span id="countdown">60</span> seconds</div>
                    </div>
                    
                    <button type="submit" class="signup-btn">Verify</button>
                </form>
                
                <form method="POST" action="" id="resendForm" style="display: none; text-align: center;">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="resend-link" id="resendButton" disabled>Resend Code</button>
                </form>
                
                <div class="login-link">
                    <p><a href="register.php">Back to Registration</a></p>
                </div>
                
                <script>
                    // Wait for the DOM to be fully loaded
                    document.addEventListener('DOMContentLoaded', function() {
                        // Countdown timer for resend code
                        let countdown = 60;
                        const timerElement = document.getElementById('countdown');
                        const resendButton = document.getElementById('resendButton');
                        const resendForm = document.getElementById('resendForm');
                        
                        if (timerElement && resendButton && resendForm) {
                            // Display resend form
                            resendForm.style.display = 'block';
                            
                            const timer = setInterval(() => {
                                countdown--;
                                timerElement.textContent = countdown;
                                
                                if (countdown <= 0) {
                                    clearInterval(timer);
                                    if (document.getElementById('timer')) {
                                        document.getElementById('timer').style.display = 'none';
                                    }
                                    resendButton.disabled = false;
                                }
                            }, 1000);
                        }
                    });
                </script>
            <?php else: ?>
                <!-- Registration Form -->
                <h1>Create Account</h1>
                <p class="subtitle">Join Zoryn community today</p>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-hint">Password must be at least 8 characters long</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="signup-btn">Register</button>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            <?php endif; ?>
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