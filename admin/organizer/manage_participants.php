<?php
// FILE: admin/organizer/manage_participants.php
session_start();

// Adjust path: Go up 2 levels (organizer -> admin -> root) to find config
require_once __DIR__ . '/../../config.php'; 

// 1. SECURITY: Ensure User is Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL_LINK . "auth/login.php");
    exit;
}

// 2. GET EVENT ID
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    die("Error: Event ID is missing.");
}
$event_id = intval($_GET['event_id']);

// 3. HANDLE ACTIONS (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participant_id = $_POST['participant_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE event_participants SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $participant_id);
        $stmt->execute();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("DELETE FROM event_participants WHERE id = ?");
        $stmt->bind_param("i", $participant_id);
        $stmt->execute();
    }
    // Refresh page to show changes
    header("Location: manage_participants.php?event_id=" . $event_id);
    exit;
}

// 4. FETCH EVENT DETAILS (For the Title)
$evt_sql = "SELECT event_name FROM events WHERE event_id = ?";
$evt_stmt = $conn->prepare($evt_sql);
$evt_stmt->bind_param("i", $event_id);
$evt_stmt->execute();
$event_name = $evt_stmt->get_result()->fetch_assoc()['event_name'] ?? "Unknown Event";

// 5. FETCH PARTICIPANTS
// FIXED: Changed u.username to u.name based on your register.php
$sql = "SELECT ep.id as registration_id, ep.status, u.name, u.email, u.phone 
        FROM event_participants ep 
        JOIN users u ON ep.user_id = u.id 
        WHERE ep.event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$participants = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Participants - <?= htmlspecialchars($event_name); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Reusing Admin Styles */
        :root { --primary: #4f46e5; --bg: #f1f5f9; --text: #1e293b; }
        body { font-family: sans-serif; background: var(--bg); display: flex; height: 100vh; margin: 0; }
        
        .sidebar { width: 250px; background: #fff; border-right: 1px solid #e2e8f0; padding: 20px; display: flex; flex-direction: column; }
        .sidebar a { text-decoration: none; color: #64748b; padding: 12px; margin-bottom: 5px; display: flex; align-items: center; gap: 10px; border-radius: 8px; }
        .sidebar a:hover { background: #e0e7ff; color: var(--primary); }

        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        
        .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #64748b; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-approved { background: #dcfce7; color: #166534; }
        
        .btn-action { border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; color: white; font-size: 0.8rem; margin-right: 5px; }
        .btn-approve { background: #10b981; }
        .btn-reject { background: #ef4444; }
        .btn-approve:hover { background: #059669; }
        .btn-reject:hover { background: #dc2626; }
        .back-btn { display: inline-block; margin-bottom: 20px; color: var(--primary); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<aside class="sidebar">
    <h2><i class="fas fa-shield-alt"></i> Admin</h2>
    <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</aside>

<main class="main-content">
    <a href="../index.php" class="back-btn"><i class="fas fa-chevron-left"></i> Back to Dashboard</a>
    
    <h1>Manage Participants</h1>
    <h3 style="color: #64748b; margin-top: -10px;">Event: <?= htmlspecialchars($event_name); ?></h3>

    <div class="table-container">
        <?php if ($participants->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $participants->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['phone']); ?></td>
                            <td>
                                <?php 
                                    $status = $row['status'] ?? 'pending'; 
                                    $badgeClass = ($status == 'approved') ? 'badge-approved' : 'badge-pending';
                                ?>
                                <span class="badge <?= $badgeClass; ?>"><?= ucfirst($status); ?></span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="participant_id" value="<?= $row['registration_id']; ?>">
                                    
                                    <?php if($status !== 'approved'): ?>
                                        <button type="submit" name="action" value="approve" class="btn-action btn-approve">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Are you sure you want to remove this participant?');">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; padding: 20px; color: #64748b;">No participants have registered for this event yet.</p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>