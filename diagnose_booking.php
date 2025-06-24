<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

echo "<h1>Tour Booking Diagnostic Tool</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div style='color:red'>ERROR: No user is logged in. Please <a href='login.php'>login</a> first.</div>";
} else {
    echo "<div style='color:green'>User is logged in with ID: " . $_SESSION['user_id'] . "</div>";
}

// Check the table structure
echo "<h2>Database Tables Check</h2>";

// Check if tour_bookings table exists
$result = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
if ($result->num_rows == 0) {
    echo "<div style='color:red'>ERROR: 'tour_bookings' table doesn't exist!</div>";
} else {
    echo "<div style='color:green'>Table 'tour_bookings' exists</div>";
    
    // Get table structure
    echo "<h3>tour_bookings Table Structure:</h3>";
    $structure = $conn->query("DESCRIBE tour_bookings");
    echo "<ul>";
    while ($col = $structure->fetch_assoc()) {
        echo "<li>{$col['Field']} - {$col['Type']} - " . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "</li>";
    }
    echo "</ul>";
    
    // Get existing bookings
    echo "<h3>Recent Bookings (Last 5):</h3>";
    $bookings = $conn->query("SELECT * FROM tour_bookings ORDER BY id DESC LIMIT 5");
    if ($bookings->num_rows > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Tour ID</th><th>Date Created</th><th>Booking Date</th><th>People</th><th>Total Amount</th><th>Status</th></tr>";
        
        while ($booking = $bookings->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$booking['id']}</td>";
            echo "<td>{$booking['user_id']}</td>";
            echo "<td>{$booking['tour_id']}</td>";
            echo "<td>" . (isset($booking['created_at']) ? $booking['created_at'] : 'N/A') . "</td>";
            echo "<td>{$booking['booking_date']}</td>";
            echo "<td>{$booking['people']}</td>";
            echo "<td>₹" . (isset($booking['total_amount']) ? number_format($booking['total_amount'], 2) : 'N/A') . "</td>";
            echo "<td>{$booking['booking_status']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div>No bookings found.</div>";
    }
}

// Check if tours table exists and has data
echo "<h2>Tours Table Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'tours'");
if ($result->num_rows == 0) {
    echo "<div style='color:red'>ERROR: 'tours' table doesn't exist!</div>";
} else {
    echo "<div style='color:green'>Table 'tours' exists</div>";
    
    // Get tours count
    $toursCount = $conn->query("SELECT COUNT(*) as total FROM tours")->fetch_assoc();
    echo "<div>Total tours: {$toursCount['total']}</div>";
    
    // Show sample tour
    if ($toursCount['total'] > 0) {
        $sampleTour = $conn->query("SELECT * FROM tours LIMIT 1")->fetch_assoc();
        echo "<h3>Sample Tour:</h3>";
        echo "<ul>";
        foreach ($sampleTour as $key => $value) {
            echo "<li>{$key}: {$value}</li>";
        }
        echo "</ul>";
    }
}

// Form submission test
echo "<h2>Form Submission Test</h2>";
echo "<p>This will simulate submitting a booking form with test data.</p>";

if (isset($_POST['test_booking'])) {
    // User clicked the test button
    try {
        $user_id = $_SESSION['user_id'];
        $tour_id = 1; // Assuming first tour
        $booking_date = date('Y-m-d', strtotime('+7 days'));
        $people = 2;
        $special_requests = "Test booking from diagnostic tool";
        $total_amount = 2000.00; // Sample amount
        
        // Prepare SQL for test insertion
        $sql = "INSERT INTO tour_bookings (user_id, tour_id, booking_date, people, special_requests, total_amount, payment_status, booking_status) 
                VALUES ($user_id, $tour_id, '$booking_date', $people, '$special_requests', $total_amount, 'pending', 'pending')";
        
        echo "<div>Test SQL: " . htmlspecialchars($sql) . "</div>";
        
        // Execute the SQL
        if ($conn->query($sql)) {
            echo "<div style='color:green'>TEST SUCCESS: Booking was successfully added to the database.</div>";
            echo "<div>New booking ID: {$conn->insert_id}</div>";
        } else {
            echo "<div style='color:red'>TEST ERROR: " . $conn->error . "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color:red'>TEST EXCEPTION: " . $e->getMessage() . "</div>";
    }
}

// Show test form
echo "<form method='post' action=''>";
echo "<input type='hidden' name='test_booking' value='1'>";
echo "<button type='submit' style='padding: 10px; background-color: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer;'>Run Test Booking</button>";
echo "</form>";

// Suggest fixes
echo "<h2>Possible Solutions</h2>";
echo "<ol>";
echo "<li>Check the 'created_at' column in the tour_bookings table. If it's not a TIMESTAMP with DEFAULT CURRENT_TIMESTAMP, it may cause an issue.</li>";
echo "<li>Verify that the form field names match what the PHP script expects (booking_date, people, special_requests).</li>";
echo "<li>Make sure the tour_id is being correctly passed to the form and included in the submission.</li>";
echo "<li>Check if there are any foreign key constraints that might be preventing insertion.</li>";
echo "<li>Verify that the user_id in the SESSION is valid and exists in the users table.</li>";
echo "</ol>";

echo "<h2>Fix Recommendations</h2>";
echo "<a href='fix_tables.php' style='padding: 10px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px; display: inline-block;'>Run Database Fix Script</a>";
echo " ";
echo "<a href='tours.php' style='padding: 10px; background-color: #2ecc71; color: white; text-decoration: none; border-radius: 4px; display: inline-block;'>Back to Tours</a>";
?> 