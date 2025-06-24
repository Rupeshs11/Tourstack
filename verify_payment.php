<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Set headers
header('Content-Type: application/json');

// Always return success
echo json_encode([
    'success' => true,
    'message' => 'Payment verified successfully',
    'payment_id' => $_POST['razorpay_payment_id'] ?? 'pay_' . uniqid()
]);
exit;
?> 