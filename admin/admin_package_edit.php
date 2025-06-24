<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin/admin.php");
    exit;
}

// Check if package ID is provided
if (!isset($_POST['package_id']) || !is_numeric($_POST['package_id'])) {
    $_SESSION['error'] = "Invalid package ID.";
    header("Location: admin_packages.php");
    exit;
}

$package_id = $_POST['package_id'];

// Get form data
$name = $_POST['name'];
$description = $_POST['description'];
$price = $_POST['price'];
$duration = $_POST['duration'];
$includes = $_POST['included_items'];
$status = $_POST['status'];

// Check if package exists
$check_query = "SELECT * FROM packages WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $package_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "Package not found.";
    header("Location: admin_packages.php");
    exit;
}

// Check if another package with the same name exists
$name_check_query = "SELECT id FROM packages WHERE name = ? AND id != ?";
$name_check_stmt = $conn->prepare($name_check_query);
$name_check_stmt->bind_param("si", $name, $package_id);
$name_check_stmt->execute();
$name_check_result = $name_check_stmt->get_result();

if ($name_check_result->num_rows > 0) {
    $_SESSION['error'] = "A package with this name already exists.";
    header("Location: admin_packages.php");
    exit;
}

// Prepare image upload if provided
$image_path = '';
$update_image = false;

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['image']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $allowed)) {
        $target_dir = "../images/packages/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $new_filename = 'package_' . uniqid() . '.' . $ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
            $update_image = true;
        } else {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: admin_packages.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
        header("Location: admin_packages.php");
        exit;
    }
}

// Update package
if ($update_image) {
    $update_query = "UPDATE packages SET 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    duration = ?, 
                    included_items = ?, 
                    status = ?, 
                    image_path = ? 
                    WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssdssssi", $name, $description, $price, $duration, $includes, $status, $image_path, $package_id);
} else {
    $update_query = "UPDATE packages SET 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    duration = ?, 
                    included_items = ?, 
                    status = ? 
                    WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssdsssi", $name, $description, $price, $duration, $includes, $status, $package_id);
}

if ($update_stmt->execute()) {
    $_SESSION['message'] = "Package updated successfully!";
} else {
    $_SESSION['error'] = "Error updating package: " . $conn->error;
}

header("Location: admin_packages.php");
exit; 