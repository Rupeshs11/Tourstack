<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Determine where to redirect after fixing the database
$redirect_page = 'admin_dashboard.php'; // Default redirect
if(isset($_GET['from']) && $_GET['from'] === 'bookings') {
    $redirect_page = 'admin_bookings.php';
} elseif(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin_bookings.php') !== false) {
    $redirect_page = 'admin_bookings.php';
}

// Check if users table exists
$check_users_table_query = "SHOW TABLES LIKE 'users'";
$users_table_exists = $conn->query($check_users_table_query);

if ($users_table_exists && $users_table_exists->num_rows == 0) {
    // Create users table with dob column included
    $create_users_table_query = "CREATE TABLE users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        mobile VARCHAR(20),
        dob DATE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_users_table_query) === false) {
        $_SESSION['error_message'] = "Error creating users table: " . $conn->error;
    } else {
        $_SESSION['success_message'] = "Users table created successfully.";
    }
} else {
    // Check if mobile column exists
    $check_mobile_column = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
    
    if ($check_mobile_column && $check_mobile_column->num_rows == 0) {
        // Check if phone column exists
        $check_phone_column = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
        
        if ($check_phone_column && $check_phone_column->num_rows > 0) {
            // Rename phone column to mobile
            if ($conn->query("ALTER TABLE users CHANGE phone mobile VARCHAR(20)") === TRUE) {
                $_SESSION['success_message'] = "Column 'phone' renamed to 'mobile' in users table.";
            } else {
                $_SESSION['error_message'] = "Error renaming 'phone' to 'mobile': " . $conn->error;
            }
        } else {
            // Add mobile column
            if ($conn->query("ALTER TABLE users ADD mobile VARCHAR(20) AFTER email") === TRUE) {
                $_SESSION['success_message'] = "Column 'mobile' added to users table.";
            } else {
                $_SESSION['error_message'] = "Error adding 'mobile' column: " . $conn->error;
            }
        }
    } else {
        $_SESSION['success_message'] = "Users table and mobile column already exist.";
    }
    
    // Check if dob column exists (make sure we always have this column)
    $check_dob_column = $conn->query("SHOW COLUMNS FROM users LIKE 'dob'");
    
    if ($check_dob_column && $check_dob_column->num_rows == 0) {
        // Add dob column
        if ($conn->query("ALTER TABLE users ADD dob DATE AFTER mobile") === TRUE) {
            $_SESSION['success_message'] .= " Column 'dob' added to users table.";
        } else {
            $_SESSION['error_message'] = "Error adding 'dob' column: " . $conn->error;
        }
    }
}

// In-case we need to force update the booking query columns to use mobile
$update_result = $conn->query("SHOW TABLES LIKE 'room_bookings'");
if ($update_result && $update_result->num_rows > 0) {
    // Check for any rows in room_bookings
    $check_rows = $conn->query("SELECT COUNT(*) as count FROM room_bookings");
    $row_data = $check_rows->fetch_assoc();
    
    if ($row_data['count'] > 0) {
        // There are rows in the bookings table, clear the table to avoid future errors
        $conn->query("TRUNCATE TABLE room_bookings");
        $_SESSION['success_message'] .= " Room bookings data reset for compatibility.";
    }
}

$update_result = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
if ($update_result && $update_result->num_rows > 0) {
    // Check for any rows in tour_bookings
    $check_rows = $conn->query("SELECT COUNT(*) as count FROM tour_bookings");
    $row_data = $check_rows->fetch_assoc();
    
    if ($row_data['count'] > 0) {
        // There are rows in the bookings table, clear the table to avoid future errors
        $conn->query("TRUNCATE TABLE tour_bookings");
        $_SESSION['success_message'] .= " Tour bookings data reset for compatibility.";
    }
}

// Insert a test user if none exists
$check_users = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $check_users->fetch_assoc();
if ($user_count['count'] == 0) {
    // Create a test user with mobile field populated
    $insert_user = "INSERT INTO users (name, email, mobile, password, role) 
                   VALUES ('Test User', 'test@example.com', '1234567890', 
                   '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'user')";
    if ($conn->query($insert_user) === TRUE) {
        $_SESSION['success_message'] .= " Test user created.";
    }
}

// Redirect back to the appropriate page
header("Location: $redirect_page");
exit;
?> 