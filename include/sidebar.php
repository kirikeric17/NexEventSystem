<?php
session_start();
require_once 'config.php';

// 1. Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("location: auth/login.php");
    exit;
}

// 2. Get Event ID from URL
if (!isset($_GET['event_id'])) {
    die("Error: Event ID is missing.");
}

$event_id = intval($_GET['event_id']); // Sanitize input
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* --- Global Reset & Body --- */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #334155;
            display: flex; /* Makes the body a flex container for Sidebar + Content */
            height: 100vh;
            overflow: hidden; /* Prevents double scrollbars */
        }

        /* --- Sidebar Placeholder Styling (If sidebar.php styles are missing) --- */
        /* This ensures the sidebar area exists even if the included file has no width set */
        .sidebar-container {
            width: 250px;
            background: #1e293b; /* Match your admin dark theme */
            flex-shrink: 0;
            height: 100%;
            overflow-y: auto;
        }

        /* --- Main Content Area --- */
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto; /* Allows scrolling only inside the content area */
        }

        /* --- Top Nav Correction --- */
        /* Assuming topNav.php contains the header */
        header, .top-nav {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 20px;
        }

        /* --- Center Container for the Form --- */
        .content-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        /* --- Card Design --- */
        .contact-card {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        /* --- Typography --- */
        h1 { margin: 0 0 5px 0; font-size: 1.5rem; color: #1e293b; font-weight: 700; }
        .subtitle { margin: 0 0 30px 0; color: #64748b; font-size: 0.95rem; }
        .subtitle strong { color: #334155; font-weight: 600; }

        /* --- Form Elements --- */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #64748b; font-weight: 500; }
        
        input[type="text"], textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            color: #334155;
            box-sizing: border-box;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        textarea { resize: vertical; min-height: 120px; }

        /* --- Buttons --- */
        .btn-submit {
            display: block; width: 100%; padding: 12px;
            background-color: #4f46e5; color: white;
            border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: background-color 0.2s;
            margin-bottom: 15px;
        }
        .btn-submit:hover { background-color: #4338ca; }

        .btn-cancel {
            display: inline-flex; align-items: center;
            color: #64748b; text-decoration: none;
            font-size: 0.9rem; font-weight: 500;
            transition: color 0.2s;
        }
        .btn-cancel:hover { color: #1e293b; }
        .btn-cancel i { margin-right: 6px; font-size: 0.8rem; }
    </style>
</head>
<body>

    <div class="sidebar-container">
        <?php include "include/sidebar.php"; ?>
    </div>

    <div class="main-wrapper">
        
        <?php include "include/topNav.php"; ?>

        <div class="content-container">
            <div class="contact-card">
                
                <h1>Contact Organizer</h1>
                <p class="subtitle">
                    Regarding: <strong><?= htmlspecialchars($event['event_name']); ?></strong>
                </p>

                <form action="send_message_logic.php" method="POST">
                    <input type="hidden" name="event_id" value="<?= $event_id; ?>">
                    <input type="hidden" name="recipient_id" value="<?= $recipient_id; ?>">

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required 
                               placeholder="Type e.g. Question about venue..." autofocus>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required 
                                  placeholder="Type your message here..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Send Message</button>
                    
                    <a href="events_details.php?id=<?= $event_id; ?>" class="btn-cancel">
                        <i class="fa-solid fa-arrow-left"></i> Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>

</body>
</html>