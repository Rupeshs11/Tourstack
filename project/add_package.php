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

// Add new package
if (isset($_POST['add_package'])) {
    debug_to_console('Processing add_package submission');
    debug_to_console($_POST);
    debug_to_console($_FILES);
    
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $included_items = isset($_POST['included_items']) ? $_POST['included_items'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // Check if package with same name exists
    $check_query = "SELECT id FROM packages WHERE name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "A package with this name already exists.";
    } else {
        // Prepare image upload if provided
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $target_dir = "images/packages/";
                
                // Create directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                $new_filename = 'package_' . uniqid() . '.' . $ext;
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
            // Check what columns exist in the packages table
            $columns_query = "SHOW COLUMNS FROM packages";
            $columns_result = $conn->query($columns_query);
            $column_names = [];
            
            if ($columns_result) {
                while ($column = $columns_result->fetch_assoc()) {
                    $column_names[] = $column['Field'];
                }
            }
            
            debug_to_console("Available columns in packages table: " . implode(", ", $column_names));
            
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
            
            if (in_array('description', $column_names)) {
                $fields[] = 'description';
                $values[] = '?';
                $types .= 's';
                $params[] = $description;
            }
            
            if (in_array('price', $column_names)) {
                $fields[] = 'price';
                $values[] = '?';
                $types .= 'd';
                $params[] = $price;
            }
            
            if (in_array('duration', $column_names)) {
                $fields[] = 'duration';
                $values[] = '?';
                $types .= 's';
                $params[] = $duration;
            }
            
            if (in_array('included_items', $column_names)) {
                $fields[] = 'included_items';
                $values[] = '?';
                $types .= 's';
                $params[] = $included_items;
            }
            
            if (in_array('image_path', $column_names)) {
                $fields[] = 'image_path';
                $values[] = '?';
                $types .= 's';
                $params[] = $image_path;
            }
            
            if (in_array('status', $column_names)) {
                $fields[] = 'status';
                $values[] = '?';
                $types .= 's';
                $params[] = $status;
            }
            
            // Proceed with the insert if we have fields to insert
            if (!empty($fields)) {
                $insert_query = "INSERT INTO packages (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
                debug_to_console("Insert query: " . $insert_query);
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
                    $message = "Package added successfully!";
                    // Redirect back to packages page
                    header("Location: simple_packages.php?message=" . urlencode($message));
                    exit;
                } else {
                    $error = "Error adding package: " . $conn->error . " | SQL: " . $insert_query;
                }
            } else {
                $error = "No valid fields found for insert. Check your database schema.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Package - TOUR STACK Admin</title>
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
        <h1 class="admin-page-title">Add New Package</h1>
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
                <a href="simple_rooms.php" class="sidebar-nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="simple_packages.php" class="sidebar-nav-item active">
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
                    <h2 class="section-title">Add New Package</h2>
                </div>
                
                <div class="form-container">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Package Name</label>
                            <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price (₹)</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="duration">Duration</label>
                                <input type="text" id="duration" name="duration" placeholder="e.g. 3 Days / 2 Nights" required value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="included_items">What's Included</label>
                            <textarea id="included_items" name="included_items" rows="3" placeholder="List included services, separated by commas"><?php echo isset($_POST['included_items']) ? htmlspecialchars($_POST['included_items']) : ''; ?></textarea>
                            <small>Enter items separated by commas (e.g. Airport Pickup, Breakfast, WiFi)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Package Image</label>
                            <input type="file" id="image" name="image">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <a href="simple_packages.php" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <input type="hidden" name="add_package" value="1">
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i> Add Package
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>

</html> 