<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_index.php");
    exit;
}

// Check if room ID is provided
if (!isset($_POST['room_id']) || !is_numeric($_POST['room_id'])) {
    $_SESSION['admin_error'] = "Invalid room ID.";
    header("Location: simple_rooms.php");
    exit;
}

$room_id = $_POST['room_id'];

// Check if the room exists
$check_query = "SELECT * FROM rooms WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $room_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['admin_error'] = "Room not found.";
    header("Location: simple_rooms.php");
    exit;
}

// Get form data
$name = $_POST['name'];
$type = $_POST['type'];
$room_number = $_POST['room_number'];
$floor = $_POST['floor'];
$price = $_POST['price'];
$description = $_POST['description'];
$status = isset($_POST['status']) ? $_POST['status'] : 'active';
$beds = isset($_POST['beds']) ? $_POST['beds'] : 1;
$room_size = isset($_POST['room_size']) ? $_POST['room_size'] : null;
$promo_text = isset($_POST['promo_text']) ? $_POST['promo_text'] : null;

// Check if room number already exists (but not for this room)
$check_number_query = "SELECT id FROM rooms WHERE room_number = ? AND id != ?";
$check_number_stmt = $conn->prepare($check_number_query);
$check_number_stmt->bind_param("si", $room_number, $room_id);
$check_number_stmt->execute();
$check_number_result = $check_number_stmt->get_result();

if ($check_number_result->num_rows > 0) {
    $_SESSION['admin_error'] = "A room with this room number already exists.";
    header("Location: simple_rooms.php");
    exit;
}

// Handle image upload if provided
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['image']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $allowed)) {
        $target_dir = "images/rooms/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $new_filename = 'room_' . uniqid() . '.' . $ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
            
            // Get the old image path to delete it later
            $old_image_query = "SELECT image_path FROM rooms WHERE id = ?";
            $old_image_stmt = $conn->prepare($old_image_query);
            $old_image_stmt->bind_param("i", $room_id);
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
            header("Location: simple_rooms.php");
            exit;
        }
    } else {
        $_SESSION['admin_error'] = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
        header("Location: simple_rooms.php");
        exit;
    }
}

// Update the room in the database
if ($image_path) {
    // If a new image was uploaded
    $update_query = "UPDATE rooms SET 
                    name = ?, 
                    type = ?, 
                    room_number = ?, 
                    floor = ?, 
                    price = ?, 
                    description = ?, 
                    status = ?, 
                    image_path = ?, 
                    beds = ?, 
                    room_size = ?, 
                    promo_text = ? 
                    WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssiidssisssi", $name, $type, $room_number, $floor, $price, $description, $status, $image_path, $beds, $room_size, $promo_text, $room_id);
} else {
    // If no new image was uploaded
    $update_query = "UPDATE rooms SET 
                    name = ?, 
                    type = ?, 
                    room_number = ?, 
                    floor = ?, 
                    price = ?, 
                    description = ?, 
                    status = ?, 
                    beds = ?, 
                    room_size = ?, 
                    promo_text = ? 
                    WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssiidssissi", $name, $type, $room_number, $floor, $price, $description, $status, $beds, $room_size, $promo_text, $room_id);
}

if ($update_stmt->execute()) {
    $_SESSION['admin_message'] = "Room updated successfully!";
} else {
    $_SESSION['admin_error'] = "Error updating room: " . $conn->error;
}

header("Location: simple_rooms.php");
exit;
?> 