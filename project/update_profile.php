<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to update your profile.";
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$dob = $_POST['dob'] ?? '';

// Validate inputs
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required.";
}

if (empty($email)) {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
} else {
    // Check if email is already in use by another user
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email is already in use by another account.";
    }
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $_SESSION['profile_errors'] = $errors;
    header('Location: profile.php');
    exit;
}

// Update user profile
// Check if users table has a phone or mobile column
$check_phone_column = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
$check_mobile_column = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
$phone_column_exists = $check_phone_column && $check_phone_column->num_rows > 0;
$mobile_column_exists = $check_mobile_column && $check_mobile_column->num_rows > 0;

// Update user in database with the appropriate field (phone or mobile)
if ($phone_column_exists) {
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, dob = ? WHERE id = ?";
} else if ($mobile_column_exists) {
    $sql = "UPDATE users SET name = ?, email = ?, mobile = ?, dob = ? WHERE id = ?";
} else {
    // If neither column exists, just update name and email
    $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $email, $user_id);
    
    if ($stmt->execute()) {
        // Update successful
        $_SESSION['profile_success'] = "Your profile has been updated successfully.";
        
        // Update session variables if needed
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
    } else {
        // Update failed
        $_SESSION['profile_error'] = "Failed to update profile. Please try again.";
    }
    
    header('Location: profile.php');
    exit;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $name, $email, $phone, $dob, $user_id);

if ($stmt->execute()) {
    // Update successful
    $_SESSION['profile_success'] = "Your profile has been updated successfully.";
    
    // Update session variables if needed
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
} else {
    // Update failed
    $_SESSION['profile_error'] = "Failed to update profile. Please try again.";
}

header('Location: profile.php');
exit;
?> 