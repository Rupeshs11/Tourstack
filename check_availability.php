<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $check_in = filter_var($_POST['check_in'], FILTER_SANITIZE_STRING);
    $check_out = filter_var($_POST['check_out'], FILTER_SANITIZE_STRING);
    $adults = filter_var($_POST['adults'], FILTER_SANITIZE_NUMBER_INT);
    $children = filter_var($_POST['children'], FILTER_SANITIZE_NUMBER_INT);
    
    // Validate dates
    $today = date('Y-m-d');
    if (strtotime($check_in) < strtotime($today)) {
        $_SESSION['availability_error'] = "Check-in date cannot be in the past.";
        header("Location: index.php");
        exit;
    }
    
    if (strtotime($check_out) <= strtotime($check_in)) {
        $_SESSION['availability_error'] = "Check-out date must be after check-in date.";
        header("Location: index.php");
        exit;
    }
    
    // Calculate total capacity needed
    $total_guests = intval($adults) + intval($children);
    
    // Store search parameters in session
    $_SESSION['search_params'] = [
        'check_in' => $check_in,
        'check_out' => $check_out,
        'adults' => $adults,
        'children' => $children,
        'total_guests' => $total_guests
    ];
    
    // Query to find available rooms
    $available_rooms_query = "
        SELECT r.* FROM rooms r
        WHERE r.status = 'active'
        AND r.capacity >= ?
        AND r.id NOT IN (
            SELECT rb.room_id FROM room_bookings rb
            WHERE (rb.check_in_date <= ? AND rb.check_out_date >= ?)
            OR (rb.check_in_date <= ? AND rb.check_out_date >= ?)
            OR (rb.check_in_date >= ? AND rb.check_out_date <= ?)
            AND rb.booking_status != 'cancelled'
        )
    ";
    
    $stmt = $conn->prepare($available_rooms_query);
    $stmt->bind_param("issssss", $total_guests, $check_out, $check_in, $check_in, $check_out, $check_in, $check_out);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Store available rooms in session
    $available_rooms = [];
    while ($room = $result->fetch_assoc()) {
        $available_rooms[] = $room;
    }
    
    $_SESSION['available_rooms'] = $available_rooms;
    
    // Redirect to available rooms page
    header("Location: available_rooms.php");
    exit;
} else {
    // If not a POST request, redirect to home page
    header("Location: index.php");
    exit;
}
?> 