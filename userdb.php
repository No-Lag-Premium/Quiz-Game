<?php
// Database configuration - Update these with your actual database credentials
$host = 'localhost';
$username = 'root';      // Change to your database username
$password = '';          // Change to your database password
$database = 'numeriq';   // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $database, 3309);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");
?>
