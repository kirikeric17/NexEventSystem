<?php
require_once __DIR__ . '/../../config.php'; //global config

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_id    = intval($_POST['event_id']);
    $event_name  = trim($_POST['event_name']);
    $description = trim($_POST['description']);
    $category_id    = intval($_POST['category_id']); // category_id -- integer FK
    $venue       = trim($_POST['venue']);
    $event_date  = $_POST['date'];
    $mode        = trim($_POST['mode']);
    $remarks     = trim($_POST['remarks']);

    // --- Fetch existing poster ---
    $sql_old = "SELECT poster_path FROM events WHERE event_id = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $event_id);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    $old_data = $result_old->fetch_assoc();
    $poster_path = $old_data['poster_path'];

    // --- Handle new poster upload ---
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = ROOT_PATH . '/uploads/'; // absolute path on disk
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["poster"]["name"]);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed = ["jpg", "jpeg", "png"];
        if (in_array($file_type, $allowed)) {
            if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
                // Save relative path to database (not full server path)
                $poster_path = $file_name;
            }
            else{
              $poster_path = "";
            }
        }
    }

    // --- Update the record ---
    $sql = "UPDATE events 
            SET event_name = ?, description = ?, category_id = ?, venue = ?, event_date = ?, mode = ?, remarks = ?, poster_path = ?
            WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssssi", $event_name, $description, $category_id, $venue, $event_date, $mode, $remarks, $poster_path, $event_id);

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
      <h2>Update Event</h2>

      <p style="padding: 1rem; background-color: #eef; border-radius: 6px;">
        <?php echo $message; ?>
      </p>

      <a href="manage.php" style="display:inline-block; margin-top:10px;">&larr; Back to Manage Events</a>
    </main>
  </div>

  <footer>
    <hr>
    <p>&copy; NexEvent </p>
  </footer>

  <script>
    // Toggle submenu
    document.querySelectorAll('.submenu-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.nextElementSibling.classList.toggle('show');
      });
    });
  </script>
</body>
</html>
