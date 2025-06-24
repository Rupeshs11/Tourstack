<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get booking ID and type from request
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$booking_type = isset($_GET['type']) ? $_GET['type'] : '';

if ($booking_id <= 0 || empty($booking_type)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID or type']);
    exit;
}

// First check if mobile column exists
$mobile_column_exists = false;
$check_mobile = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
if ($check_mobile && $check_mobile->num_rows > 0) {
    $mobile_column_exists = true;
}

// Check if phone column exists if mobile doesn't
$phone_column_exists = false;
if (!$mobile_column_exists) {
    $check_phone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($check_phone && $check_phone->num_rows > 0) {
        $phone_column_exists = true;
    }
}

// Get booking details based on type
try {
    if ($booking_type === 'tour') {
        // Build query based on column existence
        if ($mobile_column_exists) {
            $query = "SELECT tb.*, u.name as user_name, u.email as user_email, u.mobile as user_phone,
                      t.name as tour_name, t.price as tour_price, t.image_path as image_path, t.name as item_name,
                      t.description as description
                      FROM tour_bookings tb 
                      LEFT JOIN users u ON tb.user_id = u.id 
                      LEFT JOIN tours t ON tb.tour_id = t.id 
                      WHERE tb.id = ?";
        } elseif ($phone_column_exists) {
            $query = "SELECT tb.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                      t.name as tour_name, t.price as tour_price, t.image_path as image_path, t.name as item_name,
                      t.description as description
                      FROM tour_bookings tb 
                      LEFT JOIN users u ON tb.user_id = u.id 
                      LEFT JOIN tours t ON tb.tour_id = t.id 
                      WHERE tb.id = ?";
        } else {
            $query = "SELECT tb.*, u.name as user_name, u.email as user_email, 'Not Available' as user_phone,
                      t.name as tour_name, t.price as tour_price, t.image_path as image_path, t.name as item_name,
                      t.description as description
                      FROM tour_bookings tb 
                      LEFT JOIN users u ON tb.user_id = u.id 
                      LEFT JOIN tours t ON tb.tour_id = t.id 
                      WHERE tb.id = ?";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        
        // Format date for display
        if (isset($booking['booking_date'])) {
            $booking['booking_date'] = date('d M Y', strtotime($booking['booking_date']));
        }
        
    } else if ($booking_type === 'room') {
        // Build query based on column existence
        if ($mobile_column_exists) {
            $query = "SELECT rb.*, u.name as user_name, u.email as user_email, u.mobile as user_phone,
                      r.name as room_name, r.price as room_price, r.image_path as image_path, r.name as item_name,
                      r.description as description
                      FROM room_bookings rb 
                      LEFT JOIN users u ON rb.user_id = u.id 
                      LEFT JOIN rooms r ON rb.room_id = r.id 
                      WHERE rb.id = ?";
        } elseif ($phone_column_exists) {
            $query = "SELECT rb.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                      r.name as room_name, r.price as room_price, r.image_path as image_path, r.name as item_name,
                      r.description as description
                      FROM room_bookings rb 
                      LEFT JOIN users u ON rb.user_id = u.id 
                      LEFT JOIN rooms r ON rb.room_id = r.id 
                      WHERE rb.id = ?";
        } else {
            $query = "SELECT rb.*, u.name as user_name, u.email as user_email, 'Not Available' as user_phone,
                      r.name as room_name, r.price as room_price, r.image_path as image_path, r.name as item_name,
                      r.description as description
                      FROM room_bookings rb 
                      LEFT JOIN users u ON rb.user_id = u.id 
                      LEFT JOIN rooms r ON rb.room_id = r.id 
                      WHERE rb.id = ?";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        
        // Format dates for display
        if (isset($booking['check_in_date'])) {
            $booking['check_in_date'] = date('d M Y', strtotime($booking['check_in_date']));
        }
        if (isset($booking['check_out_date'])) {
            $booking['check_out_date'] = date('d M Y', strtotime($booking['check_out_date']));
        }
    } else {
        throw new Exception('Invalid booking type');
    }
    
    // Format created_at date
    if (isset($booking['created_at'])) {
        $booking['created_at'] = date('d M Y H:i', strtotime($booking['created_at']));
    }
    
    // Format payment and booking status to uppercase
    if (isset($booking['payment_status'])) {
        $booking['payment_status'] = strtoupper($booking['payment_status']);
    }
    if (isset($booking['booking_status'])) {
        $booking['booking_status'] = strtoupper($booking['booking_status']);
    }
    
    // Check if booking was found
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Return success response with booking data
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'booking' => $booking]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 