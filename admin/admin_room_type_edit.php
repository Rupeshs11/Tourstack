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

// Check if room type ID is provided
if (!isset($_POST['type_id']) || !is_numeric($_POST['type_id'])) {
    $_SESSION['admin_error'] = "Invalid room type ID.";
    header("Location: admin/admin_room_types.php");
    exit;
}

$type_id = $_POST['type_id'];

// Check if the room type exists
$check_query = "SELECT * FROM room_types WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $type_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['admin_error'] = "Room type not found.";
    header("Location: admin/admin_room_types.php");
    exit;
}

// Get form data
$name = $_POST['name'];
$description = $_POST['description'];
$max_occupancy = $_POST['max_occupancy'];
$amenities = $_POST['amenities'];
$price_multiplier = $_POST['price_multiplier'];
$status = isset($_POST['status']) ? $_POST['status'] : 'active';

// Check if room type name already exists (but not for this type)
$check_name_query = "SELECT id FROM room_types WHERE name = ? AND id != ?";
$check_name_stmt = $conn->prepare($check_name_query);
$check_name_stmt->bind_param("si", $name, $type_id);
$check_name_stmt->execute();
$check_name_result = $check_name_stmt->get_result();

if ($check_name_result->num_rows > 0) {
    $_SESSION['admin_error'] = "A room type with this name already exists.";
    header("Location: admin/admin_room_types.php");
    exit;
}

// Handle image upload if provided
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['image']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $allowed)) {
        $target_dir = "../images/room_types/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $new_filename = 'type_' . uniqid() . '.' . $ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
            
            // Get the old image path to delete it later
            $old_image_query = "SELECT image_path FROM room_types WHERE id = ?";
            $old_image_stmt = $conn->prepare($old_image_query);
            $old_image_stmt->bind_param("i", $type_id);
            $old_image_stmt->execute();
            $old_image_result = $old_image_stmt->get_result();
            
            if ($old_image_result->num_rows > 0) {
                $old_image = $old_image_result->fetch_assoc()['image_path'];
                // Delete the old image if it exists and is not a default image
                if (!empty($old_image) && file_exists($old_image) && strpos($old_image, 'default') === false) {
                    unlink($old_image);
                }
            }
        } else {
            $_SESSION['admin_error'] = "Failed to upload image.";
            header("Location: admin/admin_room_types.php");
            exit;
        }
    } else {
        $_SESSION['admin_error'] = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
        header("Location: admin/admin_room_types.php");
        exit;
    }
}

// Update the room type in the database
if ($image_path) {
    // If a new image was uploaded
    $update_query = "UPDATE room_types SET 
                    name = ?, 
                    description = ?, 
                    max_occupancy = ?, 
                    amenities = ?, 
                    image_path = ?, 
                    price_multiplier = ?, 
                    status = ? 
                    WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssissdsi", $name, $description, $max_occupancy, $amenities, $image_path, $price_multiplier, $status, $type_id);
} else {
    // If no new image was uploaded
    $update_query = "UPDATE room_types SET 
                    name = ?, 
                    description = ?, 
                    max_occupancy = ?, 
                    amenities = ?, 
                    price_multiplier = ?, 
                    status = ? 
                    WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssisssi", $name, $description, $max_occupancy, $amenities, $price_multiplier, $status, $type_id);
}

if ($update_stmt->execute()) {
    $_SESSION['admin_message'] = "Room type updated successfully!";
} else {
    $_SESSION['admin_error'] = "Error updating room type: " . $conn->error;
}

header("Location: admin/admin_room_types.php");
exit;
?> 