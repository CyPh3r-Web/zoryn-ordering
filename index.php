<?php
session_start();

// Redirect already-authenticated users
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit();
}
if (isset($_SESSION['user_id']) && empty($_SESSION['2fa_pending'])) {
    header("Location: users/home.php");
    exit();
}

require_once 'backend/dbconn.php';

$error        = '';
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$last_attempt   = $_SESSION['last_attempt']   ?? 0;
$cooldown_time  = 15;

// Clear expired lock
if (isset($_SESSION['lock_until']) && $_SESSION['lock_until'] <= time()) {
    unset($_SESSION['lock_until'], $_SESSION['login_attempts'], $_SESSION['last_attempt']);
    $login_attempts = 0;
}

$is_locked = ($login_attempts >= 3 && (time() - $last_attempt) < $cooldown_time);

// ── Process POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, username, full_name, password, role,
                    account_status, two_factor_enabled
             FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_attempt']   = time();

        } elseif ($user['account_status'] !== 'active') {
            switch ($user['account_status']) {
                case 'pending':
                    $_SESSION['pending_email'] = $email;
                    $error = 'Your account is not verified. '
                           . '<a href="users/verify-account.php" class="text-[#F5D76E] font-semibold underline hover:text-white transition">Verify it here</a>.';
                    break;
                case 'suspended':
                    $error = 'Your account has been suspended. Please contact support.';
                    break;
                case 'banned':
                    $error = 'Your account has been banned. Please contact support.';
                    break;
                default:
                    $error = 'Your account is not active. Please contact support.';
            }
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_attempt']   = time();

        } else {
            // ── Credentials OK ────────────────────────────────────────────────
            unset($_SESSION['login_attempts'], $_SESSION['last_attempt']);

            $is_admin = ($user['role'] === 'admin');

            if ($user['two_factor_enabled'] == 1) {
                $code    = sprintf('%06d', mt_rand(0, 999999));
                $expires = time() + 600;

                $upd = $conn->prepare(
                    "UPDATE users
                     SET twofa_code = ?, twofa_expires = FROM_UNIXTIME(?),
                         last_2fa_sent = NOW(), two_factor_attempts = 0
                     WHERE user_id = ?"
                );
                $upd->bind_param("sii", $code, $expires, $user['user_id']);
                $upd->execute();

                $_SESSION['user_id']     = $user['user_id'];
                $_SESSION['2fa_expires'] = $expires;
                $_SESSION['2fa_pending'] = true;
                $_SESSION['2fa_role']    = $user['role'];

                $fn_path = $is_admin ? 'admin/email_functions.php' : 'users/email_functions.php';
                require_once $fn_path;
                sendVerificationEmail($user['full_name'], $email, $code);

                $redirect = $is_admin ? 'admin/2fa.php' : 'users/2fa.php';
                header("Location: $redirect");
                exit();

            } else {
                if ($is_admin) {
                    $_SESSION['admin_id']   = $user['user_id'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    header("Location: admin/dashboard.php");
                } else {
                    $_SESSION['user_id']   = $user['user_id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role']      = $user['role'];

                    if ($remember) {
                        setcookie('remember_user', $user['user_id'], time() + (86400 * 30), "/");
                    }
                    header("Location: users/home.php");
                }
                exit();
            }
        }
    }
}

$seconds_left = ($is_locked) ? max(0, $cooldown_time - (time() - $last_attempt)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Zoryn</title>
    <link rel="icon" type="image/jpeg" href="assets/zoryn/zoryn.jpg">
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
                            black: '#0D0D0D',
                            dark: '#121212',
                            gray: '#1F1F1F',
                            'gray-light': '#2A2A2A',
                            border: '#2E2E2E',
                            gold: '#D4AF37',
                            'gold-light': '#F5D76E',
                            'gold-muted': '#B8921E',
                            'gold-pale': '#F4D26B',
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
        .login-bg {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
                        url('assets/zoryn/background.jpg') center/cover no-repeat fixed;
        }
        .brand-gradient {
            background: linear-gradient(160deg, #F4D26B 0%, #D4AF37 45%, #B8921E 100%);
        }
        .gold-gradient-btn {
            background: linear-gradient(135deg, #F4D26B, #C99B2A);
        }
        .gold-gradient-btn:hover {
            background: linear-gradient(135deg, #FFDF7D, #D3A533);
        }
        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #181818 inset !important;
            -webkit-text-fill-color: #F5D76E !important;
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4 font-poppins">

<div class="flex w-full max-w-[900px] rounded-2xl overflow-hidden border border-z-gold/30 shadow-2xl animate-[fadeIn_0.5s_ease]">

    <!-- Left: Login Form -->
    <div class="flex-1 bg-gradient-to-b from-[#151515] to-[#090909] p-10 flex flex-col justify-center">
        
        <!-- Logo for mobile (hidden on desktop) -->
        <div class="flex justify-center mb-6 lg:hidden">
            <img src="assets/zoryn/zoryn.jpg" alt="Zoryn" class="w-20 h-20 rounded-xl object-cover">
        </div>

        <h1 class="text-2xl font-bold text-z-gold-pale mb-1">Welcome back!</h1>
        <p class="text-sm text-z-gold-pale/60 mb-8 font-light">Please enter your details to continue</p>

        <?php if ($error): ?>
            <div class="bg-z-gold/10 border border-z-gold/30 rounded-xl px-4 py-3 mb-6 text-sm text-z-gold-light animate-[slideDown_0.3s_ease]">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-z-gold"></i>
                    <span><?= $error ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($is_locked): ?>
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 mb-6 text-center text-sm text-red-400" id="countdown">
                <i class="fas fa-lock mr-2"></i>
                Account locked for <span id="countdown-number" class="font-bold text-red-300"><?= $seconds_left ?></span> seconds
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" class="space-y-5">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-medium text-z-gold-pale/80 mb-2 uppercase tracking-wider">Email address</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-z-gold/50">
                        <i class="fas fa-envelope text-sm"></i>
                    </span>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           <?= $is_locked ? 'disabled' : '' ?>
                           class="w-full pl-11 pr-4 py-3 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm placeholder:text-z-border focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all duration-300 disabled:opacity-50"
                           placeholder="you@example.com">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-medium text-z-gold-pale/80 mb-2 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-z-gold/50">
                        <i class="fas fa-lock text-sm"></i>
                    </span>
                    <input type="password" id="password" name="password" required
                           <?= $is_locked ? 'disabled' : '' ?>
                           class="w-full pl-11 pr-12 py-3 bg-z-dark border border-z-border rounded-xl text-z-gold-light text-sm placeholder:text-z-border focus:border-z-gold focus:ring-2 focus:ring-z-gold/20 outline-none transition-all duration-300 disabled:opacity-50"
                           placeholder="••••••••">
                    <button type="button" onclick="togglePassword('password')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-z-gold/50 hover:text-z-gold-light transition p-1">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Options Row -->
            <div class="flex items-center justify-between text-xs">
                <div></div>
                <a href="users/forgot-password.php" class="text-z-gold-pale/70 hover:text-z-gold-light transition">Forgot password?</a>
            </div>

            <!-- Login Button -->
            <button type="submit" 
                    <?= $is_locked ? 'disabled' : '' ?>
                    class="w-full py-3 gold-gradient-btn text-z-black font-semibold rounded-xl shadow-lg shadow-z-gold/20 hover:shadow-z-gold/30 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>
    </div>

    <!-- Right: Brand Panel -->
    <div class="hidden lg:flex flex-1 brand-gradient flex-col items-center justify-center p-10 relative overflow-hidden">
        <div class="relative z-10 text-center">
            <img src="assets/zoryn/zoryn.jpg" alt="Zoryn" class="w-48 h-48 object-cover mx-auto mb-6">
            <h2 class="text-3xl font-bold text-[#1a0e00] tracking-[4px] mb-3">ZORYN</h2>
            <p class="text-[#1a0e00]/70 text-sm font-medium">Restaurant Ordering System</p>
        </div>
    </div>

</div>

<script>
    <?php if ($is_locked): ?>
    let timeLeft = <?= $seconds_left ?>;
    const countdownEl  = document.getElementById('countdown-number');
    const countdownBox = document.getElementById('countdown');
    const inputs       = document.querySelectorAll('#loginForm input, #loginForm button[type="submit"]');

    const timer = setInterval(() => {
        timeLeft--;
        countdownEl.textContent = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(timer);
            countdownBox.style.display = 'none';
            inputs.forEach(el => el.disabled = false);
        }
    }, 1000);
    <?php endif; ?>

    function togglePassword(id) {
        const inp  = document.getElementById(id);
        const icon = inp.parentElement.querySelector('.fa-eye, .fa-eye-slash');
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye',       !show);
        icon.classList.toggle('fa-eye-slash',  show);
    }
</script>
</body>
</html>
