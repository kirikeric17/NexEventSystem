<?php
// 1. Config & Session
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

// 3. Fetch Data
// UPDATED SQL: Changed INNER JOIN to LEFT JOIN to ensure events without categories still show
$sql = "
    SELECT 
        e.*, 
        c.categoryName
    FROM 
        events AS e
    LEFT JOIN 
        event_category AS c 
        ON e.category_id = c.category_id
    ORDER BY 
        e.event_date DESC
    ";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Events | Admin</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <style>
      /* --- LAYOUT & SIDEBAR (Matches Dashboard) --- */
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

      /* Sidebar */
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

      /* Submenu */
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

      .submenu-content a:hover { color: #fff; }
      
      /* Submenu Active State */
      .submenu-content a.active {
          color: #fff;
          font-weight: 600;
          background: rgba(255,255,255,0.05);
      }

      .menu-item.delete {
          margin-top: auto;
          background-color: var(--danger);
          color: white;
          font-weight: 600;
      }

      /* --- MAIN CONTENT & TABLE --- */
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
          margin-bottom: 25px;
      }

      .btn-create {
          background-color: var(--primary);
          color: white;
          padding: 10px 20px;
          border-radius: 6px;
          font-weight: 500;
          display: inline-flex;
          align-items: center;
          gap: 8px;
          transition: 0.2s;
      }
      .btn-create:hover { background-color: #0b5ed7; }

      table {
          width: 100%;
          border-collapse: collapse;
          background: white;
          border-radius: 8px;
          overflow: hidden;
          box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      }

      th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
      th { background: #f8fafc; font-weight: 600; color: #64748b; }
      
      .badge-mode { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; background: #e0e7ff; color: #4338ca; }
      .poster-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }

      .action-btn { margin-right: 10px; font-weight: 500; font-size: 0.9rem; }
      .text-edit { color: #2563eb; }
      .text-delete { color: #dc2626; }
  </style>
</head>
<body>

  <aside class="sidebar">
    <nav class="menu">
      <a href="<?= BASE_URL ?>/admin/index.php" class="menu-item">
          <i class="fas fa-chart-pie" style="width:20px;"></i> Dashboard
      </a>
      <a href="<?= BASE_URL ?>/admin/users/manage.php" class="menu-item">
          <i class="fas fa-users" style="width:20px;"></i> Users
      </a>

      <div class="submenu open">
        <button class="submenu-toggle" onclick="toggleMenu(this)">
            <i class="fas fa-calendar-alt" style="width:20px;"></i> Events ▾
        </button>
        <div class="submenu-content">
          <a href="<?= BASE_URL ?>/admin/events/manage.php" class="active">Edit Events</a>
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
          <h1>Manage Events</h1>
          <a href="create.php" class="btn-create">
              <i class="fas fa-plus"></i> Create New Event
          </a>
      </div>

      <?php if ($result && $result->num_rows > 0): ?>
      <table>
          <thead>
          <tr>
              <th>ID</th>
              <th>Poster</th>
              <th>Event Name</th>
              <th>Category</th>
              <th>Date</th>
              <th>Venue</th>
              <th>Mode</th>
              <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
              <td>#<?= $row['event_id'] ?></td>
              <td>
                  <?php if (!empty($row['poster_path'])): ?>
                      <img src="../../uploads/<?= htmlspecialchars($row['poster_path']) ?>" 
                          class="poster-thumb" 
                          alt="Poster"
                          onerror="this.onerror=null;this.src='../../assets/img/default-poster.jpg';"> 
                  <?php else: ?>
                      <span style="color:#ccc; font-size:0.8rem;">No Img</span>
                  <?php endif; ?>
              </td>
                  <td style="font-weight: 500; color: #333;"><?= htmlspecialchars($row['event_name']) ?></td>
                  <td><?= htmlspecialchars($row['categoryName'] ?? 'General') ?></td>
                  <td><?= date('d M Y', strtotime($row['event_date'])) ?></td>
                  <td><?= htmlspecialchars($row['venue']) ?></td>
                  <td><span class="badge-mode"><?= htmlspecialchars($row['mode']) ?></span></td>
                  <td>
                      <a href="edit.php?id=<?= $row['event_id'] ?>" class="action-btn text-edit"><i class="fas fa-edit"></i> Edit</a>
                      <a href="delete.php?id=<?= $row['event_id'] ?>" class="action-btn text-delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</a>
                  </td>
              </tr>
          <?php endwhile; ?>
          </tbody>
      </table>
      <?php else: ?>
          <div style="text-align:center; padding: 40px; background: white; border-radius: 8px; color: #666;">
              <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px; display:block;"></i>
              No events found.
          </div>
      <?php endif; ?>

  </main>

  <script>
      function toggleMenu(button) {
          button.parentElement.classList.toggle('open');
      }
  </script>
</body>
</html>