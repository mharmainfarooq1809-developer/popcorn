<?php
$host = "localhost";      // Usually localhost
$user = "root";           // Your DB username
$pass = "";               // Your DB password
$db   = "cinema_db";      // Database name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure consistent UTF-8 handling across the admin app.
ini_set('default_charset', 'UTF-8');
if (!$conn->set_charset('utf8mb4')) {
    // Keep the site running even if charset setup fails.
}
?>
