<?php
// 1. Start Session (Must be the very first line)
session_start();

// 2. Include Database Config
require_once __DIR__ . '/../config.php'; 

// Initialize variables
$email = $password = "";
$error_msg = "";

// 3. Process Form Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize input
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        // 4. Prepare SQL Query
        $sql = "SELECT id, name, password, role FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();

                // 5. Check if email exists
                if ($stmt->num_rows == 1) {
                    // Bind the result variables
                    $stmt->bind_result($id, $name, $hashed_password, $role);
                    $stmt->fetch();

                    // 6. Verify the Password
                    if (password_verify($password, $hashed_password)) {
                        
                        // Password is correct! Store data in Session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["email"] = $email;
                        $_SESSION["name"] = $name;
                        $_SESSION["role"] = $role; 

                        // --- NEW REDIRECT LOGIC ---
                        if ($role === 'admin') {
                            // UPDATED: Redirect admin directly to Manage Events
                            header("location: ../admin/index.php"); 
                        } else {
                            // Everyone else (student, staff) goes to the User Dashboard
                            header("location: ../index.php");
                        }
                        exit;

                    } else {
                        $error_msg = "The password you entered was not valid.";
                    }
                } else {
                    $error_msg = "No account found with that email address.";
                }
            } else {
                $error_msg = "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Error</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f1f5f9; margin: 0; }
        .error-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 100%; border-top: 5px solid #ef4444; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 25px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px; transition: 0.3s; }
        .btn:hover { background: #4338ca; }
        .error-text { color: #334155; margin-bottom: 20px; font-size: 1.1rem; }
        h2 { color: #ef4444; margin-top: 0; }
    </style>
</head>
<body>
    <div class="error-card">
        <h2>Login Failed</h2>
        <p class="error-text"><?php echo $error_msg; ?></p>
        <a href="login.php" class="btn">Try Again</a>
    </div>
</body>
</html>