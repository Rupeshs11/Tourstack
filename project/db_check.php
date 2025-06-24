<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Structure Check</h2>";

// Get all tables
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);

if ($tables_result) {
    echo "<h3>Tables in Database:</h3>";
    echo "<ul>";
    while ($table = $tables_result->fetch_array()) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error getting tables: " . $conn->error;
}

// Check rooms table structure
$rooms_structure = "DESCRIBE rooms";
$rooms_result = $conn->query($rooms_structure);

if ($rooms_result) {
    echo "<h3>Structure of 'rooms' table:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $rooms_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing rooms table: " . $conn->error;
}

// Check if room_types table exists
$check_room_types = "SHOW TABLES LIKE 'room_types'";
$room_types_exists = $conn->query($check_room_types);

if ($room_types_exists && $room_types_exists->num_rows > 0) {
    echo "<h3>Structure of 'room_types' table:</h3>";
    $room_types_structure = "DESCRIBE room_types";
    $room_types_result = $conn->query($room_types_structure);
    
    if ($room_types_result) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $room_types_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error describing room_types table: " . $conn->error;
    }
    
    // Show room_types data
    echo "<h3>Data in 'room_types' table:</h3>";
    $room_types_data = "SELECT * FROM room_types";
    $room_types_data_result = $conn->query($room_types_data);
    
    if ($room_types_data_result) {
        if ($room_types_data_result->num_rows > 0) {
            echo "<table border='1'>";
            
            // Table header
            $first_row = $room_types_data_result->fetch_assoc();
            $room_types_data_result->data_seek(0);
            
            echo "<tr>";
            foreach (array_keys($first_row) as $key) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr>";
            
            // Table data
            while ($row = $room_types_data_result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . $value . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No data in room_types table.";
        }
    } else {
        echo "Error getting room_types data: " . $conn->error;
    }
} else {
    echo "<p>room_types table does not exist.</p>";
}

// Show a sample of rooms data
echo "<h3>Sample Data from 'rooms' table:</h3>";
$rooms_data = "SELECT * FROM rooms LIMIT 5";
$rooms_data_result = $conn->query($rooms_data);

if ($rooms_data_result) {
    if ($rooms_data_result->num_rows > 0) {
        echo "<table border='1'>";
        
        // Table header
        $first_row = $rooms_data_result->fetch_assoc();
        $rooms_data_result->data_seek(0);
        
        echo "<tr>";
        foreach (array_keys($first_row) as $key) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        // Table data
        while ($row = $rooms_data_result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No data in rooms table.";
    }
} else {
    echo "Error getting rooms data: " . $conn->error;
}

$conn->close();
?> 