<?php
session_start();
require_once '../config.php';

// Ensure user is logged in
if (!isset($_SESSION['loggedin'])) { header("location: ../auth/login.php"); exit; }

$organizer_id = $_SESSION['id'];
$msg = "";

// 1. Handle Sending Message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_msg'])) {
    $event_id = intval($_POST['event_id']);
    $message = trim($_POST['message']);
    
    // Get Event Name for context
    $evtQuery = $conn->query("SELECT event_name FROM events WHERE event_id = $event_id");
    $evtName = $evtQuery->fetch_assoc()['event_name'];
    
    // Format the full message
    $full_message = "📢 Announcement for <strong>$evtName</strong>: <br>" . htmlspecialchars($message);

    // Get all users registered for this event
    $sql = "SELECT user_id FROM registrations WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt_ins = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $stmt_ins->bind_param("is", $row['user_id'], $full_message);
            $stmt_ins->execute();
            $count++;
        }
        $msg = "Success! Message sent to $count participants.";
    } else {
        $msg = "No participants found for this event.";
    }
}

// 2. Fetch Events owned by this Organizer
$my_events = $conn->query("SELECT event_id, event_name FROM events WHERE organizer_id = $organizer_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Announcement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        label { display: block; margin: 15px 0 5px; font-weight: bold; color: #475569; }
        select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #4f46e5; color: white; border: none; padding: 12px; width: 100%; margin-top: 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #4338ca; }
        .alert { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-bullhorn"></i> Send Announcement</h2>
    
    <?php if ($msg): ?>
        <div class="alert"><?= $msg; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Select Event</label>
        <select name="event_id" required>
            <option value="">-- Choose an Event --</option>
            <?php while($row = $my_events->fetch_assoc()): ?>
                <option value="<?= $row['event_id']; ?>"><?= htmlspecialchars($row['event_name']); ?></option>
            <?php endwhile; ?>
        </select>

        <label>Message</label>
        <textarea name="message" rows="5" placeholder="e.g. The venue has changed to Hall B..." required></textarea>

        <button type="submit" name="send_msg" class="btn">Send to All Participants</button>
    </form>
    
    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
</div>

</body>
</html>