<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connection.php';

// Check for the tour_bookings table
$result = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
if ($result->num_rows > 0) {
    echo "<p>tour_bookings table exists.</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $columns = $conn->query("DESCRIBE tour_bookings");
    echo "<ul>";
    while ($col = $columns->fetch_assoc()) {
        echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>tour_bookings table does not exist.</p>";
    
    // Create the table
    echo "<p>Attempting to create tour_bookings table...</p>";
    
    $sql = "CREATE TABLE tour_bookings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        tour_id INT(11) NOT NULL,
        created_at DATETIME NOT NULL,
        booking_date DATE NOT NULL,
        people INT(11) NOT NULL DEFAULT 1,
        special_requests TEXT NULL,
        total_amount DECIMAL(10,2) NULL,
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
        booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>tour_bookings table created successfully!</p>";
    } else {
        echo "<p>Error creating tour_bookings table: " . $conn->error . "</p>";
    }
}

// Check if the tours table exists
$result = $conn->query("SHOW TABLES LIKE 'tours'");
if ($result->num_rows > 0) {
    echo "<p>tours table exists.</p>";
    
    // Check if there are tours
    $result = $conn->query("SELECT COUNT(*) as count FROM tours");
    $row = $result->fetch_assoc();
    echo "<p>Number of tours in database: " . $row['count'] . "</p>";
    
    // Show a sample tour
    $result = $conn->query("SELECT * FROM tours LIMIT 1");
    if ($result->num_rows > 0) {
        $tour = $result->fetch_assoc();
        echo "<h3>Sample Tour:</h3>";
        echo "<ul>";
        foreach ($tour as $key => $value) {
            echo "<li>" . $key . ": " . $value . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>tours table does not exist.</p>";
}
?> 