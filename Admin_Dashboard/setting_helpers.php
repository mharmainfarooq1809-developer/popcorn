<?php
// Admin_Dashboard/setting_helpers.php
// Helper function to fetch all settings from the database

function get_settings($conn) {
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}