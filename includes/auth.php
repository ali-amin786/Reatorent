<?php
// includes/auth.php - Security, Sanitization, CSRF, and Admin Session Guards

// Start secure session if not started
if (session_status() === PHP_SESSION_NONE) {
    // Session security configurations
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Sanitize raw string input for MySQLi query
 */
function clean($conn, $data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    return mysqli_real_escape_string($conn, $data);
}

/**
 * Sanitize integer input
 */
function clean_int($data) {
    return (int) $data;
}

/**
 * Sanitize float/decimal input
 */
function clean_float($data) {
    return (float) $data;
}

/**
 * Safe output encoding to prevent XSS
 */
function e($data) {
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF Token Generator
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token Field for Forms
 */
function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Validate CSRF Token
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Admin Session Guard with 30-minute idle timeout
 */
function require_admin_login() {
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }

    // 30-minute idle timeout (1800 seconds)
    $timeout_duration = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Check if the restaurant is currently open based on system time (Karachi Timezone: Asia/Karachi)
 */
function check_restaurant_hours() {
    // Set Karachi timezone
    date_default_timezone_set('Asia/Karachi');

    $currentTime = date('H:i');
    $open = defined('OPENING_TIME') ? OPENING_TIME : '16:00';
    $close = defined('CLOSING_TIME') ? CLOSING_TIME : '02:00';

    // Since closing is past midnight (16:00 to 02:00):
    // Open if time >= 16:00 OR time < 02:00
    $is_open = false;
    if ($open > $close) {
        if ($currentTime >= $open || $currentTime < $close) {
            $is_open = true;
        }
    } else {
        if ($currentTime >= $open && $currentTime < $close) {
            $is_open = true;
        }
    }

    $open_display = date("g:i A", strtotime($open));
    $close_display = date("g:i A", strtotime($close));

    return [
        'is_open' => $is_open,
        'open_time' => $open_display,
        'close_time' => $close_display,
        'status_text' => $is_open ? 'Open Now (Serving Fresh)' : 'Closed — Reopens at ' . $open_display,
        'badge_class' => $is_open ? 'badge-open' : 'badge-closed'
    ];
}

/**
 * Validates and handles image upload for items or payment proofs
 * @param array $file $_FILES['input_name']
 * @param string $destination_folder Directory path with trailing slash
 * @param string $prefix Prefix for generated filename
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function handle_image_upload($file, $destination_folder, $prefix = 'img_') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // Check size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size exceeds 2MB limit.'];
    }

    // Check MIME type using finfo
    if (!function_exists('finfo_open')) {
        return ['success' => false, 'error' => 'Fileinfo extension is not enabled on server.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file format. Only JPG, PNG, and WebP are allowed.'];
    }

    // Determine extension safely
    $mime_extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];
    $ext = $mime_extensions[$mime] ?? 'jpg';

    // Generate random, collision-safe filename
    $safe_name = uniqid($prefix, true) . '.' . $ext;

    // Ensure target folder exists
    if (!is_dir($destination_folder)) {
        mkdir($destination_folder, 0755, true);
    }

    $destination_path = rtrim($destination_folder, '/\\') . DIRECTORY_SEPARATOR . $safe_name;

    if (move_uploaded_file($file['tmp_name'], $destination_path)) {
        return ['success' => true, 'filename' => $safe_name];
    } else {
        return ['success' => false, 'error' => 'Failed to save uploaded file.'];
    }
}
?>
