<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to book a package',
        'redirect' => 'login.php'
    ]);
    exit;
}

// Include database connection
require_once 'db_connection.php';

// Initialize variables
$response = [
    'status' => '',
    'message' => '',
    'redirect' => ''
];

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
    $booking_date = isset($_POST['booking_date']) ? $_POST['booking_date'] : '';
    $adults = isset($_POST['adults']) ? (int)$_POST['adults'] : 1;
    $children = isset($_POST['children']) ? (int)$_POST['children'] : 0;
    $special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';
    
    // Validate input
    $errors = [];
    
    if (!$user_id) {
        $errors[] = "You must be logged in to book a package";
    }
    
    if (!$package_id) {
        $errors[] = "Invalid package selection";
    }
    
    if (!$booking_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
        $errors[] = "Invalid booking date format";
    }
    
    if ($adults < 1 || $adults > 10) {
        $errors[] = "Number of adults must be between 1 and 10";
    }
    
    if ($children < 0 || $children > 10) {
        $errors[] = "Number of children must be between 0 and 10";
    }
    
    // Check if booking date is in the future
    if (empty($errors) && $booking_date) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $booking_date_obj = new DateTime($booking_date);
        
        if ($booking_date_obj < $today) {
            $errors[] = "Booking date must be in the future";
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Get package details for price calculation
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $package_details = $result->fetch_assoc();
            
            // Calculate total number of people and price
            $total_guests = $adults + $children;
            $total_amount = $total_guests * $package_details['price'];
            
            // Check if package_bookings table exists, create if not
            $result = $conn->query("SHOW TABLES LIKE 'package_bookings'");
            if ($result->num_rows == 0) {
                $sql = "CREATE TABLE package_bookings (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    user_id INT(11) NOT NULL,
                    package_id INT(11) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    booking_date DATE NOT NULL,
                    number_of_guests INT(11) NOT NULL DEFAULT 1,
                    special_requests TEXT NULL,
                    total_amount DECIMAL(10,2) NULL,
                    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                    booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                    PRIMARY KEY (id)
                )";
                $conn->query($sql);
            }
            
            try {
                // Insert booking
                $stmt = $conn->prepare("INSERT INTO package_bookings (user_id, package_id, booking_date, number_of_guests, 
                                  special_requests, total_amount, booking_status, payment_status) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')");
                
                $stmt->bind_param("iisids", $user_id, $package_id, $booking_date, 
                            $total_guests, $special_requests, $total_amount);
                
                // Execute the statement
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = "Your package booking has been confirmed!";
                    $response['redirect'] = "my_bookings.php";
                } else {
                    $response['status'] = 'error';
                    $response['message'] = "Database error: " . $stmt->error;
                }
            } catch (Exception $e) {
                $response['status'] = 'error';
                $response['message'] = "Database error: " . $e->getMessage();
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = "Package not found";
        }
        $stmt->close();
    } else {
        // Handle validation errors
        $response['status'] = 'error';
        $response['message'] = "Please fix the following errors:<br> - " . implode("<br> - ", $errors);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // If this is a GET request with id, just redirect to packages page
    // This preserves the connector function for non-AJAX calls
    header("Location: packages.php");
    exit;
} else {
    $response['status'] = 'error';
    $response['message'] = "Invalid request method";
}

// Check if this is an AJAX request
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Return JSON response for AJAX requests, or redirect for regular requests
if ($is_ajax) {
    // Ensure we have a clean output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set proper headers
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    // Send JSON response
    echo json_encode($response);
} else {
    // For non-AJAX requests, redirect based on status
    if ($response['status'] === 'success') {
        header('Location: ' . $response['redirect']);
    } else {
        // Redirect back to packages page with error
        header('Location: packages.php?error=' . urlencode($response['message']));
    }
}
exit;
?> 