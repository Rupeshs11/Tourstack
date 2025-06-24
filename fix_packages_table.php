<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connection.php';

echo "<h1>Fixing Packages Table</h1>";

// Step 1: Check if the table exists
$table_check = $conn->query("SHOW TABLES LIKE 'packages'");
$table_exists = $table_check->num_rows > 0;

if ($table_exists) {
    echo "<p>Packages table exists. Showing current structure:</p>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE packages");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if the table has data
    $data_check = $conn->query("SELECT COUNT(*) as count FROM packages");
    $data_count = $data_check->fetch_assoc()['count'];
    
    echo "<p>The table contains {$data_count} records.</p>";
    
    // Step 2: Backup existing data if any
    $packages = [];
    if ($data_count > 0) {
        echo "<p>Backing up existing data...</p>";
        $result = $conn->query("SELECT * FROM packages");
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
        echo "<p>Backed up {$data_count} packages.</p>";
    }
    
    // Step 3: Drop the existing table
    echo "<p>Dropping existing packages table...</p>";
    if ($conn->query("DROP TABLE packages")) {
        echo "<p>Table dropped successfully.</p>";
    } else {
        echo "<p>Error dropping table: " . $conn->error . "</p>";
        exit;
    }
} else {
    echo "<p>Packages table does not exist. Will create a new one.</p>";
}

// Step 4: Create a new table with correct schema
echo "<p>Creating packages table with correct schema...</p>";
$create_table_query = "CREATE TABLE packages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    included_items TEXT,
    image_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";

if ($conn->query($create_table_query)) {
    echo "<p>Table created successfully.</p>";
} else {
    echo "<p>Error creating table: " . $conn->error . "</p>";
    exit;
}

// Step 5: Restore data if we had any
if (!empty($packages)) {
    echo "<p>Restoring package data...</p>";
    $success_count = 0;
    $error_count = 0;
    
    foreach ($packages as $package) {
        // Handle included_items vs includes column
        $included_items = isset($package['included_items']) ? $package['included_items'] : (isset($package['includes']) ? $package['includes'] : '');
        
        $insert_query = "INSERT INTO packages (id, name, description, price, duration, included_items, image_path, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        if ($stmt) {
            $stmt->bind_param(
                "issdssssss", 
                $package['id'],
                $package['name'],
                $package['description'],
                $package['price'],
                $package['duration'],
                $included_items,
                $package['image_path'],
                $package['status'],
                $package['created_at'],
                $package['updated_at']
            );
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
                echo "<p>Error restoring package #{$package['id']}: " . $stmt->error . "</p>";
            }
        } else {
            $error_count++;
            echo "<p>Error preparing statement: " . $conn->error . "</p>";
        }
    }
    
    echo "<p>Data restoration complete. Success: {$success_count}, Errors: {$error_count}</p>";
}

// Step 6: Show the new table structure
echo "<p>New table structure:</p>";
$structure = $conn->query("DESCRIBE packages");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $structure->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<p>Table fix complete! <a href='admin_packages.php'>Go back to Package Management</a></p>";
?> 