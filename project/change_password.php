<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to change your password.";
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
$errors = [];

if (empty($current_password)) {
    $errors[] = "Current password is required.";
}

if (empty($new_password)) {
    $errors[] = "New password is required.";
} elseif (strlen($new_password) < 6) {
    $errors[] = "New password must be at least 6 characters long.";
}

if ($new_password !== $confirm_password) {
    $errors[] = "New password and confirmation password do not match.";
}

// If there are no errors, verify current password
if (empty($errors)) {
    // Get user's current password from database
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $errors[] = "User not found.";
    } else {
        $user = $result->fetch_assoc();
        $stored_password = $user['password'];
        
        // Verify current password
        if (!password_verify($current_password, $stored_password) && $stored_password !== md5($current_password)) {
            $errors[] = "Current password is incorrect.";
        }
    }
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $_SESSION['password_errors'] = $errors;
    header('Location: profile.php');
    exit;
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password in database
$sql = "UPDATE users SET password = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $hashed_password, $user_id);

if ($stmt->execute()) {
    // Password changed successfully
    $_SESSION['password_success'] = "Your password has been changed successfully.";
} else {
    // Password change failed
    $_SESSION['password_error'] = "Failed to change password. Please try again.";
}

header('Location: profile.php');
exit;
?> 