<?php
session_start();
// 1. SMART CONFIG LOAD: Finds config.php whether in root or users folder
$config_path = file_exists('config.php') ? 'config.php' : '../config.php';
require_once $config_path;

// 2. CHECK LOGIN
if (!isset($_SESSION['loggedin'])) {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// 3. FETCH EVENT DETAILS
if ($isAdmin) {
    $event_sql = "SELECT event_name FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($event_sql);
    $stmt->bind_param("i", $event_id);
} else {
    $event_sql = "SELECT event_name FROM events WHERE event_id = ? AND organizer_id = ?";
    $stmt = $conn->prepare($event_sql);
    $stmt->bind_param("ii", $event_id, $user_id);
}
$stmt->execute();
$event_res = $stmt->get_result();

if($event_res->num_rows === 0) {
    echo "Event not found or Access Denied.";
    exit;
}
$event_data = $event_res->fetch_assoc();

// 4. FETCH MESSAGES
$sql = "SELECT m.*, u.name, u.email, u.id as sender_uid 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.event_id = ? AND m.receiver_id = ? 
        ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Messages | NX</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* --- RESET & LAYOUT --- */
        body {
            margin: 0; padding: 0;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column; /* Stack: Header on top, Body below */
            min-height: 100vh;
        }

        /* 1. TOP NAVIGATION CONTAINER (Full Width) */
        .top-nav-container {
            width: 100%;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            z-index: 1000;
        }

        /* 2. MAIN BODY WRAPPER (Sidebar + Content) */
        .main-body {
            display: flex;
            flex: 1; /* Fills remaining height */
            height: calc(100vh - 60px); /* Adjust based on your nav height */
        }

        /* 3. SIDEBAR (Fixed Width) */
        .sidebar-container {
            width: 250px;
            background: #343a40;
            overflow-y: auto;
            flex-shrink: 0;
            display: block; /* Ensure it's visible */
        }

        /* 4. CONTENT AREA (Flexible) */
        .content-container {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* --- MESSAGE STYLES --- */
        .msg-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .msg-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;
        }
        .sender-info strong { color: #4f46e5; font-size: 1.05rem; }
        .action-bar { margin-top: 15px; display: flex; gap: 10px; justify-content: flex-end; }
        
        .btn-reply { background: #4f46e5; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .btn-delete { color: #ef4444; border: 1px solid #ef4444; padding: 5px 10px; border-radius: 4px; text-decoration: none; }
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 2000; }
        .modal-box { background: white; padding: 25px; border-radius: 8px; width: 90%; max-width: 500px; }
        .form-group { margin-bottom: 15px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn-cancel { background: #e2e8f0; color: #334155; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px; }
    </style>
</head>
<body>

    <div class="top-nav-container">
        <?php 
        if ($isAdmin) {
            // Try to load admin topNav, fallback to normal topNav if missing
            if (file_exists('../admin/include/topNav.php')) {
                include '../admin/include/topNav.php'; 
            } else {
                include '../include/topNav.php'; 
            }
        } else {
            include '../include/topNav.php'; 
        }
        ?>
    </div>

    <div class="main-body">
        
        <div class="sidebar-container">
            <?php 
            if ($isAdmin) {
                include '../admin/include/sidebar.php'; 
            } else {
                // Check if user sidebar exists before including to prevent errors
                if (file_exists('../include/sidebar.php')) {
                    include '../include/sidebar.php'; 
                }
            }
            ?>
        </div>

        <div class="content-container">
            <a href="<?= $isAdmin ? '../admin/index.php' : 'my_events.php' ?>" style="text-decoration: none; color: #64748b; margin-bottom: 20px; display: inline-block;">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
            
            <h2 style="color: #1e293b; margin-bottom: 30px;">
                Messages for: <span style="color: #4f46e5;"><?= htmlspecialchars($event_data['event_name']); ?></span>
            </h2>

            <?php if($result->num_rows > 0): ?>
                <?php while($msg = $result->fetch_assoc()): ?>
                    <div class="msg-card">
                        <div class="msg-header">
                            <div class="sender-info">
                                <strong><?= htmlspecialchars($msg['name']); ?></strong>
                                <br><span style="color:#64748b; font-size:0.9rem;">&lt;<?= htmlspecialchars($msg['email']); ?>&gt;</span>
                            </div>
                            <div style="color:#94a3b8; font-size:0.85rem;">
                                <?= date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>

                        <div class="msg-body">
                            <h4 style="margin:0 0 5px 0;">Subject: <?= htmlspecialchars($msg['subject']); ?></h4>
                            <p style="color:#475569; margin:0; line-height:1.5;"><?= nl2br(htmlspecialchars($msg['message'])); ?></p>
                        </div>

                        <div class="action-bar">
                            <button class="btn-reply" onclick="openReplyModal('<?= $msg['sender_uid']; ?>', '<?= htmlspecialchars($msg['name']); ?>', '<?= htmlspecialchars(addslashes($msg['subject'])); ?>')">
                                <i class="fa-solid fa-reply"></i> Reply
                            </button>
                            <a href="delete_message.php?id=<?= $msg['id']; ?>&event_id=<?= $event_id; ?>" class="btn-delete" onclick="return confirm('Are you sure?');">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #64748b; background: white; border-radius: 8px; border:1px solid #e2e8f0;">
                    <i class="fa-regular fa-envelope-open" style="font-size: 2rem; margin-bottom: 10px; display:block;"></i>
                    No messages found for this event.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="replyModal" class="modal-overlay">
        <div class="modal-box">
            <h2>Reply to Message</h2>
            <form action="send_reply_logic.php" method="POST">
                <input type="hidden" name="event_id" value="<?= $event_id; ?>">
                <input type="hidden" name="receiver_id" id="modal_receiver_id">
                
                <div class="form-group">
                    <label>To:</label>
                    <input type="text" id="modal_username" readonly style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" id="modal_subject">
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" rows="5" required placeholder="Type your reply here..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeReplyModal()">Cancel</button>
                    <button type="submit" class="btn-reply">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReplyModal(senderId, username, oldSubject) {
            document.getElementById('replyModal').style.display = 'flex';
            document.getElementById('modal_receiver_id').value = senderId;
            document.getElementById('modal_username').value = username;
            document.getElementById('modal_subject').value = oldSubject.startsWith("Re: ") ? oldSubject : "Re: " + oldSubject;
        }
        function closeReplyModal() { document.getElementById('replyModal').style.display = 'none'; }
        window.onclick = function(e) { if(e.target == document.getElementById('replyModal')) closeReplyModal(); }
    </script>
</body>
</html>