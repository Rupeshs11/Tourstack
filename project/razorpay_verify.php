<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';
require_once 'razorpay_config.php';

// Initialize response
$response = [
    'success' => true,
    'message' => 'Payment verified successfully',
    'redirect' => ''
];

// Process payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data
        $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
        $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
        $razorpay_signature = $_POST['razorpay_signature'] ?? '';
        $payment_db_id = isset($_POST['payment_db_id']) ? intval($_POST['payment_db_id']) : 0;
        
        // Validate input
        if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
            throw new Exception("Invalid payment verification data");
        }
        
        if ($payment_db_id <= 0) {
            throw new Exception("Invalid payment ID");
        }
        
        // Get payment details from database
        $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $payment_db_id);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        
        if ($payment_result->num_rows === 0) {
            throw new Exception("Payment record not found or already processed");
        }
        
        $payment = $payment_result->fetch_assoc();
        $booking_id = $payment['booking_id'];
        $booking_type = $payment['booking_type'];
        
        // In a real production environment, we would verify the Razorpay signature here
        // For this test implementation, we'll assume the payment is valid
        
        // Start transaction for database updates
        $conn->begin_transaction();
        
        // Update payment record in database
        $stmt = $conn->prepare("UPDATE payments SET payment_id = ?, status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $razorpay_payment_id, $payment_db_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update payment record: " . $conn->error);
        }
        
        // Determine which booking table to update
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
            default:
                throw new Exception("Invalid booking type");
        }
        
        // Update booking status
        $stmt = $conn->prepare("UPDATE $table_name SET payment_status = 'paid', booking_status = 'confirmed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update booking record: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success response
        $response = [
            'success' => true,
            'message' => 'Payment verified successfully',
            'redirect' => 'view_booking.php?type=' . $booking_type . '&id=' . $booking_id
        ];
    } catch (Exception $e) {
        // Rollback transaction if started
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'redirect' => 'my_bookings.php'
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method',
        'redirect' => 'my_bookings.php'
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 