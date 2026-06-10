<?php
session_start();
require_once '../config.php'; 

// 1. Check Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../auth/login.php");
    exit;
}

$organizer_id = $_SESSION['id'];
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// 2. HANDLE ACTIONS (Approve, Reject, Mark Present, Export)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ACTION: UPDATE STATUS
    if (isset($_POST['action_type']) && isset($_POST['reg_id'])) {
        $reg_id = intval($_POST['reg_id']);
        $action = $_POST['action_type'];
        $sql = ""; 

        if ($action == 'approve') {
            $sql = "UPDATE registrations SET approval_status='Approved' WHERE id=?";
        } elseif ($action == 'reject') {
            $sql = "UPDATE registrations SET approval_status='Rejected' WHERE id=?";
        } elseif ($action == 'present') {
            $sql = "UPDATE registrations SET attendance_status='Present' WHERE id=?";
        } elseif ($action == 'absent') {
            $sql = "UPDATE registrations SET attendance_status='Absent' WHERE id=?";
        }

        if ($sql != "") {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $reg_id);
            
            // Execute the update
            if ($stmt->execute()) {
                
                // --- NEW: NOTIFICATION LOGIC START ---
                // Only send notifications for Approval or Rejection (not attendance)
                if ($action == 'approve' || $action == 'reject') {
                    
                    // A. Fetch User ID and Event Name using the Registration ID (reg_id)
                    $info_sql = "SELECT r.user_id, e.event_name 
                                 FROM registrations r 
                                 JOIN events e ON r.event_id = e.event_id 
                                 WHERE r.id = ?";
                    
                    $info_stmt = $conn->prepare($info_sql);
                    $info_stmt->bind_param("i", $reg_id);
                    $info_stmt->execute();
                    $info_result = $info_stmt->get_result();

                    if ($info_row = $info_result->fetch_assoc()) {
                        $target_user_id = $info_row['user_id'];
                        $target_event_name = $info_row['event_name'];
                        $notif_message = "";

                        // B. Construct the message
                        if ($action == 'approve') {
                            $notif_message = "✅ <strong>Good news!</strong> Your registration for <strong>$target_event_name</strong> has been Approved.";
                        } elseif ($action == 'reject') {
                            $notif_message = "❌ Your registration for <strong>$target_event_name</strong> was Rejected.";
                        }

                        // C. Insert into Notifications Table
                        if ($notif_message != "") {
                            $ins_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                            $ins_stmt->bind_param("is", $target_user_id, $notif_message);
                            $ins_stmt->execute();
                        }
                    }
                }
                // --- NEW: NOTIFICATION LOGIC END ---

            }
            $stmt->close();
        }
    }

    // ACTION: EXPORT CSV
    if (isset($_POST['export_csv'])) {
        ob_clean(); 
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="participants_list.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Name', 'Email', 'Phone', 'Registration Date', 'Approval Status', 'Attendance'));
        
        $sql = "SELECT u.name, u.email, u.phone, r.registration_date, r.approval_status, r.attendance_status 
                FROM registrations r
                JOIN users u ON r.user_id = u.id
                WHERE r.event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $rows = $stmt->get_result();
        
        while ($row = $rows->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
}

// 3. FETCH EVENTS CREATED BY THIS USER (For Dropdown)
$my_events_sql = "SELECT event_id, event_name FROM events WHERE organizer_id = ?";
$stmt = $conn->prepare($my_events_sql);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$my_events = $stmt->get_result();

// 4. FETCH PARTICIPANTS (If event selected)
$participants = null;
if ($event_id) {
    // Security: Ensure event belongs to logged-in user
    $check_owner = $conn->prepare("SELECT event_id FROM events WHERE event_id = ? AND organizer_id = ?");
    $check_owner->bind_param("ii", $event_id, $organizer_id);
    $check_owner->execute();
    
    if($check_owner->get_result()->num_rows > 0){
        $sql = "SELECT r.id as reg_id, u.name, u.email, u.phone, r.registration_date, r.approval_status, r.attendance_status 
                FROM registrations r
                JOIN users u ON r.user_id = u.id 
                WHERE r.event_id = ? 
                ORDER BY r.registration_date DESC";
        $stmt_p = $conn->prepare($sql);
        $stmt_p->bind_param("i", $event_id);
        $stmt_p->execute();
        $participants = $stmt_p->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Participants | CEMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* --- DASHBOARD STYLES (COPIED FROM DASHBOARD.PHP) --- */
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

        .brand {
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid #334155;
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

        /* --- ADDED: RED LOGOUT BUTTON STYLE --- */
        .nav-links li a.logout-btn {
            color: #ef4444; /* Red Text */
            font-weight: bold;
            margin-top: 20px;
            border-top: 1px solid #334155;
        }
        .nav-links li a.logout-btn:hover {
            background-color: #ef4444; /* Red Background */
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

        .card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .card h3 { margin-top: 0; color: var(--primary); border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }

        /* --- TABLE & BADGE STYLES (ADDED FOR PARTICIPANT LIST) --- */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; color: #334155; font-weight: 600; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-approved { background: #dcfce7; color: #16a34a; }
        .badge-rejected { background: #fee2e2; color: #dc2626; }

        .btn-action { border: none; background: none; cursor: pointer; font-size: 1.2rem; margin-right: 8px; transition: 0.2s; }
        .btn-action:hover { transform: scale(1.1); }
        .btn-present { background: var(--primary); color: white; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; }
        
        /* Select Dropdown Style */
        .event-select { padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; min-width: 250px; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 60px; }
            .sidebar .text { display: none; }
            .brand { font-size: 1rem; }
            .main-content { margin-left: 60px; }
            .table-responsive { overflow-x: auto; }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">NX</div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fa-solid fa-user"></i> <span class="text">My Profile</span></a></li>
            <li><a href="my_events.php"><i class="fa-solid fa-calendar-check"></i> <span class="text">My Events</span></a></li>
            
            <li>
                <a href="manage_participants.php" class="active">
                    <i class="fa-solid fa-users-gear"></i> <span class="text">Manage Participants</span>
                </a>
            </li>
            
            <li><a href="../index.php"><i class="fa-solid fa-home"></i> <span class="text">Home</span></a></li>
            
            <li><a href="../auth/logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> <span class="text">Logout</span></a></li>
        </ul>
    </nav>

    <div class="main-content">
        
        <div class="header">
            <h2>Manage Participants</h2>
        </div>

        <div class="card">
            <h3>Select Event</h3>
            <form method="GET" style="display:flex; align-items:center; gap:15px;">
                <label style="font-weight:600;">Choose Event:</label>
                <select name="event_id" onchange="this.form.submit()" class="event-select">
                    <option value="">-- Select an Event --</option>
                    <?php foreach($my_events as $e): ?>
                        <option value="<?= $e['event_id'] ?>" <?= ($event_id == $e['event_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['event_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($event_id && $participants): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:15px; margin-bottom:15px;">
                <h3 style="border:none; margin:0; padding:0;">Registered Users (<?= $participants->num_rows ?>)</h3>
                
                <form method="POST">
                    <input type="hidden" name="export_csv" value="1">
                    <button type="submit" style="background:#10b981; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer; font-weight:600;">
                        <i class="fas fa-file-excel"></i> Export CSV
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <?php if ($participants->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Reg Date</th>
                            <th>Status</th>
                            <th>Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $participants->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($row['email']) ?><br>
                                <small style="color:#64748b;"><?= htmlspecialchars($row['phone']) ?></small>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['registration_date'])) ?></td>
                            
                            <td>
                                <?php if($row['approval_status'] == 'Pending'): ?>
                                    <span class="status-badge badge-pending">Pending</span>
                                <?php elseif($row['approval_status'] == 'Approved'): ?>
                                    <span class="status-badge badge-approved">Approved</span>
                                <?php else: ?>
                                    <span class="status-badge badge-rejected">Rejected</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if($row['attendance_status'] == 'Present'): ?>
                                    <span style="color:#16a34a; font-weight:bold;"><i class="fas fa-check"></i> Present</span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">Absent</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="reg_id" value="<?= $row['reg_id'] ?>">
                                    
                                    <?php if($row['approval_status'] == 'Pending'): ?>
                                        <button type="submit" name="action_type" value="approve" class="btn-action" style="color:#16a34a;" title="Approve"><i class="fas fa-check-circle"></i></button>
                                        <button type="submit" name="action_type" value="reject" class="btn-action" style="color:#dc2626;" title="Reject"><i class="fas fa-times-circle"></i></button>
                                    
                                    <?php elseif($row['approval_status'] == 'Approved'): ?>
                                        <?php if($row['attendance_status'] == 'Absent'): ?>
                                            <button type="submit" name="action_type" value="present" class="btn-action btn-present">Mark Present</button>
                                        <?php else: ?>
                                            <button type="submit" name="action_type" value="absent" class="btn-action" style="font-size:0.8rem; color:#64748b;">Undo</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color:#64748b; text-align:center; padding:20px;">No participants found for this event.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif($event_id): ?>
            <div class="card">
                <p style="color:red;">Error: You do not have permission to view this event or it does not exist.</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>