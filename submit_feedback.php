<?php
session_start();
require_once 'db_connect.php'; // adjust path if needed

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Read JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Validate fields
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');

$errors = [];
if (empty($name)) $errors[] = 'Name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (empty($message)) $errors[] = 'Message is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// SMTP settings (replace with your actual credentials)
$smtp_host = 'smtp.gmail.com';
$smtp_user = 'mharmainfarooq1809@gmail.com';
$smtp_pass = 'rifpqnjolzessdug';
$smtp_secure = 'ssl';
$smtp_port = 465;

// Admin email and ID (change to your actual admin user ID)
$admin_email = 'mharmainfarooq1809@gmail.com';
$admin_name = 'Popcorn Hub Admin';
$admin_user_id = 1; // <-- IMPORTANT: set this to your admin's user ID

try {
    // 1. Save feedback to database
    $stmt = $conn->prepare("INSERT INTO feedback (name, email, message, status, submitted_at) VALUES (?, ?, ?, 'unread', NOW())");
    $stmt->bind_param("sss", $name, $email, $message);
    $stmt->execute();
    $feedback_id = $stmt->insert_id;
    $stmt->close();

    // 2. Send email via PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = $smtp_secure;
    $mail->Port       = $smtp_port;

    $mail->setFrom($smtp_user, 'Popcorn Hub Feedback');
    $mail->addAddress($admin_email, $admin_name);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = "New Feedback from " . $name;
    $mail->Body    = "
        <h3>New feedback received</h3>
        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
    ";
    $mail->AltBody = strip_tags("Name: {$name}\nEmail: {$email}\nMessage:\n{$message}");

    $mail->send();

    // 3. Create global notification for admin
    $notif_msg = "New feedback from " . $name;
    $notif_link = "Admin_Dashboard/view_feedback.php?id=" . $feedback_id; // adjust path if needed
    $stmt_notif = $conn->prepare("INSERT INTO notifications (type, message, link, created_at) VALUES ('feedback', ?, ?, NOW())");
    $stmt_notif->bind_param("ss", $notif_msg, $notif_link);
    $stmt_notif->execute();
    $stmt_notif->close();

    // 4. Return success
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Feedback submission error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to send feedback. Please try again later.']);
}
?>