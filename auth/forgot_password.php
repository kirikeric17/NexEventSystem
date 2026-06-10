<?php
session_start();
require_once '../config.php';

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $conn->real_escape_string($_POST['email']);

    // 1. Check if email exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 2. Generate a unique token
        $token = bin2hex(random_bytes(16)); // 32 characters
        $token_hash = hash("sha256", $token); // Hash it for storage
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // Expires in 30 minutes

        // 3. Update User Record with Token
        $update_sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $token_hash, $expiry, $email);
        
        if ($update_stmt->execute()) {
            // ---------------------------------------------------------
            // SIMULATING EMAIL SENDING (For Localhost Testing)
            // In a real app, you would use mail() or PHPMailer here.
            // ---------------------------------------------------------
            
            $reset_link = "http://localhost/cems/auth/reset_password.php?token=" . $token . "&email=" . $email;
            
            $msg = "<strong>Simulation Mode:</strong><br>We found your account. <br>
                    <a href='$reset_link' style='color: #4f46e5; font-weight:bold;'>Click here to Reset Password</a>";
            $msg_type = "success";
        } else {
            $msg = "Something went wrong. Please try again.";
            $msg_type = "error";
        }
    } else {
        // Security: Usually you don't want to tell people if an email exists, 
        // but for this project, we will show an error for clarity.
        $msg = "No account found with that email address.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | CEMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --bg: #f1f5f9;
        }
        body {
            background-color: var(--bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-card h2 {
            margin-top: 0;
            color: #1e293b;
            font-size: 1.8rem;
        }
        .login-card p {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box; 
            transition: 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-submit:hover {
            background-color: #4338ca;
        }
        .links {
            margin-top: 20px;
            font-size: 0.9rem;
        }
        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .links a:hover {
            text-decoration: underline;
        }
        
        /* Alert Styles */
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: left;
        }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>Forgot Password?</h2>
        <p>Enter your email address and we'll send you a link to reset your password.</p>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="e.g. user@example.com" required>
            </div>
            
            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>

        <div class="links">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>

</body>
</html>