<?php
// FILE: index.php
session_start();
require_once 'config.php'; 

// --- 1. SEARCH & FILTER LOGIC ---
$whereClauses = [];
$params = [];
$types = "";

// A. Search Filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($searchTerm)) {
    $likeTerm = "%" . $searchTerm . "%";
    $whereClauses[] = "(event_name LIKE ? OR description LIKE ?)";
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $types .= "ss";
}

// B. Category Filter
$categoryOption = isset($_GET['category']) ? trim($_GET['category']) : '';
if (!empty($categoryOption)) {
    $whereClauses[] = "category = ?";
    $params[] = $categoryOption;
    $types .= "s";
}

// C. Date Filter
$dateInput = isset($_GET['date']) ? trim($_GET['date']) : '';
if (!empty($dateInput)) {
    $whereClauses[] = "event_date >= ?";
    $params[] = $dateInput;
    $types .= "s";
}

// Build Query
$sql = "SELECT event_id, event_name, description, event_date, venue, poster_path, category FROM events";

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY event_date ASC";

// Prepare & Execute
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();


// --- 2. CHECK USER'S STATUS (Using 'registrations' table) ---
$event_status = []; 

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $user_id = $_SESSION['id'];
    
    // Check registrations table
    $check_sql = "SELECT event_id, approval_status FROM registrations WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    
    while($r = $check_res->fetch_assoc()) {
        $event_status[$r['event_id']] = $r['approval_status'];
    }
    $check_stmt->close();
}
// ------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexEvent | Home</title>
    <link rel="stylesheet" href="assets/css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Container & Grid */
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .section-title { text-align: center; margin-bottom: 40px; color: #1e293b; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        
        /* Event Card */
        .event-card { 
            background: white; border-radius: 12px; overflow: hidden; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; border: 1px solid #e2e8f0; 
            display: flex; flex-direction: column;
        }
        .event-card:hover { transform: translateY(-5px); }
        .event-card .card-img-top {
            width: 100%; height: 200px; object-fit: cover; display: block;
        }
        
        /* Card Body */
        .card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .event-date { color: #4f46e5; font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block; }
        .event-title { margin: 0 0 10px; font-size: 1.25rem; color: #1e293b; }
        .event-venue { color: #64748b; font-size: 0.9rem; margin-bottom: 15px; }
        .event-desc { color: #475569; font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px; flex-grow: 1; }
        
        /* --- BUTTONS STYLING (FIXED) --- */
        .btn-group { 
            display: flex; 
            gap: 10px; 
            width: 100%; /* Ensure container fills width */
        }
        
        /* Ensure the FORM takes up equal space as the Details button */
        .btn-group form {
            flex: 1;
            display: flex; 
        }

        .btn-common {
            flex: 1; 
            text-align: center; 
            padding: 10px; 
            border-radius: 6px; 
            font-weight: 600; 
            border: none; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 0.9rem;
            display: flex;              /* Center content */
            justify-content: center;    /* Center content */
            align-items: center;        /* Center content */
            height: 42px;               /* Fixed height for consistency */
        }

        .btn-register { 
            background: #4f46e5; 
            color: white; 
            width: 100%; /* Force button to fill the form container */
        } 
        .btn-register:hover { background: #4338ca; }

        .btn-details { background: #e2e8f0; color: #334155; }
        .btn-details:hover { background: #cbd5e1; }

        /* --- BUTTON STATES --- */
        .btn-joined { 
            background: #94a3b8; /* Grey */
            color: white; 
            cursor: default; 
            pointer-events: none;
        }
        
        .btn-pending {
            background: #f59e0b; /* Orange */
            color: white;
            cursor: default;
            pointer-events: none;
        }

        /* Search Bar */
        .search-container {
            background: white; padding: 20px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin: -40px auto 40px; max-width: 900px; position: relative; z-index: 10;
            display: flex; flex-wrap: wrap; gap: 10px; border: 1px solid #e2e8f0;
        }
        .search-input, .search-select {
            padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; flex: 1; min-width: 150px;
        }
        .search-btn {
            padding: 12px 25px; background: #0f172a; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .badge-cat {
            background: #e0e7ff; color: #4f46e5; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; margin-bottom: 8px; display: inline-block;
        }
    </style>
</head>
<body>

<?php include "include/topNav.php"; ?>

<header class="hero">
    <div class="overlay"></div> <div class="hero-content"> 
        <img src="assets/img/logonew2.jpg" alt="NX Logo" class="logo">

        <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <h1>Hello, <?= htmlspecialchars($_SESSION['name'] ?? 'Student'); ?>!</h1>
            <p style="font-size: 1.1rem; max-width: 600px; margin: 10px auto;">Welcome back to NexEvent.</p>
        <?php else: ?>
            <h1>NexEvent</h1>
            <p>Discover, organize, and join the best events happening at NexEvent.</p>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    
    <form class="search-container" action="" method="GET">
        <input type="text" name="search" class="search-input" placeholder="Search event name..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        
        <select name="category" class="search-select">
            <option value="">All Categories</option>
            <option value="General" <?= (($_GET['category'] ?? '') == 'General') ? 'selected' : '' ?>>General</option>
            <option value="Academic" <?= (($_GET['category'] ?? '') == 'Academic') ? 'selected' : '' ?>>Academic</option>
            <option value="Sports" <?= (($_GET['category'] ?? '') == 'Sports') ? 'selected' : '' ?>>Sports</option>
            <option value="Cultural" <?= (($_GET['category'] ?? '') == 'Cultural') ? 'selected' : '' ?>>Cultural</option>
        </select>
        
        <input type="date" name="date" class="search-select" value="<?= $_GET['date'] ?? '' ?>">
        <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i> Find</button>
        <?php if(!empty($_GET)): ?>
            <a href="index.php" style="padding: 12px; color: #64748b; text-decoration: underline; align-self: center;">Clear</a>
        <?php endif; ?>
    </form>

    <h2 class="section-title">Upcoming Events</h2>
    
    <div class="events-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="event-card">
                    
                    <?php if(!empty($row['poster_path'])): ?>
                        <img src="uploads/<?= htmlspecialchars($row['poster_path']); ?>" alt="Event Poster" class="card-img-top">
                    <?php else: ?>
                        <div style="height:200px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8;">
                            <i class="fa-regular fa-image" style="font-size:3rem;"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <?php if(!empty($row['category'])): ?>
                            <div><span class="badge-cat"><?= htmlspecialchars($row['category']); ?></span></div>
                        <?php endif; ?>

                        <span class="event-date">
                            <i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($row['event_date'])); ?>
                        </span>
                        
                        <h3 class="event-title"><?= htmlspecialchars($row['event_name']); ?></h3>
                        
                        <div class="event-venue">
                            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($row['venue'] ?? 'TBA'); ?>
                        </div>
                        
                        <p class="event-desc"><?= htmlspecialchars(substr($row['description'], 0, 90)) . '...'; ?></p>
                        
                        <div class="btn-group">
                            <a href="events_details.php?id=<?= $row['event_id']; ?>" class="btn-common btn-details">Details</a>

                            <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                                
                                <?php 
                                    $status = $event_status[$row['event_id']] ?? null;
                                ?>

                                <?php if($status === 'Approved'): ?>
                                    <button type="button" class="btn-common btn-joined"><i class="fa-solid fa-check"></i> &nbsp; Joined</button>
                                
                                <?php elseif($status === 'Pending'): ?>
                                    <button type="button" class="btn-common btn-pending"><i class="fa-regular fa-clock"></i> &nbsp; Requested</button>
                                
                                <?php else: ?>
                                    <form action="users/register_event.php" method="POST" style="flex:1; display:flex;">
                                        <input type="hidden" name="event_id" value="<?= $row['event_id']; ?>">
                                        <button type="submit" class="btn-common btn-register" style="width:100%;">Join</button>
                                    </form>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <a href="auth/login.php" class="btn-common btn-register">Login to Join</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; width:100%; color:#64748b; font-size:1.2rem;">
                <i class="fa-regular fa-folder-open"></i> No events found matching your search.
            </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>