<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to cancel a booking.";
    header('Location: login.php');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid booking ID.";
    header('Location: my_bookings.php');
    exit;
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify the booking belongs to the user
$sql = "SELECT * FROM package_bookings WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Booking not found or you don't have permission to cancel it.";
    header('Location: my_bookings.php');
    exit;
}

$booking = $result->fetch_assoc();

// Check if booking is already cancelled
if ($booking['booking_status'] == 'cancelled') {
    $_SESSION['error'] = "This booking has already been cancelled.";
    header('Location: my_bookings.php');
    exit;
}

// Cancel the booking
$sql = "UPDATE package_bookings SET booking_status = 'cancelled' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    $_SESSION['booking_success'] = "Your package booking has been successfully cancelled.";
} else {
    $_SESSION['error'] = "Failed to cancel booking. Please try again.";
}

header('Location: my_bookings.php');
exit;
?> 