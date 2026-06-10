<?php
// 1. Config & Session
require_once '../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$admin_id = $_SESSION['id'];
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// 2. Handle Sending a Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_msg'])) {
    $receiver_id = intval($_POST['student_id']);
    $message_text = trim($_POST['reply_msg']);

    if (!empty($message_text)) {
        $stmt = $conn->prepare("INSERT INTO messages (event_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $event_id, $admin_id, $receiver_id, $message_text);
        $stmt->execute();
        // Refresh to show new message
        header("Location: event_messages.php?event_id=" . $event_id . "&success=1");
        exit;
    }
}

// 3. Get Event Details
$evt_sql = "SELECT event_name FROM events WHERE event_id = ?";
$stmt = $conn->prepare($evt_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event_name = $stmt->get_result()->fetch_assoc()['event_name'] ?? 'Unknown Event';

// 4. Fetch distinct students who have chatted in this event
// We look for any message related to this event where the user is a 'student'
$users_sql = "SELECT DISTINCT u.id, u.username, u.email 
              FROM users u 
              JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
              WHERE m.event_id = ? AND u.role = 'student'
              ORDER BY m.created_at DESC";
$stmt = $conn->prepare($users_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - <?= htmlspecialchars($event_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #333; }
        .btn-back { text-decoration: none; color: #555; background: #ddd; padding: 8px 15px; border-radius: 5px; font-size: 0.9rem; }

        /* Chat Card */
        .chat-card { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
        .chat-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .chat-header h3 { margin: 0; font-size: 1rem; color: #333; }
        .chat-header span { font-size: 0.85rem; color: #777; font-weight: normal; }

        /* Messages Area */
        .messages-box { padding: 20px; max-height: 300px; overflow-y: auto; background: #fff; border-bottom: 1px solid #eee; }
        
        .msg { display: flex; margin-bottom: 15px; }
        .msg.student { justify-content: flex-start; }
        .msg.admin { justify-content: flex-end; }

        .bubble { max-width: 70%; padding: 10px 15px; border-radius: 15px; font-size: 0.95rem; line-height: 1.4; position: relative; }
        
        /* Student Bubble (Gray) */
        .msg.student .bubble { background: #e9ecef; color: #333; border-bottom-left-radius: 2px; }
        .msg.student .meta { font-size: 0.7rem; color: #666; margin-top: 4px; display: block; }

        /* Admin Bubble (Blue) */
        .msg.admin .bubble { background: #0d6efd; color: white; border-bottom-right-radius: 2px; }
        .msg.admin .meta { font-size: 0.7rem; color: #e0e0e0; margin-top: 4px; display: block; text-align: right; }

        /* Reply Form */
        .reply-area { padding: 15px; background: #f1f3f5; display: flex; gap: 10px; }
        .reply-input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; }
        .btn-send { background: #0d6efd; color: white; border: none; padding: 0 20px; border-radius: 5px; cursor: pointer; font-weight: 500; }
        .btn-send:hover { background: #0b5ed7; }

        .no-msg { text-align: center; color: #999; margin-top: 50px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h4 style="margin:0; color:#777;">Inquiries & Feedback</h4>
            <h2><?= htmlspecialchars($event_name) ?></h2>
        </div>
        <a href="../../admin/index.php" class="btn-back">Back to Dashboard</a>
    </div>

    <?php if ($students->num_rows > 0): ?>
        <?php while($student = $students->fetch_assoc()): ?>
            <?php 
                // Fetch conversation for this specific student + event
                // We assume 'admin_id' is the current logged in user answering
                $s_id = $student['id'];
                $chat_sql = "SELECT * FROM messages 
                             WHERE event_id = ? 
                             AND (sender_id = ? OR receiver_id = ?) 
                             ORDER BY created_at ASC";
                $c_stmt = $conn->prepare($chat_sql);
                $c_stmt->bind_param("iii", $event_id, $s_id, $s_id);
                $c_stmt->execute();
                $chats = $c_stmt->get_result();
            ?>
            
            <div class="chat-card">
                <div class="chat-header">
                    <i class="fas fa-user-circle fa-lg" style="color:#6c757d;"></i>
                    <h3><?= htmlspecialchars($student['username']) ?></h3>
                    <span>(<?= htmlspecialchars($student['email']) ?>)</span>
                </div>

                <div class="messages-box">
                    <?php while($msg = $chats->fetch_assoc()): ?>
                        <?php 
                            $is_me = ($msg['sender_id'] == $admin_id); 
                            $class = $is_me ? 'admin' : 'student';
                        ?>
                        <div class="msg <?= $class ?>">
                            <div class="bubble">
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                <span class="meta">
                                    <?= $is_me ? 'You' : $student['username'] ?> • <?= date('M d, H:i', strtotime($msg['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <form method="POST" class="reply-area">
                    <input type="hidden" name="student_id" value="<?= $s_id ?>">
                    <input type="text" name="reply_msg" class="reply-input" placeholder="Type a reply..." required>
                    <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Send</button>
                </form>
            </div>

        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-msg">
            <i class="fas fa-comments fa-3x"></i>
            <p>No inquiries yet for this event.</p>
        </div>
    <?php endif; ?>

</div>

</body>
</html>