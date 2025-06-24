<?php
// Start the session
session_start();

// Get the error type to clear from query parameter
$error_type = isset($_GET['type']) ? $_GET['type'] : '';

// Clear specific error message
if (!empty($error_type) && isset($_SESSION[$error_type])) {
    unset($_SESSION[$error_type]);
}

// If this is an AJAX request, return success
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode(['success' => true]);
}

// If this is a direct browser request, redirect back
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Get the referer or default to homepage
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
    exit;
}
?> 