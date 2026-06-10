<?php
// 1. Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Define Base URL (If not already defined)
if (!defined('BASE_URL_LINK')) {
    // Adjust this path if your folder name is different
    define('BASE_URL_LINK', '/cems/NexEvent System/');
}

// 3. Get Current Page for "Active" highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// 4. Get User Role safely
$role = $_SESSION['role'] ?? 'public';
?>

<nav class="navbar">
    
    <a href="<?php echo BASE_URL_LINK; ?>index.php" class="brand">NX</a>

    <div class="menu-icon" onclick="toggleMenu()">
        <i class="fa-solid fa-bars"></i>
    </div>

    <div class="nav-items" id="navItems">
        
        <a href="<?php echo BASE_URL_LINK; ?>index.php" 
           class="nav-btn <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
           Home
        </a>

        <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            
            <?php if($role === 'admin' || $role === 'organizer'): ?>
                <a href="<?php echo BASE_URL_LINK; ?>admin/events/create.php" class="nav-btn">Create Event</a>
                
                <?php if($role === 'admin'): ?>
                    <a href="<?php echo BASE_URL_LINK; ?>admin/users/manage.php" class="nav-btn">Manage Users</a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($role === 'admin'): ?>
                <a href="<?php echo BASE_URL_LINK; ?>admin/events/manage.php" 
                   class="nav-btn <?php echo ($current_page == 'manage.php') ? 'active' : ''; ?>">
                   Manage All Events
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL_LINK; ?>users/my_events.php" 
                   class="nav-btn <?php echo ($current_page == 'my_events.php') ? 'active' : ''; ?>">
                   My Events
                </a>
            <?php endif; ?>

            <a href="<?php echo BASE_URL_LINK; ?>auth/logout.php" class="nav-btn" style="background-color:#dc2626; color:white; border:none;">
                Logout
            </a>
            
            <span style="color:#cbd5e1; margin-left:15px; font-size:0.9rem; align-self:center;">
                <i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                <span style="font-size:0.75rem; background:#475569; padding:2px 6px; border-radius:4px; margin-left:5px;">
                    <?php echo ucfirst($role); ?>
                </span>
            </span>

        <?php else: ?>

            <a href="<?php echo BASE_URL_LINK; ?>auth/register.php" 
               class="nav-btn <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>">
               Register
            </a>

            <a href="<?php echo BASE_URL_LINK; ?>auth/login.php" 
               class="nav-btn <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
               Login
            </a>

        <?php endif; ?>
    </div>
</nav>

<script>
    function toggleMenu() {
        var x = document.getElementById("navItems");
        if (x.style.display === "flex") {
            x.style.display = "none";
        } else {
            x.style.display = "flex";
            x.style.flexDirection = "column";
            x.style.position = "absolute";
            x.style.top = "60px";
            x.style.right = "0";
            x.style.backgroundColor = "#1e293b";
            x.style.width = "200px";
            x.style.padding = "20px";
            x.style.zIndex = "1000"; // Added z-index to ensure it floats on top
        }
    }
</script>