<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$verification_sent = false;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function generateVerificationCode() {
    return rand(1000, 9999);
}

function sendVerificationEmail($email, $full_name, $verification_code) {
    require '../vendor/autoload.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'zoryn@gmail.com';
        $mail->Password   = 'gvlg skgp lcwk zdzf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('zoryn@gmail.com', 'Zoryn');
        $mail->addAddress($email, $full_name);
        $mail->isHTML(true);
        $mail->Subject = 'Zoryn - Email Verification Code';
        $mail->Body = "
            <div style='font-family: Poppins, Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #D4AF37, #B8921E); color: #0D0D0D; padding: 30px; text-align: center; border-radius: 12px 12px 0 0;'>
                    <h1 style='margin:0; font-size: 28px; letter-spacing: 3px;'>ZORYN</h1>
                </div>
                <div style='padding: 30px; background: #1F1F1F; color: #B0B0B0; border: 1px solid #2E2E2E; border-top: none;'>
                    <h2 style='color: #D4AF37; margin-bottom: 10px;'>Email Verification</h2>
                    <p>Hello $full_name,</p>
                    <p>Thank you for registering with Zoryn. Use the verification code below:</p>
                    <div style='background: #121212; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; margin: 20px 0; border-radius: 12px; color: #D4AF37; letter-spacing: 8px; border: 1px solid #2E2E2E;'>
                        $verification_code
                    </div>
                    <p>This code will expire in 30 minutes.</p>
                    <p style='color: #888;'>If you did not request this verification, please ignore this email.</p>
                </div>
                <div style='background: #121212; padding: 15px; text-align: center; font-size: 12px; color: #888; border-radius: 0 0 12px 12px; border: 1px solid #2E2E2E; border-top: none;'>
                    &copy; " . date('Y') . " Zoryn. All rights reserved.
                </div>
            </div>
        ";
        $mail->AltBody = "Hello $full_name,\n\nYour verification code is: $verification_code\n\nThis code will expire in 30 minutes.\n\nBest regards,\nZoryn Team";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../backend/dbconn.php';

    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        if (isset($_SESSION['temp_user_data']) && isset($_POST['verification_code'])) {
            $temp_data = $_SESSION['temp_user_data'];
            $entered_code = trim($_POST['verification_code']);
            $user_id = $temp_data['user_id'];

            $stmt = $conn->prepare("SELECT verification_code, verification_expires FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($entered_code == $user['verification_code']) {
                    if (strtotime($user['verification_expires']) < time()) {
                        $error = 'Verification code has expired. Please request a new one.';
                        $verification_sent = true;
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET account_status = 'active' WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        if ($stmt->execute()) {
                            unset($_SESSION['temp_user_data']);
                            $success = 'Account verified successfully! Redirecting to login...';
                            header("refresh:3;url=login.php");
                            exit();
                        } else {
                            $error = 'Account activation failed. Please try again.';
                        }
                    }
                } else {
                    $error = 'Invalid verification code. Please try again.';
                    $verification_sent = true;
                }
            } else {
                $error = 'User not found. Please register again.';
                $verification_sent = true;
            }
        } else {
            $error = 'Session expired. Please register again.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'resend') {
        if (isset($_SESSION['temp_user_data'])) {
            $temp_data = $_SESSION['temp_user_data'];
            $email = $temp_data['email'];
            $full_name = $temp_data['full_name'];
            $user_id = $temp_data['user_id'];
            $verification_code = generateVerificationCode();
            $verification_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $stmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_expires = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $verification_code, $verification_expires, $user_id);
            if ($stmt->execute()) {
                $temp_data['verification_code'] = $verification_code;
                $_SESSION['temp_user_data'] = $temp_data;
                if (sendVerificationEmail($email, $full_name, $verification_code)) {
                    $success = 'Verification code has been resent to your email.';
                } else {
                    $error = 'Failed to send verification email. Please try again.';
                }
            } else {
                $error = 'Failed to update verification code. Please try again.';
            }
            $verification_sent = true;
        } else {
            $error = 'Session expired. Please register again.';
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $error = 'Email or username already registered';
            } else {
                $verification_code = generateVerificationCode();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $current_time = date('Y-m-d H:i:s');
                $verification_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $role = "waiter";
                $account_status = "pending";
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role, created_at, updated_at, verification_code, verification_expires, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $username, $full_name, $email, $hashed_password, $role, $current_time, $current_time, $verification_code, $verification_expires, $account_status);
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    $_SESSION['temp_user_data'] = [
                        'user_id' => $user_id,
                        'email' => $email,
                        'full_name' => $full_name,
                        'verification_code' => $verification_code
                    ];
                    if (sendVerificationEmail($email, $full_name, $verification_code)) {
                        $verification_sent = true;
                        $success = 'A verification code has been sent to your email.';
                    } else {
                        $error = 'Failed to send verification email. Please try again.';
                    }
                } else {
                    $error = 'Registration failed: ' . $stmt->error;
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
    <title>Register – Zoryn</title>
    <link rel="icon" type="image/jpeg" href="../assets/zoryn/zoryn.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .brand-gradient { background: linear-gradient(160deg, #F4D26B 0%, #D4AF37 45%, #B8921E 100%); }
        .gold-btn { background: linear-gradient(135deg, #F4D26B, #C99B2A); }
        .gold-btn:hover { background: linear-gradient(135deg, #FFDF7D, #D3A533); }
        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #121212 inset !important;
            -webkit-text-fill-color: #F5D76E !important;
        }
    </style>
</head>
<body class="bg-gradient-to-b from-[#1a1a1a] via-z-dark to-z-black min-h-screen flex items-center justify-center p-4 font-poppins">

<div class="flex w-full max-w-[920px] rounded-2xl overflow-hidden border border-z-gold/30 shadow-2xl animate-[fadeIn_0.5s_ease]">

    <!-- Left: Form -->
    <div class="flex-1 bg-gradient-to-b from-[#151515] to-[#090909] p-8 lg:p-10 overflow-y-auto max-h-[90vh]">

        <?php if ($verification_sent): ?>
        <!-- Verification Form -->
        <div class="text-center">
            <div class="w-16 h-16 bg-z-gold/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-envelope-open-text text-2xl text-z-gold"></i>
            </div>
            <h1 class="text-2xl font-bold text-z-gold-pale mb-2">Verify Email</h1>
            <p class="text-sm text-z-gold-pale/60 mb-8">Enter the 4-digit code sent to <br><span class="text-z-gold-light font-medium"><?= isset($_SESSION['temp_user_data']['email']) ? htmlspecialchars($_SESSION['temp_user_data']['email']) : '' ?></span></p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 mb-5 text-sm text-red-400 text-center">
                <i class="fas fa-exclamation-circle mr-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl px-4 py-3 mb-5 text-sm text-green-400 text-center">
                <i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-5">
            <input type="hidden" name="action" value="verify">
            <div class="flex justify-center">
                <input type="text" name="verification_code" maxlength="4" pattern="[0-9]{4}" required autofocus
                       class="w-40 text-center text-3xl tracking-[12px] py-3 bg-z-dark border border-z-border rounded-xl text-z-gold-light focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all font-bold"
                       placeholder="····">
            </div>
            <p class="text-center text-xs text-z-gold-pale/50" id="timer">
                Resend available in <span id="countdown" class="font-bold text-z-gold-pale/80">60</span>s
            </p>
            <button type="submit" class="w-full py-3 gold-btn text-z-black font-semibold rounded-xl shadow-lg shadow-z-gold/20 hover:-translate-y-0.5 transition-all">
                <i class="fas fa-check-circle mr-2"></i>Verify
            </button>
        </form>

        <form method="POST" action="" id="resendForm" class="mt-3 hidden">
            <input type="hidden" name="action" value="resend">
            <button type="submit" id="resendButton" disabled
                    class="w-full py-2.5 bg-transparent border border-z-gold/30 text-z-gold-pale/70 rounded-xl text-sm hover:bg-z-gold/5 hover:border-z-gold/50 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="fas fa-redo mr-2"></i>Resend Code
            </button>
        </form>

        <p class="text-center mt-6 text-xs text-z-gold-pale/60">
            <a href="register.php" class="text-z-gold-pale font-medium hover:text-z-gold-light transition"><i class="fas fa-arrow-left mr-1"></i>Back to Registration</a>
        </p>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let countdown = 60;
            const timerElement = document.getElementById('countdown');
            const resendButton = document.getElementById('resendButton');
            const resendForm = document.getElementById('resendForm');
            if (timerElement && resendButton && resendForm) {
                resendForm.classList.remove('hidden');
                const timer = setInterval(() => {
                    countdown--;
                    timerElement.textContent = countdown;
                    if (countdown <= 0) {
                        clearInterval(timer);
                        document.getElementById('timer').style.display = 'none';
                        resendButton.disabled = false;
                    }
                }, 1000);
            }
        });
        </script>

        <?php else: ?>
        <!-- Registration Form -->
        <div class="flex justify-center mb-5 lg:hidden">
            <img src="../assets/zoryn/zoryn.jpg" alt="Zoryn" class="w-16 h-16 rounded-xl object-cover">
        </div>
        <h1 class="text-2xl font-bold text-z-gold-pale mb-1">Create Account</h1>
        <p class="text-sm text-z-gold-pale/60 mb-6 font-light">Join the Zoryn community today</p>

        <?php if ($error): ?>
            <div class="bg-z-gold/10 border border-z-gold/30 rounded-xl px-4 py-3 mb-5 text-sm text-z-gold-light">
                <i class="fas fa-exclamation-circle mr-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl px-4 py-3 mb-5 text-sm text-green-400">
                <i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label for="username" class="block text-xs font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">Username</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-z-gold/50"><i class="fas fa-user text-sm"></i></span>
                    <input type="text" id="username" name="username" required
                           class="w-full pl-11 pr-4 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all" placeholder="Choose a username">
                </div>
            </div>
            <div>
                <label for="full_name" class="block text-xs font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">Full Name</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-z-gold/50"><i class="fas fa-id-card text-sm"></i></span>
                    <input type="text" id="full_name" name="full_name" required
                           class="w-full pl-11 pr-4 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all" placeholder="Your full name">
                </div>
            </div>
            <div>
                <label for="email" class="block text-xs font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">Email address</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-z-gold/50"><i class="fas fa-envelope text-sm"></i></span>
                    <input type="email" id="email" name="email" required
                           class="w-full pl-11 pr-4 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all" placeholder="you@example.com">
                </div>
            </div>
            <div>
                <label for="password" class="block text-xs font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-z-gold/50"><i class="fas fa-lock text-sm"></i></span>
                    <input type="password" id="password" name="password" required
                           class="w-full pl-11 pr-12 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all" placeholder="Minimum 8 characters">
                    <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-z-gold/50 hover:text-z-gold-light transition p-1">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <div>
                <label for="confirm_password" class="block text-xs font-medium text-z-gold-pale/80 mb-1.5 uppercase tracking-wider">Confirm Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-z-gold/50"><i class="fas fa-lock text-sm"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           class="w-full pl-11 pr-12 py-2.5 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all" placeholder="Repeat your password">
                    <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-z-gold/50 hover:text-z-gold-light transition p-1">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full py-3 gold-btn text-z-black font-semibold rounded-xl shadow-lg shadow-z-gold/20 hover:-translate-y-0.5 transition-all mt-2">
                <i class="fas fa-user-plus mr-2"></i>Register
            </button>
        </form>

        <p class="text-center mt-6 text-xs text-z-gold-pale/60">
            Already have an account? <a href="login.php" class="text-z-gold-pale font-semibold hover:text-z-gold-light transition">Login here</a>
        </p>
        <?php endif; ?>
    </div>

    <!-- Right: Brand Panel -->
    <div class="hidden lg:flex flex-1 brand-gradient flex-col items-center justify-center p-10 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 right-10 w-40 h-40 border border-black/10 rounded-full"></div>
            <div class="absolute bottom-20 left-10 w-60 h-60 border border-black/10 rounded-full"></div>
        </div>
        <div class="relative z-10 text-center">
            <div class="w-40 h-40 mx-auto mb-6">
                <img src="../assets/zoryn/zoryn_logo.jpg" alt="Zoryn Logo" class="w-full h-full object-contain rounded-2xl shadow-xl">
            </div>
            <h2 class="text-3xl font-bold text-[#1a0e00] tracking-[4px] mb-3">ZORYN</h2>
            <img src="../assets/zoryn/zoryn.jpg" alt="Coffee" class="w-3/5 mx-auto mt-4 rounded-xl shadow-lg object-contain">
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const inp = document.getElementById(inputId);
    const icon = inp.parentElement.querySelector('.fa-eye, .fa-eye-slash');
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !show);
    icon.classList.toggle('fa-eye-slash', show);
}
</script>
</body>
</html>
