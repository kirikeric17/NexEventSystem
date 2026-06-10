<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) { header("location: ../auth/login.php"); exit; }

$organizer_id = $_SESSION['id'];

// Fetch feedback for ALL events created by this organizer
$sql = "SELECT f.*, e.event_name, u.email as participant_name 
        FROM feedback f 
        JOIN events e ON f.event_id = e.event_id 
        JOIN users u ON f.user_id = u.id 
        WHERE e.organizer_id = ? 
        ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h2 { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .review-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .stars { color: #f59e0b; letter-spacing: 2px; }
        .event-name { font-weight: bold; color: #4f46e5; font-size: 0.9rem; text-transform: uppercase; }
        .meta { font-size: 0.85rem; color: #64748b; margin-top: 5px; }
        .back-btn { text-decoration: none; color: white; background: #475569; padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>📝 Feedback Received</h2>
        <a href="my_events.php" class="back-btn">Back to My Events</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="review-card">
                <div class="event-name"><?= htmlspecialchars($row['event_name']); ?></div>
                <div class="stars">
                    <?php for($i=0; $i<$row['rating']; $i++) echo '★'; ?>
                    <?php for($i=$row['rating']; $i<5; $i++) echo '☆'; ?>
                </div>
                <p style="margin: 10px 0; font-style: italic;">"<?= htmlspecialchars($row['comment']); ?>"</p>
                <div class="meta">
                    By: <?= htmlspecialchars($row['participant_name']); ?> • 
                    <?= date('M d, Y', strtotime($row['created_at'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; color:#94a3b8; margin-top:30px;">No feedback received yet.</p>
    <?php endif; ?>
</div>

</body>
</html>