<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header("location: auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch notifications for the logged-in user
$sql = "SELECT n.*, e.event_name 
        FROM notifications n 
        JOIN events e ON n.event_id = e.event_id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications | NexEvent</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .notif-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .notif-card {
            background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }
        .notif-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .notif-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .event-tag { background: #e0e7ff; color: #4f46e5; font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .notif-date { color: #94a3b8; font-size: 0.85rem; }
        .notif-subject { font-weight: 700; color: #1e293b; font-size: 1.1rem; margin-bottom: 5px; }
        .notif-body { color: #475569; line-height: 1.6; }
    </style>
</head>
<body>

<?php include "include/topNav.php"; ?>

<div class="notif-container">
    <h2 style="margin-bottom: 20px;">My Notifications</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="notif-card">
                <div class="notif-header">
                    <span class="event-tag"><?= htmlspecialchars($row['event_name']); ?></span>
                    <span class="notif-date"><?= date('M d, H:i', strtotime($row['created_at'])); ?></span>
                </div>
                <div class="notif-subject"><?= htmlspecialchars($row['subject']); ?></div>
                <div class="notif-body">
                    <?= nl2br(htmlspecialchars($row['message'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; color: #94a3b8; margin-top: 50px;">
            <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No notifications yet.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>