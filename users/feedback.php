<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || !isset($_GET['event_id'])) {
    header("location: dashboard.php");
    exit;
}

$user_id = $_SESSION['id'];
$event_id = intval($_GET['event_id']);
$msg = "";

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Check if already rated
    $check = $conn->prepare("SELECT id FROM feedback WHERE event_id = ? AND user_id = ?");
    $check->bind_param("ii", $event_id, $user_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $msg = "You have already rated this event.";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (event_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $event_id, $user_id, $rating, $comment);
        if ($stmt->execute()) {
            $msg = "success";
        } else {
            $msg = "Error submitting feedback.";
        }
    }
}

// 2. Get Event Details
$sql = "SELECT event_name FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        h2 { margin-top: 0; color: #1e293b; }
        select, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #4f46e5; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #4338ca; }
        .back-link { display: block; margin-top: 15px; color: #64748b; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <?php if ($msg == 'success'): ?>
        <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 3rem; margin-bottom: 20px;"></i>
        <h2>Thank You!</h2>
        <p>Your feedback has been submitted.</p>
        <a href="my_events.php" class="btn">Back to My Events</a>
    <?php else: ?>
        <h2>Rate: <?= htmlspecialchars($event['event_name'] ?? 'Event'); ?></h2>
        <?php if($msg): ?> <p style="color:red;"><?= $msg; ?></p> <?php endif; ?>
        
        <form method="POST">
            <label style="display:block; text-align:left; font-weight:bold; color:#475569;">Rating (1-5)</label>
            <select name="rating" required>
                <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                <option value="4">⭐⭐⭐⭐ (Good)</option>
                <option value="3">⭐⭐⭐ (Average)</option>
                <option value="2">⭐⭐ (Poor)</option>
                <option value="1">⭐ (Terrible)</option>
            </select>
            
            <label style="display:block; text-align:left; font-weight:bold; color:#475569; margin-top:10px;">Comment</label>
            <textarea name="comment" rows="4" placeholder="What did you like or dislike?"></textarea>
            
            <button type="submit" class="btn">Submit Feedback</button>
        </form>
        <a href="my_events.php" class="back-link">Cancel</a>
    <?php endif; ?>
</div>

</body>
</html>