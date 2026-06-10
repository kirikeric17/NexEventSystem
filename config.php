<?php
/**
 * Global Configuration File for CEMS
 * ----------------------------------
 * - Defines URLs and filesystem paths
 * - Establishes database connection
 * - Auto-detects protocol and host
 */

// --- URL and Path Settings ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
             || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

$host = $_SERVER['HTTP_HOST'];

// Adjust this to your project folder (relative to document root)
$projectFolder = '/cems/NexEvent System';

// Base URL (for web access - client side)
define('BASE_URL', $protocol . $host . $projectFolder);

// Base Root Path (for file system access - server side)
define('ROOT_PATH', rtrim($_SERVER['DOCUMENT_ROOT'] . $projectFolder, '/'));

// --- Common Subpaths (URL-based) ---
define('BASE_PATH_ASSETS', BASE_URL . '/assets');
define('BASE_PATH_CSS', BASE_PATH_ASSETS . '/css/');
define('BASE_PATH_IMG', BASE_PATH_ASSETS . '/img/');
define('BASE_PATH_UPLOADS', BASE_URL . '/uploads/');
define('BASE_PATH_INCLUDE', BASE_URL . '/include/');
define('BASE_PATH_ADMIN', BASE_URL . '/admin/');

// --- Server Paths (for PHP includes) ---
define('ROOT_PATH_ADMIN', ROOT_PATH . '/admin/');

// --- Database Connection ---
$databaseHost = 'localhost';
$databaseUsername = 'root';
$databasePassword = '';
$databaseName = 'cems';

// Use mysqli with error handling
$conn = new mysqli($databaseHost, $databaseUsername, $databasePassword, $databaseName);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: set UTF-8 encoding
$conn->set_charset("utf8");

// --- Debug Mode ---
define('DEBUG_MODE', true); // change to true for development

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
