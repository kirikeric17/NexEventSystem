<?php
require_once __DIR__ . '/../../config.php'; //global config

// --- Validate and fetch event by ID ---
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request. No event ID provided.");
}

$event_id = intval($_GET['id']);

$sql = "SELECT * FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Event not found.");
}

$event = $result->fetch_assoc();

// --- Fetch all categories for dropdown ---
$categories = [];
$cat_sql = "SELECT category_id, categoryName FROM event_category ORDER BY categoryName ASC";
$cat_result = $conn->query($cat_sql);

if ($cat_result && $cat_result->num_rows > 0) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Event - CEMS Admin</title>

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
      <h2>Edit Event</h2>

      <form action="edit_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">

        <div>
          <label for="event_name">Event Name</label>
          <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event['event_name']); ?>" required />
        </div>

        <div>
          <label for="description">Description</label>
          <textarea id="description" name="description" required><?php echo htmlspecialchars($event['description']); ?></textarea>
        </div>

        <div>
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id" required>
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat['category_id']) ?>"
                <?= ($event['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['categoryName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="venue">Venue</label>
          <input type="text" id="venue" name="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" required />
        </div>

        <div>
          <label for="date">Date</label>
          <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($event['event_date']); ?>" required />
        </div>

        <div>
          <label for="mode">Mode</label>
          <select id="mode" name="mode" required>
            <?php
            $modes = ['Physical', 'Online', 'Hybrid'];
            foreach ($modes as $mode) {
              $selected = ($event['mode'] === $mode) ? 'selected' : '';
              echo "<option value='$mode' $selected>$mode</option>";
            }
            ?>
          </select>
        </div>

        <div>
          <label for="remarks">Remarks / Notes</label>
          <textarea id="remarks" name="remarks"><?php echo htmlspecialchars($event['remarks']); ?></textarea>
        </div>

        <div>
          <label for="poster">Event Poster (Image)</label><br>
          <?php if (!empty($event['poster_path'])): ?>
            <img src="<?= BASE_PATH_UPLOADS . htmlspecialchars($event['poster_path']); ?>" alt="Poster" width="150" style="margin-bottom:10px;"><br>
          <?php endif; ?>
          <input type="file" id="poster" name="poster" accept="image/*" />
          <small>Leave blank to keep existing poster</small>
        </div>
        <button type="submit">Update Event</button>
        
      </form>
    </main>
  </div>

  <footer>
    <hr>
    <p>&copy; NexEvent </p>
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
