<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connection.php';

echo "<h1>Tour Booking System - Database Fix</h1>";

// Check if the tour_bookings table exists
$result = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
if ($result->num_rows == 0) {
    echo "<p>Creating tour_bookings table...</p>";
    
    // Create the table
    $sql = "CREATE TABLE tour_bookings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        tour_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        booking_date DATE NOT NULL,
        people INT(11) NOT NULL DEFAULT 1,
        special_requests TEXT NULL,
        total_amount DECIMAL(10,2) NULL,
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
        booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>Success! tour_bookings table created.</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>tour_bookings table already exists.</p>";
    
    // Check and fix tour_bookings column structure
    $columns_to_check = [
        'created_at' => "ALTER TABLE tour_bookings MODIFY created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];
    
    foreach ($columns_to_check as $column => $fix_sql) {
        $result = $conn->query("SHOW COLUMNS FROM tour_bookings LIKE '$column'");
        if ($result->num_rows == 0) {
            echo "<p>Adding missing column '$column'...</p>";
            if ($column == 'created_at') {
                $conn->query("ALTER TABLE tour_bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            }
        } else {
            // Check the column type
            $col_info = $result->fetch_assoc();
            if ($column == 'created_at' && $col_info['Type'] != 'timestamp') {
                echo "<p>Fixing '$column' column type...</p>";
                $conn->query($fix_sql);
            }
        }
    }
}

// Check for tours table
$result = $conn->query("SHOW TABLES LIKE 'tours'");
if ($result->num_rows == 0) {
    echo "<p>Creating tours table...</p>";
    
    // Create tours table with basic structure
    $sql = "CREATE TABLE tours (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        location VARCHAR(255),
        price DECIMAL(10,2) NOT NULL,
        duration VARCHAR(100),
        max_people INT(11) DEFAULT 10,
        image_path VARCHAR(255),
        tag VARCHAR(50),
        includes TEXT,
        itinerary TEXT,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>Success! tours table created.</p>";
        
        // Add sample tour if none exist
        $sql = "INSERT INTO tours (name, description, location, price, duration, max_people, image_path, tag, status) 
                VALUES ('City Heritage Tour', 'Explore the rich cultural heritage of our city with this guided tour. Visit historical monuments, museums, and sample local cuisine along the way.', 
                'Multiple locations', 1500.00, '5 Hours', 15, 'images/tours/city-tour.jpg', 'Popular', 'active')";
        
        if ($conn->query($sql)) {
            echo "<p>Added sample tour.</p>";
        }
    } else {
        echo "<p>Error creating tours table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>tours table already exists.</p>";
}

// Ensure users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "<p>Creating users table...</p>";
    
    // Create users table with basic structure
    $sql = "CREATE TABLE users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        role ENUM('user','admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>Success! users table created.</p>";
        
        // Add admin user
        $password_hash = password_hash("admin123", PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role) 
                VALUES ('Admin User', 'admin@example.com', '$password_hash', 'admin')";
        
        if ($conn->query($sql)) {
            echo "<p>Added admin user (admin@example.com / admin123).</p>";
        }
    } else {
        echo "<p>Error creating users table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>users table already exists.</p>";
}

echo "<p><strong>Database check and fix completed!</strong></p>";
echo "<p><a href='tours.php'>Return to Tours</a></p>";
?> 