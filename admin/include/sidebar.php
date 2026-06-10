<?php
// 1. Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Only include config if $conn isn't set, and find the correct path
if (!isset($conn)) {
    if (file_exists('config.php')) {
        require_once 'config.php';
    } elseif (file_exists('../config.php')) {
        require_once '../config.php';
    }
}
?>