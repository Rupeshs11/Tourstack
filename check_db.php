<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Set content type to plain text
header('Content-Type: text/plain');

echo "=== DATABASE STRUCTURE DIAGNOSTIC ===\n\n";

// Check if users table exists
$check_users_table = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_users_table && $check_users_table->num_rows > 0) {
    echo "✓ Users table exists\n";
    
    // Get columns from users table
    $columns_result = $conn->query("SHOW COLUMNS FROM users");
    if ($columns_result && $columns_result->num_rows > 0) {
        echo "\nColumns in users table:\n";
        echo "------------------------\n";
        while ($col = $columns_result->fetch_assoc()) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } else {
        echo "\n✗ Error retrieving columns from users table\n";
    }
    
    // Check how many records exist
    $rows_result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($rows_result) {
        $count = $rows_result->fetch_assoc();
        echo "\nNumber of user records: " . $count['count'] . "\n";
    }
} else {
    echo "✗ Users table does not exist\n";
}

echo "\n----------------------------------\n";

// Check room_bookings table
$check_room_bookings = $conn->query("SHOW TABLES LIKE 'room_bookings'");
if ($check_room_bookings && $check_room_bookings->num_rows > 0) {
    echo "\n✓ Room bookings table exists\n";
    
    // Count records
    $rows_result = $conn->query("SELECT COUNT(*) as count FROM room_bookings");
    if ($rows_result) {
        $count = $rows_result->fetch_assoc();
        echo "Number of room booking records: " . $count['count'] . "\n";
    }
} else {
    echo "\n✗ Room bookings table does not exist\n";
}

// Check tour_bookings table
$check_tour_bookings = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
if ($check_tour_bookings && $check_tour_bookings->num_rows > 0) {
    echo "\n✓ Tour bookings table exists\n";
    
    // Count records
    $rows_result = $conn->query("SELECT COUNT(*) as count FROM tour_bookings");
    if ($rows_result) {
        $count = $rows_result->fetch_assoc();
        echo "Number of tour booking records: " . $count['count'] . "\n";
    }
} else {
    echo "\n✗ Tour bookings table does not exist\n";
}

echo "\n----------------------------------\n";
echo "\nTo fix database structure, visit:\n";
echo "- General fix: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/users.php\n";
echo "- Fix and return to bookings: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/users.php?from=bookings\n";
echo "\n=== END OF DIAGNOSTIC ===\n";
?> 