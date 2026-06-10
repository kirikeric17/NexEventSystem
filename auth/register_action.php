<?php
/* =========================================================
   1. DATABASE LOGIC
   ========================================================= */
require_once __DIR__ . '/../config.php'; 

// Initialize status variables
$success = false;
$error_msg = "";

// Capture Form Data
$role_raw     = $_POST['category'] ?? 'public'; // Form says 'category', Database calls it 'role'
$name_raw     = trim($_POST['name'] ?? '');
$email_raw    = trim($_POST['email'] ?? '');
$phone_raw    = trim($_POST['phone'] ?? '');
$password_raw = $_POST['password'] ?? '';
$events_arr   = $_POST['event'] ?? []; 

// Prepare Data for Database
$events_string = implode(',', $events_arr); // Convert array to string like "workshop,seminar"
$hashed_password = password_hash($password_raw, PASSWORD_DEFAULT); // Hash the password

// Insert into Database
// UPDATED: Using table 'users' and columns 'role', 'recommend_events'
$sql = "INSERT INTO users (name, email, phone, role, password, recommend_events) VALUES (?, ?, ?, ?, ?, ?)";

if ($stmt = $conn->prepare($sql)) {
    // Bind parameters: 'ssssss' means 6 strings
    $stmt->bind_param("ssssss", $name_raw, $email_raw, $phone_raw, $role_raw, $hashed_password, $events_string);
    
    if ($stmt->execute()) {
        $success = true;
    } else {
        $error_msg = "Database Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    $error_msg = "Connection Error: " . $conn->error;
}
$conn->close();

// Prepare Data for Display (Sanitized for HTML)
$name_display  = htmlspecialchars($name_raw);
$email_display = htmlspecialchars($email_raw);
$phone_display = htmlspecialchars($phone_raw);
$role_display  = ucfirst($role_raw);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration Status | NX</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <style>
    /* 1. Base Styles & Reset */
    :root {
      --primary: #4f46e5;       /* Indigo */
      --success: #10b981;       /* Green */
      --danger: #ef4444;        /* Red */
      --bg-body: #f1f5f9;       /* Slate 100 */
      --bg-card: #ffffff;
      --text-main: #1e293b;     /* Slate 800 */
      --text-muted: #64748b;    /* Slate 500 */
      --border: #e2e8f0;
      --radius: 16px;
      --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    body {
      margin: 0;
      font-family: system-ui, -apple-system, sans-serif;
      background-color: var(--bg-body);
      color: var(--text-main);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }

    /* 2. Card Container */
    .success-card {
      background: var(--bg-card);
      width: 100%;
      max-width: 500px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      text-align: center;
      animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    /* 3. Header Section */
    .card-header {
      padding: 40px 20px;
      border-bottom: 1px solid var(--border);
    }
    
    /* Success Styles */
    .card-header.success-mode { background-color: #ecfdf5; }
    .card-header.success-mode h2 { color: #065f46; }
    .card-header.success-mode p { color: #047857; }
    .card-header.success-mode .icon-circle { 
        background-color: var(--success); 
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
    }

    /* Error Styles */
    .card-header.error-mode { background-color: #fef2f2; }
    .card-header.error-mode h2 { color: #991b1b; }
    .card-header.error-mode p { color: #7f1d1d; }
    .card-header.error-mode .icon-circle { 
        background-color: var(--danger); 
        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.3);
    }

    .icon-circle {
      width: 80px;
      height: 80px;
      color: white;
      font-size: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
    }

    .card-header h2 { margin: 0; font-size: 1.5rem; }
    .card-header p { margin: 10px 0 0; font-size: 0.95rem; }

    /* 4. Details List */
    .card-body { padding: 30px; text-align: left; }
    .detail-row { display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding: 12px 0; }
    .detail-row:last-child { border-bottom: none; }
    .label { font-weight: 600; color: var(--text-muted); }
    .value { font-weight: 500; color: var(--text-main); text-align: right; }

    /* 5. Event Tags */
    .events-section { margin-top: 20px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid var(--border); }
    .events-label { display: block; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 10px; text-align: center; }
    .tags-container { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
    .tag { background-color: #e0e7ff; color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }

    /* 6. Footer Button */
    .card-footer { padding: 20px 30px 30px; }
    .btn-home { display: block; width: 100%; padding: 14px; background-color: var(--primary); color: white; text-decoration: none; font-weight: 600; border-radius: 10px; transition: background 0.2s; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.25); }
    .btn-home:hover { background-color: #4338ca; }
    
    .btn-retry { background-color: var(--text-main); box-shadow: none; }
    .btn-retry:hover { background-color: #0f172a; }

  </style>
</head>
<body>

  <div class="success-card">
    
    <?php if ($success): ?>
    <div class="card-header success-mode">
      <div class="icon-circle">
        <i class="fa-solid fa-check"></i>
      </div>
      <h2>Thank You!</h2>
      <p>Your registration was successful.</p>
    </div>

    <div class="card-body">
      <div class="detail-row">
        <span class="label">Full Name</span>
        <span class="value"><?= $name_display ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Role</span>
        <span class="value"><?= $role_display ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Email</span>
        <span class="value"><?= $email_display ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Phone</span>
        <span class="value"><?= $phone_display ?></span>
      </div>

      <?php if (!empty($events_arr)): ?>
        <div class="events-section">
          <span class="events-label">Interested Events</span>
          <div class="tags-container">
            <?php foreach ($events_arr as $event): ?>
              <span class="tag"><?= htmlspecialchars($event) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="events-section" style="text-align:center; color: var(--text-muted);">
          <em>No specific events selected.</em>
        </div>
      <?php endif; ?>
    </div>

    <div class="card-footer">
      <a href="../auth/login.php" class="btn-home">Go to Login</a>
    </div>

    <?php else: ?>
    <div class="card-header error-mode">
      <div class="icon-circle">
        <i class="fa-solid fa-xmark"></i>
      </div>
      <h2>Registration Failed</h2>
      <p>We encountered a problem saving your data.</p>
    </div>
    <div class="card-body">
        <p style="text-align:center; color: var(--danger);">
            <?= htmlspecialchars($error_msg) ?>
        </p>
    </div>
    <div class="card-footer">
      <a href="javascript:history.back()" class="btn-home btn-retry">Try Again</a>
    </div>
    <?php endif; ?>

  </div>

</body>
</html>