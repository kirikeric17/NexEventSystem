<?php
session_start();
require_once 'config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Check if user is logged in
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("location: auth/login.php");
        exit;
    }

    // 2. Get inputs
    $sender_id = $_SESSION['id'];
    // Use isset checks to prevent "Undefined index" notices
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    // MATCHES THE NAME IN YOUR HTML FORM
    $organizer_id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // 3. Validation
    if (empty($message) || empty($subject)) {
        echo "<script>alert('Message and Subject cannot be empty.'); window.history.back();</script>";
        exit;
    }

    // 4. Insert into Database
    // ENSURE THIS LINE SAYS 'receiver_id' NOT 'recipient_id'
    $sql = "INSERT INTO messages (event_id, sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iiiss", $event_id, $sender_id, $organizer_id, $subject, $message);
        
        if ($stmt->execute()) {
            echo "<script>
                    alert('Message sent successfully!');
                    window.location.href = 'events_details.php?id=" . $event_id . "'; 
                  </script>";
        } else {
            // Debugging: Show specific SQL error
            echo "SQL Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Database Prepare Error: " . $conn->error;
    }
    
    $conn->close();
} else {
    header("location: index.php");
    exit;
}
?>