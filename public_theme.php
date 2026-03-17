<?php
require_once 'settings_init.php';

header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$theme = $settings['theme_color'] ?? '';
if (empty($theme)) {
    $theme = '#FFA500';
}

function clamp($v) {
    return max(0, min(255, $v));
}

function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $int = hexdec($hex);
    return [($int >> 16) & 255, ($int >> 8) & 255, $int & 255];
}

function rgbToHex($r, $g, $b) {
    return sprintf('#%02x%02x%02x', clamp($r), clamp($g), clamp($b));
}

function darken($hex, $pct) {
    [$r, $g, $b] = hexToRgb($hex);
    $factor = 1 - $pct;
    return rgbToHex((int)($r * $factor), (int)($g * $factor), (int)($b * $factor));
}

$primary = $theme;
$primaryDark = darken($theme, 0.2);

echo ":root {\n";
echo "  --theme-primary: {$primary};\n";
echo "  --theme-primary-dark: {$primaryDark};\n";
echo "  --primary: {$primary};\n";
echo "  --primary-dark: {$primaryDark};\n";
echo "  --popcorn-orange: {$primary};\n";
echo "  --popcorn-gold: {$primary};\n";
echo "}\n";

// Navbar overrides for public pages
$css = <<<CSS
#navbar .logo,
.navbar .logo,
header .logo,
#navbar .brand,
.navbar .brand,
header .brand {
  color: var(--theme-primary) !important;
}

#navbar .btn-signin,
.navbar .btn-signin,
header .btn-signin,
#navbar .btn-nav,
.navbar .btn-nav,
header .btn-nav,
#navbar .btn,
.navbar .btn,
header .btn {
  border-color: var(--theme-primary) !important;
  color: var(--theme-primary) !important;
}

#navbar .btn-signin:hover,
.navbar .btn-signin:hover,
header .btn-signin:hover,
#navbar .btn-nav:hover,
.navbar .btn-nav:hover,
header .btn-nav:hover,
#navbar .btn:hover,
.navbar .btn:hover,
header .btn:hover {
  background: var(--theme-primary) !important;
  color: #000 !important;
}
CSS;

echo $css;
?>
