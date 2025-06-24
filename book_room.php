<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to book a room'
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
    // Enable detailed error reporting for debugging
    error_log("Book Room API called with POST data: " . json_encode($_POST));
    
    $user_id = $_SESSION['user_id'];
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $check_in = isset($_POST['check_in']) ? $_POST['check_in'] : '';
    $check_out = isset($_POST['check_out']) ? $_POST['check_out'] : '';
    $adults = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
    $special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';
    $proceed_with_booking = isset($_POST['proceed_with_booking']) ? $_POST['proceed_with_booking'] === 'true' : false;
    
    // Validate input
    $errors = [];
    
    if (!$user_id) {
        $errors[] = "You must be logged in to book a room";
    }
    
    if (!$room_id) {
        $errors[] = "Invalid room selection";
    }
    
    if (!$check_in || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in)) {
        $errors[] = "Invalid check-in date format";
    }
    
    if (!$check_out || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_out)) {
        $errors[] = "Invalid check-out date format";
    }
    
    if ($adults < 1 || $adults > 10) {
        $errors[] = "Number of adults must be between 1 and 10";
    }
    
    if ($children < 0 || $children > 10) {
        $errors[] = "Number of children must be between 0 and 10";
    }
    
    if (strlen($special_requests) > 500) {
        $errors[] = "Special requests must be less than 500 characters";
    }
    
    // Check that check-out is after check-in
    if (empty($errors)) {
        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);
        
        if ($date1 >= $date2) {
            $errors[] = "Check-out date must be after check-in date";
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Get room details for price calculation
        try {
            // Make sure the rooms table exists
            $check_rooms_table = $conn->query("SHOW TABLES LIKE 'rooms'");
            if (!$check_rooms_table || $check_rooms_table->num_rows == 0) {
                throw new Exception("The rooms table does not exist. Please set up your database first.");
            }
            
            $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement for room query: " . $conn->error);
            }
            
            $stmt->bind_param("i", $room_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute room query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $room_details = $result->fetch_assoc();
                
                // Check room availability for selected dates using a clearer overlap formula
                // A booking overlaps if: New check-in is before existing check-out AND new check-out is after existing check-in
                $query = "SELECT COUNT(*) as count FROM room_bookings 
                         WHERE room_id = ? 
                         AND booking_status != 'cancelled'
                         AND check_in_date < ? /* Existing booking starts before new check-out */
                         AND check_out_date > ? /* Existing booking ends after new check-in */";
                
                error_log("Availability check query: " . $query);
                
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    error_log("Error preparing availability query: " . $conn->error);
                    throw new Exception("Error preparing availability query: " . $conn->error);
                }
                
                // Format dates properly to avoid any timezone or format issues
                $formatted_check_in = date('Y-m-d', strtotime($check_in));
                $formatted_check_out = date('Y-m-d', strtotime($check_out));
                
                error_log("Checking availability for room $room_id from $formatted_check_in to $formatted_check_out");
                
                // Bind the room ID and the formatted dates
                $stmt->bind_param("iss", $room_id, $formatted_check_out, $formatted_check_in);
                
                if (!$stmt->execute()) {
                    error_log("Error executing availability query: " . $stmt->error);
                    throw new Exception("Error executing availability query: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                $overlap_data = $result->fetch_assoc();
                $overlapping_bookings = $overlap_data['count'];
                
                error_log("Room availability check: Found $overlapping_bookings overlapping bookings for room $room_id from $formatted_check_in to $formatted_check_out");
                
                if ($overlapping_bookings > 0) {
                    // If there are overlapping bookings, fetch the details for debugging
                    $debug_query = "SELECT id, check_in_date, check_out_date, booking_status FROM room_bookings 
                                   WHERE room_id = ? 
                                   AND booking_status != 'cancelled'
                                   AND check_in_date < ?
                                   AND check_out_date > ?";
                    $debug_stmt = $conn->prepare($debug_query);
                    $debug_stmt->bind_param("iss", $room_id, $formatted_check_out, $formatted_check_in);
                    $debug_stmt->execute();
                    $debug_result = $debug_stmt->get_result();
                    
                    while ($overlap = $debug_result->fetch_assoc()) {
                        error_log("Overlapping booking: ID=" . $overlap['id'] . 
                                  ", check_in=" . $overlap['check_in_date'] . 
                                  ", check_out=" . $overlap['check_out_date'] . 
                                  ", status=" . $overlap['booking_status']);
                    }
                    
                    $response['status'] = 'error';
                    $response['message'] = "Room is not available for the selected dates. Please choose different dates.";
                } else {
                    // Calculate total nights and price
                    $date1 = new DateTime($check_in);
                    $date2 = new DateTime($check_out);
                    $interval = $date1->diff($date2);
                    $total_nights = $interval->days;
                    $total_amount = $total_nights * $room_details['price'];
                    
                    try {
                        // Sanitize special requests to avoid SQL issues
                        $special_requests = $conn->real_escape_string($special_requests);
                    
                        // First check if room_bookings table exists and create it if it doesn't
                        $table_check = $conn->query("SHOW TABLES LIKE 'room_bookings'");
                        if (!$table_check || $table_check->num_rows == 0) {
                            // Create the room_bookings table
                            $create_table_query = "CREATE TABLE room_bookings (
                                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                user_id INT(11) UNSIGNED NOT NULL,
                                room_id INT(11) UNSIGNED NOT NULL,
                                booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                                check_in_date DATE NOT NULL,
                                check_out_date DATE NOT NULL,
                                adults INT(2) NOT NULL DEFAULT 1,
                                children INT(2) NOT NULL DEFAULT 0,
                                total_nights INT(3) NOT NULL DEFAULT 1,
                                total_amount DECIMAL(10,2) NOT NULL,
                                booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                                payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
                                special_requests TEXT,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            )";
                            
                            if ($conn->query($create_table_query) === false) {
                                throw new Exception("Error creating room_bookings table: " . $conn->error);
                            }
                        }

                        // Check if booking_date column exists in the table
                        $column_check = $conn->query("SHOW COLUMNS FROM room_bookings LIKE 'booking_date'");
                        $has_booking_date = $column_check && $column_check->num_rows > 0;
                        
                        // Prepare the correct INSERT statement based on table structure
                        if ($has_booking_date) {
                            $stmt = $conn->prepare("INSERT INTO room_bookings (user_id, room_id, booking_date, check_in_date, check_out_date, 
                                                  adults, children, special_requests, total_nights, total_amount, payment_status, booking_status) 
                                                  VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
                                                  
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }
                            
                            error_log("Binding parameters for query with booking_date");
                            
                            // Fix parameter types: i=integer, s=string, d=double
                            $bind_result = $stmt->bind_param("iissiisid", $user_id, $room_id, $check_in, $check_out, 
                                            $adults, $children, $special_requests, $total_nights, $total_amount);
                        } else {
                            // If booking_date column doesn't exist, exclude it from the INSERT
                            $stmt = $conn->prepare("INSERT INTO room_bookings (user_id, room_id, check_in_date, check_out_date, 
                                                  adults, children, special_requests, total_nights, total_amount, payment_status, booking_status) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
                                                  
                            if (!$stmt) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }
                            
                            error_log("Binding parameters for query without booking_date");
                            
                            // Fix parameter types: i=integer, s=string, d=double
                            $bind_result = $stmt->bind_param("iissiisid", $user_id, $room_id, $check_in, $check_out, 
                                            $adults, $children, $special_requests, $total_nights, $total_amount);
                        }
                        
                        if (!$bind_result) {
                            throw new Exception("Binding parameters failed: " . $stmt->error);
                        }
                        
                        // Execute the statement
                        if ($stmt->execute()) {
                            $response['status'] = 'success';
                            $response['message'] = "Your room booking has been confirmed!";
                            $response['redirect'] = "my_bookings.php";
                            $response['booking_id'] = $conn->insert_id;
                            $response['booking_type'] = 'room';
                        } else {
                            throw new Exception("Execute failed: " . $stmt->error);
                        }
                    } catch (Exception $e) {
                        $response['status'] = 'error';
                        $response['message'] = "Database error: " . $e->getMessage();
                    }
                }
            } else {
                $response['status'] = 'error';
                $response['message'] = "Room not found";
            }
            $stmt->close();
        } catch (Exception $e) {
            $response['status'] = 'error';
            $response['message'] = "Database error: " . $e->getMessage();
        }
    } else {
        // Handle validation errors
        $response['status'] = 'error';
        $response['message'] = "Please fix the following errors:<br> - " . implode("<br> - ", $errors);
    }
} else {
    $response['status'] = 'error';
    $response['message'] = "Invalid request method";
}

// Return JSON response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Ajax request - return JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Regular form submission - redirect with message
    if ($response['status'] === 'error') {
        $_SESSION['booking_error'] = $response['message'];
        // Redirect to rooms page since room_details.php doesn't seem to exist
        header('Location: rooms.php');
    } else if ($response['status'] === 'success') {
        $_SESSION['booking_success'] = $response['message'];
        header('Location: ' . $response['redirect']);
    } else {
        // Default fallback
        header('Location: rooms.php');
    }
}
exit; 