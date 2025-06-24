<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

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
    header("Location: admin_index.php");
    exit;
}

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
                $target_dir = "images/rooms/";
                
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
            
            // Proceed with the insert if we have fields to insert
            if (!empty($fields)) {
                $insert_query = "INSERT INTO rooms (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
                $insert_stmt = $conn->prepare($insert_query);
                
                // Bind parameters dynamically
                if ($types && count($params) > 0) {
                    // Create a reference array for bind_param
                    $ref_params = [];
                    $ref_params[] = $types;
                    
                    for ($i = 0; $i < count($params); $i++) {
                        $ref_params[] = &$params[$i];
                    }
                    
                    // Call bind_param with the reference array
                    call_user_func_array([$insert_stmt, 'bind_param'], $ref_params);
                }
                
                if ($insert_stmt->execute()) {
                    $message = "Room added successfully!";
                    // Redirect back to rooms page
                    header("Location: simple_rooms.php?message=" . urlencode($message));
                    exit;
                } else {
                    $error = "Error adding room: " . $conn->error . " | SQL: " . $insert_query;
                }
            } else {
                $error = "No valid fields found for insert. Check your database schema.";
            }
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Room - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
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
        .submit-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
        .cancel-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .cancel-btn:hover {
            background-color: #5a6268;
        }
        .form-actions {
            margin-top: 20px;
            display: flex;
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Add New Room</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                <a href="admin_dashboard.php" class="sidebar-nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_customers.php" class="sidebar-nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="admin_bookings.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="admin_new_booking.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-plus"></i>
                    <span>New Booking</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-nav-title">Management</div>
                <a href="simple_tours.php" class="sidebar-nav-item">
                    <i class="fas fa-route"></i>
                    <span>Tours</span>
                </a>
                <a href="simple_rooms.php" class="sidebar-nav-item active">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="simple_packages.php" class="sidebar-nav-item">
                    <i class="fas fa-box"></i>
                    <span>Packages</span>
                </a>
                <a href="admin_users.php" class="sidebar-nav-item">
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
                    <h2 class="section-title">Add New Room</h2>
                </div>
                
                <div class="form-container">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Room Name/Number</label>
                                <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="type">Room Type</label>
                                <select id="type" name="type" required>
                                    <option value="">Select Room Type</option>
                                    <?php foreach ($room_types as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo (isset($_POST['type']) && $_POST['type'] == $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Other (New Type)</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="newTypeGroup" style="display: none;">
                                <label for="new_type">Specify New Type</label>
                                <input type="text" id="new_type" name="new_type" value="<?php echo isset($_POST['new_type']) ? htmlspecialchars($_POST['new_type']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_occupancy">Maximum Occupancy</label>
                                <input type="number" id="max_occupancy" name="max_occupancy" min="1" max="20" required value="<?php echo isset($_POST['max_occupancy']) ? htmlspecialchars($_POST['max_occupancy']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="price_per_night">Price Per Night (₹)</label>
                                <input type="number" id="price_per_night" name="price_per_night" step="0.01" min="0" required value="<?php echo isset($_POST['price_per_night']) ? htmlspecialchars($_POST['price_per_night']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="floor">Floor</label>
                                <select id="floor" name="floor">
                                    <option value="">Select Floor</option>
                                    <?php foreach ($floors as $floor): ?>
                                        <option value="<?php echo $floor; ?>" <?php echo (isset($_POST['floor']) && $_POST['floor'] == $floor) ? 'selected' : ''; ?>><?php echo $floor; ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Other (New Floor)</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="newFloorGroup" style="display: none;">
                                <label for="new_floor">Specify New Floor</label>
                                <input type="number" id="new_floor" name="new_floor" min="0" max="100" value="<?php echo isset($_POST['new_floor']) ? htmlspecialchars($_POST['new_floor']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="amenities">Amenities</label>
                            <textarea id="amenities" name="amenities" rows="3" placeholder="List amenities, separated by commas"><?php echo isset($_POST['amenities']) ? htmlspecialchars($_POST['amenities']) : ''; ?></textarea>
                            <small>Enter items separated by commas (e.g. Wi-Fi, Air Conditioning, TV, Mini Bar)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Room Image</label>
                            <input type="file" id="image" name="image">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="maintenance" <?php echo (isset($_POST['status']) && $_POST['status'] == 'maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <a href="simple_rooms.php" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <input type="hidden" name="add_room" value="1">
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i> Add Room
                            </button>
                        </div>
                    </form>
                </div>
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