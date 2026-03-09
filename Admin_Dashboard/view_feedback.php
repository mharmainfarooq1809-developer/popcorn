<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$feedback_id = intval($_GET['id'] ?? 0);
if (!$feedback_id) {
    header("Location: messages.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, name, email, message, status, submitted_at FROM feedback WHERE id = ?");
$stmt->bind_param('i', $feedback_id);
$stmt->execute();
$result = $stmt->get_result();
$feedback = $result->fetch_assoc();

if (!$feedback) {
    header("Location: messages.php");
    exit;
}

if ($feedback['status'] === 'unread') {
    $conn->query("UPDATE feedback SET status = 'read' WHERE id = $feedback_id");
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>View Feedback · STAR TREK Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 30px;
        }

        .container {
            max-width: 800px;
        }

        .card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .reply-bubble {
            background: #e9ecef;
            border-radius: 18px 18px 18px 0;
            padding: 10px 15px;
            margin: 5px 0 5px 30px;
            max-width: 80%;
        }

        .btn-primary {
            background: linear-gradient(145deg, #6F2DA8, #5B1E8C);
            border: none;
            border-radius: 40px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Feedback Details</h2>
            <a href="messages.php" class="btn btn-outline-secondary">← Back</a>
        </div>

        <div class="card p-4">
            <h5><?= htmlspecialchars($feedback['name']) ?></h5>
            <p class="text-muted"><?= htmlspecialchars($feedback['email']) ?> ·
                <?= date('M j, Y H:i', strtotime($feedback['submitted_at'])) ?></p>
            <hr>
            <p><?= nl2br(htmlspecialchars($feedback['message'])) ?></p>
        </div>

        <div class="card p-4" id="repliesSection">
            <h5>Conversation</h5>
            <div id="repliesContainer">Loading...</div>
        </div>

        <div class="card p-4">
            <h5>Send a Reply</h5>
            <form id="replyForm">
                <input type="hidden" name="feedback_id" value="<?= $feedback_id ?>">
                <div class="mb-3">
                    <input type="text" class="form-control" id="replySubject" placeholder="Subject (optional)">
                </div>
                <div class="mb-3">
                    <textarea class="form-control" id="replyMessage" rows="4" placeholder="Type your reply..."
                        required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" id="sendReplyBtn">Send Reply</button>
                <div id="replyStatus" class="mt-2 small"></div>
            </form>
        </div>
    </div>

    <script>
        const feedbackId = <?= $feedback_id ?>;
        const adminId = <?= $admin_id ?>;

        function loadReplies() {
            fetch('get_replies.php?feedback_id=' + feedbackId)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    if (data.replies.length) {
                        data.replies.forEach(reply => {
                            html += `<div class="reply-bubble"><small class="text-muted">Admin · ${new Date(reply.created_at).toLocaleString()}</small><p class="mb-0">${reply.reply_text.replace(/\n/g, '<br>')}</p></div>`;
                        });
                    } else {
                        html = '<p class="text-muted">No replies yet.</p>';
                    }
                    document.getElementById('repliesContainer').innerHTML = html;
                });
        }

        document.getElementById('replyForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const message = document.getElementById('replyMessage').value.trim();
            if (!message) return alert('Reply cannot be empty');
            const btn = document.getElementById('sendReplyBtn');
            const statusDiv = document.getElementById('replyStatus');
            btn.disabled = true;
            statusDiv.innerHTML = 'Sending...';

            const formData = new FormData();
            formData.append('feedback_id', feedbackId);
            formData.append('reply_subject', document.getElementById('replySubject').value);
            formData.append('reply_message', message);
            formData.append('admin_id', adminId);

            fetch('reply_feedback.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML = '<span class="text-success">✓ Reply sent!</span>';
                        document.getElementById('replyMessage').value = '';
                        loadReplies();
                    } else {
                        statusDiv.innerHTML = '<span class="text-danger">Error: ' + data.error + '</span>';
                    }
                })
                .catch(() => statusDiv.innerHTML = '<span class="text-danger">Network error</span>')
                .finally(() => btn.disabled = false);
        });

        loadReplies();
    </script>
</body>

</html>