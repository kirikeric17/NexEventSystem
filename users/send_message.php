<?php
// 1. Start Session & Config
session_start();
require_once '../config.php'; 

// 2. Security Check
// User must be logged in to send a message
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<script>alert('Please login to contact the organizer.'); window.location.href='../auth/login.php';</script>";
    exit;
}

// 3. Get Event Details
if (!isset($_GET['event_id'])) {
    echo "Event ID is missing.";
    exit;
}

$event_id = intval($_GET['event_id']);

// Fetch Event Name and Organizer ID automatically
// We need the organizer_id to know who receives the message
$sql = "SELECT event_name, organizer_id FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Event not found.";
    exit;
}

$event = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Organizer - <?= htmlspecialchars($event['event_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; margin: 0; padding: 40px 20px; }
        
        .contact-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        h2 { margin-top: 0; color: #1f2937; font-size: 1.5rem; }
        p { color: #6b7280; margin-bottom: 30px; }

        .form-group { margin-bottom: 20px; }
        
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
        
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            font-family: inherit;
        }

        textarea { height: 150px; resize: vertical; }

        input:focus, textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        
        button {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            transition: 0.2s;
        }

        .btn-send { background: #4f46e5; color: white; }
        .btn-send:hover { background: #4338ca; }

        .btn-cancel { background: #e5e7eb; color: #374151; text-decoration: none; text-align: center; display: inline-block; box-sizing: border-box; }
        .btn-cancel:hover { background: #d1d5db; }
    </style>
</head>
<body>

    <div class="contact-card">
        <h2>Contact Organizer</h2>
        <p>Send a message regarding <strong><?= htmlspecialchars($event['event_name']); ?></strong>.</p>

        <form action="../send_message_logic.php" method="POST">
            
            <input type="hidden" name="event_id" value="<?= $event_id; ?>">
            <input type="hidden" name="organizer_id" value="<?= $event['organizer_id']; ?>">

            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required placeholder="e.g. Question about venue..." value="Inquiry: <?= htmlspecialchars($event['event_name']); ?>">
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" required placeholder="Type your question here..."></textarea>
            </div>

            <div class="btn-group">
                <a href="../event_details.php?id=<?= $event_id; ?>" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Send Message</button>
            </div>

        </form>
    </div>

</body>
</html>