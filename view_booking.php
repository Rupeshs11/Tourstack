<?php
// Start session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'db_connection.php';
require_once 'razorpay_config.php';

// Get Razorpay key
$razorpay_key_id = defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : 'rzp_test_hoZT5C5TdV1KlY';

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
        $query = "SELECT tb.*, 
                 t.name as tour_name, t.price as tour_price, t.image_path as image_path,
                 t.description, t.duration, t.max_people
                 FROM tour_bookings tb 
                 LEFT JOIN tours t ON tb.tour_id = t.id 
                 WHERE tb.id = ? AND tb.user_id = ?";
        $title = "Tour Booking Details";
        break;
        
    case 'room':
        $query = "SELECT rb.*, 
                 r.name as room_name, r.price as room_price, r.image_path as image_path,
                 r.description, r.capacity, r.has_wifi, r.has_ac
                 FROM room_bookings rb 
                 LEFT JOIN rooms r ON rb.room_id = r.id 
                 WHERE rb.id = ? AND rb.user_id = ?";
        $title = "Room Booking Details";
        break;
        
    case 'package':
        $query = "SELECT pb.*, 
                 p.name as package_name, p.price as package_price, p.image_path as image_path,
                 p.description, p.duration, p.included_items
                 FROM package_bookings pb 
                 LEFT JOIN packages p ON pb.package_id = p.id 
                 WHERE pb.id = ? AND pb.user_id = ?";
        $title = "Package Booking Details";
        break;
}

// Get booking details
$stmt = $conn->prepare($query);
if ($stmt === false) {
    // Handle SQL preparation error
    $_SESSION['booking_error'] = "Database error: " . $conn->error;
    header('Location: my_bookings.php');
    exit;
}

// Verify tables exist before proceeding
$tableExists = false;
switch ($booking_type) {
    case 'package':
        $tableCheck = $conn->query("SHOW TABLES LIKE 'package_bookings'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION['booking_error'] = "Package bookings table does not exist. Please run fix_view_booking.php first.";
            header('Location: my_bookings.php');
            exit;
        }
        
        $tableCheck = $conn->query("SHOW TABLES LIKE 'packages'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION['booking_error'] = "Packages table does not exist. Please run fix_view_booking.php first.";
            header('Location: my_bookings.php');
            exit;
        }
        break;
    
    case 'room':
        $tableCheck = $conn->query("SHOW TABLES LIKE 'room_bookings'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION['booking_error'] = "Room bookings table does not exist.";
            header('Location: my_bookings.php');
            exit;
        }
        break;
        
    case 'tour':
        $tableCheck = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
        $tableExists = ($tableCheck && $tableCheck->num_rows > 0);
        if (!$tableExists) {
            $_SESSION['booking_error'] = "Tour bookings table does not exist.";
            header('Location: my_bookings.php');
            exit;
        }
        break;
}

$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Booking not found or does not belong to user
    $_SESSION['booking_error'] = "Booking not found or you don't have permission to view it.";
    header('Location: my_bookings.php');
    exit;
}

$booking = $result->fetch_assoc();

// Get related item details
switch ($booking_type) {
    case 'room':
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
        if ($stmt === false) {
            // Handle SQL preparation error
            die("Error preparing statement for room details: " . $conn->error);
        }
        $stmt->bind_param("i", $booking['room_id']);
        $stmt->execute();
        $item_result = $stmt->get_result();
        if ($item_result->num_rows > 0) {
            $item_details = $item_result->fetch_assoc();
        }
        break;
    case 'tour':
        $stmt = $conn->prepare("SELECT * FROM tours WHERE id = ?");
        if ($stmt === false) {
            // Handle SQL preparation error
            die("Error preparing statement for tour details: " . $conn->error);
        }
        $stmt->bind_param("i", $booking['tour_id']);
        $stmt->execute();
        $item_result = $stmt->get_result();
        if ($item_result->num_rows > 0) {
            $item_details = $item_result->fetch_assoc();
        }
        break;
    case 'package':
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
        if ($stmt === false) {
            // Handle SQL preparation error
            die("Error preparing statement for package details: " . $conn->error);
        }
        $stmt->bind_param("i", $booking['package_id']);
        $stmt->execute();
        $item_result = $stmt->get_result();
        if ($item_result->num_rows > 0) {
            $item_details = $item_result->fetch_assoc();
        }
        break;
}

// Get related payment details
$stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? AND booking_type = ? ORDER BY created_at DESC LIMIT 1");
if ($stmt === false) {
    // Handle SQL preparation error
    die("Error preparing statement for payment details: " . $conn->error);
}
$stmt->bind_param("is", $booking_id, $booking_type);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment_details = $payment_result->num_rows > 0 ? $payment_result->fetch_assoc() : null;

// Process booking message if any
$booking_message = '';
$booking_status = '';

if (isset($_SESSION['booking_message']) && isset($_SESSION['booking_status'])) {
    $booking_message = $_SESSION['booking_message'];
    $booking_status = $_SESSION['booking_status'];
    unset($_SESSION['booking_message']);
    unset($_SESSION['booking_status']);
}

// Include header
include 'includes/header.php';
?>

<!-- Include Razorpay Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<main class="main-content py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-light py-3">
                        <h1 class="h4 mb-0"><?php echo $title; ?></h1>
                        <a href="my_bookings.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Bookings
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5 mb-4 mb-md-0">
                                <?php 
                                $placeholder_image = "https://placehold.co/600x400/e74c3c/ffffff?text=Image";
                                $image_url = !empty($booking['image_path']) ? $booking['image_path'] : $placeholder_image;
                                if (!empty($booking['image_path']) && strpos($image_url, 'http') !== 0) {
                                    $image_url = $booking['image_path'];
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                     alt="<?php echo htmlspecialchars($booking[$booking_type . '_name']); ?>" 
                                     class="img-fluid rounded shadow-sm">
                                
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-<?php echo $booking['booking_status'] == 'pending' ? 'warning' : ($booking['booking_status'] == 'confirmed' ? 'success' : 'danger'); ?> p-2">
                                            <i class="fas fa-<?php echo $booking['booking_status'] == 'pending' ? 'clock' : ($booking['booking_status'] == 'confirmed' ? 'check-circle' : 'times-circle'); ?> me-1"></i>
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo $booking['payment_status'] == 'pending' ? 'secondary' : ($booking['payment_status'] == 'paid' ? 'success' : 'info'); ?> p-2">
                                            <i class="fas fa-<?php echo $booking['payment_status'] == 'pending' ? 'clock' : ($booking['payment_status'] == 'paid' ? 'check-circle' : 'undo'); ?> me-1"></i>
                                            Payment: <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($booking['booking_status'] == 'pending'): ?>
                                <div class="d-grid mt-3">
                                    <a href="#" class="btn btn-danger btn-sm cancel-booking" 
                                       data-id="<?php echo $booking_id; ?>" 
                                       data-type="<?php echo $booking_type; ?>">
                                        <i class="fas fa-times-circle me-1"></i> Cancel Booking
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7">
                                <h2 class="h5 fw-bold text-primary mb-3">
                                    <?php echo htmlspecialchars($booking[$booking_type . '_name']); ?>
                                </h2>
                                
                                <p class="small text-muted mb-3">
                                    <?php echo htmlspecialchars($booking['description']); ?>
                                </p>
                                
                                <div class="row g-3 mb-4">
                                    <?php if ($booking_type == 'tour'): ?>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Tour Date</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-calendar-day text-primary me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">People</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-users text-primary me-1"></i>
                                                    <?php echo $booking['people']; ?> people
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Duration</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-clock text-primary me-1"></i>
                                                    <?php echo $booking['duration']; ?> hours
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Max People</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-user-friends text-primary me-1"></i>
                                                    <?php echo $booking['max_people']; ?> people
                                                </p>
                                            </div>
                                        </div>
                                    <?php elseif ($booking_type == 'room'): ?>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Check-in</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-calendar-plus text-primary me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Check-out</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-calendar-minus text-primary me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Guests</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-users text-primary me-1"></i>
                                                    <?php echo $booking['adults'] + $booking['children']; ?> guests
                                                    (<?php echo $booking['adults']; ?> adults, <?php echo $booking['children']; ?> children)
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Nights</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-moon text-primary me-1"></i>
                                                    <?php echo $booking['total_nights']; ?> nights
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Room Type</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-bed text-primary me-1"></i>
                                                    <?php echo htmlspecialchars($booking['room_name']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Max Capacity</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-user-friends text-primary me-1"></i>
                                                    <?php echo $booking['capacity']; ?> people
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Daily Rate</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-rupee-sign text-primary me-1"></i>
                                                    ₹<?php echo number_format($booking['room_price'], 2); ?> per night
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Booking ID</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-hashtag text-primary me-1"></i>
                                                    #<?php echo $booking['id']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Amenities</p>
                                                <p class="mb-0 fw-bold">
                                                    <?php if (isset($booking['has_wifi']) && $booking['has_wifi']): ?>
                                                        <span class="badge bg-info me-2"><i class="fas fa-wifi me-1"></i> WiFi</span>
                                                    <?php endif; ?>
                                                    <?php if (isset($booking['has_ac']) && $booking['has_ac']): ?>
                                                        <span class="badge bg-info me-2"><i class="fas fa-snowflake me-1"></i> Air Conditioning</span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-info me-2"><i class="fas fa-tv me-1"></i> TV</span>
                                                    <span class="badge bg-info me-2"><i class="fas fa-bath me-1"></i> Private Bathroom</span>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if (!empty($booking['special_requests'])): ?>
                                        <div class="col-12 mt-2">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Special Requests</p>
                                                <p class="mb-0 small">
                                                    <i class="fas fa-comment-alt text-primary me-1"></i>
                                                    <?php echo htmlspecialchars($booking['special_requests']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Payment Status Section -->
                                        <div class="col-12 mt-3">
                                            <div class="border rounded p-3 bg-light">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="bg-<?php echo $booking['payment_status'] == 'pending' ? 'warning' : ($booking['payment_status'] == 'paid' ? 'success' : 'secondary'); ?> p-2 rounded-circle me-2">
                                                        <i class="fas fa-<?php echo $booking['payment_status'] == 'pending' ? 'clock' : ($booking['payment_status'] == 'paid' ? 'check-circle' : 'info-circle'); ?> text-white"></i>
                                                    </div>
                                                    <h6 class="mb-0 fw-bold">Payment Status: <span class="text-<?php echo $booking['payment_status'] == 'pending' ? 'warning' : ($booking['payment_status'] == 'paid' ? 'success' : 'secondary'); ?>"><?php echo ucfirst($booking['payment_status']); ?></span></h6>
                                                </div>
                                                
                                                <?php if ($booking['payment_status'] == 'pending'): ?>
                                                <div class="alert alert-warning py-2 small">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> Please complete your payment to confirm this booking.
                                                </div>
                                                <div class="d-grid">
                                                    <button id="pay-button" class="btn btn-primary btn-lg" onclick="processPayment()">
                                                        <i class="fas fa-credit-card mr-2"></i> Pay Now
                                                    </button>
                                                </div>
                                                <?php elseif ($booking['payment_status'] == 'paid'): ?>
                                                <div class="alert alert-success py-2 small">
                                                    <i class="fas fa-check-circle me-1"></i> Payment completed. Your booking is confirmed.
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <a href="generate_bill.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-file-invoice me-1"></i> View Receipt
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Payment Details Section -->
                                        <?php if (!empty($payment_details)): ?>
                                        <div class="col-12 mt-3">
                                            <h6 class="fw-bold text-uppercase text-primary mb-2"><i class="fas fa-receipt me-2"></i>Payment Details</h6>
                                            <div class="border rounded p-3 bg-light">
                                                <!-- Payment Status Card -->
                                                <div class="card mb-3 border-<?php echo $booking['payment_status'] == 'pending' ? 'warning' : ($booking['payment_status'] == 'paid' ? 'success' : 'info'); ?>">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-<?php echo $booking['payment_status'] == 'pending' ? 'warning' : ($booking['payment_status'] == 'paid' ? 'success' : 'info'); ?> p-3 rounded-circle me-3">
                                                                <i class="fas fa-<?php echo $booking['payment_status'] == 'pending' ? 'hourglass-half' : ($booking['payment_status'] == 'paid' ? 'check-circle' : 'money-bill-wave'); ?> text-white"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="fw-bold mb-1">Status: <span class="text-<?php echo $booking['payment_status'] == 'pending' ? 'warning' : ($booking['payment_status'] == 'paid' ? 'success' : 'info'); ?>"><?php echo ucfirst($booking['payment_status']); ?></span></h6>
                                                                <?php if ($booking['payment_status'] == 'pending'): ?>
                                                                <p class="small mb-0">Your payment is pending. Please complete payment to confirm your booking.</p>
                                                                <?php elseif ($booking['payment_status'] == 'paid'): ?>
                                                                <p class="small mb-0">Payment completed on <?php echo date('M d, Y', strtotime($payment_details['created_at'])); ?>. Your booking is confirmed.</p>
                                                                <?php else: ?>
                                                                <p class="small mb-0">Payment status: <?php echo ucfirst($booking['payment_status']); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <p class="text-muted mb-0 small">Payment ID</p>
                                                        <p class="mb-2 fw-bold">#<?php echo $payment_details['id']; ?></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="text-muted mb-0 small">Payment Date</p>
                                                        <p class="mb-2 fw-bold"><?php echo date('M d, Y H:i', strtotime($payment_details['created_at'])); ?></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="text-muted mb-0 small">Amount Paid</p>
                                                        <p class="mb-2 fw-bold text-success">₹<?php echo number_format($payment_details['amount'], 2); ?></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="text-muted mb-0 small">Payment Method</p>
                                                        <p class="mb-2 fw-bold">
                                                            <?php if (!empty($payment_details['payment_method'])): ?>
                                                                <i class="fas fa-<?php echo $payment_details['payment_method'] == 'credit_card' ? 'credit-card' : ($payment_details['payment_method'] == 'paypal' ? 'paypal' : 'money-bill-wave'); ?> me-1"></i>
                                                                <?php echo ucfirst(str_replace('_', ' ', $payment_details['payment_method'])); ?>
                                                            <?php else: ?>
                                                                <i class="fas fa-money-bill-wave me-1"></i> Online Payment
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <?php if (!empty($payment_details['transaction_id'])): ?>
                                                    <div class="col-12">
                                                        <p class="text-muted mb-0 small">Transaction ID</p>
                                                        <p class="mb-0 small font-monospace"><?php echo $payment_details['transaction_id']; ?></p>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($payment_details['payment_method']) && $payment_details['payment_method'] == 'credit_card'): ?>
                                                    <div class="col-12 mt-3">
                                                        <hr class="my-2">
                                                        <p class="text-muted mb-2 small fw-bold">Credit Card Information</p>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Card Type</p>
                                                                <p class="mb-0 small">
                                                                    <?php 
                                                                        $card_type = !empty($payment_details['card_type']) ? $payment_details['card_type'] : 'Credit Card';
                                                                        $card_icon = 'credit-card';
                                                                        if (stripos($card_type, 'visa') !== false) $card_icon = 'cc-visa';
                                                                        elseif (stripos($card_type, 'mastercard') !== false) $card_icon = 'cc-mastercard';
                                                                        elseif (stripos($card_type, 'amex') !== false) $card_icon = 'cc-amex';
                                                                        elseif (stripos($card_type, 'discover') !== false) $card_icon = 'cc-discover';
                                                                    ?>
                                                                    <i class="fab fa-<?php echo $card_icon; ?> me-1"></i> 
                                                                    <?php echo ucfirst($card_type); ?>
                                                                </p>
                                                            </div>
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Card Number</p>
                                                                <p class="mb-0 small">
                                                                    <i class="fas fa-credit-card me-1"></i>
                                                                    <?php 
                                                                        $masked_number = !empty($payment_details['card_last4']) 
                                                                            ? "xxxx-xxxx-xxxx-" . $payment_details['card_last4'] 
                                                                            : "xxxx-xxxx-xxxx-xxxx"; 
                                                                        echo $masked_number;
                                                                    ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php elseif (!empty($payment_details['payment_method']) && $payment_details['payment_method'] == 'paypal'): ?>
                                                    <div class="col-12 mt-3">
                                                        <hr class="my-2">
                                                        <p class="text-muted mb-2 small fw-bold">PayPal Information</p>
                                                        <div class="row g-2">
                                                            <div class="col-12">
                                                                <p class="text-muted mb-0 small">PayPal Account</p>
                                                                <p class="mb-0 small">
                                                                    <i class="fab fa-paypal me-1"></i>
                                                                    <?php echo !empty($payment_details['paypal_email']) 
                                                                        ? htmlspecialchars($payment_details['paypal_email']) 
                                                                        : "PayPal Account"; ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php elseif (!empty($payment_details['payment_method']) && $payment_details['payment_method'] == 'bank_transfer'): ?>
                                                    <div class="col-12 mt-3">
                                                        <hr class="my-2">
                                                        <p class="text-muted mb-2 small fw-bold">Bank Transfer Details</p>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Bank Name</p>
                                                                <p class="mb-0 small">
                                                                    <i class="fas fa-university me-1"></i>
                                                                    <?php echo !empty($payment_details['bank_name']) 
                                                                        ? htmlspecialchars($payment_details['bank_name']) 
                                                                        : "Bank Transfer"; ?>
                                                                </p>
                                                            </div>
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Transfer Date</p>
                                                                <p class="mb-0 small">
                                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                                    <?php echo !empty($payment_details['transfer_date']) 
                                                                        ? date('M d, Y', strtotime($payment_details['transfer_date'])) 
                                                                        : date('M d, Y', strtotime($payment_details['created_at'])); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($payment_details['billing_address'])): ?>
                                                    <div class="col-12 mt-2">
                                                        <hr class="my-2">
                                                        <p class="text-muted mb-1 small fw-bold">Billing Address</p>
                                                        <p class="mb-0 small">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($payment_details['billing_address']); ?>
                                                        </p>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Payment Summary -->
                                                    <div class="col-12 mt-3">
                                                        <hr class="my-2">
                                                        <p class="text-muted mb-2 small fw-bold">Payment Summary</p>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Subtotal</p>
                                                                <p class="mb-0 small">
                                                                    ₹<?php echo number_format($booking['room_price'] * $booking['total_nights'], 2); ?>
                                                                </p>
                                                            </div>
                                                            <?php if (!empty($payment_details['tax_amount'])): ?>
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Tax</p>
                                                                <p class="mb-0 small">
                                                                    ₹<?php echo number_format($payment_details['tax_amount'], 2); ?>
                                                                </p>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Discount</p>
                                                                <p class="mb-0 small text-danger">
                                                                    ₹<?php echo number_format($payment_details['discount_amount'], 2); ?>
                                                                </p>
                                                            </div>
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small">Payment Status</p>
                                                                <p class="mb-0 small">
                                                                    <span class="badge bg-<?php echo $booking['payment_status'] == 'pending' ? 'warning' : ($booking['payment_status'] == 'paid' ? 'success' : 'secondary'); ?>">
                                                                        <?php echo ucfirst($booking['payment_status']); ?>
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <div class="col-6">
                                                                <p class="text-muted mb-0 small fw-bold">Total Paid</p>
                                                                <p class="mb-0 small text-primary fw-bold">
                                                                    ₹<?php echo number_format($payment_details['amount'], 2); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-end mt-3">
                                                    <?php if (!empty($payment_details['receipt_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($payment_details['receipt_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                                        <i class="fas fa-external-link-alt me-1"></i> Online Receipt
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="generate_bill.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-file-invoice me-1"></i> View Receipt
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="col-12 mt-3">
                                            <h6 class="fw-bold text-uppercase text-primary mb-2"><i class="fas fa-receipt me-2"></i>Payment Details</h6>
                                            <div class="border rounded p-3 bg-light">
                                                <p class="text-muted mb-0 small">No payment details available.</p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php elseif ($booking_type == 'package'): ?>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Booking Date</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-calendar-day text-primary me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Duration</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-clock text-primary me-1"></i>
                                                    <?php echo $booking['duration']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">Number of Guests</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-users text-primary me-1"></i>
                                                    <?php echo $booking['number_of_guests']; ?> guests
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 bg-light h-100">
                                                <p class="text-muted mb-0 small">What's Included</p>
                                                <p class="mb-0 fw-bold">
                                                    <i class="fas fa-list-check text-primary me-1"></i>
                                                    <?php echo htmlspecialchars(substr($booking['included_items'], 0, 50) . (strlen($booking['included_items']) > 50 ? '...' : '')); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                    <h3 class="h6 text-muted mb-0">Price Summary</h3>
                                    <a class="small text-primary" data-bs-toggle="collapse" href="#priceSummary" role="button" aria-expanded="false" aria-controls="priceSummary">
                                        Show Details <i class="fas fa-chevron-down ms-1"></i>
                                    </a>
                                </div>
                                
                                <div class="collapse show" id="priceSummary">
                                    <?php if ($booking_type == 'tour'): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Tour price per person</span>
                                            <span class="small fw-bold">₹<?php echo number_format($booking['tour_price'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Number of people</span>
                                            <span class="small fw-bold"><?php echo $booking['people']; ?></span>
                                        </div>
                                    <?php elseif ($booking_type == 'room'): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Room price per night</span>
                                            <span class="small fw-bold">₹<?php echo number_format($booking['room_price'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Number of nights</span>
                                            <span class="small fw-bold"><?php echo $booking['total_nights']; ?></span>
                                        </div>
                                    <?php elseif ($booking_type == 'package'): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Package price per person</span>
                                            <span class="small fw-bold">₹<?php echo number_format($booking['package_price'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small">Number of guests</span>
                                            <span class="small fw-bold"><?php echo $booking['number_of_guests']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                        <span class="fw-bold">Total Amount</span>
                                        <span class="h5 mb-0 text-primary fw-bold">₹<?php echo number_format($booking['total_amount'], 2); ?></span>
                                    </div>
                                    
                                    <?php if ($booking['payment_status'] === 'pending'): ?>
                                    <div class="d-grid mt-3">
                                        <button id="pay-button" class="btn btn-primary btn-lg" onclick="processPayment()">
                                            <i class="fas fa-credit-card mr-2"></i> Pay Now
                                        </button>
                                    </div>
                                    <?php elseif ($booking['payment_status'] === 'paid'): ?>
                                    <div class="d-flex justify-content-between mt-3">
                                        <a href="generate_bill.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                            <i class="fas fa-file-invoice me-1"></i> Generate Bill
                                        </a>
                                        <span class="badge bg-success p-2 ms-2">
                                            <i class="fas fa-check-circle me-1"></i> Payment Completed
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle booking cancellation
        const cancelButton = document.querySelector('.cancel-booking');
        if (cancelButton) {
            cancelButton.addEventListener('click', function(e) {
                e.preventDefault();
                const bookingId = this.getAttribute('data-id');
                const bookingType = this.getAttribute('data-type');
                
                Swal.fire({
                    title: 'Cancel Booking?',
                    text: 'Are you sure you want to cancel this booking? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, cancel it!',
                    cancelButtonText: 'No, keep it',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing',
                            text: 'Please wait while we cancel your booking...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Redirect to the cancel booking URL
                        window.location.href = `cancel_booking.php?type=${bookingType}&id=${bookingId}`;
                    }
                });
            });
        }
    });
</script>

<!-- Direct Payment Script -->
<script>
// Payment processing function with enhanced UI
function processPayment() {
    // First show processing animation
    Swal.fire({
        title: 'Processing Payment',
        html: `
            <div class="payment-processing">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="sr-only">Processing...</span>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                </div>
                <div class="payment-cards">
                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/visa/visa-original.svg" width="50" class="mx-1">
                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mastercard/mastercard-original.svg" width="50" class="mx-1">
                    <img src="https://www.svgrepo.com/show/328132/american-express.svg" width="50" class="mx-1">
                </div>
                <p class="mt-2">Please do not close this window</p>
            </div>
        `,
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            const progressBar = Swal.getPopup().querySelector('.progress-bar');
            let width = 0;
            const interval = setInterval(() => {
                width += 5;
                progressBar.style.width = width + '%';
                if (width >= 100) {
                    clearInterval(interval);
                    processActualPayment();
                }
            }, 150);
        }
    });
}

function processActualPayment() {
    // Get booking details
    const bookingType = '<?php echo $booking_type; ?>';
    const bookingId = '<?php echo $booking_id; ?>';
    const amount = '<?php echo $booking['total_amount']; ?>';
    
    // Process the payment through the fake payment system
    fetch('process_fake_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            booking_type: bookingType,
            booking_id: bookingId,
            amount: amount,
            payment_method: 'credit_card',
            card_number: '4111111111111111'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Payment successful
            startConfetti();
            
            // Show success message with receipt
            const txnDate = new Date();
            Swal.fire({
                icon: 'success',
                title: 'Payment Successful!',
                html: `
                    <div class="payment-receipt border p-3 text-left">
                        <h5 class="text-center mb-3">Payment Receipt</h5>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Transaction ID:</div>
                            <div class="col-6 font-weight-bold">${data.transaction_id}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Date:</div>
                            <div class="col-6">${txnDate.toLocaleDateString()}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Time:</div>
                            <div class="col-6">${txnDate.toLocaleTimeString()}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Amount:</div>
                            <div class="col-6 font-weight-bold">$${amount}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Status:</div>
                            <div class="col-6 text-success">PAID</div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <small>Your booking is paid but pending admin confirmation.</small>
                        </div>
                    </div>
                `,
                confirmButtonText: 'View My Bookings',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = data.redirect;
                }
            });
            
            // Update the UI to reflect payment
            document.getElementById('pay-button').disabled = true;
            document.getElementById('pay-button').innerHTML = '<i class="fas fa-check-circle"></i> Payment Complete';
            document.getElementById('pay-button').classList.remove('btn-primary');
            document.getElementById('pay-button').classList.add('btn-success');
        } else {
            // Payment failed
            Swal.fire({
                icon: 'error',
                title: 'Payment Failed',
                text: data.message || 'There was an error processing your payment. Please try again.',
                confirmButtonText: 'Try Again'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Payment Error',
            text: 'There was a network error. Please check your connection and try again.',
            confirmButtonText: 'Close'
        });
    });
}

// Confetti effect for successful payment
function startConfetti() {
    const duration = 3000;
    const animationEnd = Date.now() + duration;
    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    const interval = setInterval(function() {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);
        
        // Since particles fall down, start a bit higher than random
        confetti(Object.assign({}, defaults, { 
            particleCount, 
            origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } 
        }));
        confetti(Object.assign({}, defaults, { 
            particleCount, 
            origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } 
        }));
    }, 250);
}
</script>

<!-- Add confetti library -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>

<!-- Add SweetAlert2 for improved alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.payment-processing {
    text-align: center;
    padding: 20px 0;
}
.payment-receipt {
    background-color: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>

<?php if (isset($razorpay) && $booking['payment_status'] !== 'paid'): ?>
<script>
    var options = {
        "key": "<?php echo $razorpay_key_id; ?>",
        "amount": "<?php echo $booking['total_amount'] * 100; ?>",
        "currency": "INR",
        "name": "TourStack Hotel",
        "description": "<?php echo $title; ?> #<?php echo $booking_id; ?>",
        "image": "assets/img/logo.png",
        "theme": {
            "color": "#4e73df"  // Updated to a more modern blue color
        }
    };
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 