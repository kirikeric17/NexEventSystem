<?php
require_once __DIR__ . '/../../config.php'; //global config

$message = "";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $event_id = intval($_GET['id']);

    // Fetch existing poster file before deleting
    $sql_select = "SELECT poster_path FROM events WHERE event_id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $event_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $poster_path = $row['poster_path'];

        // Delete record
        $sql_delete = "DELETE FROM events WHERE event_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $event_id);

        if ($stmt_delete->execute()) {
            // Delete image file if exists
            $fullPosterPath = ROOT_PATH . '/uploads/' . $poster_path;
            if (!empty($poster_path) && file_exists($fullPosterPath)) {
                unlink($fullPosterPath);
            }
            $message = "✅ Event (ID: {$event_id}) deleted successfully.";
        } else {
            $message = "❌ Error deleting event: " . $stmt_delete->error;
        }

        $stmt_delete->close();
    } else {
        $message = "⚠️ Event not found.";
    }

    $stmt_select->close();
} else {
    $message = "⚠️ Invalid event ID.";
}

$conn->close();
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
      <h2>Delete Event</h2>

      <p style="padding: 1rem; background-color: #f2f2f2; border-radius: 6px;">
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
