<?php
session_start();
// FIX: Add "../" to find config.php
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) {
    // FIX: Add "../" to find login
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- CASE 1: UPDATE INFO ---
    if ($_POST['action'] == 'update_info') {
        $name  = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
            if ($stmt->execute()) {
                // Update session name immediately so the header updates too
                $_SESSION['name'] = $name;
                header("location: dashboard.php?msg=updated");
            } else {
                header("location: dashboard.php?error=Database error");
            }
            $stmt->close();
        }
    }

    // --- CASE 2: CHANGE PASSWORD ---
    elseif ($_POST['action'] == 'change_password') {
        $current_pass = $_POST['current_password'];
        $new_pass     = $_POST['new_password'];

        // 1. Get current hash from DB
        $sql = "SELECT password FROM users WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($db_hash);
            $stmt->fetch();
            $stmt->close();

            // 2. Verify current password
            if (password_verify($current_pass, $db_hash)) {
                // 3. Hash new password and update
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                
                $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                if ($ustmt = $conn->prepare($update_sql)) {
                    $ustmt->bind_param("si", $new_hash, $user_id);
                    $ustmt->execute();
                    $ustmt->close();
                    header("location: dashboard.php?msg=updated");
                }
            } else {
                header("location: dashboard.php?error=Current password is incorrect");
            }
        }
    }
}
$conn->close();
?>