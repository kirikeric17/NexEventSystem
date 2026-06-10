<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin'])) { header("location: ../auth/login.php"); exit; }

$organizer_id = $_SESSION['id'];
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = "";

// 1. Fetch Existing Data
$sql = "SELECT * FROM events WHERE event_id = ? AND organizer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $event_id, $organizer_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) { echo "Event not found or access denied."; exit; }

// --- HELPER FUNCTION ---
function handleFileUpload($fileArray) {
    if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) { return [false, null]; }
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) { return [false, "Error: Invalid file type."]; }
    if ($fileArray['size'] > 5*1024*1024) { return [false, "Error: File too large."]; }
    $newFileName = uniqid('event_') . '.' . $ext;
    if (move_uploaded_file($fileArray['tmp_name'], '../uploads/' . $newFileName)) { return [true, $newFileName]; }
    else { return [false, "Error: Upload failed."]; }
}

// 2. Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $category_name = $_POST['category'];
    $mode = $_POST['mode']; // NEW
    $max_p = intval($_POST['max_participants']);
    $visibility = $_POST['visibility'];
    
    $poster_path = $event['poster_path']; 

    // --- CATEGORY MAPPING (Must match create_event logic) ---
    $category_map = [
        'Academic' => 1, 'Workshop' => 1, 'Seminar'  => 2,
        'Competition' => 3, 'Cultural' => 4, 'Festival' => 4,
        'Sports' => 5, 'Esports' => 5, 'Course' => 6
    ];
    $category_id = isset($category_map[$category_name]) ? $category_map[$category_name] : 1;

    if (isset($_FILES['poster']) && $_FILES['poster']['size'] > 0) {
        list($uploadSuccess, $uploadResult) = handleFileUpload($_FILES['poster']);
        if ($uploadSuccess) {
            if (!empty($event['poster_path']) && file_exists('../uploads/' . $event['poster_path'])) {
                unlink('../uploads/' . $event['poster_path']);
            }
            $poster_path = $uploadResult; 
        } else {
            $error = $uploadResult;
        }
    }

    if (empty($error)) {
        // UPDATED SQL: Set 'mode' and 'category_id'
        $update_sql = "UPDATE events SET event_name=?, event_date=?, event_time=?, venue=?, description=?, max_participants=?, category=?, category_id=?, visibility=?, mode=?, poster_path=? WHERE event_id=? AND organizer_id=?";
        
        if ($stmt = $conn->prepare($update_sql)) {
            // Types: sssssiissssii (added extra s and i for mode and category_id)
            $stmt->bind_param("sssssiissssii", $title, $date, $time, $location, $description, $max_p, $category_name, $category_id, $visibility, $mode, $poster_path, $event_id, $organizer_id);
            if ($stmt->execute()) {
                header("location: my_events.php?msg=updated");
                exit;
            } else {
                $error = "Error updating event: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event | NX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Reuse styles */
        body { font-family: system-ui, sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .form-card { background: white; padding: 40px; border-radius: 12px; width: 100%; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #4f46e5; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #334155; }
        input[type="text"], input[type="date"], input[type="time"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        input[type="file"] { padding: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%; }
        .btn-submit { background: #4f46e5; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #4338ca; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; }
        .row { display: flex; gap: 15px; }
        .col { flex: 1; }
        .alert { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        .current-poster { margin-bottom: 10px; }
        .current-poster img { max-width: 100%; height: auto; border-radius: 6px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    <div class="form-card">
        <h2>Edit Event</h2>
        <?php if($error) echo "<div class='alert'>$error</div>"; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Event Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($event['event_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Event Poster</label>
                <?php if(!empty($event['poster_path'])): ?>
                    <div class="current-poster">
                        <img src="../uploads/<?= htmlspecialchars($event['poster_path']); ?>" alt="Current Poster" style="max-height: 150px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="poster" accept="image/png, image/jpeg, image/gif">
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= $event['event_date'] ?>" required>
                </div>
                <div class="col form-group">
                    <label>Time</label>
                    <input type="time" name="time" value="<?= $event['event_time'] ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Venue</label>
                <input type="text" name="location" value="<?= htmlspecialchars($event['venue'] ?? '') ?>" required>
            </div>

            <div class="row">
                 <div class="col form-group">
                    <label>Event Mode</label>
                    <select name="mode">
                        <option value="Physical" <?= ($event['mode'] == 'Physical') ? 'selected' : '' ?>>Physical</option>
                        <option value="Online" <?= ($event['mode'] == 'Online') ? 'selected' : '' ?>>Online</option>
                        <option value="Hybrid" <?= ($event['mode'] == 'Hybrid') ? 'selected' : '' ?>>Hybrid</option>
                    </select>
                </div>
                <div class="col form-group">
                    <label>Max Participants</label>
                    <input type="number" name="max_participants" value="<?= $event['max_participants'] ?>">
                </div>
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php 
                        // Full list of categories
                        $cats = ['Academic', 'Workshop', 'Seminar', 'Competition', 'Sports', 'Cultural', 'Festival', 'Esports', 'Course']; 
                        ?>
                        <?php foreach($cats as $c): ?>
                            <option value="<?= $c ?>" <?= ($event['category'] == $c) ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col form-group">
                    <label>Visibility</label>
                    <select name="visibility">
                        <option value="Public" <?= ($event['visibility'] == 'Public') ? 'selected' : '' ?>>Public</option>
                        <option value="Private" <?= ($event['visibility'] == 'Private') ? 'selected' : '' ?>>Private</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">Update Event</button>
            <a href="my_events.php" class="back-link">Cancel</a>
        </form>
    </div>

</body>
</html>