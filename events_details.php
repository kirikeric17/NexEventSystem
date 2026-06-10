<?php
session_start();
require_once 'config.php';

// 1. Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: index.php");
    exit;
}

$event_id = $_GET['id'];

// 2. Fetch Event Details
$sql = "SELECT e.*, u.email as organizer_email 
        FROM events e 
        LEFT JOIN users u ON e.organizer_id = u.id 
        WHERE e.event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Event not found.";
    exit;
}

$event = $result->fetch_assoc();

// 3. CHECK USER STATUS
$status = null;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $user_id = $_SESSION['id'];
    $check_sql = "SELECT approval_status FROM registrations WHERE user_id = ? AND event_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $event_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    
    if($r = $check_res->fetch_assoc()) {
        $status = $r['approval_status']; // 'Approved', 'Pending', etc.
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Details | NX</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include "include/topNav.php"; ?>

<main>
    <div class="container" style="max-width: 1000px; margin: 0 auto;">
        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Events
        </a>

        <div class="detail-card">
            <?php if(!empty($event['poster_path'])): ?>
                <img src="uploads/<?= htmlspecialchars($event['poster_path']); ?>" alt="Event Poster" class="detail-img">
            <?php else: ?>
                <div class="detail-img" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#cbd5e1;">
                    <i class="fa-regular fa-image" style="font-size:4rem;"></i>
                </div>
            <?php endif; ?>

            <div class="detail-content">
                <?php if(!empty($event['category'])): ?>
                    <div><span class="badge-cat"><?= htmlspecialchars($event['category']); ?></span></div>
                <?php endif; ?>

                <h1 class="d-title"><?= htmlspecialchars($event['event_name']); ?></h1>

                <div class="meta-info">
                    <div><i class="fa-regular fa-calendar"></i> <?= date('F d, Y', strtotime($event['event_date'])); ?></div>
                    <div><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event['venue']); ?></div>
                    <div><i class="fa-regular fa-user"></i> Organizer: <?= htmlspecialchars($event['organizer_email'] ?? 'Admin'); ?></div>
                </div>

                <div class="d-desc">
                    <h4>About this Event</h4>
                    <?= nl2br(htmlspecialchars($event['description'])); ?>
                </div>

                <div class="action-area">
                    <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        
                        <?php if($status === 'Approved'): ?>
                            <button class="btn-common btn-joined">
                                <i class="fa-solid fa-check"></i> You have joined this event
                            </button>
                        
                        <?php elseif($status === 'Pending'): ?>
                            <button class="btn-common btn-pending">
                                <i class="fa-regular fa-clock"></i> Registration Requested
                            </button>
                        
                        <?php else: ?>
                            <form action="users/register_event.php" method="POST">
                                <input type="hidden" name="event_id" value="<?= $event_id; ?>">
                                <button type="submit" class="btn-common btn-register">
                                    Register for Event
                                </button>
                            </form>
                        <?php endif; ?>

                        <div style="margin-top: 15px; text-align: center;">
                            <a href="contact_organizer.php?event_id=<?= $event_id; ?>" 
                               style="color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: color 0.2s;">
                                <i class="fa-regular fa-envelope"></i> Contact Organizer
                            </a>
                        </div>

                    <?php else: ?>
                        <a href="auth/login.php" class="btn-common btn-register">
                            Login to Register
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</main>

</body>
</html>