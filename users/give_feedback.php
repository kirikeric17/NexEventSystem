<?php
session_start();
require_once '../config.php'; // Make sure this path points to your DB connection

// 1. Check Login
if (!isset($_SESSION['loggedin'])) {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$msg = "";

// 2. Fetch Event Details (for display)
$sql = "SELECT event_name FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows == 0) {
    die("Event not found.");
}
$event = $result->fetch_assoc();

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    // Insert Feedback
    $stmt = $conn->prepare("INSERT INTO feedback (user_id, event_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $event_id, $rating, $comment);
    
    if ($stmt->execute()) {
        // Success: Redirect back to dashboard
        echo "<script>alert('Thank you for your feedback!'); window.location.href='my_events.php';</script>";
        exit;
    } else {
        $msg = "Error submitting feedback.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Give Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        h2 { color: #1e293b; margin-bottom: 5px; }
        p { color: #64748b; margin-bottom: 25px; }
        
        /* Star Rating CSS */
        .rating { display: flex; justify-content: center; flex-direction: row-reverse; gap: 10px; margin-bottom: 20px; }
        .rating input { display: none; }
        .rating label { font-size: 2rem; color: #ddd; cursor: pointer; transition: 0.2s; }
        .rating input:checked ~ label,
        .rating input:hover ~ label, 
        .rating label:hover ~ label { color: #fbbf24; } /* Gold Color */

        textarea { width: 100%; height: 100px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 20px; resize: none; font-family: sans-serif; }
        .btn-submit { background: #4f46e5; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #4338ca; }
        .btn-cancel { display: block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Rate Event</h2>
        <p>How was <strong><?= htmlspecialchars($event['event_name']); ?></strong>?</p>

        <form method="POST">
            <div class="rating">
                <input type="radio" name="rating" id="star5" value="5" required><label for="star5" title="Excellent"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star4" value="4"><label for="star4" title="Good"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star3" value="3"><label for="star3" title="Average"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star2" value="2"><label for="star2" title="Poor"><i class="fa-solid fa-star"></i></label>
                <input type="radio" name="rating" id="star1" value="1"><label for="star1" title="Terrible"><i class="fa-solid fa-star"></i></label>
            </div>

            <textarea name="comment" placeholder="Share your experience (optional)..."></textarea>

            <button type="submit" class="btn-submit">Submit Feedback</button>
            <a href="my_events.php" class="btn-cancel">Cancel</a>
        </form>
    </div>

</body>
</html>