<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users

include 'userdb.php';

// Check if it's an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $errors[] = "This email is already registered. Please login instead.";
        }
        $check_stmt->close();
    }
    
    // If no errors, create account
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
                exit();
            }
            header("Location: index.php?error=" . urlencode("Database error. Please try again."));
            exit();
        }
        
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Auto-login after registration
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['logged_in'] = true;
            
            // Create user settings
            $settings_stmt = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
            $settings_stmt->bind_param("i", $user_id);
            $settings_stmt->execute();
            $settings_stmt->close();
            
            $stmt->close();
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Account created successfully!', 'redirect' => 'dashboard.php']);
                exit();
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            $stmt->close();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error creating account. Please try again.']);
                exit();
            }
            header("Location: index.php?error=" . urlencode("Error creating account. Please try again."));
            exit();
        }
    } else {
        // Return with errors
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => implode(" ", $errors)]);
            exit();
        }
        header("Location: index.php?error=" . urlencode(implode(" ", $errors)));
        exit();
    }
}

$conn->close();
?>
