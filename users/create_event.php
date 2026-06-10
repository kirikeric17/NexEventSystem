<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) { header("location: ../auth/login.php"); exit; }

$error = "";
$success = "";

// --- HELPER FUNCTION FOR FILE UPLOAD ---
function handleFileUpload($fileArray) {
    if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return [false, null];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $fileName = $fileArray['name'];
    $fileSize = $fileArray['size'];
    $fileTmpName = $fileArray['tmp_name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        return [false, "Error: Only JPG, JPEG, PNG, and GIF files are allowed."];
    }

    if ($fileSize > 5 * 1024 * 1024) {
        return [false, "Error: File size must be less than 5MB."];
    }

    $newFileName = uniqid('event_') . '.' . $fileExtension;
    $uploadDestination = '../uploads/' . $newFileName;

    if (move_uploaded_file($fileTmpName, $uploadDestination)) {
        return [true, $newFileName];
    } else {
        return [false, "Error: Failed to move uploaded file. Check folder permissions."];
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $organizer_id = $_SESSION['id'];
    $title = trim($_POST['title']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $category_name = $_POST['category']; // This is the name (e.g., "Sports")
    $mode = $_POST['mode']; // NEW: Capture the mode input
    $max_p = intval($_POST['max_participants']);
    $visibility = $_POST['visibility'];
    $poster_path = null; 

    // --- CATEGORY ID MAPPING ---
    // Maps the selected name to the correct ID based on your database structure
    $category_map = [
        'Academic' => 1,
        'Workshop' => 1, 
        'Seminar'  => 2,
        'Competition' => 3,
        'Cultural' => 4,
        'Festival' => 4, // Added based on context
        'Sports'   => 5,
        'Esports'  => 5,
        'Course'   => 6
    ];
    // Default to 1 (Academic) if the category isn't found in the map
    $category_id = isset($category_map[$category_name]) ? $category_map[$category_name] : 1;

    if (empty($title) || empty($date) || empty($location)) {
        $error = "Please fill in all required fields.";
    } else {
        if (isset($_FILES['poster']) && $_FILES['poster']['size'] > 0) {
            list($uploadSuccess, $uploadResult) = handleFileUpload($_FILES['poster']);
            if ($uploadSuccess) {
                $poster_path = $uploadResult; 
            } else {
                $error = $uploadResult; 
            }
        }

        if (empty($error)) {
            // UPDATED SQL: Added 'category_id' and 'mode' columns
            $sql = "INSERT INTO events (event_name, event_date, event_time, venue, description, organizer_id, max_participants, category, category_id, visibility, mode, poster_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                // Updated bind_param: added 'i' for category_id and 's' for mode
                // Types: sssssiisisss
                $stmt->bind_param("sssssiisisss", $title, $date, $time, $location, $description, $organizer_id, $max_p, $category_name, $category_id, $visibility, $mode, $poster_path);
                
                if ($stmt->execute()) {
                    header("location: my_events.php?msg=created");
                    exit;
                } else {
                    $error = "Execution Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Event | NX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Reuse previous form styles */
        body { font-family: system-ui, sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .form-card { background: white; padding: 40px; border-radius: 12px; width: 100%; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #4f46e5; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #334155; }
        input[type="text"], input[type="date"], input[type="time"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        input[type="file"] { padding: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%; }
        .btn-submit { background: #4f46e5; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem; }
        .btn-submit:hover { background: #4338ca; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; }
        .row { display: flex; gap: 15px; }
        .col { flex: 1; }
        .alert { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="form-card">
        <h2>Create New Event</h2>
        <?php if($error) echo "<div class='alert'>$error</div>"; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Event Title *</label>
                <input type="text" name="title" required placeholder="e.g., Annual Tech Symposium">
            </div>

            <div class="form-group">
                <label>Event Poster (Image)</label>
                <input type="file" name="poster" accept="image/png, image/jpeg, image/gif">
                <small style="color: #64748b;">Max size 5MB. JPG, PNG, GIF only.</small>
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Date *</label>
                    <input type="date" name="date" required>
                </div>
                <div class="col form-group">
                    <label>Time *</label>
                    <input type="time" name="time" required>
                </div>
            </div>

            <div class="form-group">
                <label>Venue / Location *</label>
                <input type="text" name="location" required placeholder="e.g., Main Auditorium">
            </div>

            <div class="row">
                 <div class="col form-group">
                    <label>Event Mode *</label>
                    <select name="mode" required>
                        <option value="Physical">Physical</option>
                        <option value="Online">Online</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                <div class="col form-group">
                    <label>Max Participants</label>
                    <input type="number" name="max_participants" value="50" min="1">
                </div>
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="Academic">Academic</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Competition">Competition</option>
                        <option value="Esports">Esports</option>
                        <option value="Festival">Festival</option>
                    </select>
                </div>
                <div class="col form-group">
                    <label>Visibility</label>
                    <select name="visibility">
                        <option value="Public">Public (Everyone)</option>
                        <option value="Private">Private (University Only)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Describe your event..."></textarea>
            </div>

            <button type="submit" class="btn-submit">Create Event</button>
            <a href="my_events.php" class="back-link">Cancel and Go Back</a>
        </form>
    </div>

</body>
</html>