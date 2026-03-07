<?php
session_start();
include 'userdb.php';

// Set JSON header for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $isAjax = true;
} else {
    $isAjax = false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['success' => false, 'message' => 'Please enter a valid email address.'];
        if ($isAjax) {
            echo json_encode($response);
            exit();
        } else {
            header("Location: index.php?error=" . urlencode($response['message']));
            exit();
        }
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Don't reveal if email exists for security
        $response = ['success' => true, 'message' => 'If an account exists with this email, you will receive a password reset link.'];
        if ($isAjax) {
            echo json_encode($response);
            exit();
        } else {
            header("Location: index.php?success=" . urlencode($response['message']));
            exit();
        }
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $user_name = $user['name'];
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Delete any existing tokens for this user
    $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Insert new token
    $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iss", $user_id, $token, $expires);
    
    if ($insert_stmt->execute()) {
        // Generate reset link
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
        
        // Send email using PHPMailer
        $emailSent = sendResetEmail($email, $user_name, $reset_link);
        
        if ($emailSent) {
            $response = [
                'success' => true, 
                'message' => 'Password reset link has been sent to your email.',
                'reset_link' => $reset_link
            ];
        } else {
            // Email failed but token was created - show link in development
            $response = [
                'success' => true, 
                'message' => 'Password reset link generated. (Email sending failed - showing link for development)',
                'reset_link' => $reset_link
            ];
        }
        
        if ($isAjax) {
            echo json_encode($response);
            exit();
        } else {
            header("Location: index.php?success=" . urlencode($response['message'] . " (Development: $reset_link)"));
            exit();
        }
    } else {
        $response = ['success' => false, 'message' => 'Error generating reset link. Please try again.'];
        if ($isAjax) {
            echo json_encode($response);
            exit();
        } else {
            header("Location: index.php?error=" . urlencode($response['message']));
            exit();
        }
    }
    
    $insert_stmt->close();
    $stmt->close();
}

$conn->close();

/**
 * Send password reset email using PHPMailer with Gmail SMTP
 * 
 * @param string $toEmail Recipient email address
 * @param string $userName Recipient name
 * @param string $resetLink Password reset link
 * @return bool True if email sent successfully, false otherwise
 */
function sendResetEmail($toEmail, $userName, $resetLink) {
    // PHPMailer path - adjust if PHPMailer is installed in a different location
    $phpmailerPaths = [
        __DIR__ . '/PHPMailer/src/PHPMailer.php',
        __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
        __DIR__ . '/PHPMailer/PHPMailer.php',
    ];
    
    $phpmailerLoaded = false;
    foreach ($phpmailerPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            require_once dirname($path) . '/SMTP.php';
            require_once dirname($path) . '/Exception.php';
            $phpmailerLoaded = true;
            break;
        }
    }
    
    // Check if PHPMailer class exists
    if (!$phpmailerLoaded || !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not found. Please install PHPMailer using: composer require phpmailer/phpmailer");
        return false;
    }
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // CHANGE THIS: Your Gmail address
        $mail->Password = 'your-app-password';     // CHANGE THIS: Your Gmail App Password (not your real password)
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('your-email@gmail.com', 'NumerIQ Support'); // CHANGE THIS
        $mail->addAddress($toEmail, $userName);
        
        // Validate email before sending
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email address: $toEmail");
            return false;
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - NumerIQ';
        
        // HTML Email Body
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #0a0014; margin: 0; padding: 0; color: #fff; }
                .container { max-width: 600px; margin: 0 auto; background-color: #1a0033; padding: 30px; margin-top: 20px; border: 2px solid #00ffff; }
                .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #00ffff; }
                .header h1 { color: #00ffff; margin: 0; font-family: "Press Start 2P", cursive; }
                .content { padding: 20px 0; }
                .content p { color: #a0a0ff; line-height: 1.6; }
                .button { display: inline-block; background: #00ffff; color: #0a0014; text-decoration: none; padding: 15px 30px; margin: 20px 0; font-weight: bold; font-family: "Press Start 2P", cursive; font-size: 0.7rem; }
                .link-box { background-color: #0a0014; padding: 15px; word-break: break-all; font-family: monospace; font-size: 12px; color: #00ffff; border: 1px solid #00ffff; }
                .footer { text-align: center; padding-top: 20px; border-top: 1px solid #1a0033; color: #606080; font-size: 12px; }
                .warning { background-color: rgba(255, 170, 0, 0.2); border-left: 4px solid #ffaa00; padding: 10px 15px; margin: 15px 0; color: #ffaa00; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>NumerIQ</h1>
                    <p>Master the Numbers, Challenge Your Mind!</p>
                </div>
                <div class="content">
                    <h2>Hello ' . htmlspecialchars($userName) . ',</h2>
                    <p>We received a request to reset your password for your NumerIQ account.</p>
                    <p>Click the button below to reset your password:</p>
                    <div style="text-align: center;">
                        <a href="' . $resetLink . '" class="button">Reset My Password</a>
                    </div>
                    <p>Or copy and paste this link into your browser:</p>
                    <div class="link-box">' . $resetLink . '</div>
                    <div class="warning">
                        <strong>Important:</strong> This link will expire in 5 minutes for security reasons.
                    </div>
                    <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' NumerIQ. All rights reserved.</p>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Plain text alternative
        $mail->AltBody = "Hello $userName,\n\nWe received a request to reset your password for your NumerIQ account.\n\nClick the link below to reset your password:\n$resetLink\n\nThis link will expire in 5 minutes for security reasons.\n\nIf you did not request a password reset, please ignore this email.\n\n© " . date('Y') . " NumerIQ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log error server-side, never expose to user
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
