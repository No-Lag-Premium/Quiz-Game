<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to home with logout flag (different from success)
header("Location: index.php?logout=1");
exit();
?>
