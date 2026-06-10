<?php
// 1. Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

// 2. Check Login
if (!isset($_SESSION['loggedin'])) {
    header("location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Get Inputs
    $sender_id = $_SESSION['id']; // You (Organizer)
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0; // The Student
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // 4. Validate Inputs
    if (empty($message) || empty($subject) || $receiver_id == 0 || $event_id == 0) {
        echo "Error: Missing Data. <a href='javascript:history.back()'>Go Back</a>";
        exit;
    }

    // 5. Insert Reply into Messages Table
    $sql = "INSERT INTO messages (event_id, sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iiiss", $event_id, $sender_id, $receiver_id, $subject, $message);
        
        if ($stmt->execute()) {
            
            // --- NEW CODE: CREATE NOTIFICATION ---
            // This inserts a notification for the student ($receiver_id)
            
            $notif_msg = "New reply from Organizer: " . $subject;
            $notif_sql = "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)";
            
            if ($notif_stmt = $conn->prepare($notif_sql)) {
                $notif_stmt->bind_param("is", $receiver_id, $notif_msg);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
            // -------------------------------------

            echo "<script>
                    alert('Reply sent & Notification created!');
                    window.location.href = 'event_messages.php?event_id=" . $event_id . "';
                  </script>";
        } else {
            echo "Database Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare Error: " . $conn->error;
    }
    
    $conn->close();

} else {
    header("location: ../index.php");
    exit;
}
?>