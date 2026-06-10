<?php
require_once '../config.php';

$msg = "";
$msg_type = "";
$valid_link = false;

// 1. GET REQUEST: VALIDATE THE LINK
if (isset($_GET['token']) && isset($_GET['email'])) {
    
    $token = $_GET['token'];
    $email = $_GET['email'];
    $token_hash = hash("sha256", $token);

    // Check database for this email and token hash
    $sql = "SELECT * FROM users WHERE email = ? AND reset_token_hash = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Check if token has expired
        if (strtotime($user['reset_token_expires_at']) <= time()) {
            $msg = "This password reset link has expired.";
            $msg_type = "error";
        } else {
            $valid_link = true; // Link is good, show the form
        }
    } else {
        $msg = "Invalid reset link. Token mismatch.";
        $msg_type = "error";
    }
} else if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $msg = "Invalid link parameters.";
    $msg_type = "error";
}

// 2. POST REQUEST: PROCESS PASSWORD CHANGE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $email = $_POST['email'];
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];

    // Basic Validation
    if (strlen($pass1) < 6) {
        $msg = "Password must be at least 6 characters long.";
        $msg_type = "error";
        $valid_link = true; // Keep form open to try again
    } elseif ($pass1 !== $pass2) {
        $msg = "Passwords do not match.";
        $msg_type = "error";
        $valid_link = true;
    } else {
        // Hash the new password
        $new_password_hash = password_hash($pass1, PASSWORD_DEFAULT);

        // UPDATE PASSWORD AND CLEAR TOKEN (Critical Security Step)
        $sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $new_password_hash, $email);

        if ($stmt->execute()) {
            $msg = "Password has been reset successfully! <br> <a href='login.php' style='color:#15803d; font-weight:bold;'>Login Now</a>";
            $msg_type = "success";
            $valid_link = false; // Hide form
        } else {
            $msg = "Error updating password.";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | NX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary: #4f46e5; --bg: #f1f5f9; }
        body { background-color: var(--bg); font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h2 { margin-top: 0; color: #1e293b; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; color: #334155; font-weight: 600; }
        input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        input:focus { outline: none; border-color: var(--primary); }
        .btn-submit { width: 100%; padding: 12px; background-color: var(--primary); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background-color: #4338ca; }
        
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.95rem; text-align: left; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Reset Password</h2>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if ($valid_link): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? $_POST['token']); ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? $_POST['email']); ?>">

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Min 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-type password" required>
                </div>

                <button type="submit" class="btn-submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <?php if (!$valid_link && $msg_type == "error"): ?>
            <p><a href="forgot_password.php" style="color:var(--primary); text-decoration:none;">Request a new link</a></p>
        <?php endif; ?>
    </div>

</body>
</html>