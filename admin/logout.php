<?php
// 1. Include config to get BASE_URL
require_once '../config.php'; 

// 2. Start the session
session_start();

// 3. Unset all session variables
$_SESSION = array();

// 4. Destroy the session (logs the user out)
session_destroy();

// 5. Redirect to the MAIN index.php (Student/Staff Login)
// Using BASE_URL ensures it goes to http://localhost/cems/IndivAsg/index.php
header("Location: " . BASE_URL . "/index.php");
exit;
?>