<?php
session_start();
require_once '../../config/config.php';

// 1. Security Check
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("location: ../../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Get Input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    // 3. Validation
    if (empty($name) || empty($email) || empty($password)) {
        header("Location: create.php?error=All fields are required");
        exit;
    }

    // 4. Check if Email Exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            header("Location: create.php?error=Email already registered");
            exit;
        }
        $stmt->close();
    }

    // 5. Insert New User
    $insert_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_sql)) {
        // Hash Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            // Success
            header("Location: manage.php?success=User created successfully");
        } else {
            // SQL Error
            header("Location: create.php?error=Something went wrong. Please try again.");
        }
        $stmt->close();
    }
    
    $conn->close();
} else {
    // Redirect if accessed directly without POST
    header("Location: create.php");
    exit;
}
?>