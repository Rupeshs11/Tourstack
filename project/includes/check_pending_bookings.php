<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Include database connection
require_once '../db_connection.php';

$user_id = $_SESSION['user_id'];
$pending_bookings = [];

// Check for pending room bookings
$room_query = "SELECT rb.id, rb.booking_date, r.name, 'room' as type 
              FROM room_bookings rb 
              JOIN rooms r ON rb.room_id = r.id 
              WHERE rb.user_id = ? AND rb.booking_status = 'pending'";
              
$stmt = $conn->prepare($room_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_bookings[] = $row;
}
$stmt->close();

// Check for pending tour bookings
$tour_query = "SELECT tb.id, tb.booking_date, t.name, 'tour' as type 
              FROM tour_bookings tb 
              JOIN tours t ON tb.tour_id = t.id 
              WHERE tb.user_id = ? AND tb.booking_status = 'pending'";
              
$stmt = $conn->prepare($tour_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_bookings[] = $row;
}
$stmt->close();

// Check for pending package bookings
$package_query = "SELECT pb.id, pb.booking_date, p.name, 'package' as type 
                FROM package_bookings pb 
                JOIN packages p ON pb.package_id = p.id 
                WHERE pb.user_id = ? AND pb.booking_status = 'pending'";
                
$stmt = $conn->prepare($package_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_bookings[] = $row;
}
$stmt->close();

// Return the results as JSON
echo json_encode(['pending_bookings' => $pending_bookings]);
?> 