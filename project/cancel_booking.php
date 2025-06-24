<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'db_connection.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Get booking type and ID from URL
$booking_type = isset($_GET['type']) ? $_GET['type'] : '';
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate booking type and ID
if (empty($booking_type) || !in_array($booking_type, ['tour', 'room', 'package']) || $booking_id <= 0) {
    $_SESSION['booking_error'] = "Invalid booking information.";
    header('Location: my_bookings.php');
    exit;
}

// Prepare query based on booking type
switch ($booking_type) {
    case 'tour':
        $table = 'tour_bookings';
        break;
    case 'room':
        $table = 'room_bookings';
        break;
    case 'package':
        $table = 'package_bookings';
        break;
}

// Check if booking exists and belongs to the user
$check_query = "SELECT * FROM $table WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Booking not found or does not belong to user
    $_SESSION['booking_error'] = "Booking not found or you don't have permission to cancel it.";
    header('Location: my_bookings.php');
    exit;
}

$booking = $result->fetch_assoc();

// Check if booking is already cancelled or confirmed
if ($booking['booking_status'] !== 'pending') {
    $_SESSION['booking_error'] = "Only pending bookings can be cancelled.";
    header('Location: my_bookings.php');
    exit;
}

// Cancel the booking
$update_query = "UPDATE $table SET booking_status = 'cancelled' WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $booking_id, $user_id);

if ($stmt->execute()) {
    // Booking cancelled successfully
    $_SESSION['booking_success'] = "Your " . $booking_type . " booking has been cancelled successfully.";
} else {
    // Error cancelling booking
    $_SESSION['booking_error'] = "Error cancelling booking: " . $conn->error;
}

// Redirect back to bookings page
header('Location: my_bookings.php');
exit;
?> 