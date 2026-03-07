<?php
session_start();
include 'userdb.php';

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
$error = '';
$success = '';

// Verify token on GET request (when user clicks the link)
if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($token)) {
    $stmt = $conn->prepare("SELECT pr.user_id, pr.expires_at, u.name 
                           FROM password_resets pr 
                           JOIN users u ON pr.user_id = u.id 
                           WHERE pr.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Invalid or expired reset token.";
    } else {
        $reset_data = $result->fetch_assoc();
        // Convert expires_at to timestamp before comparing
        $expires_timestamp = strtotime($reset_data['expires_at']);
        if ($expires_timestamp < time()) {
            $error = "This reset link has expired. Please request a new one.";
        }
    }
    $stmt->close();
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && empty($token)) {
    $error = "No reset token provided.";
}

// Handle password reset form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Get user_id from token and validate it again
        $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset_data = $result->fetch_assoc();
            $user_id = $reset_data['user_id'];
            $expires_timestamp = strtotime($reset_data['expires_at']);
            
            // Check if token is still valid
            if ($expires_timestamp < time()) {
                $error = "This reset link has expired. Please request a new one.";
            } else {
                // Token is valid - proceed with password update
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    // Password updated successfully - NOW delete the token
                    $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $delete_stmt->bind_param("s", $token);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    
                    $success = "Password has been reset successfully. You can now login with your new password.";
                } else {
                    $error = "Error updating password. Please try again.";
                }
                
                $update_stmt->close();
            }
        } else {
            $error = "Invalid or expired token.";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - NumerIQ</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: var(--bg-primary);
        }
        .reset-box {
            background: var(--bg-secondary);
            border: 3px solid var(--border-color);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 8px 8px 0 rgba(0, 0, 0, 0.3), var(--glow-cyan);
        }
        .reset-box h2 {
            margin-bottom: 10px;
            font-family: 'Press Start 2P', cursive;
            font-size: 1rem;
            color: var(--primary);
            text-shadow: var(--glow-cyan);
        }
        .reset-box p {
            color: var(--text-secondary);
            margin-bottom: 25px;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-family: 'VT323', monospace;
            font-size: 1rem;
            color: var(--text-secondary);
        }
        .password-input-wrapper {
            position: relative;
        }
        .form-group input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: var(--glow-cyan);
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            transition: color 0.2s ease;
        }
        .password-toggle:hover {
            color: var(--primary);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            font-family: 'VT323', monospace;
            font-size: 1rem;
            border: 2px solid;
        }
        .alert-error {
            background: rgba(255, 0, 64, 0.1);
            color: var(--danger);
            border-color: var(--danger);
        }
        .alert-success {
            background: rgba(0, 255, 0, 0.1);
            color: var(--success);
            border-color: var(--success);
        }
        .btn {
            width: 100%;
            padding: 15px;
            border: 3px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            font-family: 'Press Start 2P', cursive;
            font-size: 0.65rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0 rgba(0, 0, 0, 0.3);
        }
        .btn-primary {
            background: var(--primary);
            color: var(--bg-primary);
            border-color: var(--primary);
        }
        .btn-primary:hover {
            box-shadow: var(--glow-cyan);
        }
        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        .btn-secondary:hover {
            border-color: var(--secondary);
            box-shadow: var(--glow-pink);
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-box">
            <h2><i class="fas fa-lock"></i> Reset Password</h2>
            <p>Enter your new password below</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <a href="index.php" class="btn btn-primary">Go to Login</a>
            <?php elseif (empty($error)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="newPassword" required minlength="6" placeholder="Enter new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="confirm_password" id="confirmPassword" required minlength="6" placeholder="Confirm new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            <?php else: ?>
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, toggleBtn) {
            const input = document.getElementById(inputId);
            const icon = toggleBtn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
