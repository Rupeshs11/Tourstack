<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';
require_once 'razorpay_config.php';

// Set Razorpay key variables for use in JavaScript
$razorpay_key_id = defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : 'rzp_test_hoZT5C5TdV1KlY';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get parameters
$booking_type = isset($_GET['type']) ? $_GET['type'] : '';
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
        break;
    case 'tour':
        $table_name = 'tour_bookings';
        break;
    case 'package':
        $table_name = 'package_bookings';
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

// If already paid, redirect to booking details
if ($booking['payment_status'] === 'paid') {
    $_SESSION['booking_message'] = "This booking has already been paid";
    $_SESSION['booking_status'] = "info";
    header('Location: view_booking.php?type=' . $booking_type . '&id=' . $booking_id);
    exit;
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Determine booking details based on type
$booking_details = [];
$item_name = '';
$amount = 0;

switch ($booking_type) {
    case 'room':
        $amount = $booking['total_amount'];
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $booking['room_id']);
        $stmt->execute();
        $room_result = $stmt->get_result();
        $room = $room_result->fetch_assoc();
        $item_name = $room['name'] ?? 'Room';
        $booking_details = [
            'name' => $item_name,
            'check_in' => $booking['check_in_date'],
            'check_out' => $booking['check_out_date'],
            'nights' => $booking['total_nights'],
            'guests' => $booking['adults'] + $booking['children']
        ];
        break;
    case 'tour':
        $amount = $booking['total_amount'];
        $stmt = $conn->prepare("SELECT * FROM tours WHERE id = ?");
        $stmt->bind_param("i", $booking['tour_id']);
        $stmt->execute();
        $tour_result = $stmt->get_result();
        $tour = $tour_result->fetch_assoc();
        $item_name = $tour['name'] ?? 'Tour';
        $booking_details = [
            'name' => $item_name,
            'date' => $booking['booking_date'],
            'people' => $booking['people']
        ];
        break;
    case 'package':
        $amount = $booking['total_amount'];
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
        $stmt->bind_param("i", $booking['package_id']);
        $stmt->execute();
        $package_result = $stmt->get_result();
        $package = $package_result->fetch_assoc();
        $item_name = $package['name'] ?? 'Package';
        $booking_details = [
            'name' => $item_name,
            'date' => $booking['booking_date'],
            'guests' => $booking['number_of_guests']
        ];
        break;
}

// If amount is 0 or not set, use a default
if ($amount <= 0) {
    if ($booking_type === 'room' && isset($booking['total_nights']) && isset($room['price'])) {
        $amount = $booking['total_nights'] * $room['price'];
    } elseif ($booking_type === 'tour' && isset($booking['people']) && isset($tour['price'])) {
        $amount = $booking['people'] * $tour['price'];
    } elseif ($booking_type === 'package' && isset($booking['number_of_guests']) && isset($package['price'])) {
        $amount = $booking['number_of_guests'] * $package['price'];
    } else {
        $amount = 999;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | TOUR STACK</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .payment-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .payment-header {
            margin-bottom: 30px;
        }
        
        .payment-header h1 {
            color: #ff6600;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .payment-summary {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: left;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #555;
        }
        
        .summary-row.total {
            color: #000;
            font-weight: bold;
            font-size: 22px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #ddd;
        }
        
        .payment-methods {
            margin-bottom: 25px;
        }
        
        .method-description {
            margin-bottom: 20px;
            color: #666;
        }
        
        .method-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .method-icons i {
            font-size: 24px;
            color: #555;
        }
        
        .payment-btn {
            background-color: #ff6600;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.3s;
            display: inline-block;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(255, 102, 0, 0.3);
        }
        
        .payment-btn:hover {
            background-color: #e55c00;
            transform: translateY(-2px);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #777;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #ff6600;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container payment-container">
        <div class="payment-header">
            <h1>Complete Your Payment</h1>
            <p>Simple and secure payment for your booking</p>
        </div>
        
        <div class="payment-summary">
            <div class="summary-row">
                <span>Booking Type:</span>
                <span><?php echo ucfirst($booking_type); ?></span>
            </div>
            
            <div class="summary-row">
                <span>Item:</span>
                <span><?php echo htmlspecialchars($booking_details['name']); ?></span>
            </div>
            
            <?php if ($booking_type === 'room'): ?>
            <div class="summary-row">
                <span>Dates:</span>
                <span><?php echo date('d M', strtotime($booking_details['check_in'])); ?> - <?php echo date('d M Y', strtotime($booking_details['check_out'])); ?></span>
            </div>
            <?php else: ?>
            <div class="summary-row">
                <span>Date:</span>
                <span><?php echo date('d M Y', strtotime($booking_details['date'])); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="summary-row total">
                <span>Total:</span>
                <span>₹<?php echo number_format($amount, 2); ?></span>
            </div>
        </div>
        
        <div class="payment-methods">
            <h3>Choose Your Payment Method</h3>
            <p class="method-description">Pay seamlessly using credit/debit cards, UPI, net banking, or wallets</p>
            
            <div class="method-icons">
                <i class="fas fa-credit-card" title="Credit/Debit Card"></i>
                <i class="fas fa-mobile-alt" title="UPI"></i>
                <i class="fas fa-university" title="Net Banking"></i>
                <i class="fas fa-wallet" title="Wallets"></i>
            </div>
        </div>
        
        <button id="rzp-button" class="payment-btn">
            <i class="fas fa-lock me-1"></i> Pay Now ₹<?php echo number_format($amount, 2); ?>
        </button>
        
        <div>
            <a href="view_booking.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" class="back-link">
                <i class="fas fa-arrow-left me-1"></i> Back to Booking
            </a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Direct payment button handler
        document.getElementById('rzp-button').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update button text to show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting...';
            this.disabled = true;
            
            // Generate a random payment ID for testing
            const paymentId = 'pay_' + Math.random().toString(36).substr(2, 9);
            
            // Redirect to fake payment page with correct parameters
            window.location.href = 'fake_payment.php?order_id=' + paymentId + 
                                  '&amount=<?php echo $amount; ?>' + 
                                  '&type=<?php echo $booking_type; ?>' + 
                                  '&booking_id=<?php echo $booking_id; ?>';
        });
    });
    </script>
</body>
</html> 

