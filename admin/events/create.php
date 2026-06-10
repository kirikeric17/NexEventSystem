<?php 
require_once __DIR__ . '/../../config.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('BASE_URL')) {
    define('BASE_URL', '../..'); 
}

// Admin Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
  <meta charset="UTF-8" /> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
  <title>Create Event | Admin</title> 
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <style>
      /* --- SHARED LAYOUT STYLES --- */
      :root {
          --primary: #0d6efd;
          --danger: #dc3545;
          --sidebar-bg: #343a40;
          --sidebar-text: #c2c7d0;
          --bg-body: #f4f6f9;
      }
      body { margin: 0; font-family: 'Inter', sans-serif; background: var(--bg-body); display: flex; height: 100vh; overflow: hidden; }
      a { text-decoration: none; }
      
      /* Sidebar */
      .sidebar { width: 250px; background: var(--sidebar-bg); display: flex; flex-direction: column; height: 100vh; flex-shrink: 0; }
      .menu { display: flex; flex-direction: column; height: 100%; padding:0; margin:0; }
      .menu::before { content: 'NX Admin'; display: block; padding: 20px; font-size: 1.25rem; font-weight: 600; color: #fff; background: rgba(0,0,0,0.2); margin-bottom: 10px; }
      .menu-item, .submenu-toggle { padding: 15px 20px; color: var(--sidebar-text); display: flex; align-items: center; gap: 10px; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; background: none; border: none; width: 100%; text-align: left; }
      .menu-item:hover, .submenu-toggle:hover { background: rgba(255,255,255,0.1); color: #fff; padding-left: 25px; transition: 0.2s; }
      .submenu-content { display: none; background: rgba(0,0,0,0.2); }
      .submenu.open .submenu-content { display: block; }
      .submenu-content a { display: block; padding: 10px 20px 10px 40px; color: var(--sidebar-text); font-size: 0.9rem; }
      .submenu-content a:hover, .submenu-content a.active { color: #fff; }
      .menu-item.delete { margin-top: auto; background: var(--danger); color: white; font-weight: 600; }

      /* --- FORM STYLES --- */
      .main-content { flex: 1; padding: 40px; overflow-y: auto; }
      
      .form-card {
          background: white;
          padding: 30px;
          border-radius: 12px;
          box-shadow: 0 4px 6px rgba(0,0,0,0.05);
          max-width: 800px;
          margin: 0 auto;
      }
      
      .form-group { margin-bottom: 20px; }
      .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
      .form-group input, .form-group select, .form-group textarea {
          width: 100%;
          padding: 10px;
          border: 1px solid #d1d5db;
          border-radius: 6px;
          font-size: 1rem;
          box-sizing: border-box; /* Fixes padding width issues */
      }
      .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
          outline: none;
          border-color: var(--primary);
          box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
      }
      
      .btn-submit {
          background: var(--primary);
          color: white;
          border: none;
          padding: 12px 24px;
          border-radius: 6px;
          font-size: 1rem;
          cursor: pointer;
          font-weight: 600;
          display: block;
          width: 100%;
          margin-top: 10px;
      }
      .btn-submit:hover { background: #0b5ed7; }
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
          <a href="<?= BASE_URL ?>/admin/events/manage.php">Manage Events</a>
          <a href="<?= BASE_URL ?>/admin/events/create.php" class="active">Create Event</a>
        </div>
      </div>

      <a href="<?= BASE_URL ?>/auth/logout.php" class="menu-item delete">
          <i class="fa-solid fa-right-from-bracket" style="width:20px;"></i> Logout
      </a>
    </nav>
  </aside>

  <main class="main-content">
      <div class="form-card">
          <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:15px;">Create New Event</h2>
          
          <form action="create_action.php" method="POST" enctype="multipart/form-data"> 
              <div class="form-group"> 
                  <label for="event_name">Event Name</label> 
                  <input type="text" id="event_name" name="event_name" required placeholder="e.g. Annual Tech Symposium" /> 
              </div> 
              
              <div class="form-group"> 
                  <label for="description">Description</label> 
                  <textarea id="description" name="description" rows="4" required placeholder="Enter event details..."></textarea> 
              </div> 

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                  <div class="form-group"> 
                      <label for="category">Category</label> 
                      <select id="category" name="category" required> 
                          <option value="">-- Select Type --</option> 
                          <option value="1">Workshop</option> 
                          <option value="2">Seminar</option> 
                          <option value="3">Competition</option> 
                          <option value="4">Festival</option> 
                          <option value="5">Sport</option> 
                          <option value="6">Course</option> 
                      </select> 
                  </div> 
                  
                  <div class="form-group"> 
                      <label for="mode">Mode</label> 
                      <select id="mode" name="mode" required> 
                          <option value="">-- Select Mode --</option> 
                          <option>Physical</option> 
                          <option>Online</option> 
                          <option>Hybrid</option> 
                      </select> 
                  </div> 
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                  <div class="form-group"> 
                      <label for="venue">Venue</label> 
                      <input type="text" id="venue" name="venue" required placeholder="e.g. Main Hall or Zoom Link" /> 
                  </div> 
                  <div class="form-group"> 
                      <label for="date">Date</label> 
                      <input type="date" id="date" name="date" required /> 
                  </div> 
              </div>

              <div class="form-group"> 
                  <label for="remarks">Remarks / Notes</label> 
                  <textarea id="remarks" name="remarks" rows="2" placeholder="e.g. Bring own laptop"></textarea> 
              </div> 

              <div class="form-group"> 
                  <label for="poster">Event Poster</label> 
                  <input type="file" id="poster" name="poster" accept="image/*" required style="border:none; padding-left:0;" /> 
              </div> 

              <button type="submit" class="btn-submit">
                  <i class="fas fa-paper-plane"></i> Publish Event
              </button> 
          </form> 
      </div>
  </main> 

  <script> 
    function toggleMenu(button) { 
      button.parentElement.classList.toggle('open'); 
    } 
  </script> 
</body> 
</html>