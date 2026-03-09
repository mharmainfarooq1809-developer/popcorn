<?php
// settings_init.php – load all settings into $settings array
if (!isset($conn)) {
    // If db_connect hasn't been included yet, include it
    require_once 'db_connect.php';
}

// Helper function (can be placed here or in a separate file)
function get_settings($conn, $refresh = false) {
    static $settings = null;
    if ($refresh) {
        $settings = null;
    }
    if ($settings === null) {
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

$settings = get_settings($conn);

// if the stored logo path doesn't exist on disk, try to auto-find a recent logo file
if (!empty($settings['site_logo'])) {
    $logoPath = __DIR__ . '/' . $settings['site_logo'];
    if (!file_exists($logoPath)) {
        $candidates = glob(__DIR__ . '/uploads/logo_*');
        if (!empty($candidates)) {
            // pick most recently modified file
            usort($candidates, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $newRel = 'uploads/' . basename($candidates[0]);
            // update database and settings array so front-end sees it
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'");
            $stmt->bind_param('s', $newRel);
            $stmt->execute();
            $stmt->close();
            $settings['site_logo'] = $newRel;
        }
    }
}

?>
