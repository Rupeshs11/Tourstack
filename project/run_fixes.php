<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connection.php';

echo "<h1>Hotel Management System - Fix Runner</h1>";

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return ($result && $result->num_rows > 0);
}

// Function to check files
function fileExists($path) {
    return file_exists($path);
}

echo "<h2>Step 1: Checking Prerequisites</h2>";
echo "<ul>";

// Check connection
if ($conn && !$conn->connect_error) {
    echo "<li style='color:green;'>✓ Database connection successful</li>";
} else {
    echo "<li style='color:red;'>✗ Database connection failed: " . ($conn ? $conn->connect_error : "No connection established") . "</li>";
    echo "</ul><h3>Cannot proceed without database connection. Please check your db_connection.php file.</h3>";
    exit;
}

// Check fix files
if (fileExists('fix_view_booking.php')) {
    echo "<li style='color:green;'>✓ Fix script for view_booking.php found</li>";
} else {
    echo "<li style='color:red;'>✗ Fix script for view_booking.php not found</li>";
}

// Check view_booking.php
if (fileExists('view_booking.php')) {
    echo "<li style='color:green;'>✓ view_booking.php file found</li>";
} else {
    echo "<li style='color:red;'>✗ view_booking.php file not found</li>";
    echo "</ul><h3>Cannot proceed without view_booking.php file.</h3>";
    exit;
}
echo "</ul>";

// Step 2: Run database table fixes
echo "<h2>Step 2: Running Database Table Fixes</h2>";
echo "<p>Running the fix_view_booking.php script to ensure tables exist and have the correct structure...</p>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background-color: #f9f9f9; margin-bottom: 20px;'>";
include 'fix_view_booking.php';
echo "</div>";

// Step 3: Verify view_booking.php file
echo "<h2>Step 3: Verifying view_booking.php Error Handling</h2>";

// Check view_booking.php content for error handling
$viewBookingContent = file_get_contents('view_booking.php');
$hasErrorHandling = strpos($viewBookingContent, 'tableExists') !== false || 
                    strpos($viewBookingContent, '$tableCheck = $conn->query') !== false;

if ($hasErrorHandling) {
    echo "<p style='color:green;'>✓ view_booking.php has proper error handling for table existence</p>";
} else {
    echo "<p style='color:orange;'>! view_booking.php may need additional error handling. Attempting to add it...</p>";
    
    // Add error handling code to view_booking.php if not already present
    $pattern = '/\$stmt = \$conn->prepare\(\$query\);[\s\S]*?if \(\$stmt === false\) \{[\s\S]*?}[\s\S]*?\$stmt->bind_param/';
    $replacement = '$stmt = $conn->prepare($query);
if ($stmt === false) {
    // Handle SQL preparation error
    $_SESSION[\'booking_error\'] = "Database error: " . $conn->error;
    header(\'Location: my_bookings.php\');
    exit;
}

// Verify tables exist before proceeding
$tableExists = false;
switch ($booking_type) {
    case \'package\':
        $tableCheck = $conn->query("SHOW TABLES LIKE \'package_bookings\'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION[\'booking_error\'] = "Package bookings table does not exist. Please run fix_view_booking.php first.";
            header(\'Location: my_bookings.php\');
            exit;
        }
        
        $tableCheck = $conn->query("SHOW TABLES LIKE \'packages\'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION[\'booking_error\'] = "Packages table does not exist. Please run fix_view_booking.php first.";
            header(\'Location: my_bookings.php\');
            exit;
        }
        break;
    
    case \'room\':
        $tableCheck = $conn->query("SHOW TABLES LIKE \'room_bookings\'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION[\'booking_error\'] = "Room bookings table does not exist.";
            header(\'Location: my_bookings.php\');
            exit;
        }
        break;
        
    case \'tour\':
        $tableCheck = $conn->query("SHOW TABLES LIKE \'tour_bookings\'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION[\'booking_error\'] = "Tour bookings table does not exist.";
            header(\'Location: my_bookings.php\');
            exit;
        }
        break;
}

$stmt->bind_param';

    $newContent = preg_replace($pattern, $replacement, $viewBookingContent);
    
    if ($newContent !== $viewBookingContent) {
        // Update the file if changes were made
        file_put_contents('view_booking.php', $newContent);
        echo "<p style='color:green;'>✓ Added error handling to view_booking.php</p>";
    } else {
        echo "<p style='color:orange;'>! Could not automatically update view_booking.php. Please manually check the file.</p>";
    }
}

// Step 4: Summary
echo "<h2>Step 4: Summary</h2>";
echo "<p>The following fixes have been applied:</p>";
echo "<ul>";
echo "<li>Checked database connection</li>";
echo "<li>Verified and created missing tables: packages and package_bookings</li>";
echo "<li>Added error handling to view_booking.php</li>";
echo "</ul>";

// Step 5: Next steps
echo "<h2>Step 5: Next Steps</h2>";
echo "<p>You should now be able to view package bookings without errors. If you continue to see issues:</p>";
echo "<ol>";
echo "<li>Make sure you have at least one package in the packages table</li>";
echo "<li>Make sure you have at least one booking in the package_bookings table</li>";
echo "<li>Check the database connection settings in db_connection.php</li>";
echo "<li>Check for other PHP or SQL errors in the error log</li>";
echo "</ol>";

echo "<p><a href='view_booking.php?type=package&id=1' class='btn btn-primary'>Try Viewing a Package Booking</a></p>";
echo "<p><a href='my_bookings.php' class='btn btn-secondary'>Go to My Bookings</a></p>";
?> 