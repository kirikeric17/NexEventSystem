<?php 
// 1. Start Session & Include Config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure config is included (defines BASE_URL if strictly needed, though we use relative paths here mostly)
require_once '../config.php'; 

// Define BASE_URL if not already defined to prevent errors in your sidebar code
if (!defined('BASE_URL')) {
    define('BASE_URL', '..'); // Fallback to parent directory
}

// 2. SECURITY CHECK
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../users/dashboard.php");
    exit;
}

// 3. FETCH STATISTICS
// A. Total Students
$sql_users = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$res_users = $conn->query($sql_users);
$total_students = $res_users ? $res_users->fetch_assoc()['total'] : 0;

// B. Total Events
$sql_events = "SELECT COUNT(*) as total FROM events";
$res_events = $conn->query($sql_events);
$total_events = $res_events ? $res_events->fetch_assoc()['total'] : 0;

// C. Total Registrations
$total_participants = 0;
$check_table = $conn->query("SHOW TABLES LIKE 'event_participants'");
if ($check_table && $check_table->num_rows > 0) {
    $sql_part = "SELECT COUNT(*) as total FROM event_participants";
    $res_part = $conn->query($sql_part);
    $total_participants = $res_part ? $res_part->fetch_assoc()['total'] : 0;
}

// 4. FETCH EVENTS ASSIGNED TO ADMIN
$admin_id = $_SESSION['id'] ?? 0; 
$sql_my_events = "SELECT event_id, event_name, event_date, venue FROM events WHERE organizer_id = ?";
$stmt = $conn->prepare($sql_my_events);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$my_events = $stmt->get_result();
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
  <meta charset="UTF-8" /> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
  <title>NX - Admin Dashboard</title> 
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <style>
      /* --- RESET & LAYOUT --- */
      :root {
          --primary: #0d6efd;       /* Blue for Active Items */
          --danger: #dc3545;        /* Red for Logout */
          --sidebar-bg: #343a40;    /* Dark Sidebar */
          --sidebar-text: #c2c7d0;  /* Light Text */
          --bg-body: #f4f6f9;
      }

      body {
          margin: 0;
          font-family: 'Inter', sans-serif;
          background-color: var(--bg-body);
          display: flex; /* Sidebar Left, Content Right */
          height: 100vh;
          overflow: hidden;
      }

      a { text-decoration: none; }

      /* --- YOUR SIDEBAR STYLES (Dark Theme) --- */
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
          height: 100%;
      }

      /* Sidebar Brand/Header Area (Optional visual padding) */
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

      /* Menu Items */
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
      }

      .menu-item:hover, .submenu-toggle:hover {
          background-color: rgba(255,255,255,0.1);
          color: #fff;
          padding-left: 25px;
      }

      /* Active State */
      .menu-item.active {
          background-color: var(--primary);
          color: #fff;
      }

      /* Submenu Styles */
      .submenu-content {
          display: none; /* Hidden by default */
          background-color: rgba(0,0,0,0.2);
      }
      
      .submenu.open .submenu-content {
          display: block;
      }

      .submenu-content a {
          display: block;
          padding: 10px 20px 10px 40px; /* Indented */
          color: var(--sidebar-text);
          font-size: 0.9rem;
      }

      .submenu-content a:hover {
          color: #fff;
      }

      /* Logout Button (Red, pushed to bottom) */
      .menu-item.delete {
          margin-top: auto; /* Pushes to bottom */
          background-color: var(--danger);
          color: white;
          font-weight: 600;
      }

      .menu-item.delete:hover {
          background-color: #bb2d3b;
      }

      /* --- MAIN CONTENT STYLES --- */
      .main-content {
          flex: 1;
          padding: 30px;
          overflow-y: auto;
      }

      .header h1 { font-size: 1.8rem; margin: 0; color: #333; }
      
      /* Cards & Tables */
      .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin: 30px 0; }
      
      .card { 
          background: white; padding: 24px; border-radius: 12px; 
          box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
          display: flex; justify-content: space-between; align-items: center;
      }
      .card h3 { margin: 0 0 5px 0; color: #6b7280; font-size: 0.9rem; font-weight: 500; }
      .card .number { font-size: 2rem; font-weight: 700; color: #111827; }
      
      .icon-box { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
      .icon-blue { background: #e0e7ff; color: #4f46e5; }
      .icon-green { background: #d1fae5; color: #10b981; }
      .icon-orange { background: #fef3c7; color: #f59e0b; }

      .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
      .table-header { font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
      
      table { width: 100%; border-collapse: separate; border-spacing: 0; }
      th { text-align: left; padding: 12px; background: #f9fafb; color: #6b7280; font-size: 0.85rem; text-transform: uppercase; }
      td { padding: 16px 12px; border-bottom: 1px solid #f3f4f6; color: #374151; }
      
      .btn-manage { background: #4f46e5; color: white; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; margin-right: 5px; }
      .btn-msg { border: 1px solid #d97706; color: #d97706; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; }

  </style>
</head> 
<body> 

  <aside class="sidebar" id="sidebar">
    <nav class="menu">
      <a href="<?= BASE_URL ?>/admin/index.php" class="menu-item active">
          <i class="fas fa-chart-pie"></i> Dashboard
      </a>
      <a href="<?= BASE_URL ?>/admin/users/manage.php" class="menu-item">
          <i class="fas fa-users"></i> Users
      </a>

      <div class="submenu">
        <button class="submenu-toggle" onclick="toggleMenu(this)">
            <i class="fas fa-calendar-alt"></i> Events ▾
        </button>
        <div class="submenu-content">
          <a href="<?= BASE_URL ?>/admin/events/manage.php">Manage Events</a>
          <a href="<?= BASE_URL ?>/admin/events/create.php">Create Event</a>
        </div>
      </div>

      <a href="logout.php" class="menu-item delete">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    </nav>
  </aside>

  <main class="main-content">
      <div class="header">
          <h1>Dashboard</h1>
          <p style="color: #6b7280; margin: 5px 0 0;">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
      </div>

      <div class="stats-grid">
          <div class="card">
              <div>
                  <h3>Total Students</h3>
                  <div class="number"><?= $total_students ?></div>
              </div>
              <div class="icon-box icon-blue"><i class="fas fa-user-graduate"></i></div>
          </div>

          <div class="card">
              <div>
                  <h3>Active Events</h3>
                  <div class="number"><?= $total_events ?></div>
              </div>
              <div class="icon-box icon-green"><i class="fas fa-calendar-check"></i></div>
          </div>

          <div class="card">
              <div>
                  <h3>Registrations</h3>
                  <div class="number"><?= $total_participants ?></div>
              </div>
              <div class="icon-box icon-orange"><i class="fas fa-users"></i></div>
          </div>
      </div>

      <div class="table-container">
          <div class="table-header">Manage My Events</div>
          <?php if($my_events->num_rows > 0): ?>
              <table>
                  <thead>
                      <tr>
                          <th>Event Name</th>
                          <th>Date</th>
                          <th>Venue</th>
                          <th style="text-align:right;">Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php while($event = $my_events->fetch_assoc()): ?>
                          <tr>
                              <td style="font-weight: 500;"><?= htmlspecialchars($event['event_name']); ?></td>
                              <td><span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px;"><?= date('M d, Y', strtotime($event['event_date'])); ?></span></td>
                              <td><?= htmlspecialchars($event['venue']); ?></td>
                              <td style="text-align: right;">
                                  <a href="organizer/manage_participants.php?event_id=<?= $event['event_id']; ?>" class="btn-manage">Manage</a>
                                  <a href="../users/event_messages.php?event_id=<?= $event['event_id']; ?>" class="btn-msg">Messages</a>
                              </td>
                          </tr>
                      <?php endwhile; ?>
                  </tbody>
              </table>
          <?php else: ?>
              <p style="text-align:center; color:#6b7280; padding:20px;">No events assigned.</p>
          <?php endif; ?>
      </div>
  </main>

  <script>
      // Simple script to toggle the dropdown menu
      function toggleMenu(button) {
          button.parentElement.classList.toggle('open');
      }
  </script>

</body> 
</html>