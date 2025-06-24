<?php
// Include database connection
require_once 'db_connection.php';

// Check if the table exists
$table_exists_query = "SHOW TABLES LIKE 'package_bookings'";
$table_exists_result = mysqli_query($conn, $table_exists_query);

echo "<h1>Package Bookings Table Check</h1>";

if (mysqli_num_rows($table_exists_result) > 0) {
    echo "<p style='color:green;'>The package_bookings table exists.</p>";
    
    // Get table structure
    $table_structure_query = "DESCRIBE package_bookings";
    $table_structure_result = mysqli_query($conn, $table_structure_query);
    
    echo "<h2>Table Structure:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($field = mysqli_fetch_assoc($table_structure_result)) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "<td>" . $field['Default'] . "</td>";
        echo "<td>" . $field['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check for sample data
    $sample_data_query = "SELECT * FROM package_bookings LIMIT 5";
    $sample_data_result = mysqli_query($conn, $sample_data_query);
    
    if (mysqli_num_rows($sample_data_result) > 0) {
        echo "<h2>Sample Data (up to 5 records):</h2>";
        echo "<table border='1' cellpadding='5'>";
        
        // Table header
        echo "<tr>";
        $field_info = mysqli_fetch_fields($sample_data_result);
        foreach ($field_info as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Table data
        while ($row = mysqli_fetch_assoc($sample_data_result)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No data in the package_bookings table yet.</p>";
    }
} else {
    echo "<p style='color:red;'>The package_bookings table does not exist!</p>";
    
    // Offer to create the table
    echo "<form method='post'>";
    echo "<input type='submit' name='create_table' value='Create Table'>";
    echo "</form>";
    
    if (isset($_POST['create_table'])) {
        $create_table_sql = "CREATE TABLE IF NOT EXISTS package_bookings (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            package_id INT(11) NOT NULL,
            booking_date DATE NOT NULL,
            number_of_guests INT(11) NOT NULL,
            special_requests TEXT,
            total_amount DECIMAL(10,2) NOT NULL,
            booking_status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
            payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (mysqli_query($conn, $create_table_sql)) {
            echo "<p style='color:green;'>Table created successfully!</p>";
            echo "<p>Refresh this page to see the table structure.</p>";
        } else {
            echo "<p style='color:red;'>Error creating table: " . mysqli_error($conn) . "</p>";
        }
    }
}
?> 