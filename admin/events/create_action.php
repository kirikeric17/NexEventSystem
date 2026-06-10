<?php
require_once __DIR__ . '/../../config.php'; //global config

if ($_SERVER["REQUEST_METHOD"] === "POST") {    
    // Collect form data
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $venue = $_POST['venue'];
    $date = $_POST['date'];
    $mode = $_POST['mode'];
    $remarks = $_POST['remarks'];

    // Handle file upload
    $poster_path = "";
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $upload_dir = ROOT_PATH . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . '_' . basename($_FILES["poster"]["name"]); //basic rename file to avoid duplicate
        $poster_path = $upload_dir . $filename;
        move_uploaded_file($_FILES["poster"]["tmp_name"], $poster_path);
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO events (
        event_name, description, category_id, venue, event_date, mode, remarks, poster_path
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssisssss", 
        $event_name, $description, $category, $venue, $date, $mode, $remarks, $filename
    );

    $message = "";
    if ($stmt->execute()) {
        $message = "✅ Event updated successfully.";
    } else {
        $message = "❌ Error updating event: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CEMS - Admin Dashboard</title>

  <link rel="stylesheet" href="<?= BASE_PATH_CSS ?>admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <header class="hero">
    <div class="overlay"></div>
    <div class="hero-content">      
      <h1>CEMS - Admin Dashboard</h1>
    </div>
  </header>

  <div class="admin-container">
    <!-- Include Sidebar -->
    <?php include(ROOT_PATH_ADMIN . 'include/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content" id="main-content">
      <h2>Create Event</h2>

      <p style="padding: 1rem; background-color: #eef; border-radius: 6px;">
        <?php echo $message; ?>
      </p>

      <a href="manage.php" style="display:inline-block; margin-top:10px;">&larr; Back to Manage Events</a>
    </main>
  </div>

  <footer>
    <hr>
        
  </footer>

  <script>
  
    document.querySelectorAll('.submenu-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.nextElementSibling.classList.toggle('show');
      });
    });
  </script>
</body>
</html>