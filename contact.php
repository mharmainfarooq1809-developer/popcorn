<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db_connect.php';
require_once 'settings_init.php'; // loads settings via get_settings()

$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// settings already initialized by settings_init.php
// $settings = get_settings($conn); (not needed)

if (isset($_POST["send"])) {
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars(trim($_POST["subject"]));
    $message = htmlspecialchars(trim($_POST["message"]));

    if (!$email) {
        echo "<script>alert('Invalid email address!'); window.location.href='login.php';</script>";
        exit;
    }

    try {
        // 1. Send email using settings from database
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_username'];
        $mail->Password   = $settings['smtp_password'];
        $mail->SMTPSecure = $settings['smtp_secure'];
        $mail->Port       = $settings['smtp_port'];

        $mail->setFrom($settings['smtp_username'], 'Website Contact Form');
        $mail->addAddress($settings['admin_email']); // recipient from settings
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Contact Form: " . $subject;
        $mail->Body    = "
            <h3>New message from website contact form</h3>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
        ";

        $mail->send();

        // 2. Save to database
        $stmt = $conn->prepare("INSERT INTO feedback (name, email, message, status, submitted_at) VALUES (?, ?, ?, 'unread', NOW())");
        $stmt->bind_param("sss", $name, $email, $message);
        $stmt->execute();
        $feedback_id = $stmt->insert_id;
        $stmt->close();

        // 3. Create global notification for admin
        $notif_message = "New feedback from " . $name;
        $notif_link = "Admin_Dashboard/view_feedback.php?id=" . $feedback_id;
        $stmt = $conn->prepare("INSERT INTO notifications (type, message, link, created_at) VALUES ('new_feedback', ?, ?, NOW())");
        $stmt->bind_param("ss", $notif_message, $notif_link);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Message was sent successfully!'); window.location.href='login.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='login.php';</script>";
    }
}
?>
