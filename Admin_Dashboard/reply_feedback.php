<?php
// Keep logs on server, but never print warnings/notices into JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../db_connect.php'; // adjust path if needed
header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Include PHPMailer (adjust paths to your actual phpmailer folder)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

// Helper function to get settings (if you have a settings table)
function getSettings($conn) {
    // Option 1: Fetch from database (if you have a settings table)
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    // Option 2: Fallback to hardcoded values if database not used
    if (empty($settings)) {
        $settings = [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '465',
            'smtp_secure' => 'ssl',
            'smtp_username' => 'mharmainfarooq1809@gmail.com',
            'smtp_password' => 'rifpqnjolzessdug',
            'admin_name' => 'STAR TREK Admin'
        ];
    }
    return $settings;
}

$settings = getSettings($conn);
$settings = array_merge([
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '465',
    'smtp_secure' => 'ssl',
    'smtp_username' => '',
    'smtp_password' => '',
    'admin_name' => ($_SESSION['user_name'] ?? 'Admin')
], $settings);

// Get POST data
$feedback_id = intval($_POST['feedback_id'] ?? 0);
$reply_subject = trim($_POST['reply_subject'] ?? '');
$reply_message = trim($_POST['reply_message'] ?? '');
$admin_id = intval($_POST['admin_id'] ?? ($_SESSION['user_id'] ?? 0));

// Validate required fields
if (!$feedback_id || empty($reply_message) || !$admin_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Fetch user details from feedback table
$stmt = $conn->prepare("SELECT name, email FROM feedback WHERE id = ?");
$stmt->bind_param('i', $feedback_id);
$stmt->execute();
$result = $stmt->get_result();
$feedback = $result->fetch_assoc();

if (!$feedback) {
    echo json_encode(['success' => false, 'error' => 'Feedback not found']);
    exit;
}

$user_name = $feedback['name'];
$user_email = $feedback['email'];

// Default subject if not provided
if (empty($reply_subject)) {
    $reply_subject = "Re: Your feedback to STAR TREK";
}

try {
    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $settings['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_username'];
    $mail->Password   = $settings['smtp_password'];
    $mail->SMTPSecure = $settings['smtp_secure'];
    $mail->Port       = $settings['smtp_port'];

    // Optional: enable debug output (comment out after testing)
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug: $str"); };

    $mail->setFrom($settings['smtp_username'], $settings['admin_name']);
    $mail->addAddress($user_email, $user_name);
    $mail->addReplyTo($settings['smtp_username'], $settings['admin_name'] . ' Support');

    $mail->isHTML(true);
    $mail->Subject = $reply_subject;
    $mail->Body    = "
        <h3>Reply from " . htmlspecialchars($settings['admin_name']) . "</h3>
        <p>Dear {$user_name},</p>
        <p>Thank you for your feedback. Here is our response:</p>
        <p>" . nl2br(htmlspecialchars($reply_message)) . "</p>
        <hr>
        <p>-- " . htmlspecialchars($settings['admin_name']) . " Team</p>
    ";
    $mail->AltBody = strip_tags("Dear {$user_name},\n\nThank you for your feedback. Here is our response:\n\n{$reply_message}\n\n-- {$settings['admin_name']} Team");

    // Send email
    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }

    // Store reply in database
    $stmt = $conn->prepare("INSERT INTO feedback_replies (feedback_id, admin_id, reply_text, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iis', $feedback_id, $admin_id, $reply_message);
    $stmt->execute();
    $stmt->close();

    // Update replied_at timestamp in feedback table
    $conn->query("UPDATE feedback SET replied_at = NOW() WHERE id = $feedback_id");

    // Return success
    echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);

} catch (Exception $e) {
    // Log full error to server log
    error_log('PHPMailer Error in reply_feedback.php: ' . $e->getMessage());
    // Return generic error to client
    echo json_encode(['success' => false, 'error' => 'Failed to send email. Please check server logs.']);
}
?>
