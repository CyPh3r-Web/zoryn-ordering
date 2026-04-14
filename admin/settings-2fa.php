<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once '../backend/dbconn.php';
require_once 'email_functions.php';

// Initialize variables
$user_id = $_SESSION['admin_id'];
$message = '';
$alert_type = '';
$two_factor_enabled = false;
$show_verification = false;
$email = '';
$full_name = '';

// Get current 2FA status
$stmt = $conn->prepare("SELECT email, two_factor_enabled, full_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $two_factor_enabled = ($user['two_factor_enabled'] == 1);
    $email = $user['email'];
    $full_name = $user['full_name'];
} else {
    $message = "Error retrieving user data";
    $alert_type = "danger";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_2fa'])) {
        // Enable 2FA
        if (!$two_factor_enabled) {
            // Generate a 6-digit code for verification
            $verification_code = sprintf('%06d', mt_rand(0, 999999));
            
            // Store code in session
            $_SESSION['enable_2fa_code'] = password_hash($verification_code, PASSWORD_DEFAULT);
            $_SESSION['enable_2fa_expires'] = time() + 600; // 10 minutes
            
            // Send verification email
            sendVerificationEmail($full_name, $email, $verification_code);
            
            // Show verification form
            $show_verification = true;
        }
        
    } else if (isset($_POST['disable_2fa'])) {
        // Disable 2FA - require password confirmation
        $password = $_POST['current_password'] ?? '';
        
        if (empty($password)) {
            $message = "Please enter your password to disable 2FA";
            $alert_type = "danger";
        } else {
            // Verify password
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Password correct, disable 2FA
                $stmt = $conn->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $message = "Two-factor authentication has been disabled";
                    $alert_type = "success";
                    $two_factor_enabled = false;
                } else {
                    $message = "Error disabling two-factor authentication";
                    $alert_type = "danger";
                }
            } else {
                $message = "Incorrect password";
                $alert_type = "danger";
            }
        }
        
    } else if (isset($_POST['verify_2fa_code'])) {
        // Verify the code entered by user
        $verification_code = $_POST['verification_code'] ?? '';
        
        if (empty($verification_code)) {
            $message = "Please enter the verification code";
            $alert_type = "danger";
            $show_verification = true;
        } else if (!isset($_SESSION['enable_2fa_code']) || !isset($_SESSION['enable_2fa_expires'])) {
            $message = "Verification session expired. Please try again";
            $alert_type = "danger";
        } else if (time() > $_SESSION['enable_2fa_expires']) {
            $message = "Verification code expired. Please try again";
            $alert_type = "danger";
            unset($_SESSION['enable_2fa_code']);
            unset($_SESSION['enable_2fa_expires']);
        } else if (password_verify($verification_code, $_SESSION['enable_2fa_code'])) {
            // Code is valid, enable 2FA
            $stmt = $conn->prepare("UPDATE users SET two_factor_enabled = 1, twofa_code = ?, twofa_expires = FROM_UNIXTIME(?) WHERE user_id = ?");
            $expires = time() + (10 * 60); // 10 minutes from now
            $stmt->bind_param("sii", $verification_code, $expires, $user_id);
            
            if ($stmt->execute()) {
                $message = "Two-factor authentication has been enabled";
                $alert_type = "success";
                $two_factor_enabled = true;
                
                // Clean up session
                unset($_SESSION['enable_2fa_code']);
                unset($_SESSION['enable_2fa_expires']);
                $show_verification = false;
            } else {
                $message = "Error enabling two-factor authentication";
                $alert_type = "danger";
                $show_verification = true;
            }
        } else {
            $message = "Invalid verification code";
            $alert_type = "danger";
            $show_verification = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Settings - Zoryn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/dashboard-charts.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

     <!-- SweetAlert2 CSS and JS -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.all.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .card-header h2 {
            color: #6F4E37;
            font-size: 24px;
            font-weight: 600;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #6F4E37;
            outline: none;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #6F4E37;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5D4130;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .status-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .status-enabled {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-disabled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .verification-input {
            font-size: 20px;
            letter-spacing: 10px;
            text-align: center;
        }
        
        .info-box {
            background-color: #e2f0fd;
            border: 1px solid #b8daff;
            color: #004085;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .info-box p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../navigation/admin-navbar.php'; ?>
    <?php include '../navigation/admin-sidebar.php'; ?>
    <br>
    <br>
    <br>    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Two-Factor Authentication</h2>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>Enhance Your Account Security</h3>
                <p>Two-factor authentication adds an extra layer of security to your account by requiring a verification code in addition to your password when logging in.</p>
                <p>When enabled, we'll send a unique code to your email address each time you sign in.</p>
            </div>
            
            <div class="form-group">
                <h3>
                    Status: 
                    <?php if ($two_factor_enabled): ?>
                        <span class="status-indicator status-enabled">Enabled</span>
                    <?php else: ?>
                        <span class="status-indicator status-disabled">Disabled</span>
                    <?php endif; ?>
                </h3>
            </div>
            
            <?php if ($show_verification): ?>
                <!-- Verification form for enabling 2FA -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="verification_code">Enter the verification code sent to your email:</label>
                        <input type="text" id="verification_code" name="verification_code" class="form-control verification-input" 
                               maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="verify_2fa_code" class="btn btn-primary">Verify & Enable 2FA</button>
                    </div>
                </form>
            <?php elseif ($two_factor_enabled): ?>
                <!-- Form to disable 2FA -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Enter your password to disable 2FA:</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="disable_2fa" class="btn btn-danger">Disable Two-Factor Authentication</button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Form to enable 2FA -->
                <form method="POST" action="">
                    <div class="form-group">
                        <p>A verification code will be sent to your email address: <strong><?php echo $email; ?></strong></p>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="enable_2fa" class="btn btn-primary">Enable Two-Factor Authentication</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Include your site footer here -->
</body>
</html>