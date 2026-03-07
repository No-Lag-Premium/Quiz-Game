<?php
session_start();
include 'userdb.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
            exit();
        }
        header("Location: index.php?error=" . urlencode("Please enter both email and password."));
        exit();
    }

    // Check database connection
    if ($conn->connect_error) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database connection error.']);
            exit();
        }
        header("Location: index.php?error=" . urlencode("Database connection error. Please try again later."));
        exit();
    }

    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email=?");
    
    if (!$stmt) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit();
        }
        header("Location: index.php?error=" . urlencode("Database error. Please try again."));
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $user_email, $hashedPassword);
        $stmt->fetch();

        if (!empty($hashedPassword) && password_verify($password, $hashedPassword)) {
            // Set session variables
            $_SESSION['username'] = $name;
            $_SESSION['user_id'] = $id;
            $_SESSION['email'] = $user_email;
            $_SESSION['logged_in'] = true;
            
            // Update last login time
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Create user settings if not exists
            $settings_check = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $settings_check->bind_param("i", $id);
            $settings_check->execute();
            $settings_check->store_result();
            
            if ($settings_check->num_rows === 0) {
                $settings_stmt = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
                $settings_stmt->bind_param("i", $id);
                $settings_stmt->execute();
                $settings_stmt->close();
            }
            $settings_check->close();
            
            // Return JSON response for AJAX requests
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'dashboard.php']);
                exit();
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid password. Please try again.']);
                exit();
            }
            header("Location: index.php?error=" . urlencode("Invalid password. Please try again."));
            exit();
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
            exit();
        }
        header("Location: index.php?error=" . urlencode("No account found with that email."));
        exit();
    }

    $stmt->close();
}
$conn->close();
?>
