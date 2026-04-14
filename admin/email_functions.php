<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function sendVerificationEmail($name, $email, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'zoryn@gmail.com'; 
        $mail->Password = 'gvlg skgp lcwk zdzf';  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@zoryn.com', 'Zoryn');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Zoryn - 2FA Verification Code';
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: 'Arial', sans-serif; }
                .container { padding: 20px; max-width: 600px; margin: 0 auto; }
                .header { background-color: #6F4E37; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .code { font-size: 24px; font-weight: bold; text-align: center; padding: 15px; 
                        background-color: #f8f8f8; margin: 20px 0; letter-spacing: 5px; }
                .footer { font-size: 12px; color: #666; text-align: center; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Zoryn Security</h2>
                </div>
                <div class='content'>
                    <p>Hello $name,</p>
                    <p>Your verification code is:</p>
                    <div class='code'>$code</div>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn't request this code, please ignore this email or contact support if you believe this is unauthorized activity.</p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " Zoryn. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->Body = $message;
        $mail->AltBody = "Your Zoryn verification code is: $code\nThis code will expire in 10 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
} 