<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get parameters
$booking_type = isset($_GET['type']) ? $_GET['type'] : '';
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : '';

// Validate parameters
if (!in_array($booking_type, ['room', 'tour', 'package']) || $booking_id <= 0) {
    $_SESSION['booking_message'] = "Invalid booking details";
    $_SESSION['booking_status'] = "error";
    header('Location: my_bookings.php');
    exit;
}

// Determine which table to query based on booking type
$table_name = '';
switch ($booking_type) {
    case 'room':
        $table_name = 'room_bookings';
        $item_type = 'Room';
        break;
    case 'tour':
        $table_name = 'tour_bookings';
        $item_type = 'Tour';
        break;
    case 'package':
        $table_name = 'package_bookings';
        $item_type = 'Package';
        break;
}

// Get booking details
$stmt = $conn->prepare("SELECT * FROM $table_name WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['booking_message'] = "Booking not found";
    $_SESSION['booking_status'] = "error";
    header('Location: my_bookings.php');
    exit;
}

$booking = $result->fetch_assoc();

// Check if payment was actually completed
if ($booking['payment_status'] !== 'paid') {
    $_SESSION['booking_message'] = "Payment not yet completed";
    $_SESSION['booking_status'] = "warning";
    header('Location: view_booking.php?type=' . $booking_type . '&id=' . $booking_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success | TOUR STACK</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .success-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100px;
            height: 100px;
            background-color: #4CAF50;
            color: white;
            font-size: 50px;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        
        .success-title {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .success-message {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .booking-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #ff6600;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #e55c00;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .payment-id {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1 class="success-title">Payment Successful!</h1>
        <p class="success-message">Your <?php echo strtolower($item_type); ?> booking has been confirmed and you're all set for an amazing experience.</p>
        
        <div class="booking-details">
            <div class="detail-row">
                <span class="detail-label">Booking Reference:</span>
                <span>#<?php echo $booking_id; ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Payment ID:</span>
                <span class="payment-id"><?php echo htmlspecialchars($booking['payment_id']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Amount Paid:</span>
                <span>₹<?php echo number_format($booking['total_amount'], 2); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Payment Date:</span>
                <span><?php echo date('d M Y, h:i A', strtotime($booking['payment_date'])); ?></span>
            </div>
        </div>
        
        <p>
            A confirmation email has been sent to your registered email address.
            <br>You can view your booking details and invoice at any time in your account.
        </p>
        
        <div class="action-buttons">
            <a href="view_booking.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" class="btn">
                <i class="fas fa-eye"></i> View Booking Details
            </a>
            <a href="my_bookings.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> See All My Bookings
            </a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 