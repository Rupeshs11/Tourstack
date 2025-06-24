<?php
// Include database connection
require_once 'db_connection.php';

// Create tours table if it doesn't exist
$tours_table = "CREATE TABLE IF NOT EXISTS tours (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(255),
    location VARCHAR(255),
    max_people INT(11),
    image_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active'
)";

if ($conn->query($tours_table) === FALSE) {
    die("Error creating tours table: " . $conn->error);
}

// Clear existing tours
$conn->query("TRUNCATE TABLE tours");

// Insert the exact tours from the screenshot
$tours = [
    [
        'name' => 'City Heritage Tour',
        'description' => 'Explore the rich cultural heritage of our city with this guided tour. Visit historical monuments, museums, and sample local cuisine along the way.',
        'price' => 1500.00,
        'duration' => '5 Hours',
        'location' => 'Multiple locations',
        'max_people' => 15,
        'image_path' => 'images/tours/city-heritage.jpg',
        'status' => 'active'
    ],
    [
        'name' => 'Adventure Mountain Trek',
        'description' => 'For the adventure seekers, this trekking experience offers breathtaking views and an adrenaline rush. Suitable for moderately fit participants.',
        'price' => 2500.00,
        'duration' => '1 Day',
        'location' => 'Multiple locations',
        'max_people' => 12,
        'image_path' => 'images/tours/mountain-trek.jpg',
        'status' => 'active'
    ],
    [
        'name' => 'Nature and Wildlife Tour',
        'description' => 'Discover the natural beauty and wildlife in the surrounding nature reserves. Perfect for nature lovers and those seeking a peaceful retreat from city life.',
        'price' => 2800.00,
        'duration' => '8 Hours',
        'location' => 'Multiple locations',
        'max_people' => 10,
        'image_path' => 'images/tours/wildlife-tour.jpg',
        'status' => 'active'
    ]
];

// Insert the tours
$inserted = 0;
foreach ($tours as $tour) {
    $stmt = $conn->prepare("INSERT INTO tours (name, description, price, duration, location, max_people, image_path, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssiss", 
        $tour['name'], 
        $tour['description'], 
        $tour['price'], 
        $tour['duration'], 
        $tour['location'], 
        $tour['max_people'],
        $tour['image_path'],
        $tour['status']
    );
    
    if ($stmt->execute()) {
        $inserted++;
    } else {
        echo "Error inserting tour {$tour['name']}: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

echo "<h1>Tours Setup Complete</h1>";
echo "<p>{$inserted} tours have been created to match the checkpoint.</p>";
echo "<p><a href='tours.php'>View Tours Page</a></p>";
?> 