<?php
function get_settings($conn) {
    static $settings = null;
    if ($settings === null) {
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}