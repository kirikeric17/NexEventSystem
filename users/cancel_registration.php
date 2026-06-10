<?php
session_start();
require_once '../config.php'; // Ensure this path matches your my_events.php config path

// 1. Check Login
if (!isset($_SESSION['loggedin'])) {
    header("location: ../auth/login.php");
    exit;
}

// 2. Process Cancellation
if (isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
    $user_id = $_SESSION['id'];

    // Delete from 'registrations' table
    $stmt = $conn->prepare("DELETE FROM registrations WHERE event_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $event_id, $user_id);

    if ($stmt->execute()) {
        header("Location: my_events.php?msg=cancelled");
    } else {
        header("Location: my_events.php?msg=error");
    }
    $stmt->close();
} else {
    header("Location: my_events.php");
}
exit;
?>