<?php
session_start();
require_once '../backend/dbconn.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$error = '';
$success = '';
$verification_sent = false;
$email = '';
$user_data = null;

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
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        
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
        error_log("Email sent successfully to: " . $email);
        return true;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        error_log("SMTP Debug: " . $mail->ErrorInfo);
        return false;
    }
}

// Check if we have a pending email from a login attempt
if (isset($_SESSION['pending_email'])) {
    $email = $_SESSION['pending_email'];
    
    // Get user information
    $stmt = $conn->prepare("SELECT user_id, full_name, account_status FROM users WHERE email = ? AND account_status = 'pending'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
    } else {
        $error = "Invalid account or account already verified.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle email submission
    if (isset($_POST['action']) && $_POST['action'] === 'submit_email') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = "Please enter your email address.";
        } else {
            // Check if email exists and account is pending
            $stmt = $conn->prepare("SELECT user_id, full_name, account_status FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                
                if ($user_data['account_status'] === 'active') {
                    $error = "This account is already verified. Please login.";
                } else if ($user_data['account_status'] === 'pending') {
                    // Generate new verification code
                    $verification_code = generateVerificationCode();
                    $verification_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    
                    // Update verification code in database
                    $stmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_expires = ? WHERE user_id = ?");
                    $stmt->bind_param("ssi", $verification_code, $verification_expires, $user_data['user_id']);
                    
                    if ($stmt->execute()) {
                        // Store user data in session for verification
                        $_SESSION['temp_user_data'] = [
                            'user_id' => $user_data['user_id'],
                            'email' => $email,
                            'full_name' => $user_data['full_name'],
                            'verification_code' => $verification_code
                        ];
                        
                        // Send verification email
                        if (sendVerificationEmail($email, $user_data['full_name'], $verification_code)) {
                            $verification_sent = true;
                            $success = 'A verification code has been sent to your email. Please check your inbox.';
                            error_log("Verification code sent successfully to: " . $email . ", Code: " . $verification_code);
                        } else {
                            $error = 'Failed to send verification email. Please try again.';
                            error_log("Failed to send verification email to: " . $email);
                        }
                    } else {
                        $error = 'Failed to update verification code. Please try again.';
                        error_log("Failed to update verification code in database: " . $stmt->error);
                    }
                } else {
                    $error = "This account cannot be verified. Please contact support.";
                }
            } else {
                $error = "Email not found. Please check your email or register a new account.";
            }
        }
    }
    // Handle verification code submission
    else if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        if (isset($_SESSION['temp_user_data']) && isset($_POST['verification_code'])) {
            $temp_data = $_SESSION['temp_user_data'];
            $entered_code = trim($_POST['verification_code']);
            $user_id = $temp_data['user_id'];
            
            // Debug: Log verification attempt
            error_log("Verification attempt - User ID: " . $user_id);
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
            $error = 'Session expired or verification code missing. Please try again.';
            error_log("Session expired during verification or code missing");
        }
    }
    // Handle resend verification code
    else if (isset($_POST['action']) && $_POST['action'] === 'resend') {
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
                    $success = 'A new verification code has been sent to your email.';
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
            $error = 'Session expired. Please try again.';
            error_log("Session expired during resend attempt");
        }
    }
}

// If we have valid user data, prepare to show verification form
if ($user_data && $user_data['account_status'] === 'pending') {
    $verification_sent = true;
    
    // If no error message but we have user data, maybe this is the first visit
    if (empty($error) && empty($success)) {
        $success = 'Please enter the verification code sent to your email.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - Zoryn</title>
    <link rel="icon" type="image/jpeg" href="../assets/zoryn/zoryn.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <style>
        /* Additional styles for verification page */
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
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            padding: 0;
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
        .login-form {
            min-height: 400px;
        }
        .verify-btn {
            background-color: #6F4E37;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .verify-btn:hover {
            background-color: #5d4230;
        }
    </style>
</head>
<body class="auth-page auth-login">
    <div class="login-container">
        <div class="login-form">
            <h1>Verify Account</h1>
            <p class="subtitle">Complete your registration</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($verification_sent): ?>
                <!-- Verification Code Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="verification-container">
                        <p>Enter the 4-digit code sent to <?php echo htmlspecialchars($email); ?></p>
                        <input type="text" class="verification-code" name="verification_code" maxlength="4" pattern="[0-9]{4}" required autofocus>
                        <div class="timer" id="timer">Resend available in <span id="countdown">60</span> seconds</div>
                    </div>
                    
                    <button type="submit" class="verify-btn">Verify</button>
                </form>
                
                <form method="POST" action="" id="resendForm" style="display: none; text-align: center;">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="resend-link" id="resendButton" disabled>Resend Code</button>
                </form>
            <?php else: ?>
                <!-- Email Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="submit_email">
                    
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <button type="submit" class="verify-btn">Continue</button>
                </form>
            <?php endif; ?>
            
            <div class="register-link" style="margin-top: 20px;">
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
        
        <div class="brand-container">
            <div class="logo-container">
                <img src="../assets/zoryn/zoryn_logo.jpg" alt="Zoryn Logo" class="logo">
            </div>
            <h2 class="brand-name">ZORYN</h2>
            <img src="../assets/zoryn/zoryn.jpg" alt="Coffee Illustration" class="coffee-illustration">
        </div>
    </div>

    <?php if ($verification_sent): ?>
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
    <?php endif; ?>
</body>
</html>