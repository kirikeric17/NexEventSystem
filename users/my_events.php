<?php
session_start();

// 1. Correct File Path
require_once '../config.php';

// 2. Check Login
if (!isset($_SESSION['loggedin'])) {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'] ?? 'public';
$msg = "";

// --- HANDLE ACTIONS ---

// 3. Delete Owned Event (Organizer ONLY)
if (isset($_POST['delete_event']) && $role !== 'public') {
    $delete_id = intval($_POST['event_id']);
    // Security: Only delete if the logged-in user is the organizer
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ? AND organizer_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    if ($stmt->execute()) { $msg = "deleted"; } 
    else { $msg = "error"; }
    $stmt->close();
}

// 4. Unregister from Event (Participant)
if (isset($_POST['unregister_event'])) {
    $leave_id = intval($_POST['event_id']);
    // Security: Only delete the registration for this specific user
    $stmt = $conn->prepare("DELETE FROM registrations WHERE event_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $leave_id, $user_id);
    if ($stmt->execute()) { $msg = "unregistered"; } 
    else { $msg = "error"; }
    $stmt->close();
}

// --- FETCH EVENTS ---

// 5. Events Created by Me (Organizer)
$result_created = null;
if ($role !== 'public') {
    $sql_created = "SELECT * FROM events WHERE organizer_id = ? ORDER BY event_date DESC";
    $stmt = $conn->prepare($sql_created);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_created = $stmt->get_result();
    $stmt->close();
}

// 6. Events I Joined (Participant)
// We join with 'events' to get details like name and date
$sql_joined = "SELECT e.*, r.registration_date, r.approval_status 
               FROM registrations r 
               JOIN events e ON r.event_id = e.event_id 
               WHERE r.user_id = ?  
               ORDER BY e.event_date DESC";
               
$stmt = $conn->prepare($sql_joined);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_joined = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Events | CEMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* --- STYLES --- */
        :root { --primary: #4f46e5; --sidebar-bg: #1e293b; --bg-body: #f1f5f9; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-body); display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 250px; background: var(--sidebar-bg); color: white; display: flex; flex-direction: column; position: fixed; height: 100%; }
        .brand { padding: 20px; font-size: 1.5rem; text-align: center; border-bottom: 1px solid #334155; font-weight: bold; }
        .nav-links { list-style: none; padding: 0; margin-top: 20px; }
        .nav-links li a { display: block; padding: 15px 25px; color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: 0.3s; }
        .nav-links li a:hover, .nav-links li a.active { background-color: var(--primary); color: white; }
        
        /* Logout Button */
        .nav-links li a.logout-btn { color: #ef4444; font-weight: bold; margin-top: 20px; border-top: 1px solid #334155; }
        .nav-links li a.logout-btn:hover { background-color: #ef4444; color: white; }
        
        /* Main Content */
        .main-content { margin-left: 250px; padding: 30px; width: 100%; box-sizing: border-box; }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        h2 { color: #1e293b; margin: 0; }
        h3 { color: #475569; margin-top: 40px; margin-bottom: 15px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        
        .btn-create {
            background-color: var(--primary); color: white; padding: 10px 20px; border-radius: 6px;
            text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-create:hover { background-color: #4338ca; }

        .table-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { color: #64748b; font-size: 0.85rem; text-transform: uppercase; background: #f8fafc; }

        /* Badges & Buttons */
        .badge { padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; display: inline-block; }
        .badge-public { background: #dcfce7; color: #166534; }
        .badge-private { background: #fee2e2; color: #991b1b; }

        .badge-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .badge-approved { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .action-btn { border: none; background: none; cursor: pointer; font-size: 1.1rem; margin-right: 8px; transition: 0.2s; }
        .btn-edit { color: #2563eb; }
        .btn-delete { color: #dc2626; }
        
        /* Updated Message Button Style */
        .btn-msg { 
            color: #0ea5e9; 
            background: #e0f2fe; 
            padding: 5px 8px; 
            border-radius: 4px;
            font-size: 1rem;
        } 
        .btn-msg:hover { background: #0ea5e9; color: white; }

        .btn-participants { color: #059669; }

        .btn-leave { color: #dc2626; background: #fee2e2; padding: 5px 10px; border-radius: 6px; font-size: 0.9rem; font-weight: bold; border: none; cursor: pointer;}
        .btn-leave:hover { background: #fca5a5; }

        .btn-rate { color: white; background: var(--primary); padding: 5px 10px; border-radius: 6px; font-size: 0.9rem; font-weight: bold; text-decoration: none; margin-right: 8px; }
        .btn-rate:hover { background: #4338ca; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background:#dcfce7; color:#166534; }
        .alert-danger { background:#fee2e2; color:#991b1b; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">NX</div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
            <li><a href="my_events.php" class="active"><i class="fa-solid fa-calendar-check"></i> My Events</a></li>
            
            <?php if($role !== 'public'): ?>
                <li>
                    <a href="manage_participants.php">
                        <i class="fa-solid fa-users-gear"></i> Manage Participants
                    </a>
                </li>
            <?php endif; ?>

            <li><a href="../index.php"><i class="fa-solid fa-home"></i> Home</a></li>
            
            <li><a href="../auth/logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        
        <div class="header-row">
            <h2>My Events Dashboard</h2>
            <div>
                <?php if($role !== 'public'): ?>
                    <a href="create_event.php" class="btn-create"><i class="fa-solid fa-plus"></i> Create Event</a>
                    <a href="view_feedback.php" class="btn-create" style="background-color: #0ea5e9; margin-left: 10px;">View Feedback</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'created') echo "<div class='alert alert-success'>Event created successfully!</div>"; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated') echo "<div class='alert alert-success'>Event updated successfully!</div>"; ?>
        <?php if($msg == 'deleted') echo "<div class='alert alert-danger'>Event deleted.</div>"; ?>
        <?php if($msg == 'unregistered') echo "<div class='alert alert-danger'>You have unregistered from the event.</div>"; ?>

        <h3><i class="fa-solid fa-pen-ruler"></i> Events I Organized</h3>
        <div class="table-card">
            
            <?php if($role === 'public'): ?>
                
                <div style="text-align: center; padding: 30px; color: #64748b;">
                    <i class="fa-solid fa-ban" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p>As a <strong>Public</strong> user, you cannot organize events.</p>
                </div>

            <?php else: ?>
                
                <?php if ($result_created && $result_created->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date & Venue</th>
                                <th>Visibility</th>
                                <th>Capacity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result_created->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['event_name'] ?? ''); ?></strong><br>
                                        <small style="color:#64748b;"><?= htmlspecialchars($row['category'] ?? 'General'); ?></small>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($row['event_date'])); ?><br>
                                        <small><?= htmlspecialchars($row['venue'] ?? $row['location'] ?? 'Online'); ?></small>
                                    </td>
                                    <td><span class="badge <?= ($row['visibility'] == 'Public') ? 'badge-public' : 'badge-private' ?>"><?= htmlspecialchars($row['visibility']); ?></span></td>
                                    <td><i class="fa-solid fa-users"></i> <?= $row['max_participants']; ?></td>
                                    
                                    <td>
                                        <a href="edit_event.php?id=<?= $row['event_id'] ?>" class="action-btn btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event? This cannot be undone.');">
                                            <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                                            <button type="submit" name="delete_event" class="action-btn btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                        
                                        <a href="manage_participants.php?event_id=<?= $row['event_id'] ?>" class="action-btn btn-participants" title="Manage Participants"><i class="fa-solid fa-users-viewfinder"></i></a>
                                        
                                        <a href="event_messages.php?event_id=<?= $row['event_id'] ?>" class="action-btn btn-msg" title="View Messages">
                                            <i class="fa-solid fa-envelope"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#64748b; padding:10px;">You haven't organized any events yet.</p>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        <h3><i class="fa-solid fa-ticket"></i> Events I Joined</h3>
        <div class="table-card">
            <?php if ($result_joined && $result_joined->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date & Venue</th>
                            <th>Registered On</th>
                            <th>Status</th> 
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php while($row = $result_joined->fetch_assoc()): ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($row['event_name'] ?? ''); ?></strong><br>
                <small style="color:#64748b;"><?= htmlspecialchars($row['category'] ?? 'General'); ?></small>
            </td>
            <td>
                <?= date('M d, Y', strtotime($row['event_date'])); ?><br>
                <small><?= htmlspecialchars($row['venue'] ?? $row['location'] ?? 'Online'); ?></small>
            </td>
            <td>
                <i class="fa-regular fa-clock"></i> <?= date('M d, Y', strtotime($row['registration_date'])); ?>
            </td>
            
            <td>
                <?php 
                    $status = $row['approval_status'];
                    if($status == 'Approved') {
                        echo '<span class="badge badge-approved"><i class="fas fa-check"></i> Approved</span>';
                    } elseif($status == 'Rejected') {
                        echo '<span class="badge badge-rejected"><i class="fas fa-times"></i> Rejected</span>';
                    } else {
                        echo '<span class="badge badge-pending"><i class="fas fa-hourglass-half"></i> Pending</span>';
                    }
                ?>
            </td>

            <td>
                <a href="event_messages.php?event_id=<?= $row['event_id'] ?>" class="btn-msg" style="text-decoration:none; margin-right:5px;" title="Message Organizer">
                    <i class="fa-solid fa-envelope"></i>
                </a>

                <?php 
                    $today = date('Y-m-d');
                    if ($row['approval_status'] == 'Approved' && $row['event_date'] < $today): 
                ?>
                    <a href="give_feedback.php?event_id=<?= $row['event_id']; ?>" class="btn-rate">
                        <i class="fa-regular fa-star"></i> Rate
                    </a>
                <?php endif; ?>

                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to unregister from this event?');">
                    <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                    <button type="submit" name="unregister_event" class="action-btn btn-leave">
                        <i class="fa-solid fa-person-walking-arrow-right"></i> Unregister
                    </button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; color: #64748b;">
                    <p>You haven't joined any events yet.</p>
                    <a href="../index.php" style="color: var(--primary); font-weight: bold;">Browse Events</a>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>