<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connection.php';

echo "<h1>Booking Tables Fix Tool</h1>";

// Fix tour_bookings table
echo "<h2>Fixing Tour Bookings Table</h2>";

// Check if tour_bookings table exists
$result = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
if ($result->num_rows == 0) {
    echo "<p>Creating tour_bookings table...</p>";
    
    // Create the table with all required columns
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
        echo "<p style='color:green'>✓ tour_bookings table created successfully!</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating tour_bookings table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>tour_bookings table already exists. Checking structure...</p>";
    
    // Array of expected columns and their types
    $columns = [
        'id' => 'int(11)',
        'user_id' => 'int(11)',
        'tour_id' => 'int(11)',
        'created_at' => 'timestamp',
        'booking_date' => 'date',
        'people' => 'int(11)',
        'special_requests' => 'text',
        'total_amount' => 'decimal(10,2)',
        'payment_status' => "enum('pending','paid','failed','refunded')",
        'booking_status' => "enum('pending','confirmed','completed','cancelled')"
    ];
    
    // Check each column
    foreach ($columns as $column => $type) {
        $result = $conn->query("SHOW COLUMNS FROM tour_bookings LIKE '$column'");
        
        if ($result->num_rows == 0) {
            echo "<p>Adding missing column '$column'...</p>";
            
            // Add the missing column
            switch ($column) {
                case 'id':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
                    break;
                case 'user_id':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN user_id INT(11) NOT NULL AFTER id";
                    break;
                case 'tour_id':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN tour_id INT(11) NOT NULL AFTER user_id";
                    break;
                case 'created_at':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER tour_id";
                    break;
                case 'booking_date':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN booking_date DATE NOT NULL AFTER created_at";
                    break;
                case 'people':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN people INT(11) NOT NULL DEFAULT 1 AFTER booking_date";
                    break;
                case 'special_requests':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN special_requests TEXT NULL AFTER people";
                    break;
                case 'total_amount':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN total_amount DECIMAL(10,2) NULL AFTER special_requests";
                    break;
                case 'payment_status':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending' AFTER total_amount";
                    break;
                case 'booking_status':
                    $sql = "ALTER TABLE tour_bookings ADD COLUMN booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' AFTER payment_status";
                    break;
            }
            
            if ($conn->query($sql)) {
                echo "<p style='color:green'>✓ Added column '$column'</p>";
            } else {
                echo "<p style='color:red'>✗ Error adding column '$column': " . $conn->error . "</p>";
            }
        }
    }
    
    echo "<p style='color:green'>✓ tour_bookings table structure verified and fixed.</p>";
}

// Fix room_bookings table
echo "<h2>Fixing Room Bookings Table</h2>";

// Check if room_bookings table exists
$result = $conn->query("SHOW TABLES LIKE 'room_bookings'");
if ($result->num_rows == 0) {
    echo "<p>Creating room_bookings table...</p>";
    
    // Create the table with all required columns
    $sql = "CREATE TABLE room_bookings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        room_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        check_in DATE NOT NULL,
        check_out DATE NOT NULL,
        adults INT(11) NOT NULL DEFAULT 1,
        children INT(11) NOT NULL DEFAULT 0,
        nights INT(11) NOT NULL DEFAULT 1,
        special_requests TEXT NULL,
        total_amount DECIMAL(10,2) NULL,
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
        booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✓ room_bookings table created successfully!</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating room_bookings table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>room_bookings table already exists. Checking structure...</p>";
    
    // Array of expected columns and their types
    $columns = [
        'id' => 'int(11)',
        'user_id' => 'int(11)',
        'room_id' => 'int(11)',
        'created_at' => 'timestamp',
        'check_in' => 'date',
        'check_out' => 'date',
        'adults' => 'int(11)',
        'children' => 'int(11)',
        'nights' => 'int(11)',
        'special_requests' => 'text',
        'total_amount' => 'decimal(10,2)',
        'payment_status' => "enum('pending','paid','failed','refunded')",
        'booking_status' => "enum('pending','confirmed','completed','cancelled')"
    ];
    
    // Check each column
    foreach ($columns as $column => $type) {
        $result = $conn->query("SHOW COLUMNS FROM room_bookings LIKE '$column'");
        
        if ($result->num_rows == 0) {
            echo "<p>Adding missing column '$column'...</p>";
            
            // Add the missing column
            switch ($column) {
                case 'id':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
                    break;
                case 'user_id':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN user_id INT(11) NOT NULL AFTER id";
                    break;
                case 'room_id':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN room_id INT(11) NOT NULL AFTER user_id";
                    break;
                case 'created_at':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER room_id";
                    break;
                case 'check_in':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN check_in DATE NOT NULL AFTER created_at";
                    break;
                case 'check_out':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN check_out DATE NOT NULL AFTER check_in";
                    break;
                case 'adults':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN adults INT(11) NOT NULL DEFAULT 1 AFTER check_out";
                    break;
                case 'children':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN children INT(11) NOT NULL DEFAULT 0 AFTER adults";
                    break;
                case 'nights':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN nights INT(11) NOT NULL DEFAULT 1 AFTER children";
                    break;
                case 'special_requests':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN special_requests TEXT NULL AFTER nights";
                    break;
                case 'total_amount':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN total_amount DECIMAL(10,2) NULL AFTER special_requests";
                    break;
                case 'payment_status':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending' AFTER total_amount";
                    break;
                case 'booking_status':
                    $sql = "ALTER TABLE room_bookings ADD COLUMN booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' AFTER payment_status";
                    break;
            }
            
            if ($conn->query($sql)) {
                echo "<p style='color:green'>✓ Added column '$column'</p>";
            } else {
                echo "<p style='color:red'>✗ Error adding column '$column': " . $conn->error . "</p>";
            }
        }
    }
    
    // Check for wrong column names (common errors)
    $check_in_date = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_in_date'");
    if ($check_in_date->num_rows > 0) {
        echo "<p>Found 'check_in_date' column, renaming to 'check_in'...</p>";
        
        // First check if check_in already exists
        $check_in = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_in'");
        if ($check_in->num_rows == 0) {
            // Rename the column
            $sql = "ALTER TABLE room_bookings CHANGE COLUMN check_in_date check_in DATE NOT NULL";
            if ($conn->query($sql)) {
                echo "<p style='color:green'>✓ Renamed 'check_in_date' to 'check_in'</p>";
            } else {
                echo "<p style='color:red'>✗ Error renaming column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:orange'>! Both 'check_in_date' and 'check_in' exist. Please check your data.</p>";
        }
    }
    
    $check_out_date = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_out_date'");
    if ($check_out_date->num_rows > 0) {
        echo "<p>Found 'check_out_date' column, renaming to 'check_out'...</p>";
        
        // First check if check_out already exists
        $check_out = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'check_out'");
        if ($check_out->num_rows == 0) {
            // Rename the column
            $sql = "ALTER TABLE room_bookings CHANGE COLUMN check_out_date check_out DATE NOT NULL";
            if ($conn->query($sql)) {
                echo "<p style='color:green'>✓ Renamed 'check_out_date' to 'check_out'</p>";
            } else {
                echo "<p style='color:red'>✗ Error renaming column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:orange'>! Both 'check_out_date' and 'check_out' exist. Please check your data.</p>";
        }
    }
    
    $total_nights = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'total_nights'");
    if ($total_nights->num_rows > 0) {
        echo "<p>Found 'total_nights' column, renaming to 'nights'...</p>";
        
        // First check if nights already exists
        $nights = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'nights'");
        if ($nights->num_rows == 0) {
            // Rename the column
            $sql = "ALTER TABLE room_bookings CHANGE COLUMN total_nights nights INT(11) NOT NULL DEFAULT 1";
            if ($conn->query($sql)) {
                echo "<p style='color:green'>✓ Renamed 'total_nights' to 'nights'</p>";
            } else {
                echo "<p style='color:red'>✗ Error renaming column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:orange'>! Both 'total_nights' and 'nights' exist. Please check your data.</p>";
        }
    }
    
    echo "<p style='color:green'>✓ room_bookings table structure verified and fixed.</p>";
}

echo "<h2>Summary</h2>";
echo "<p>Database tables have been checked and fixed as needed.</p>";
echo "<p><a href='admin_bookings.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Return to Bookings</a></p>";
?> 