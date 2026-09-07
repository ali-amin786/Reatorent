<?php
// config/db.php - Database connection & application constants
// A1 Peshawari Chapli Kabab (MASHAALLAH Naan House)

// Error reporting for development (safe for display, clean procedural handling)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide raw errors from public view
ini_set('log_errors', 1);

// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "kabab_restaurant";

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    // Graceful error display if DB is unreachable
    http_response_code(500);
    die("<div style='font-family: sans-serif; text-align: center; padding: 50px;'>
            <h2>Database Connection Error</h2>
            <p>Could not connect to the database. Please verify that MySQL is running in XAMPP and the database <strong>$db_name</strong> exists.</p>
         </div>");
}

// Set UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");

// Application Constants
define('SITE_NAME', 'A1 Peshawari Chapli Kabab');
define('SUB_NAME', 'MASHAALLAH Naan House');
define('RESTAURANT_ADDRESS', 'Main Super Highway Road, Gulzar-e-Hijri Scheme 33, Karachi, Pakistan');
define('PHONE_NUMBER', '+92 300 1234567');
define('WHATSAPP_NUMBER', '923001234567'); // international format without +
define('CURRENCY', 'PKR');
define('MIN_ORDER_AMOUNT', 500.00);

// Operating Hours (4:00 PM to 2:00 AM)
define('OPENING_TIME', '16:00');
define('CLOSING_TIME', '02:00');

// Base URL detection helper
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Normalize base path for root app
if (strpos($script_dir, '/admin') !== false) {
    $base_dir = substr($script_dir, 0, strpos($script_dir, '/admin'));
} else {
    $base_dir = rtrim($script_dir, '/');
}
define('BASE_URL', $protocol . $host . $base_dir . '/');
?>
