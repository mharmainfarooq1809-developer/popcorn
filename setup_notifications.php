<?php
require_once 'db_connect.php';

// Check if notifications table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_exists->num_rows == 0) {
    // Create notifications table
    $create_table = "
    CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255),
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if ($conn->query($create_table)) {
        echo "OK: Notifications table created successfully!";
    } else {
        echo "Error: Failed to create notifications table: " . $conn->error;
    }
} else {
    echo "OK: Notifications table already exists!";
}
?>
