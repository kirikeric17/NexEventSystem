<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) { header("location: ../auth/login.php"); exit; }

$user_id = $_SESSION['id'];

// 1. Mark all as read when page is opened
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

// 2. Fetch Notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #1e293b; }
        .back-btn { text-decoration: none; color: white; background: #64748b; padding: 8px 16px; border-radius: 6px; }
        
        .notif-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #4f46e5; }
        .time { font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px; display: block; }
        .message { color: #334155; line-height: 1.5; }
        .empty { text-align: center; color: #94a3b8; margin-top: 50px; font-size: 1.2rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>🔔 Notifications</h2>
        <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="notif-card">
                <span class="time"><?= date('M d, Y • h:i A', strtotime($row['created_at'])); ?></span>
                <div class="message"><?= nl2br($row['message']); ?></div> </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty">
            <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 10px;"></i>
            <p>You have no notifications yet.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>