<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Required files
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if (isset($_POST["send"])) {

    // Get and sanitize user input
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars(trim($_POST["subject"]));
    $message = htmlspecialchars(trim($_POST["message"]));

    if (!$email) {
        echo "<script>alert('Invalid email address!'); window.location.href='login.php';</script>";
        exit;
    }

    try {
        $mail = new PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'samra_sadaqat@aptechnorth.edu.pk'; // Your Gmail
        $mail->Password   = 'rqdtspnwqbbjklha';                 // App password
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        // Sender & recipient
        $mail->setFrom('samra_sadaqat@aptechnorth.edu.pk', 'Website Contact Form'); // Always your Gmail
        $mail->addAddress('samra_sadaqat@aptechnorth.edu.pk'); // Where you receive emails
        $mail->addReplyTo($email, $name); // So you can reply to the user

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Contact Form: " . $subject;
        $mail->Body    = "
            <h3>New message from website contact form</h3>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
        ";

        // Send email
        $mail->send();
        echo "<script>alert('Message was sent successfully!'); window.location.href='login.php';</script>";

    } catch (Exception $e) {
        echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='login.php';</script>";
    }
}
?>