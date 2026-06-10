<?php
require_once __DIR__ . '/../../config.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();

// Admin Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Fetch User Data
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) { die("User not found."); }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        $updateSql = "UPDATE users SET name=?, email=?, role=? WHERE id=?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("sssi", $name, $email, $role, $id);
        
        if ($stmt->execute()) {
            $success = "User updated successfully!";
            // Refresh user data
            $user['name'] = $name;
            $user['email'] = $email;
            $user['role'] = $role;
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .form-container { background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 20px auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-save { background: #4f46e5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div style="padding: 40px; background: #f1f5f9; min-height: 100vh;">
        <a href="manage.php" style="text-decoration:none; color: #64748b;">&larr; Back to Users</a>
        
        <div class="form-container">
            <h2>Edit User</h2>
            
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>