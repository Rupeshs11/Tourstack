<?php
// Include database connection
require_once 'db_connection.php';

// Check if location column exists
$check_column = $conn->query("SHOW COLUMNS FROM tours LIKE 'location'");
$column_exists = $check_column->num_rows > 0;

// If location column doesn't exist, add it
if (!$column_exists) {
    $query = "ALTER TABLE tours ADD COLUMN location VARCHAR(100) DEFAULT 'Local' AFTER description";
    
    if ($conn->query($query)) {
        echo "<h3>Success: Added 'location' column to tours table</h3>";
        
        // Update existing tours with sample locations
        $locations = ['City Center', 'Mountain Region', 'Beach Area', 'Historic District', 'Wildlife Reserve'];
        
        // Get all tour IDs
        $tour_query = "SELECT id FROM tours";
        $tour_result = $conn->query($tour_query);
        
        if ($tour_result->num_rows > 0) {
            echo "<p>Updating existing tours with sample locations...</p>";
            
            while ($tour = $tour_result->fetch_assoc()) {
                $location = $locations[array_rand($locations)];
                $update_query = "UPDATE tours SET location = '$location' WHERE id = " . $tour['id'];
                
                if ($conn->query($update_query)) {
                    echo "<p>Updated tour ID " . $tour['id'] . " with location: " . $location . "</p>";
                } else {
                    echo "<p>Error updating tour ID " . $tour['id'] . ": " . $conn->error . "</p>";
                }
            }
        }
    } else {
        echo "<h3>Error: Failed to add 'location' column to tours table</h3>";
        echo "<p>" . $conn->error . "</p>";
    }
} else {
    echo "<h3>The 'location' column already exists in the tours table</h3>";
}

// Display current tours with locations
echo "<h3>Current Tours with Locations</h3>";
$tours_query = "SELECT id, name, location FROM tours";
$tours_result = $conn->query($tours_query);

if ($tours_result->num_rows > 0) {
    echo "<ul>";
    while ($tour = $tours_result->fetch_assoc()) {
        echo "<li>Tour ID: " . $tour['id'] . ", Name: " . $tour['name'] . ", Location: " . $tour['location'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No tours found in the database.</p>";
}
?> 