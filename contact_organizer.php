<?php
session_start();
require_once 'config.php';

// 1. Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    // Redirect to login with a return URL (optional improvement)
    header("location: auth/login.php");
    exit;
}

// 2. Get Event ID from URL
if (!isset($_GET['event_id'])) {
    die("Error: Event ID is missing.");
}

$event_id = intval($_GET['event_id']); 
$sender_id = $_SESSION['id'];

// 3. Fetch Event Details & Organizer ID
$stmt = $conn->prepare("SELECT event_name, organizer_id FROM events WHERE event_id = ?"); 
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Event not found.");
}

$event = $result->fetch_assoc();
$recipient_id = $event['organizer_id']; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Organizer | CEMS</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* Local overrides just for the contact card, 
           inheriting the rest from style.css */
        
        .contact-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh; /* Centers card vertically */
            padding: 40px 0;
        }

        .contact-card {
            background: white;
            width: 100%;
            max-width: 600px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .contact-card h1 {
            margin-top: 0;
            font-size: 1.8rem;
            color: #1e293b;
        }

        .contact-subtitle {
            margin-bottom: 25px;
            color: #64748b;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box; /* Fixes padding width issues */
        }

        .form-group input:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .btn-send {
            width: 100%;
            padding: 12px;
            background-color: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 15px;
            transition: background 0.2s;
        }

        .btn-send:hover {
            background-color: #4338ca;
        }

        .btn-back {
            display: inline-block;
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
        }
        
        .btn-back:hover {
            color: #1e293b;
        }
    </style>
</head>
<body>

    <?php include "include/topNav.php"; ?>

    <main>
        <div class="container">
            
            <div class="contact-wrapper">
                <div class="contact-card">
                    <h1>Contact Organizer</h1>
                    <p class="contact-subtitle">
                        Regarding: <strong><?= htmlspecialchars($event['event_name']); ?></strong>
                    </p>

                    <form action="send_message_logic.php" method="POST">
                        <input type="hidden" name="event_id" value="<?= $event_id; ?>">
                        <input type="hidden" name="recipient_id" value="<?= $recipient_id; ?>">

                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required 
                                   placeholder="e.g. Question about venue..." autofocus>
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required 
                                      placeholder="Type your message here..."></textarea>
                        </div>

                        <button type="submit" class="btn-send">
                            <i class="fa-regular fa-paper-plane"></i> Send Message
                        </button>

                        <div style="text-align: center;">
                            <a href="events_details.php?id=<?= $event_id; ?>" class="btn-back">
                                <i class="fa-solid fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>

</body>
</html>