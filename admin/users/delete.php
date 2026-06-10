<?php
require_once __DIR__ . '/../../config.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();

// Admin Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Optional: Prevent deleting yourself
    if ($id == $_SESSION['id']) {
        echo "<script>alert('You cannot delete your own account!'); window.location='manage.php';</script>";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: manage.php?msg=deleted");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    header("Location: manage.php");
}
?>