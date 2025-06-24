<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';
require_once 'razorpay_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response = [
        'success' => false,
        'message' => 'You must be logged in to make a payment',
        'redirect' => 'login.php'
    ];
    echo json_encode($response);
    exit;
}

// Get booking details from POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_type = $_POST['booking_type'] ?? ''; // 'room', 'tour', or 'package'
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    // Validate input
    $errors = [];
    
    if (!in_array($booking_type, ['room', 'tour', 'package'])) {
        $errors[] = "Invalid booking type";
    }
    
    if ($booking_id <= 0) {
        $errors[] = "Invalid booking ID";
    }
    
    if ($amount <= 0) {
        $errors[] = "Invalid payment amount";
    }
    
    // Fetch user details for the payment
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        $errors[] = "User not found";
    } else {
        $user = $user_result->fetch_assoc();
    }
    
    // Process if no errors
    if (empty($errors)) {
        try {
            // Determine the correct table name based on booking type
            $table_name = '';
            switch ($booking_type) {
                case 'room':
                    $table_name = 'room_bookings';
                    break;
                case 'tour':
                    $table_name = 'tour_bookings';
                    break;
                case 'package':
                    $table_name = 'package_bookings';
                    break;
            }
            
            // Get booking details from the appropriate table
            $stmt = $conn->prepare("SELECT * FROM $table_name WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $booking_id, $user_id);
            $stmt->execute();
            $booking_result = $stmt->get_result();
            
            if ($booking_result->num_rows === 0) {
                $response = [
                    'success' => false,
                    'message' => 'Booking not found or you do not have permission to access it'
                ];
                echo json_encode($response);
                exit;
            }
            
            $booking = $booking_result->fetch_assoc();
            
            // Check if payment has already been made
            if ($booking['payment_status'] === 'paid') {
                $response = [
                    'success' => false,
                    'message' => 'This booking has already been paid',
                    'redirect' => 'view_booking.php?type=' . $booking_type . '&id=' . $booking_id
                ];
                echo json_encode($response);
                exit;
            }
            
            // Create unique order ID
            $timestamp = time();
            $order_id = 'ORD_' . $timestamp . '_' . $booking_id . '_' . $user_id;
            $payment_id = null;
            $status = 'pending';
            
            // Ensure the payment table exists
            $conn->query("CREATE TABLE IF NOT EXISTS payments (
                id INT(11) NOT NULL AUTO_INCREMENT,
                order_id VARCHAR(50) NOT NULL,
                payment_id VARCHAR(100) DEFAULT NULL,
                booking_id INT(11) NOT NULL,
                booking_type ENUM('room', 'tour', 'package') NOT NULL,
                user_id INT(11) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )");
            
            // Check if there's an existing pending payment for this booking
            $stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? AND booking_type = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("is", $booking_id, $booking_type);
            $stmt->execute();
            $existing_payment = $stmt->get_result();
            
            if ($existing_payment->num_rows > 0) {
                // Use existing payment record
                $payment = $existing_payment->fetch_assoc();
                $order_id = $payment['order_id'];
                $payment_id = $payment['id'];
            } else {
                // Insert new payment record
                $stmt = $conn->prepare("INSERT INTO payments (order_id, booking_id, booking_type, user_id, amount, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisids", $order_id, $booking_id, $booking_type, $user_id, $amount, $status);
                $stmt->execute();
                $payment_id = $conn->insert_id;
            }
            
            // Prepare item name for payment
            $item_name = '';
            switch ($booking_type) {
                case 'room':
                    $stmt = $conn->prepare("SELECT name FROM rooms WHERE id = ?");
                    $stmt->bind_param("i", $booking['room_id']);
                    break;
                case 'tour':
                    $stmt = $conn->prepare("SELECT name FROM tours WHERE id = ?");
                    $stmt->bind_param("i", $booking['tour_id']);
                    break;
                case 'package':
                    $stmt = $conn->prepare("SELECT name FROM packages WHERE id = ?");
                    $stmt->bind_param("i", $booking['package_id']);
                    break;
            }
            
            $stmt->execute();
            $item_result = $stmt->get_result();
            $item = $item_result->fetch_assoc();
            $item_name = $item['name'] ?? ucfirst($booking_type) . ' Booking';
            
            // Convert amount to paisa (INR currency)
            $amount_in_paisa = intval($amount * 100);
            
            // Prepare Razorpay payment data
            $razorpay_data = [
                'key' => RAZORPAY_KEY_ID,
                'amount' => $amount_in_paisa,
                'currency' => RAZORPAY_CURRENCY,
                'name' => RAZORPAY_COMPANY_NAME,
                'description' => "$item_name - Booking #$booking_id",
                'order_id' => $order_id,
                'prefill' => [
                    'name' => $user['name'] ?? '',
                    'email' => $user['email'] ?? '',
                    'contact' => $user['phone'] ?? ''
                ],
                'notes' => [
                    'booking_id' => $booking_id,
                    'booking_type' => $booking_type,
                    'user_id' => $user_id
                ],
                'theme' => [
                    'color' => '#FF6600'
                ],
                'modal' => [
                    'animation' => true,
                    'escape' => false,
                    'backdropclose' => false
                ]
            ];
            
            $response = [
                'success' => true,
                'payment_data' => $razorpay_data,
                'order_id' => $order_id,
                'payment_id' => $payment_id
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ];
            echo json_encode($response);
        }
    } else {
        $response = [
            'success' => false,
            'message' => implode(', ', $errors)
        ];
        echo json_encode($response);
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method'
    ];
    echo json_encode($response);
} 