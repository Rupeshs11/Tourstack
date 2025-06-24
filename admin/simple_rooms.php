<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Debug function
function debug_to_console($data) {
    $output = $data;
    if (is_array($output) || is_object($output)) {
        $output = json_encode($output);
    }
    echo "<script>console.log('DEBUG: " . addslashes($output) . "');</script>";
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin/admin_index.php");
    exit;
}

// Create rooms table if not exists
$create_table_query = "CREATE TABLE IF NOT EXISTS rooms (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    max_occupancy INT(2) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    amenities TEXT,
    image_path VARCHAR(255),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    floor INT(2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";
$conn->query($create_table_query);

// Process form submissions
$message = '';
$error = '';

// Add new room
if (isset($_POST['add_room'])) {
    debug_to_console('Processing add_room submission');
    debug_to_console($_POST);
    debug_to_console($_FILES);
    
    $name = $_POST['name'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $max_occupancy = $_POST['max_occupancy'];
    $price_per_night = $_POST['price_per_night'];
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : '';
    $floor = isset($_POST['floor']) ? $_POST['floor'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // Check if room with same name exists
    $check_query = "SELECT id FROM rooms WHERE name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "A room with this name already exists.";
    } else {
        // Prepare image upload if provided
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $target_dir = "../images/rooms/";
                
                // Create directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                $new_filename = 'room_' . uniqid() . '.' . $ext;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $error = "Failed to upload image. Error: " . $_FILES['image']['error'];
                }
            } else {
                $error = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
            }
        }
        
        if (empty($error)) {
            // Process "other" type option
            if ($type === 'other' && isset($_POST['new_type']) && !empty($_POST['new_type'])) {
                $type = $_POST['new_type'];
            }
            
            // Process "other" floor option
            if ($floor === 'other' && isset($_POST['new_floor']) && !empty($_POST['new_floor'])) {
                $floor = $_POST['new_floor'];
            }
            
            // Check what columns exist in the rooms table
            $columns_query = "SHOW COLUMNS FROM rooms";
            $columns_result = $conn->query($columns_query);
            $column_names = [];
            
            if ($columns_result) {
                while ($column = $columns_result->fetch_assoc()) {
                    $column_names[] = $column['Field'];
                }
            }
            
            // Construct query based on existing columns
            $fields = [];
            $values = [];
            $types = "";
            $params = [];
            
            if (in_array('name', $column_names)) {
                $fields[] = 'name';
                $values[] = '?';
                $types .= 's';
                $params[] = $name;
            }
            
            if (in_array('room_number', $column_names)) {
                $fields[] = 'room_number';
                $values[] = '?';
                $types .= 's';
                $params[] = $name; // Using name as room number if that column exists
            }
            
            if (in_array('type', $column_names)) {
                $fields[] = 'type';
                $values[] = '?';
                $types .= 's';
                $params[] = $type;
            }
            
            if (in_array('description', $column_names)) {
                $fields[] = 'description';
                $values[] = '?';
                $types .= 's';
                $params[] = $description;
            }
            
            if (in_array('max_occupancy', $column_names)) {
                $fields[] = 'max_occupancy';
                $values[] = '?';
                $types .= 'i';
                $params[] = $max_occupancy;
            }
            
            if (in_array('capacity', $column_names)) {
                $fields[] = 'capacity';
                $values[] = '?';
                $types .= 'i';
                $params[] = $max_occupancy; // Using max_occupancy for capacity if that column exists
            }
            
            if (in_array('price_per_night', $column_names)) {
                $fields[] = 'price_per_night';
                $values[] = '?';
                $types .= 'd';
                $params[] = $price_per_night;
            }
            
            if (in_array('price', $column_names)) {
                $fields[] = 'price';
                $values[] = '?';
                $types .= 'd';
                $params[] = $price_per_night; // Using price_per_night for price if that column exists
            }
            
            if (in_array('amenities', $column_names)) {
                $fields[] = 'amenities';
                $values[] = '?';
                $types .= 's';
                $params[] = $amenities;
            }
            
            if (in_array('image_path', $column_names)) {
                $fields[] = 'image_path';
                $values[] = '?';
                $types .= 's';
                $params[] = $image_path;
            }
            
            if (in_array('floor', $column_names)) {
                $fields[] = 'floor';
                $values[] = '?';
                $types .= 's';
                $params[] = $floor;
            }
            
            if (in_array('status', $column_names)) {
                $fields[] = 'status';
                $values[] = '?';
                $types .= 's';
                $params[] = $status;
            }
            
            // Construct and execute the INSERT query
            if (!empty($fields)) {
                $insert_query = "INSERT INTO rooms (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
                
                $insert_stmt = $conn->prepare($insert_query);
                
                if ($insert_stmt) {
                    // Dynamically bind parameters
                    if (!empty($params)) {
                        $bind_params = array();
                        $bind_params[] = &$types;
                        
                        for ($i = 0; $i < count($params); $i++) {
                            $bind_params[] = &$params[$i];
                        }
                        
                        call_user_func_array(array($insert_stmt, 'bind_param'), $bind_params);
                    }
                    
                    if ($insert_stmt->execute()) {
                        $message = "Room added successfully!";
                        // Clear the form data
                        unset($_POST);
                    } else {
                        $error = "Error adding room: " . $conn->error . " - " . $insert_stmt->error;
                    }
                } else {
                    $error = "Error preparing statement: " . $conn->error;
                }
            } else {
                $error = "No valid fields found for insertion";
            }
        }
    }
}

// Delete room
if (isset($_GET['delete'])) {
    $room_id = $_GET['delete'];
    
    // Check if related tables exist and for foreign key constraints
    $related_tables = ['bookings', 'room_bookings'];
    $has_constraints = false;
    
    foreach ($related_tables as $table) {
        $table_check_query = "SHOW TABLES LIKE '$table'";
        $table_check_result = $conn->query($table_check_query);
        
        if ($table_check_result && $table_check_result->num_rows > 0) {
            // Table exists, check for records referencing this room
            $check_query = "";
            $foreign_key = "";
            
            if ($table == 'bookings') {
                $check_query = "SELECT COUNT(*) as count FROM bookings WHERE room_id = ?";
                $foreign_key = "room_id";
            } else if ($table == 'room_bookings') {
                $check_query = "SELECT COUNT(*) as count FROM room_bookings WHERE room_id = ?";
                $foreign_key = "room_id";
            }
            
            if (!empty($check_query)) {
                $check_stmt = $conn->prepare($check_query);
                if ($check_stmt) {
                    $check_stmt->bind_param("i", $room_id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    if ($result && $row = $result->fetch_assoc()) {
                        if ($row['count'] > 0) {
                            // Found related records
                            $error = "Cannot delete room: There are references in the $table table. Please remove those entries first.";
                            $has_constraints = true;
                            break;
                        }
                    }
                }
            }
        }
    }
    
    if ($has_constraints) {
        goto delete_end;
    }
    
    // Now try to delete the room
    $delete_query = "DELETE FROM rooms WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $room_id);
    
    if ($delete_stmt->execute()) {
        $message = "Room deleted successfully!";
    } else {
        $error = "Error deleting room: " . $conn->error;
    }
    
    delete_end:
    // This is a label for goto, no code needed here
}

// Update room status
if (isset($_GET['toggle_status'])) {
    $room_id = $_GET['toggle_status'];
    $status_query = "SELECT status FROM rooms WHERE id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("i", $room_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $room = $status_result->fetch_assoc();
        $new_status = '';
        
        // Rotate through statuses: active -> maintenance -> inactive -> active
        if ($room['status'] == 'active') {
            $new_status = 'maintenance';
        } else if ($room['status'] == 'maintenance') {
            $new_status = 'inactive';
        } else {
            $new_status = 'active';
        }
        
        $update_query = "UPDATE rooms SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $room_id);
        
        if ($update_stmt->execute()) {
            $message = "Room status updated successfully!";
        } else {
            $error = "Error updating room status: " . $conn->error;
        }
    }
}

// Get room types for dropdown
$types_query = "SHOW COLUMNS FROM rooms LIKE 'type'";
$types_result = $conn->query($types_query);
$has_type_column = $types_result->num_rows > 0;

if ($has_type_column) {
    $types_query = "SELECT DISTINCT type FROM rooms WHERE type IS NOT NULL ORDER BY type";
    $types_result = $conn->query($types_query);
    $room_types = [];
    if ($types_result && $types_result->num_rows > 0) {
        while ($type = $types_result->fetch_assoc()) {
            $room_types[] = $type['type'];
        }
    }
} else {
    // Default room types if column doesn't exist
    $room_types = ['Standard', 'Deluxe', 'Suite', 'Executive'];
}

// Get floors for dropdown - Check if column exists first
$floors_query = "SHOW COLUMNS FROM rooms LIKE 'floor'";
$floors_result = $conn->query($floors_query);
$has_floor_column = $floors_result->num_rows > 0;

if ($has_floor_column) {
    $floors_query = "SELECT DISTINCT floor FROM rooms WHERE floor IS NOT NULL ORDER BY floor";
    $floors_result = $conn->query($floors_query);
    $floors = [];
    if ($floors_result && $floors_result->num_rows > 0) {
        while ($floor = $floors_result->fetch_assoc()) {
            $floors[] = $floor['floor'];
        }
    }
} else {
    // Default floors if column doesn't exist
    $floors = [1, 2, 3, 4, 5];
}

// Get rooms
$rooms_query = "SELECT * FROM rooms ORDER BY id DESC";
$rooms_result = $conn->query($rooms_query);

// Debug the actual table structure
$table_structure_query = "DESCRIBE rooms";
$table_structure_result = $conn->query($table_structure_query);
$rooms_columns = [];
if ($table_structure_result) {
    while ($column = $table_structure_result->fetch_assoc()) {
        $rooms_columns[] = $column['Field'];
    }
    debug_to_console("Rooms table columns: " . implode(", ", $rooms_columns));
}

// Get rooms with proper field checking
$rooms_query = "SELECT * FROM rooms ORDER BY id DESC";
$rooms_result = $conn->query($rooms_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Customization - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-maintenance {
            background-color: #fff3cd;
            color: #856404;
        }
        .action-btn {
            display: inline-block;
            margin-right: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 12px;
        }
        .edit-btn {
            background-color: #0d6efd;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        .status-btn {
            background-color: #6c757d;
        }
        .room-thumbnail {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .success-message, .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Room Customization</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            <a href="admin/admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-hotel"></i>
                <span>TOUR STACK</span>
            </div>
            <div class="sidebar-nav">
                <div class="sidebar-nav-title">Main</div>
                <a href="admin/admin_dashboard.php" class="sidebar-nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin/admin_customers.php" class="sidebar-nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="admin/admin_bookings.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="admin/admin_new_booking.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-plus"></i>
                    <span>New Booking</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-nav-title">Management</div>
                <a href="admin/simple_tours.php" class="sidebar-nav-item">
                    <i class="fas fa-route"></i>
                    <span>Tours</span>
                </a>
                <a href="admin/simple_rooms.php" class="sidebar-nav-item active">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="admin/simple_packages.php" class="sidebar-nav-item">
                    <i class="fas fa-box"></i>
                    <span>Packages</span>
                </a>
                <a href="admin/admin_users.php" class="sidebar-nav-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Users</span>
                </a>
                
            </div>
        </aside>
        
        <main class="admin-main">
            <?php if (!empty($message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <section class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">All Rooms</h2>
                    <div>
                        <a href="admin/add_room.php" class="action-btn edit-btn" style="background-color: #28a745;">
                            <i class="fas fa-plus"></i> Add New Room
                        </a>
                        <a href="admin/remove_all_rooms.php" class="action-btn delete-btn" style="background-color: #e74c3c;">
                            <i class="fas fa-trash-alt"></i> Remove All Rooms
                        </a>
                    </div>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Occupancy</th>
                            <th>Price/Night</th>
                            <th>Floor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rooms_result && $rooms_result->num_rows > 0): ?>
                            <?php while($room = $rooms_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $room['id'] ?? ''; ?></td>
                                    <td>
                                        <?php if (isset($room['image_path']) && !empty($room['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($room['image_path']); ?>" alt="<?php echo htmlspecialchars($room['name'] ?? ''); ?>" class="room-thumbnail">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo isset($room['name']) ? htmlspecialchars($room['name']) : 'N/A'; ?></td>
                                    <td>
                                        <?php 
                                        if (isset($room['type'])) {
                                            echo htmlspecialchars($room['type']);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($room['max_occupancy'])) {
                                            echo htmlspecialchars($room['max_occupancy']) . ' persons';
                                        } elseif (isset($room['capacity'])) {
                                            echo htmlspecialchars($room['capacity']) . ' persons';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($room['price_per_night'])) {
                                            echo '₹' . number_format($room['price_per_night'], 2);
                                        } elseif (isset($room['price'])) {
                                            echo '₹' . number_format($room['price'], 2);
                                        } else {
                                            echo '₹0.00';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $room['floor'] ?? 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo isset($room['status']) ? strtolower($room['status']) : 'inactive'; ?>">
                                            <?php echo isset($room['status']) ? ucfirst($room['status']) : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="simple_room_detail.php?id=<?php echo $room['id']; ?>" class="action-btn edit-btn">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?toggle_status=<?php echo $room['id']; ?>" class="action-btn status-btn">
                                            <i class="fas fa-sync-alt"></i> Toggle Status
                                        </a>
                                        <a href="?delete=<?php echo $room['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this room? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-data">No rooms found. Add your first room!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    
    <script>
        // Show/hide new type field based on selection
        document.getElementById('type').addEventListener('change', function() {
            var newTypeGroup = document.getElementById('newTypeGroup');
            if (this.value === 'other') {
                newTypeGroup.style.display = 'block';
                document.getElementById('new_type').setAttribute('required', 'required');
            } else {
                newTypeGroup.style.display = 'none';
                document.getElementById('new_type').removeAttribute('required');
            }
        });
        
        // Show/hide new floor field based on selection
        document.getElementById('floor').addEventListener('change', function() {
            var newFloorGroup = document.getElementById('newFloorGroup');
            if (this.value === 'other') {
                newFloorGroup.style.display = 'block';
                document.getElementById('new_floor').setAttribute('required', 'required');
            } else {
                newFloorGroup.style.display = 'none';
                document.getElementById('new_floor').removeAttribute('required');
            }
        });
        
        // Check if values were set before page reload
        window.onload = function() {
            var typeSelect = document.getElementById('type');
            var floorSelect = document.getElementById('floor');
            
            if (typeSelect.value === 'other') {
                document.getElementById('newTypeGroup').style.display = 'block';
            }
            
            if (floorSelect.value === 'other') {
                document.getElementById('newFloorGroup').style.display = 'block';
            }
        };
    </script>
</body>

</html> 