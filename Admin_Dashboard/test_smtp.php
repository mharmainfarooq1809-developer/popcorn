<?php
// test_smtp.php – must start with <?php, no spaces before
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$smtp_host = $_POST['smtp_host'] ?? '';
$smtp_port = $_POST['smtp_port'] ?? '';
$smtp_secure = $_POST['smtp_secure'] ?? '';
$smtp_username = $_POST['smtp_username'] ?? '';
$smtp_password = $_POST['smtp_password'] ?? '';
$admin_email = $_POST['admin_email'] ?? '';
$admin_name = $_POST['admin_name'] ?? 'Popcorn Hub Admin';

if (empty($smtp_host) || empty($smtp_port) || empty($smtp_username) || empty($smtp_password) || empty($admin_email)) {
    echo json_encode(['success' => false, 'error' => 'Missing required SMTP settings.']);
    exit;
}

// Check for PHPMailer (Composer or manual)
$phpmailerLoaded = false;
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
    $phpmailerLoaded = true;
} elseif (file_exists('../PHPMailer/src/PHPMailer.php')) {
    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';
    $phpmailerLoaded = true;
}

if (!$phpmailerLoaded) {
    echo json_encode(['success' => false, 'error' => 'PHPMailer not found. Please install it via Composer or manually.']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_username;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = $smtp_secure;
    $mail->Port       = $smtp_port;

    $mail->setFrom($smtp_username, $admin_name);
    $mail->addAddress($admin_email);

    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Popcorn Hub Admin';
    $mail->Body    = '<h1>SMTP Test Successful</h1><p>Your email settings are working correctly.</p>';
    $mail->AltBody = 'SMTP Test Successful. Your email settings are working correctly.';

    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
} catch (Throwable $t) {
    echo json_encode(['success' => false, 'error' => $t->getMessage()]);
}