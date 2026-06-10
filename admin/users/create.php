<?php
// 1. Config & Session - Path matched to your manage.php
require_once __DIR__ . '/../../config.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();

// Define BASE_URL if missing
if (!defined('BASE_URL')) {
    define('BASE_URL', '../..'); 
}

// 2. Admin Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User | NexEvent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* matching your manage.php layout */
        :root {
            --primary: #0d6efd;
            --sidebar-bg: #343a40;
            --sidebar-text: #c2c7d0;
            --bg-body: #f1f5f9;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles (Copy-pasted from your manage.php for consistency) */
        .sidebar { width: 250px; background-color: var(--sidebar-bg); display: flex; flex-direction: column; height: 100vh; flex-shrink: 0; }
        .menu { display: flex; flex-direction: column; padding: 0; margin: 0; height: 100%; }
        .menu::before { content: 'NX Admin'; display: block; padding: 20px; font-size: 1.25rem; font-weight: 600; color: #fff; background: rgba(0,0,0,0.2); border-bottom: 1px solid #4b545c; margin-bottom: 10px; }
        .menu-item { padding: 15px 20px; color: var(--sidebar-text); display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .menu-item:hover { background-color: rgba(255,255,255,0.1); color: #fff; }
        .menu-item.active { background-color: var(--primary); color: #fff; }
        .menu-item.delete { margin-top: auto; background-color: #dc3545; color: white; }

        .main-content { flex: 1; padding: 30px; overflow-y: auto; }

        /* Form Styles */
        .form-card {
            max-width: 600px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        .btn-submit {
            background-color: #4f46e5;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
        }
        .btn-submit:hover { background-color: #4338ca; }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <nav class="menu">
            <a href="<?= BASE_URL ?>/admin/index.php" class="menu-item">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/users/manage.php" class="menu-item active">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="menu-item delete">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <a href="manage.php" style="text-decoration: none; color: #64748b; margin-bottom: 20px; display: inline-block;">
            <i class="fa-solid fa-arrow-left"></i> Back to User List
        </a>

        <div class="form-card">
            <h2 style="margin: 0 0 20px 0; color: #1e293b;">Add New User</h2>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="create_action.php" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Full Name">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="email@example.com">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                        <option value="public">Public</option>
                        <option value="organizer">Organizer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Temporary Password">
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i> Create User Account
                </button>
            </form>
        </div>
    </main>

</body>
</html>