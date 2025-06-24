<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connection.php';

echo "<h1>Fixing Database Tables for View Booking</h1>";

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

// Step 1: Check if packages table exists
$packagesExists = tableExists($conn, 'packages');
echo "<h2>1. Checking Packages Table</h2>";

if (!$packagesExists) {
    echo "<p>Packages table does not exist. Creating it...</p>";
    
    $createPackagesTable = "CREATE TABLE packages (
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
    
    if ($conn->query($createPackagesTable)) {
        echo "<p style='color:green;'>✓ Packages table created successfully.</p>";
    } else {
        echo "<p style='color:red;'>✗ Error creating packages table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Packages table exists. Checking structure...</p>";
    
    // Check for required columns
    $requiredColumns = ['id', 'name', 'description', 'price', 'duration', 'included_items', 'image_path'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!columnExists($conn, 'packages', $column)) {
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "<p style='color:orange;'>! Missing columns in packages table: " . implode(', ', $missingColumns) . "</p>";
        
        // Add missing columns
        foreach ($missingColumns as $column) {
            $alterQuery = "";
            switch ($column) {
                case 'description':
                    $alterQuery = "ALTER TABLE packages ADD COLUMN description TEXT AFTER name";
                    break;
                case 'price':
                    $alterQuery = "ALTER TABLE packages ADD COLUMN price DECIMAL(10,2) NOT NULL AFTER description";
                    break;
                case 'duration':
                    $alterQuery = "ALTER TABLE packages ADD COLUMN duration VARCHAR(50) NOT NULL AFTER price";
                    break;
                case 'included_items':
                    $alterQuery = "ALTER TABLE packages ADD COLUMN included_items TEXT AFTER duration";
                    break;
                case 'image_path':
                    $alterQuery = "ALTER TABLE packages ADD COLUMN image_path VARCHAR(255) AFTER included_items";
                    break;
            }
            
            if (!empty($alterQuery)) {
                if ($conn->query($alterQuery)) {
                    echo "<p style='color:green;'>✓ Added column '$column' to packages table.</p>";
                } else {
                    echo "<p style='color:red;'>✗ Error adding column '$column': " . $conn->error . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color:green;'>✓ Packages table structure looks good.</p>";
    }
}

// Step 2: Check if package_bookings table exists
$packageBookingsExists = tableExists($conn, 'package_bookings');
echo "<h2>2. Checking Package Bookings Table</h2>";

if (!$packageBookingsExists) {
    echo "<p>Package bookings table does not exist. Creating it...</p>";
    
    $createPackageBookingsTable = "CREATE TABLE package_bookings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        package_id INT(11) NOT NULL,
        booking_date DATE NOT NULL,
        number_of_guests INT(11) NOT NULL DEFAULT 1,
        special_requests TEXT,
        total_amount DECIMAL(10,2) NOT NULL,
        booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($createPackageBookingsTable)) {
        echo "<p style='color:green;'>✓ Package bookings table created successfully.</p>";
    } else {
        echo "<p style='color:red;'>✗ Error creating package bookings table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Package bookings table exists. Checking structure...</p>";
    
    // Check for required columns
    $requiredColumns = ['id', 'user_id', 'package_id', 'booking_date', 'number_of_guests', 'total_amount', 'booking_status', 'payment_status'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!columnExists($conn, 'package_bookings', $column)) {
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "<p style='color:orange;'>! Missing columns in package_bookings table: " . implode(', ', $missingColumns) . "</p>";
        
        // Add missing columns
        foreach ($missingColumns as $column) {
            $alterQuery = "";
            switch ($column) {
                case 'user_id':
                    $alterQuery = "ALTER TABLE package_bookings ADD COLUMN user_id INT(11) NOT NULL AFTER id";
                    break;
                case 'package_id':
                    $alterQuery = "ALTER TABLE package_bookings ADD COLUMN package_id INT(11) NOT NULL AFTER user_id";
                    break;
                case 'booking_date':
                    $alterQuery = "ALTER TABLE package_bookings ADD COLUMN booking_date DATE NOT NULL AFTER package_id";
                    break;
                case 'number_of_guests':
                    $alterQuery = "ALTER TABLE package_bookings ADD COLUMN number_of_guests INT(11) NOT NULL DEFAULT 1 AFTER booking_date";
                    break;
                case 'total_amount':
                    $alterQuery = "ALTER TABLE package_bookings ADD COLUMN total_amount DECIMAL(10,2) NOT NULL AFTER number_of_guests";
                    break;
                case 'booking_status':
                    $alterQuery = "ALTER TABLE package_bookings ADD COLUMN booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' AFTER total_amount";
                    break;
                case 'payment_status':
                    $alterQuery = "ALTER TABLE package_bookings ADD COLUMN payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending' AFTER booking_status";
                    break;
            }
            
            if (!empty($alterQuery)) {
                if ($conn->query($alterQuery)) {
                    echo "<p style='color:green;'>✓ Added column '$column' to package_bookings table.</p>";
                } else {
                    echo "<p style='color:red;'>✗ Error adding column '$column': " . $conn->error . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color:green;'>✓ Package bookings table structure looks good.</p>";
    }
}

// Step 3: Add error handling to view_booking.php
echo "<h2>3. Checking View Booking Error Handling</h2>";
echo "<p>This script has added the necessary tables and columns. The view_booking.php file has better error handling already, but you may still see issues if the data relationships are incorrect.</p>";

// Step 4: Test the query
echo "<h2>4. Testing Queries</h2>";

echo "<h3>Testing packages query:</h3>";
$testPackagesQuery = "SELECT * FROM packages LIMIT 1";
$packagesResult = $conn->query($testPackagesQuery);

if ($packagesResult) {
    if ($packagesResult->num_rows > 0) {
        echo "<p style='color:green;'>✓ Successfully queried packages table and found data.</p>";
    } else {
        echo "<p style='color:orange;'>! Successfully queried packages table but found no data. You may need to add some packages.</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Error querying packages table: " . $conn->error . "</p>";
}

echo "<h3>Testing package_bookings query:</h3>";
$testBookingsQuery = "SELECT * FROM package_bookings LIMIT 1";
$bookingsResult = $conn->query($testBookingsQuery);

if ($bookingsResult) {
    if ($bookingsResult->num_rows > 0) {
        echo "<p style='color:green;'>✓ Successfully queried package_bookings table and found data.</p>";
    } else {
        echo "<p style='color:orange;'>! Successfully queried package_bookings table but found no data. This might be normal if no bookings exist yet.</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Error querying package_bookings table: " . $conn->error . "</p>";
}

echo "<h3>Testing join query:</h3>";
$testJoinQuery = "SELECT pb.*, p.name as package_name, p.price as package_price, p.image_path as image_path, 
                 p.description, p.duration, p.included_items
                 FROM package_bookings pb 
                 LEFT JOIN packages p ON pb.package_id = p.id 
                 LIMIT 1";
$joinResult = $conn->query($testJoinQuery);

if ($joinResult) {
    echo "<p style='color:green;'>✓ Join query is syntactically correct.</p>";
    if ($joinResult->num_rows > 0) {
        echo "<p style='color:green;'>✓ Join query returned data.</p>";
    } else {
        echo "<p style='color:orange;'>! Join query is valid but returned no data. This might be normal if no bookings exist yet.</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Error with join query: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h2>Summary:</h2>";
echo "<p>The database tables needed for viewing package bookings have been checked and fixed if necessary.</p>";
echo "<p>You should now be able to go back to <a href='view_booking.php'>view_booking.php</a> without the error.</p>";
echo "<p>If you continue to see errors, make sure there is at least one package in the packages table and one valid booking in the package_bookings table.</p>";
?> 