<?php
session_start();
require_once '../config.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../auth/login.php");
    exit;
}

// 2. Fetch User Data
$user_id = $_SESSION['id'];
$sql = "SELECT name, email, phone, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle case if user is deleted while logged in
if (!$user) {
    session_destroy();
    header("location: ../auth/login.php");
    exit;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | NX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* DASHBOARD STYLES (Admin-like look) */
        :root {
            --primary: #4f46e5;
            --sidebar-bg: #1e293b;
            --sidebar-text: #f8fafc;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #334155;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background-color: var(--bg-body);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
        }

        /* UPDATED BRAND SECTION WITH LOGO */
        .brand {
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid #334155;
            
            /* Flexbox to align logo and text */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px; /* Space between logo and text */
        }

        /* NEW LOGO STYLE */
        .logo {
            width: 50px;       /* Small and just nice */
            height: 50px;
            object-fit: cover; /* Ensures image doesn't stretch */
            border-radius: 100%; /* Optional: Makes it circular (looks modern) */
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .nav-links li a {
            display: block;
            padding: 15px 25px;
            color: #94a3b8;
            text-decoration: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links li a:hover, .nav-links li a.active {
            background-color: var(--primary);
            color: white;
        }

        /* RED LOGOUT BUTTON STYLE */
        .nav-links li a.logout-btn {
            color: #ef4444; 
            font-weight: bold;
            margin-top: 20px;
            border-top: 1px solid #334155;
        }
        .nav-links li a.logout-btn:hover {
            background-color: #ef4444; 
            color: white;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 250px; 
            flex: 1;
            padding: 30px;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-notify {
            background-color: #f59e0b;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn-notify:hover { background-color: #d97706; }

        .btn-action {
            background-color: #0ea5e9;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-right: 10px;
        }

        .card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .card h3 { margin-top: 0; color: var(--primary); border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-main); }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .form-group input:disabled { background-color: #f1f5f9; color: #94a3b8; }

        .btn-save {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-save:hover { background-color: #4338ca; }

        .alert { padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .sidebar { width: 60px; }
            .sidebar .text, .brand span { display: none; } /* Hide text on mobile */
            .brand { padding: 10px; }
            .main-content { margin-left: 60px; }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <img src="../assets/img/logonew2.jpg" alt="NX Logo" class="logo"> 
            <span>NX</span>
        </div>

        <ul class="nav-links">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-user"></i> <span class="text">Dashboard</span></a></li>
            
            <li><a href="my_events.php"><i class="fa-solid fa-calendar-check"></i> <span class="text">My Events</span></a></li>
            
            <?php if($user['role'] == 'organizer'): ?>
            <li>
                <a href="manage_participants.php">
                    <i class="fa-solid fa-users-gear"></i> <span class="text">Manage Participants</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li><a href="../index.php"><i class="fa-solid fa-home"></i> <span class="text">Home</span></a></li>
            
            <li><a href="../auth/logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> <span class="text">Logout</span></a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="header">
            <div class="welcome-group">
                <h2 style="margin:0;">Welcome, <?= htmlspecialchars($user['name']); ?></h2>
                <span style="background: #e0e7ff; color: #4f46e5; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: bold;">
                    <?= ucfirst($user['role']); ?>
                </span>
            </div>
            
            <a href="notifications.php" class="btn-notify">
                <i class="fa-solid fa-bell"></i> Notifications
            </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div class="alert alert-success">Profile updated successfully!</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if($user['role'] == 'organizer'): ?>
            <div class="card">
                <h3>Organizer Actions</h3>
                <p>Manage your event communications here.</p>
                <a href="send_announcement.php" class="btn-action"><i class="fa-solid fa-bullhorn"></i> Send Announcement</a>
                <a href="view_feedback.php" class="btn-action" style="background-color: #6366f1;"><i class="fa-solid fa-star"></i> View Feedback</a>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Personal Details</h3>
            <form action="profile_action.php" method="POST">
                <input type="hidden" name="action" value="update_info">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']); ?>">
                </div>

                <div class="form-group">
                    <label>Role (Cannot be changed)</label>
                    <input type="text" value="<?= ucfirst($user['role']); ?>" disabled>
                </div>

                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>

        <div class="card">
            <h3>Security</h3>
            <form action="profile_action.php" method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required placeholder="Enter current password">
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Min 6 characters">
                </div>

                <button type="submit" class="btn-save">Update Password</button>
            </form>
        </div>
    </div>

</body>
</html>