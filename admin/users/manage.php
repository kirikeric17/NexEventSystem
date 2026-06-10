<?php
// 1. Config & Session
require_once __DIR__ . '/../../config.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();

// Define BASE_URL if missing (Fallback)
if (!defined('BASE_URL')) {
    define('BASE_URL', '../..'); 
}

// 2. Admin Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

// 3. Handle Search Logic
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC";

if (!empty($search)) {
    // If searching, filter by Name or Email
    $sql = "SELECT * FROM users WHERE role != 'admin' AND (name LIKE ? OR email LIKE ?) ORDER BY created_at DESC";
}

$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("ss", $searchParam, $searchParam);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* [Existing Styles Kept...] */
        :root {
            --primary: #0d6efd;
            --danger: #dc3545;
            --sidebar-bg: #343a40;
            --sidebar-text: #c2c7d0;
            --bg-body: #f4f6f9;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        a { text-decoration: none; }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            height: 100vh;
            flex-shrink: 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .menu {
            display: flex;
            flex-direction: column;
            padding: 0;
            margin: 0;
            height: 100%;
        }

        .menu::before {
            content: 'NX Admin';
            display: block;
            padding: 20px;
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid #4b545c;
            margin-bottom: 10px;
        }

        .menu-item, .submenu-toggle {
            padding: 15px 20px;
            color: var(--sidebar-text);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            cursor: pointer;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            transition: 0.2s;
            box-sizing: border-box;
        }

        .menu-item:hover, .submenu-toggle:hover {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
            padding-left: 25px;
        }

        .menu-item.active {
            background-color: var(--primary);
            color: #fff;
        }

        .submenu-content {
            display: none;
            background-color: rgba(0,0,0,0.2);
        }
        
        .submenu.open .submenu-content {
            display: block;
        }

        .submenu-content a {
            display: block;
            padding: 10px 20px 10px 40px;
            color: var(--sidebar-text);
            font-size: 0.9rem;
        }

        .submenu-content a:hover {
            color: #fff;
        }

        .menu-item.delete {
            margin-top: auto; 
            background-color: var(--danger);
            color: white;
            font-weight: 600;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f1f5f9;
        }

        .page-header {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px;
        }

        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-search { padding: 8px 15px; background: #4f46e5; color: white; border: none; border-radius: 5px; cursor: pointer; }
        
        /* New Create Button Style */
        .btn-create {
            background-color: #10b981; 
            color: white; 
            padding: 10px 20px; 
            border-radius: 6px; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn-create:hover { background-color: #059669; transform: translateY(-1px); }

        /* Success Alert */
        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; font-weight: 600; color: #64748b; }
        
        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; }
        .role-student { background: #e0f2fe; color: #0284c7; }
        .role-staff { background: #fef3c7; color: #d97706; }
        .role-public { background: #f3e8ff; color: #7e22ce; }
        .role-guest { background: #f3f4f6; color: #4b5563; }
        
        .action-btn { 
            text-decoration: none; 
            margin-right: 15px; 
            font-size: 0.95rem; 
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit { color: #2563eb !important; }
        .btn-delete { color: #dc2626 !important; }

    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <nav class="menu">
            <a href="<?= BASE_URL ?>/admin/index.php" class="menu-item">
                <i class="fas fa-chart-pie" style="width:20px;"></i> Dashboard
            </a>
            
            <a href="<?= BASE_URL ?>/admin/users/manage.php" class="menu-item active">
                <i class="fas fa-users" style="width:20px;"></i> Users
            </a>

            <div class="submenu">
                <button class="submenu-toggle" onclick="toggleMenu(this)">
                    <i class="fas fa-calendar-alt" style="width:20px;"></i> Events ▾
                </button>
                <div class="submenu-content">
                    <a href="<?= BASE_URL ?>/admin/events/manage.php">Manage Events</a>
                    <a href="<?= BASE_URL ?>/admin/events/create.php">Create Event</a>
                </div>
            </div>

            <a href="<?= BASE_URL ?>/auth/logout.php" class="menu-item delete">
                <i class="fa-solid fa-right-from-bracket" style="width:20px;"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="main-content">
        
        <div class="page-header">
            <div>
                <h1 style="margin:0;">Manage Users</h1>
                <p style="color:#64748b; margin:5px 0 0 0;">Total Users Found: <?= $result->num_rows ?></p>
            </div>
            
            <div style="display: flex; gap: 15px; align-items: center;">
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </form>

                <a href="create.php" class="btn-create">
                    <i class="fas fa-plus"></i> Add User
                </a>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <?php 
                                    $badgeClass = 'role-guest';
                                    if($row['role'] == 'student') $badgeClass = 'role-student';
                                    elseif($row['role'] == 'staff' || $row['role'] == 'organizer') $badgeClass = 'role-staff';
                                    elseif($row['role'] == 'public') $badgeClass = 'role-public';
                                ?>
                                <span class="role-badge <?= $badgeClass ?>">
                                    <?= ucfirst($row['role']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete.php?id=<?= $row['id'] ?>" 
                                   class="action-btn btn-delete"
                                   onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 30px; color: #94a3b8;">
                            No users found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </main>

    <script>
        function toggleMenu(button) {
            button.parentElement.classList.toggle('open');
        }
    </script>

</body>
</html>