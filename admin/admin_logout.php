<?php
// Start session
session_start();

// Unset admin session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

// Destroy the session
session_destroy();

// Redirect to admin login page
header("Location: admin/admin.php");
exit;
?> 