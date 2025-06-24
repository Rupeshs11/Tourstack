<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Safeguard function to get array values with defaults
function safe_get($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Check if room_bookings table has the correct structure
function check_room_bookings_structure($conn) {
    // First check if the room_bookings table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'room_bookings'");
    if (!$table_check || $table_check->num_rows == 0) {
        return ""; // Table doesn't exist yet, no need to check structure
    }
    
    // Check if check_in_date and check_out_date columns exist
    $result = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_in_date'");
    $check_in_date_exists = $result && $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_out_date'");
    $check_out_date_exists = $result && $result->num_rows > 0;
    
    // If columns don't exist, create them
    if (!$check_in_date_exists || !$check_out_date_exists) {
        if (!$check_in_date_exists) {
            // First check if check_in exists, if so rename it
            $result = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_in'");
            if ($result && $result->num_rows > 0) {
                $conn->query("ALTER TABLE room_bookings CHANGE check_in check_in_date DATE NOT NULL");
            } else {
                $conn->query("ALTER TABLE room_bookings ADD check_in_date DATE NOT NULL AFTER created_at");
            }
        }
        
        if (!$check_out_date_exists) {
            // First check if check_out exists, if so rename it
            $result = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_out'");
            if ($result && $result->num_rows > 0) {
                $conn->query("ALTER TABLE room_bookings CHANGE check_out check_out_date DATE NOT NULL");
            } else {
                $conn->query("ALTER TABLE room_bookings ADD check_out_date DATE NOT NULL AFTER check_in_date");
            }
        }
        
        return "Database structure updated. Columns check_in_date and check_out_date now exist.";
    }
    
    return ""; // No changes needed
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Only check for database issues if there's an explicit request or detected error
$checkDatabase = isset($_GET['check_db']) && $_GET['check_db'] == '1';

// Function to check for database issues
function checkDatabaseStructure($conn) {
    // Check if users table exists
    $usersTableResult = $conn->query("SHOW TABLES LIKE 'users'");
    if ($usersTableResult && $usersTableResult->num_rows > 0) {
        // Check if mobile column exists in users table
        $mobileColumnResult = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
        if ($mobileColumnResult && $mobileColumnResult->num_rows == 0) {
            // Mobile column doesn't exist, redirect to fix
            return false;
        }
    } else {
        // Users table doesn't exist, redirect to fix
        return false;
    }
    return true;
}

// Only check database structure if explicitly requested
if ($checkDatabase && !checkDatabaseStructure($conn)) {
    header("Location: admin_index.php?error=database&from=bookings");
    exit;
}

// Check if tour_bookings table exists
$check_tour_table_query = "SHOW TABLES LIKE 'tour_bookings'";
$tour_table_exists = $conn->query($check_tour_table_query);
if ($tour_table_exists && $tour_table_exists->num_rows == 0) {
    // Create tour_bookings table
    $create_tour_table_query = "CREATE TABLE tour_bookings (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        tour_id INT(11) UNSIGNED NOT NULL,
        booking_date DATE NOT NULL,
        people INT(3) NOT NULL DEFAULT 1,
        total_amount DECIMAL(10,2) NOT NULL,
        booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
        special_requests TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_tour_table_query) === false) {
        $_SESSION['error_message'] = "Error creating tour_bookings table: " . $conn->error;
    }
}

// Check if room_bookings table exists
$check_room_table_query = "SHOW TABLES LIKE 'room_bookings'";
$room_table_exists = $conn->query($check_room_table_query);
if ($room_table_exists && $room_table_exists->num_rows == 0) {
    // Create room_bookings table
    $create_room_table_query = "CREATE TABLE room_bookings (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        room_id INT(11) UNSIGNED NOT NULL,
        check_in_date DATE NOT NULL,
        check_out_date DATE NOT NULL,
        adults INT(2) NOT NULL DEFAULT 1,
        children INT(2) NOT NULL DEFAULT 0,
        total_nights INT(3) NOT NULL DEFAULT 1,
        total_amount DECIMAL(10,2) NOT NULL,
        booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
        special_requests TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_room_table_query) === false) {
        $_SESSION['error_message'] = "Error creating room_bookings table: " . $conn->error;
    }
}

// Check if package_bookings table exists
$check_package_table_query = "SHOW TABLES LIKE 'package_bookings'";
$package_table_exists = $conn->query($check_package_table_query);
if ($package_table_exists && $package_table_exists->num_rows == 0) {
    // Create package_bookings table
    $create_package_table_query = "CREATE TABLE package_bookings (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        package_id INT(11) UNSIGNED NOT NULL,
        booking_date DATE NOT NULL,
        number_of_guests INT(3) NOT NULL DEFAULT 1,
        total_amount DECIMAL(10,2) NOT NULL,
        booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
        special_requests TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_package_table_query) === false) {
        $_SESSION['error_message'] = "Error creating package_bookings table: " . $conn->error;
    }
}

// Check and fix the room_bookings table structure
$structure_message = check_room_bookings_structure($conn);
if (!empty($structure_message)) {
    $_SESSION['success_message'] = $structure_message;
}

// Handle booking status update
if (isset($_POST['update_status'])) {
    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
    $booking_status = filter_var($_POST['booking_status'], FILTER_SANITIZE_STRING);
    $payment_status = filter_var($_POST['payment_status'], FILTER_SANITIZE_STRING);
    $booking_type = filter_var($_POST['booking_type'], FILTER_SANITIZE_STRING);
    
    if ($booking_type == 'tour') {
        $update_query = "UPDATE tour_bookings SET booking_status = ?, payment_status = ? WHERE id = ?";
    } else if ($booking_type == 'package') {
        $update_query = "UPDATE package_bookings SET booking_status = ?, payment_status = ? WHERE id = ?";
    } else { // room booking
        $update_query = "UPDATE room_bookings SET booking_status = ?, payment_status = ? WHERE id = ?";
    }
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $booking_status, $payment_status, $booking_id);
    $stmt->execute();
    $stmt->close();
    
    // Set success message
    $_SESSION['success_message'] = ucfirst($booking_type) . " booking #" . $booking_id . " status updated successfully";
    
    // Redirect to refresh the page
    header("Location: admin_bookings.php");
    exit;
}

// First check if mobile column exists
$mobile_column_exists = false;
$check_mobile = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
if ($check_mobile && $check_mobile->num_rows > 0) {
    $mobile_column_exists = true;
}

// Get all tour bookings - adjust query based on column existence
if ($mobile_column_exists) {
    $tour_bookings_query = "SELECT tb.*, u.name as user_name, u.email as user_email, 
                       u.mobile as user_phone,
                       t.name as tour_name, t.price as tour_price, t.image_path as tour_image
                       FROM tour_bookings tb 
                       LEFT JOIN users u ON tb.user_id = u.id 
                       LEFT JOIN tours t ON tb.tour_id = t.id 
                       ORDER BY tb.created_at DESC";
} else {
    // Check if phone column exists
    $check_phone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($check_phone && $check_phone->num_rows > 0) {
        $tour_bookings_query = "SELECT tb.*, u.name as user_name, u.email as user_email, 
                           u.phone as user_phone,
                           t.name as tour_name, t.price as tour_price, t.image_path as tour_image
                           FROM tour_bookings tb 
                           LEFT JOIN users u ON tb.user_id = u.id 
                           LEFT JOIN tours t ON tb.tour_id = t.id 
                           ORDER BY tb.created_at DESC";
    } else {
        // Neither mobile nor phone column exists
        $tour_bookings_query = "SELECT tb.*, u.name as user_name, u.email as user_email, 
                           'Not Available' as user_phone,
                  t.name as tour_name, t.price as tour_price, t.image_path as tour_image
                  FROM tour_bookings tb 
                           LEFT JOIN users u ON tb.user_id = u.id 
                           LEFT JOIN tours t ON tb.tour_id = t.id 
                  ORDER BY tb.created_at DESC";
    }
}

try {
    $tour_bookings_result = $conn->query($tour_bookings_query);
    if (!$tour_bookings_result) {
        // Set error message but don't redirect
        $error_message = "Database error: " . $conn->error;
        // Add a fix button to the error message
        $error_message .= ' <a href="users.php?from=bookings" class="btn btn-sm btn-warning">Fix Now</a>';
    }
} catch (Exception $e) {
    // Set error message but don't redirect
    $error_message = "Error: " . $e->getMessage();
    // Set a default empty result
    $tour_bookings_result = null;
}

// Get all room bookings - adjust query based on column existence
if ($mobile_column_exists) {
    $room_bookings_query = "SELECT rb.id, rb.user_id, rb.room_id, rb.created_at, 
                       rb.check_in_date, rb.check_out_date, rb.adults, rb.children, 
                       rb.total_nights, rb.special_requests, rb.total_amount, 
                       rb.payment_status, rb.booking_status,
                       u.name as user_name, u.email as user_email, 
                       u.mobile as user_phone,
                       r.name as room_name, r.price as room_price, r.image_path as room_image
                       FROM room_bookings rb 
                       LEFT JOIN users u ON rb.user_id = u.id 
                       LEFT JOIN rooms r ON rb.room_id = r.id 
                       ORDER BY rb.created_at DESC";
} else {
    // Check if phone column exists
    $check_phone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($check_phone && $check_phone->num_rows > 0) {
        $room_bookings_query = "SELECT rb.id, rb.user_id, rb.room_id, rb.created_at, 
                           rb.check_in_date, rb.check_out_date, rb.adults, rb.children, 
                           rb.total_nights, rb.special_requests, rb.total_amount, 
                           rb.payment_status, rb.booking_status,
                           u.name as user_name, u.email as user_email, 
                           u.phone as user_phone,
                           r.name as room_name, r.price as room_price, r.image_path as room_image
                           FROM room_bookings rb 
                           LEFT JOIN users u ON rb.user_id = u.id 
                           LEFT JOIN rooms r ON rb.room_id = r.id 
                           ORDER BY rb.created_at DESC";
    } else {
        // Neither mobile nor phone column exists
$room_bookings_query = "SELECT rb.id, rb.user_id, rb.room_id, rb.created_at, 
                  rb.check_in_date, rb.check_out_date, rb.adults, rb.children, 
                  rb.total_nights, rb.special_requests, rb.total_amount, 
                  rb.payment_status, rb.booking_status,
                           u.name as user_name, u.email as user_email, 
                           'Not Available' as user_phone,
                  r.name as room_name, r.price as room_price, r.image_path as room_image
                  FROM room_bookings rb 
                           LEFT JOIN users u ON rb.user_id = u.id 
                           LEFT JOIN rooms r ON rb.room_id = r.id 
                  ORDER BY rb.created_at DESC";
    }
}

try {
    $room_bookings_result = $conn->query($room_bookings_query);
    if (!$room_bookings_result) {
        // Set error message but don't redirect
        $error_message = "Database error: " . $conn->error;
        // Add a fix button to the error message
        $error_message .= ' <a href="users.php?from=bookings" class="btn btn-sm btn-warning">Fix Now</a>';
    }
} catch (Exception $e) {
    // Set error message but don't redirect
    $error_message = "Error: " . $e->getMessage();
    // Set a default empty result
    $room_bookings_result = null;
}

// Get all package bookings - adjust query based on column existence
if ($mobile_column_exists) {
    $package_bookings_query = "SELECT pb.*, u.name as user_name, u.email as user_email, 
                         u.mobile as user_phone,
                         p.name as package_name, p.price as package_price, p.image_path as package_image,
                         p.duration as package_duration
                         FROM package_bookings pb 
                         LEFT JOIN users u ON pb.user_id = u.id 
                         LEFT JOIN packages p ON pb.package_id = p.id 
                         ORDER BY pb.created_at DESC";
} else {
    // Check if phone column exists
    $check_phone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($check_phone && $check_phone->num_rows > 0) {
        $package_bookings_query = "SELECT pb.*, u.name as user_name, u.email as user_email, 
                             u.phone as user_phone,
                             p.name as package_name, p.price as package_price, p.image_path as package_image,
                             p.duration as package_duration
                             FROM package_bookings pb 
                             LEFT JOIN users u ON pb.user_id = u.id 
                             LEFT JOIN packages p ON pb.package_id = p.id 
                             ORDER BY pb.created_at DESC";
    } else {
        // Neither mobile nor phone column exists
        $package_bookings_query = "SELECT pb.*, u.name as user_name, u.email as user_email, 
                             'Not Available' as user_phone,
                             p.name as package_name, p.price as package_price, p.image_path as package_image,
                             p.duration as package_duration
                             FROM package_bookings pb 
                             LEFT JOIN users u ON pb.user_id = u.id 
                             LEFT JOIN packages p ON pb.package_id = p.id 
                             ORDER BY pb.created_at DESC";
    }
}

try {
    $package_bookings_result = $conn->query($package_bookings_query);
    if (!$package_bookings_result) {
        // Set error message but don't redirect
        $error_message = "Database error: " . $conn->error;
        // Add a fix button to the error message
        $error_message .= ' <a href="users.php?from=bookings" class="btn btn-sm btn-warning">Fix Now</a>';
    }
} catch (Exception $e) {
    // Set error message but don't redirect
    $error_message = "Error: " . $e->getMessage();
    // Set a default empty result
    $package_bookings_result = null;
}

$has_bookings = ($tour_bookings_result && $tour_bookings_result->num_rows > 0) || 
                ($room_bookings_result && $room_bookings_result->num_rows > 0) ||
                ($package_bookings_result && $package_bookings_result->num_rows > 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .section-title {
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff6600;
            color: #343a40;
            font-size: 1.5rem;
        }
        .admin-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            max-width: 500px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #ff6600;
            color: white;
        }
        .btn-primary:hover {
            background-color: #e55c00;
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Bookings Management</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <?php include_once 'admin_sidebar.php'; ?>
        
        <main class="admin-main">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message" style="background-color: #ffebee; color: #c62828; padding: 15px; margin-bottom: 20px; border-left: 5px solid #c62828; border-radius: 5px;">
                    <strong>Error:</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Diagnostic information if enabled -->
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                <div class="admin-section" style="margin-bottom: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px;">
                    <h3 style="margin-top: 0;">Database Diagnostic Information</h3>
                    <?php 
                        // Check users table
                        $dbg_users = $conn->query("SHOW TABLES LIKE 'users'");
                        $users_exists = $dbg_users && $dbg_users->num_rows > 0;
                        
                        if ($users_exists) {
                            echo "<p>✅ Users table exists</p>";
                            
                            // Check mobile column
                            $mobile_col = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
                            if ($mobile_col && $mobile_col->num_rows > 0) {
                                echo "<p>✅ Mobile column exists in users table</p>";
                            } else {
                                echo "<p>❌ Mobile column does not exist in users table</p>";
                                
                                // Check if phone column exists instead
                                $phone_col = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
                                if ($phone_col && $phone_col->num_rows > 0) {
                                    echo "<p>ℹ️ A 'phone' column exists instead of 'mobile'</p>";
                                }
                            }
                            
                            // Show column list
                            echo "<p><strong>Columns in users table:</strong></p><ul>";
                            $columns = $conn->query("SHOW COLUMNS FROM users");
                            while ($col = $columns->fetch_assoc()) {
                                echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p>❌ Users table does not exist</p>";
                        }
                    ?>
                    <p>
                        <a href="users.php?from=bookings" class="btn btn-warning">Fix Database Structure</a>
                        <a href="check_db.php" target="_blank" class="btn btn-info" style="margin-left: 10px;">Full Diagnostics</a>
                        <a href="admin_bookings.php" class="btn btn-secondary" style="margin-left: 10px;">Hide Debug Info</a>
                    </p>
                </div>
            <?php else: ?>
                <?php if (isset($error_message)): ?>
                <p>
                    <a href="admin_bookings.php?debug=1" class="btn btn-sm btn-info">Show Debug Information</a>
                </p>
                <?php endif; ?>
            <?php endif; ?>
            
            <section class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">All Bookings</h2>
                    <div class="search-container">
                        <div class="search-box">
                            <input type="text" id="bookingSearch" placeholder="Search bookings...">
                            <button><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
                
                <!-- Tour Bookings -->
                <h3 class="sub-title">Tour Bookings</h3>
                <?php if ($tour_bookings_result && $tour_bookings_result->num_rows > 0): ?>
                    <div class="table-responsive">
                <table class="admin-table" id="bookingsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                                    <th>Tour</th>
                            <th>User</th>
                            <th>Date</th>
                                    <th>People</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                                <?php while ($booking = $tour_bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo safe_get($booking, 'id', '0'); ?></td>
                                        <td><?php echo htmlspecialchars(safe_get($booking, 'tour_name', 'Unknown')); ?></td>
                                        <td><?php echo htmlspecialchars(safe_get($booking, 'user_name', 'Unknown')); ?><br><small><?php echo safe_get($booking, 'user_email', ''); ?></small></td>
                                        <td><?php 
                                            $booking_date = safe_get($booking, 'booking_date', '');
                                            echo $booking_date ? date('d M Y', strtotime($booking_date)) : '01 Jan 1970'; 
                                        ?></td>
                                        <td><?php echo safe_get($booking, 'people', '0'); ?></td>
                                        <td>₹<?php echo number_format(safe_get($booking, 'total_amount', 0), 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(safe_get($booking, 'booking_status', 'pending')); ?>">
                                                <?php echo ucfirst(safe_get($booking, 'booking_status', 'Pending')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge payment-<?php echo strtolower(safe_get($booking, 'payment_status', 'pending')); ?>">
                                                <?php echo ucfirst(safe_get($booking, 'payment_status', 'Pending')); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button class="action-btn edit-btn" onclick="viewBookingDetails(<?php echo safe_get($booking, 'id', '0'); ?>, 'tour')" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit-btn" onclick="openUpdateModal(<?php echo safe_get($booking, 'id', '0'); ?>, 'tour', '<?php echo safe_get($booking, 'booking_status', 'pending'); ?>', '<?php echo safe_get($booking, 'payment_status', 'pending'); ?>')" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No tour bookings found</p>
                <?php endif; ?>
                
                <!-- Room Bookings -->
                <h3 class="sub-title">Room Bookings</h3>
                <?php if ($room_bookings_result && $room_bookings_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Room</th>
                                    <th>User</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Guests</th>
                                    <th>Nights</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $room_bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo safe_get($booking, 'id', '0'); ?></td>
                                        <td><?php echo htmlspecialchars(safe_get($booking, 'room_name', 'Unknown')); ?></td>
                                        <td><?php echo htmlspecialchars(safe_get($booking, 'user_name', 'Unknown')); ?><br><small><?php echo safe_get($booking, 'user_email', ''); ?></small></td>
                                        <td><?php 
                                            $check_in = safe_get($booking, 'check_in_date', '');
                                            echo $check_in ? date('d M Y', strtotime($check_in)) : '01 Jan 1970'; 
                                        ?></td>
                                        <td><?php 
                                            $check_out = safe_get($booking, 'check_out_date', '');
                                            echo $check_out ? date('d M Y', strtotime($check_out)) : '01 Jan 1970'; 
                                        ?></td>
                                        <td><?php echo (int)safe_get($booking, 'adults', 1) + (int)safe_get($booking, 'children', 0); ?></td>
                                        <td><?php echo (int)safe_get($booking, 'total_nights', 1); ?></td>
                                        <td>₹<?php echo number_format(safe_get($booking, 'total_amount', 0), 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(safe_get($booking, 'booking_status', 'pending')); ?>">
                                                <?php echo ucfirst(safe_get($booking, 'booking_status', 'Pending')); ?>
                                        </span>
                                    </td>
                                    <td>
                                            <span class="status-badge payment-<?php echo strtolower(safe_get($booking, 'payment_status', 'pending')); ?>">
                                                <?php echo ucfirst(safe_get($booking, 'payment_status', 'Pending')); ?>
                                        </span>
                                    </td>
                                        <td class="actions">
                                            <button class="action-btn edit-btn" onclick="viewBookingDetails(<?php echo safe_get($booking, 'id', '0'); ?>, 'room')" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit-btn" onclick="openUpdateModal(<?php echo safe_get($booking, 'id', '0'); ?>, 'room', '<?php echo safe_get($booking, 'booking_status', 'pending'); ?>', '<?php echo safe_get($booking, 'payment_status', 'pending'); ?>')" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                    </tbody>
                </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No room bookings found</p>
                <?php endif; ?>
                
                <!-- Package Bookings -->
                <h3 class="sub-title">Package Bookings</h3>
                <?php if ($package_bookings_result && $package_bookings_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Package</th>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $package_bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo safe_get($booking, 'id', '0'); ?></td>
                                        <td><?php echo htmlspecialchars(safe_get($booking, 'package_name', 'Unknown')); ?></td>
                                        <td><?php echo htmlspecialchars(safe_get($booking, 'user_name', 'Unknown')); ?><br><small><?php echo safe_get($booking, 'user_email', ''); ?></small></td>
                                        <td><?php 
                                            $booking_date = safe_get($booking, 'booking_date', '');
                                            echo $booking_date ? date('d M Y', strtotime($booking_date)) : '01 Jan 1970'; 
                                        ?></td>
                                        <td><?php echo safe_get($booking, 'package_duration', 'N/A'); ?></td>
                                        <td>₹<?php echo number_format(safe_get($booking, 'total_amount', 0), 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(safe_get($booking, 'booking_status', 'pending')); ?>">
                                                <?php echo ucfirst(safe_get($booking, 'booking_status', 'Pending')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge payment-<?php echo strtolower(safe_get($booking, 'payment_status', 'pending')); ?>">
                                                <?php echo ucfirst(safe_get($booking, 'payment_status', 'Pending')); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button class="action-btn edit-btn" onclick="viewBookingDetails(<?php echo safe_get($booking, 'id', '0'); ?>, 'package')" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit-btn" onclick="openUpdateModal(<?php echo safe_get($booking, 'id', '0'); ?>, 'package', '<?php echo safe_get($booking, 'booking_status', 'pending'); ?>', '<?php echo safe_get($booking, 'payment_status', 'pending'); ?>')" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No package bookings found</p>
                <?php endif; ?>
                
                <?php if (!$has_bookings): ?>
                    <div class="no-data">No bookings found</div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('bookingSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    let input = this.value.toLowerCase();
                    let table = document.getElementById('bookingsTable');
                    let rows = table.getElementsByTagName('tr');
                    
                    for (let i = 1; i < rows.length; i++) {
                        let showRow = false;
                        let cells = rows[i].getElementsByTagName('td');
                        
                        for (let j = 0; j < cells.length; j++) {
                            let text = cells[j].textContent || cells[j].innerText;
                            if (text.toLowerCase().indexOf(input) > -1) {
                                showRow = true;
                                break;
                            }
                        }
                        
                        rows[i].style.display = showRow ? '' : 'none';
                    }
                });
            }
        });
    </script>
    
    <!-- Status Update Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('updateStatusModal').style.display='none'">&times;</span>
            <h2 id="update-modal-title">Update Booking Status</h2>
            
            <form method="post" action="">
                <input type="hidden" id="booking_id" name="booking_id">
                <input type="hidden" id="booking_type" name="booking_type">
                
                <div class="form-group">
                    <label for="booking_status">Booking Status:</label>
                    <select id="booking_status" name="booking_status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="payment_status">Payment Status:</label>
                    <select id="payment_status" name="payment_status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn" onclick="document.getElementById('updateStatusModal').style.display='none'">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Booking Details Modal -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content" style="width: 80%; max-width: 800px;">
            <span class="close-details">&times;</span>
            <h2 id="bookingDetailTitle">Booking Details</h2>
            <div id="bookingDetailsContent">
                <div class="loader" style="margin: 20px auto; text-align: center;">Loading...</div>
            </div>
        </div>
    </div>
    
    <script>
        // Function to open the update status modal
        function openUpdateModal(id, type, currentStatus, currentPayment) {
            document.getElementById('booking_id').value = id;
            document.getElementById('booking_type').value = type;
            document.getElementById('booking_status').value = currentStatus;
            document.getElementById('payment_status').value = currentPayment;
            
            // Update modal title
            let typeDisplayName = type.charAt(0).toUpperCase() + type.slice(1);
            document.getElementById('update-modal-title').innerText = `Update ${typeDisplayName} Booking #${id}`;
            
            // Show the modal
            document.getElementById('updateStatusModal').style.display = 'block';
        }
        
        // Function to view booking details
        function viewBookingDetails(id, type) {
            // Redirect to the booking details page with the ID and type
            window.location.href = `admin_booking_details.php?id=${id}&type=${type}`;
        }
        
        // Close the modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('updateStatusModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
    </script>
</body>

</html> 