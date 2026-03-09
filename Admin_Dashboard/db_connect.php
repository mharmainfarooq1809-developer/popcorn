<?php
$host = "localhost";      // Usually localhost
$user = "root";           // Your DB username
$pass = "";               // Your DB password
$db   = "cinema_db";      // Database name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
