<?php
// Start session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'includes/db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to make a payment.']);
    exit;
}

// Validate input data
if (!isset($_POST['booking_type']) || !isset($_POST['booking_id']) || !isset($_POST['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required payment information.']);
    exit;
}

// Sanitize inputs
$booking_type = filter_var($_POST['booking_type'], FILTER_SANITIZE_STRING);
$booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
$amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$user_id = $_SESSION['user_id'];
$payment_method = isset($_POST['payment_method']) ? filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING) : 'credit_card';

// Get payment details
$card_number = isset($_POST['card_number']) ? 'XXXX' . substr(filter_var($_POST['card_number'], FILTER_SANITIZE_NUMBER_INT), -4) : null;
$payment_details = [
    'method' => $payment_method,
    'cardDetails' => $card_number,
    'transactionId' => 'TXN' . bin2hex(random_bytes(6))
];
$payment_details_json = json_encode($payment_details);

// Start transaction
$conn->begin_transaction();

try {
    // 1. Update booking payment status
    switch ($booking_type) {
        case 'room':
            $table = 'room_bookings';
            break;
        case 'tour':
            $table = 'tour_bookings';
            break;
        case 'package':
            $table = 'package_bookings';
            break;
        default:
            throw new Exception("Invalid booking type.");
    }
    
    // Only update payment_status to 'paid', leave booking_status as 'pending'
    $update_query = "UPDATE $table SET payment_status = 'paid' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $booking_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating booking: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No booking was updated. Please verify the booking details.");
    }
    
    // 2. Insert into payments table
    $payment_date = date('Y-m-d H:i:s');
    $insert_query = "INSERT INTO payments (user_id, booking_id, booking_type, amount, payment_date, payment_method, payment_details, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')";
    $stmt = $conn->prepare($insert_query);
    
    if (!$stmt) {
        throw new Exception("Error preparing payment statement: " . $conn->error);
    }
    
    $stmt->bind_param("iisdsss", $user_id, $booking_id, $booking_type, $amount, $payment_date, $payment_method, $payment_details_json);
    
    if (!$stmt->execute()) {
        throw new Exception("Error recording payment: " . $stmt->error);
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Payment successful! Your booking is paid but pending admin confirmation.',
        'transaction_id' => $payment_details['transactionId'],
        'redirect' => 'my_bookings.php'
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction if any error occurs
    $conn->rollback();
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Payment failed: ' . $e->getMessage()
    ]);
}

// Close the connection
$conn->close();
?> 
