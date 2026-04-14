<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Zoryn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verification-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        
        .verification-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .verification-header h1 {
            color: #6F4E37;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .verification-header p {
            color: #666;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .verification-form {
            margin-bottom: 20px;
        }
        
        .verification-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 5px;
            margin-bottom: 20px;
        }
        
        .verification-input:focus {
            border-color: #6F4E37;
            outline: none;
        }
        
        .verify-btn {
            width: 100%;
            padding: 12px;
            background-color: #6F4E37;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .verify-btn:hover {
            background-color: #5D4130;
        }
        
        .timer {
            text-align: center;
            color: #666;
            margin-top: 20px;
        }
        
        .resend-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .resend-link a {
            color: #6F4E37;
            text-decoration: none;
        }
        
        .resend-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <input type="hidden" id="timeLeft" value="<?php 
            $timeLeft = max(0, $_SESSION['2fa_expires'] - time());
            echo $timeLeft;
        ?>">
        <div class="verification-header">
            <h1>Two-Factor Authentication</h1>
            <p>We've sent a verification code to<br><strong><?php echo htmlspecialchars($email_masked); ?></strong></p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="verification-form" autocomplete="off">
            <input type="text" name="verification_code" class="verification-input" 
                   maxlength="6" inputmode="numeric" pattern="[0-9]*" 
                   autocomplete="one-time-code" required placeholder="Enter 6-digit code">
            
            <button type="submit" class="verify-btn">Verify Code</button>
        </form>
        
        <div class="timer">
            Code expires in: <span id="countdown">10:00</span>
        </div>
        
        <div class="resend-link">
            <a href="resend-2fa.php">Resend code</a>
        </div>
    </div>

    <script>
        // Countdown timer
        function startTimer(duration, display) {
            let timer = duration, minutes, seconds;
            const interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    window.location.href = "login.php?error=2fa_expired";
                }
            }, 1000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const timeLeft = parseInt(document.getElementById('timeLeft').value);
            const display = document.querySelector('#countdown');
            startTimer(timeLeft, display);

            // Handle form submission
            const form = document.querySelector('.verification-form');
            const verifyBtn = form.querySelector('.verify-btn');
            const errorMessage = document.querySelector('.error-message');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const verificationCode = form.querySelector('input[name="verification_code"]').value;
                
                if (!verificationCode) {
                    showError('Please enter the verification code');
                    return;
                }
                
                if (!/^\d{6}$/.test(verificationCode)) {
                    showError('Verification code must be 6 digits');
                    return;
                }
                
                verifyBtn.disabled = true;
                verifyBtn.textContent = 'Verifying...';
                
                try {
                    const response = await fetch('verify-2fa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `verification_code=${encodeURIComponent(verificationCode)}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = 'home.php';
                    } else {
                        showError(data.message || 'Invalid verification code');
                        verifyBtn.disabled = false;
                        verifyBtn.textContent = 'Verify Code';
                    }
                } catch (error) {
                    showError('An error occurred. Please try again.');
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify Code';
                }
            });
            
            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
            }
        });
    </script>
</body>
</html>