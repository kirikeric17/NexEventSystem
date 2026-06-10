<?php
session_start();
require_once '../config.php';

// 1. Check Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<script>alert('Please login to register.'); window.location.href='../auth/login.php';</script>";
    exit;
}

// 2. Validate Input
if (!isset($_POST['event_id'])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$event_id = intval($_POST['event_id']);

// 3. CHECK DUPLICATE (The Fix)
// We check the 'registrations' table to see if they are already there.
$check_sql = "SELECT * FROM registrations WHERE user_id = ? AND event_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Already registered
    $stmt->close();
    echo "<script>alert('You have already registered for this event!'); window.location.href='../index.php';</script>";
    exit;
}
$stmt->close();

// 4. INSERT INTO REGISTRATIONS
// We insert into 'registrations' with a default status of 'Pending' (or 'Approved' if you prefer)
$insert_sql = "INSERT INTO registrations (user_id, event_id, registration_date, approval_status) VALUES (?, ?, NOW(), 'Pending')";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("ii", $user_id, $event_id);

if ($stmt->execute()) {
    echo "<script>alert('Registration successful! Please wait for approval.'); window.location.href='my_events.php';</script>";
} else {
    echo "<script>alert('Error registering. Please try again.'); window.location.href='../index.php';</script>";
}

$stmt->close();
$conn->close();
?>