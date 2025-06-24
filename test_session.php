<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Display current session status
echo "<h2>Session Status</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Display database connection status
echo "<h2>Database Connection Status</h2>";
if ($conn && !$conn->connect_error) {
    echo "Database connection is working properly.";
} else {
    echo "Database connection error: " . $conn->connect_error;
}

// Check header display logic
echo "<h2>Header Logic Test</h2>";
if (isset($_SESSION['user_id'])) {
    // Get user data
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        echo "User found in database. User details:<br>";
        echo "Name: " . htmlspecialchars($user['name']) . "<br>";
        echo "Email: " . htmlspecialchars($user['email']) . "<br>";
        // Header would show user profile dropdown
    } else {
        echo "User ID exists in session but user not found in database.<br>";
        echo "This would trigger 'logout' and show login buttons.";
        // Header would show login/register buttons
    }
} else {
    echo "No user ID in session.<br>";
    echo "This would show login/register buttons.";
    // Header would show login/register buttons
}

// Add a link to clear session for testing
echo "<hr><p><a href='test_session.php?clear=1'>Clear Session</a> | <a href='index.php'>Back to Home</a></p>";

// Clear session if requested
if (isset($_GET['clear'])) {
    session_unset();
    session_destroy();
    echo "<p>Session cleared. <a href='test_session.php'>Refresh</a> to see changes.</p>";
}
?> 