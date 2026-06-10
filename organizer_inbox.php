<?php
// ... standard header and config includes ... 
$organizer_id = $_SESSION['id']; // Assuming the logged-in user is the organizer

$sql = "SELECT m.*, u.username, u.email, e.event_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        JOIN events e ON m.event_id = e.event_id
        WHERE m.recipient_id = ?
        ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container" style="padding: 20px;">
    <h2>Organizer Inbox</h2>
    <table border="1" cellpadding="10" style="width:100%; border-collapse: collapse; background: white;">
        <thead>
            <tr style="background:#f1f5f9;">
                <th>From</th>
                <th>Event</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while($msg = $result->fetch_assoc()): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($msg['username']); ?></strong><br>
                    <small><?= htmlspecialchars($msg['email']); ?></small>
                </td>
                <td><?= htmlspecialchars($msg['event_name']); ?></td>
                <td><?= htmlspecialchars($msg['subject']); ?></td>
                <td><?= nl2br(htmlspecialchars($msg['message'])); ?></td>
                <td><?= $msg['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>