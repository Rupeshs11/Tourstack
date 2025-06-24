<?php
// Include database connection
require_once 'db_connection.php';

// Create packages table if it doesn't exist
$packages_table = "CREATE TABLE IF NOT EXISTS packages (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT(11) NOT NULL,
    location VARCHAR(255) NOT NULL,
    max_guests INT(11) NOT NULL DEFAULT 4,
    image_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active'
)";

if ($conn->query($packages_table) === FALSE) {
    die("Error creating packages table: " . $conn->error);
}

// Clear existing packages
$conn->query("TRUNCATE TABLE packages");

// Insert sample packages
$packages = [
    [
        'name' => 'Beach Paradise Getaway',
        'description' => 'Experience the ultimate beach vacation with our all-inclusive package. Enjoy pristine beaches, luxurious accommodations, and exciting water activities.',
        'price' => 1200.00,
        'duration' => 5,
        'location' => 'Maldives',
        'max_guests' => 4,
        'image_path' => 'images/packages/beach-paradise.jpg',
        'status' => 'active'
    ],
    [
        'name' => 'Mountain Adventure',
        'description' => 'Embark on an exhilarating mountain adventure with guided hiking tours, scenic views, and cozy mountain lodge accommodations.',
        'price' => 950.00,
        'duration' => 4,
        'location' => 'Switzerland Alps',
        'max_guests' => 6,
        'image_path' => 'images/packages/mountain-adventure.jpg',
        'status' => 'active'
    ],
    [
        'name' => 'Cultural City Tour',
        'description' => 'Immerse yourself in rich cultural experiences with our city tour package. Visit museums, historical landmarks, and enjoy local cuisine.',
        'price' => 750.00,
        'duration' => 3,
        'location' => 'Rome, Italy',
        'max_guests' => 8,
        'image_path' => 'images/packages/city-tour.jpg',
        'status' => 'active'
    ]
];

// Insert the packages
$inserted = 0;
foreach ($packages as $package) {
    $stmt = $conn->prepare("INSERT INTO packages (name, description, price, duration, location, max_guests, image_path, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssis", 
        $package['name'], 
        $package['description'], 
        $package['price'], 
        $package['duration'], 
        $package['location'], 
        $package['max_guests'],
        $package['image_path'],
        $package['status']
    );
    
    if ($stmt->execute()) {
        $inserted++;
    } else {
        echo "Error inserting package {$package['name']}: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

echo "<h1>Packages Setup Complete</h1>";
echo "<p>{$inserted} packages have been created successfully.</p>";
echo "<p><a href='packages.php'>View Packages Page</a></p>";
?> 