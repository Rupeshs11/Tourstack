<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the database connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Show the tables in the database
echo "<h3>Checking database tables</h3>";
$tables_result = $conn->query("SHOW TABLES");
echo "<p>Tables in database: ";
$tableList = [];
while ($table = $tables_result->fetch_row()) {
    $tableList[] = $table[0];
}
echo implode(", ", $tableList) . "</p>";

// First, check if rooms table exists and examine its structure
$rooms_result = $conn->query("SHOW TABLES LIKE 'rooms'");
if ($rooms_result->num_rows > 0) {
    // Show the structure of the rooms table
    echo "<h3>Rooms table structure:</h3>";
    $room_columns = $conn->query("DESCRIBE rooms");
    echo "<ul>";
    while ($column = $room_columns->fetch_assoc()) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
}

// Check if the room_types table already exists
$table_exists = $conn->query("SHOW TABLES LIKE 'room_types'")->num_rows > 0;
echo "<p>room_types table exists: " . ($table_exists ? "Yes" : "No") . "</p>";

// If table does not exist, create it and populate with default values
if (!$table_exists) {
    echo "<h3>Creating room_types table</h3>";
    // Create room_types table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `room_types` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `max_occupancy` INT(11) NOT NULL DEFAULT 2,
        `amenities` TEXT,
        `image_path` VARCHAR(255),
        `price_multiplier` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
        `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($create_table_sql)) {
        echo "<p>room_types table created successfully</p>";
    } else {
        echo "<p>Error creating room_types table: " . $conn->error . "</p>";
    }
    
    // Add default room types
    $default_types = [
        [
            'name' => 'Standard Room',
            'description' => 'Our standard rooms offer comfort and essential amenities for a pleasant stay. These rooms feature comfortable beds, a private bathroom, and basic facilities.',
            'max_occupancy' => 2,
            'amenities' => 'Free Wi-Fi, Air Conditioning, TV, Private Bathroom, Daily Housekeeping',
            'image_path' => 'images/rooms/standard-room.jpg',
            'price_multiplier' => 1.00
        ],
        [
            'name' => 'Deluxe Room',
            'description' => 'Experience enhanced comfort in our deluxe rooms. These spacious accommodations feature premium amenities and elegant furnishings to ensure a memorable stay.',
            'max_occupancy' => 3,
            'amenities' => 'Free Wi-Fi, Air Conditioning, TV, Mini Fridge, Tea/Coffee Maker, Private Bathroom, Daily Housekeeping, Work Desk',
            'image_path' => 'images/rooms/deluxe-room.jpg',
            'price_multiplier' => 1.50
        ],
        [
            'name' => 'Executive Suite',
            'description' => 'Our executive suites offer premium luxury with separate living and sleeping areas. These spacious accommodations are ideal for longer stays or for those seeking extra comfort.',
            'max_occupancy' => 4,
            'amenities' => 'Free Wi-Fi, Air Conditioning, Smart TV, Mini Fridge, Tea/Coffee Maker, Private Bathroom, Daily Housekeeping, Work Desk, Lounge Area, Bathtub',
            'image_path' => 'images/rooms/executive-suite.jpg',
            'price_multiplier' => 2.00
        ],
        [
            'name' => 'Family Room',
            'description' => 'Perfect for families, our family rooms provide ample space for everyone. These rooms feature multiple beds and extra amenities to ensure a comfortable stay for the whole family.',
            'max_occupancy' => 5,
            'amenities' => 'Free Wi-Fi, Air Conditioning, TV, Mini Fridge, Tea/Coffee Maker, Private Bathroom, Daily Housekeeping, Extra Beds',
            'image_path' => 'images/rooms/family-room.jpg',
            'price_multiplier' => 1.75
        ],
        [
            'name' => 'Premium Suite',
            'description' => 'Experience unmatched luxury in our premium suites. These exquisitely designed accommodations feature the finest amenities and services for an unforgettable stay.',
            'max_occupancy' => 2,
            'amenities' => 'Free Wi-Fi, Air Conditioning, Smart TV, Mini Bar, Espresso Machine, Luxury Bathroom, Daily Housekeeping, Work Desk, Lounge Area, Jacuzzi, Bathrobes, Premium Toiletries',
            'image_path' => 'images/rooms/premium-suite.jpg',
            'price_multiplier' => 2.50
        ],
    ];
    
    echo "<h3>Adding default room types</h3>";
    // Insert default room types
    $insert_sql = "INSERT INTO `room_types` (name, description, max_occupancy, amenities, image_path, price_multiplier) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    
    foreach ($default_types as $type) {
        $stmt->bind_param("ssissd", 
            $type['name'], 
            $type['description'], 
            $type['max_occupancy'], 
            $type['amenities'], 
            $type['image_path'], 
            $type['price_multiplier']
        );
        if ($stmt->execute()) {
            echo "<p>Added room type: {$type['name']}</p>";
        } else {
            echo "<p>Error adding room type {$type['name']}: " . $stmt->error . "</p>";
        }
    }
    
    $stmt->close();
    
    // Check if we should update the rooms table
    echo "<h3>Checking rooms table for update</h3>";
    $result = $conn->query("DESCRIBE rooms");
    $fields = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row['Field'];
        }
    }
    
    if (!in_array('room_type_id', $fields)) {
        echo "<p>The room_type_id column doesn't exist in the rooms table. We'll leave the rooms table as is and rely on the type field for joining.</p>";
    } else {
        echo "<p>The room_type_id column already exists. Updating entries...</p>";
        // Update existing rooms with appropriate room type IDs based on the 'type' column
        $conn->query("UPDATE `rooms` SET `room_type_id` = (SELECT `id` FROM `room_types` WHERE `name` = 'Standard Room') WHERE `type` = 'Standard' OR `type` IS NULL");
        $conn->query("UPDATE `rooms` SET `room_type_id` = (SELECT `id` FROM `room_types` WHERE `name` = 'Deluxe Room') WHERE `type` = 'Deluxe'");
        $conn->query("UPDATE `rooms` SET `room_type_id` = (SELECT `id` FROM `room_types` WHERE `name` = 'Executive Suite') WHERE `type` = 'Suite'");
    }
    
    // Check if room_images table should be created
    if (!in_array('room_images', $tableList)) {
        echo "<h3>Creating room_images table</h3>";
        $room_images_sql = "CREATE TABLE IF NOT EXISTS `room_images` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `room_id` INT(11) NOT NULL,
            `image_path` VARCHAR(255) NOT NULL,
            `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `room_id` (`room_id`),
            CONSTRAINT `fk_room_images_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($room_images_sql)) {
            echo "<p>room_images table created successfully</p>";
        } else {
            echo "<p>Error creating room_images table: " . $conn->error . "</p>";
        }
    }
    
    $_SESSION['setup_success'] = "Room types system has been set up successfully!";
}

echo "<h3>Room types setup complete</h3>";
echo "<p><a href='rooms.php'>Go to Rooms Page</a></p>";
?> 