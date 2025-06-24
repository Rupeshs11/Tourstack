<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Get payment parameters from URL
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$amount = isset($_GET['amount']) ? $_GET['amount'] : '';
$booking_type = isset($_GET['type']) ? $_GET['type'] : '';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Validate required parameters
if (empty($order_id) || empty($amount) || empty($booking_type) || empty($booking_id)) {
    $_SESSION['payment_error'] = "Missing required payment parameters";
    header("Location: my_bookings.php");
    exit();
}

// Get booking information based on booking type
$booking_info = array('id' => $booking_id);

if ($booking_type == 'room') {
    $query = "SELECT r.name, r.price, rb.check_in_date, rb.check_out_date, rb.total_amount 
              FROM room_bookings rb 
              JOIN rooms r ON rb.room_id = r.id 
              WHERE rb.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $booking_info['details'] = $row;
        $booking_info['title'] = $row['name'] . " Room";
    }
} elseif ($booking_type == 'tour') {
    $query = "SELECT t.name, t.price, tb.booking_date, tb.total_amount 
              FROM tour_bookings tb 
              JOIN tours t ON tb.tour_id = t.id 
              WHERE tb.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $booking_info['details'] = $row;
        $booking_info['title'] = $row['name'] . " Tour";
    }
} elseif ($booking_type == 'package') {
    $query = "SELECT p.name, p.price, pb.booking_date, pb.total_amount 
              FROM package_bookings pb 
              JOIN packages p ON pb.package_id = p.id 
              WHERE pb.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $booking_info['details'] = $row;
        $booking_info['title'] = $row['name'] . " Package";
    }
}

// Generate unique transaction ID
$transaction_id = 'rzp_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5cc5;
            --secondary-color: #e74c3c;
            --accent-color: #f7f9fc;
            --border-color: #e0e0e0;
            --text-color: #333;
            --success-color: #2ecc71;
        }
        body {
            background-color: #f5f7fa;
            color: var(--text-color);
            font-family: 'Segoe UI', Roboto, sans-serif;
        }
        .payment-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.08);
            background-color: #fff;
            overflow: hidden;
        }
        .payment-header {
            background-color: var(--primary-color);
            color: white;
            padding: 25px 30px;
            position: relative;
        }
        .payment-header h2 {
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }
        .payment-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .payment-body {
            display: flex;
            flex-wrap: wrap;
        }
        .payment-summary {
            flex: 1;
            min-width: 300px;
            background-color: var(--accent-color);
            padding: 30px;
            border-right: 1px solid var(--border-color);
        }
        .payment-methods {
            flex: 2;
            min-width: 400px;
            padding: 30px;
        }
        .order-summary {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .order-summary h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--primary-color);
            font-weight: 600;
        }
        .order-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .total-amount {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 18px;
        }
        .payment-steps {
            display: flex;
            margin-bottom: 25px;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 15px;
            right: -15px;
            width: 30px;
            height: 2px;
            background-color: var(--border-color);
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .step-text {
            font-size: 13px;
            color: #666;
        }
        .step.active .step-text {
            color: var(--primary-color);
            font-weight: 600;
        }
        .payment-method {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        .payment-method:hover {
            border-color: #bbb;
            transform: translateY(-2px);
        }
        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: rgba(44, 92, 197, 0.05);
        }
        .payment-method-logo {
            height: 40px;
            margin-right: 15px;
            object-fit: contain;
        }
        .payment-method-info {
            flex: 1;
        }
        .payment-method-title {
            font-weight: 600;
            margin: 0;
        }
        .payment-method-description {
            font-size: 13px;
            color: #777;
            margin: 4px 0 0;
        }
        .payment-method-details {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        .payment-method.selected .payment-method-details {
            display: block;
        }
        
        .btn-pay {
            background-color: var(--secondary-color);
            color: white;
            padding: 14px 20px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            font-weight: 600;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-pay:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.2);
        }
        .form-control {
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 92, 197, 0.1);
        }
        .form-label {
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: #555;
        }
        
        /* Razorpay Modal Styles */
        .razorpay-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .razorpay-modal.active {
            display: flex;
            opacity: 1;
        }
        .razorpay-content {
            background-color: white;
            border-radius: 12px;
            width: 95%;
            max-width: 500px;
            height: 85vh;
            max-height: 650px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            transform: scale(0.9);
            transition: transform 0.3s;
        }
        .razorpay-modal.active .razorpay-content {
            transform: scale(1);
        }
        .razorpay-header {
            background-color: #022a72;
            color: white;
            padding: 18px 20px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .razorpay-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }
        .razorpay-close {
            background: none;
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .razorpay-close:hover {
            opacity: 1;
        }
        .razorpay-body {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }
        .razorpay-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .razorpay-method {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .razorpay-method:hover {
            background-color: #f9f9f9;
        }
        .razorpay-method.active {
            border-color: #6366f1;
            background-color: #f5f7ff;
        }
        .razorpay-method-card {
            display: flex;
            align-items: center;
        }
        .razorpay-method-card img {
            width: 32px;
            height: 32px;
            margin-right: 12px;
            object-fit: contain;
        }
        .razorpay-method-form {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .razorpay-method.active .razorpay-method-form {
            display: block;
        }
        .razorpay-footer {
            padding: 15px 25px;
            border-top: 1px solid #eee;
            background-color: #f9fafb;
        }
        .razorpay-btn {
            background-color: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
        }
        .razorpay-btn:hover {
            background-color: #4f46e5;
        }
        .razorpay-secured {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 12px;
        }
        .razorpay-secured i {
            margin-right: 6px;
            color: #10b981;
        }
        .razorpay-logo {
            text-align: center;
            margin-top: 15px;
        }
        .razorpay-logo img {
            height: 20px;
            opacity: 0.7;
        }
        
        /* Success Modal Styles */
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s;
        }
        .success-modal.active {
            display: flex;
            opacity: 1;
        }
        .success-content {
            background-color: white;
            border-radius: 12px;
            padding: 40px 30px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s;
        }
        .success-modal.active .success-content {
            transform: scale(1) translateY(0);
        }
        .success-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background-color: #ecfdf5;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        .success-icon i {
            font-size: 40px;
            color: var(--success-color);
        }
        .success-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #111827;
        }
        .success-message {
            color: #6b7280;
            margin-bottom: 25px;
        }
        .success-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .success-btn:hover {
            background-color: #1e429f;
            transform: translateY(-2px);
        }
        
        /* Payment Processing Animation */
        .payment-processing {
            display: inline-block;
            position: relative;
            width: 16px;
            height: 16px;
            margin-right: 8px;
        }
        .payment-processing:after {
            content: " ";
            display: block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #fff;
            border-color: #fff transparent #fff transparent;
            animation: payment-processing 1.2s linear infinite;
        }
        @keyframes payment-processing {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        /* Receipt styles */
        .receipt {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-title {
            font-size: 22px;
            color: var(--primary-color);
            margin: 0;
        }
        .receipt-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin: 5px 0 0;
        }
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e5e7eb;
        }
        .receipt-info-item {
            flex: 1;
        }
        .receipt-info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .receipt-info-value {
            font-weight: 500;
        }
        .receipt-items {
            margin-bottom: 25px;
        }
        .receipt-total {
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        /* Add responsive styles for the payment interface */
        @media (max-width: 991px) {
            .payment-body {
                flex-direction: column;
            }
            .payment-summary {
                min-width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            .payment-methods {
                min-width: 100%;
            }
        }

        @media (max-width: 767px) {
            .payment-container {
                margin: 20px auto;
                border-radius: 8px;
            }
            .payment-header {
                padding: 20px;
            }
            .payment-summary, 
            .payment-methods {
                padding: 20px;
            }
            .payment-method {
                padding: 15px;
            }
            .receipt {
                padding: 20px;
            }
            .razorpay-content {
                width: 100%;
                height: 100%;
                max-height: none;
                border-radius: 0;
            }
            .razorpay-header {
                border-radius: 0;
            }
            .success-content {
                width: 95%;
                padding: 30px 20px;
            }
        }

        @media (max-width: 575px) {
            .payment-steps {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            .step {
                display: flex;
                align-items: center;
                width: 100%;
            }
            .step:not(:last-child):after {
                display: none;
            }
            .step-number {
                margin-bottom: 0;
                margin-right: 10px;
            }
            .order-detail {
                flex-direction: column;
                gap: 2px;
                margin-bottom: 15px;
            }
            .total-amount {
                flex-direction: column;
                gap: 5px;
                text-align: left;
            }
            .payment-method {
                flex-direction: column;
                align-items: flex-start;
            }
            .payment-method-logo {
                margin-right: 0;
                margin-bottom: 10px;
            }
            .form-control {
                font-size: 14px;
                padding: 10px;
            }
            .receipt-info {
                flex-direction: column;
                gap: 15px;
            }
            .receipt-info-item {
                width: 100%;
            }
            .receipt-total {
                flex-direction: column;
                gap: 5px;
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h2>Complete Your Payment</h2>
                <p>Secure payment processing for your booking</p>
            </div>
            
            <div class="payment-body">
                <div class="payment-summary">
                    <div class="payment-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-text">Booking</div>
                        </div>
                        <div class="step active">
                            <div class="step-number">2</div>
                            <div class="step-text">Payment</div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-text">Confirmation</div>
                        </div>
                    </div>
                    
                    <div class="order-summary">
                        <h3>Order Summary</h3>
                        <div class="order-detail">
                            <span>Booking ID:</span>
                            <span><?php echo htmlspecialchars($order_id); ?></span>
                        </div>
                        <div class="order-detail">
                            <span>Item:</span>
                            <span><?php echo isset($booking_info['title']) ? htmlspecialchars($booking_info['title']) : '-'; ?></span>
                        </div>
                        <?php if ($booking_type == 'room' && isset($booking_info['details'])) { ?>
                        <div class="order-detail">
                            <span>Check-in:</span>
                            <span><?php echo htmlspecialchars($booking_info['details']['check_in_date']); ?></span>
                        </div>
                        <div class="order-detail">
                            <span>Check-out:</span>
                            <span><?php echo htmlspecialchars($booking_info['details']['check_out_date']); ?></span>
                        </div>
                        <?php } elseif (($booking_type == 'tour' || $booking_type == 'package') && isset($booking_info['details'])) { ?>
                        <div class="order-detail">
                            <span>Date:</span>
                            <span><?php echo htmlspecialchars($booking_info['details']['booking_date']); ?></span>
                        </div>
                        <?php } ?>
                        <div class="total-amount">
                            <span>Total Amount:</span>
                            <span>₹<?php echo number_format(floatval($amount), 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="receipt">
                        <div class="receipt-header">
                            <h4 class="receipt-title">Payment Receipt</h4>
                            <p class="receipt-subtitle">This will be generated after payment</p>
                        </div>
                        <div class="receipt-info">
                            <div class="receipt-info-item">
                                <div class="receipt-info-label">Transaction ID</div>
                                <div class="receipt-info-value"><?php echo $transaction_id; ?></div>
                            </div>
                            <div class="receipt-info-item">
                                <div class="receipt-info-label">Date</div>
                                <div class="receipt-info-value"><?php echo date('d M Y'); ?></div>
                            </div>
                        </div>
                        <div class="receipt-items">
                            <div class="order-detail">
                                <span><?php echo isset($booking_info['title']) ? htmlspecialchars($booking_info['title']) : 'Booking'; ?></span>
                                <span>₹<?php echo number_format(floatval($amount), 2); ?></span>
                            </div>
                        </div>
                        <div class="receipt-total">
                            <span>Total Paid</span>
                            <span>₹<?php echo number_format(floatval($amount), 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <h3 class="mb-4">Select Payment Method</h3>
                    
                    <div class="payment-method selected" data-method="card">
                        <img src="https://cdn.razorpay.com/static/assets/method-card.png" alt="Credit/Debit Card" class="payment-method-logo">
                        <div class="payment-method-info">
                            <h5 class="payment-method-title">Credit/Debit Card</h5>
                            <p class="payment-method-description">Pay securely using your card</p>
                        </div>
                        <div class="payment-method-details">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Card Number</label>
                                    <input type="text" class="form-control" placeholder="Enter your card number">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" placeholder="MM/YY">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" placeholder="123">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name on Card</label>
                                <input type="text" class="form-control" placeholder="Enter name as on card">
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-method" data-method="upi">
                        <img src="https://cdn.razorpay.com/static/assets/method-upi.png" alt="UPI" class="payment-method-logo">
                        <div class="payment-method-info">
                            <h5 class="payment-method-title">UPI</h5>
                            <p class="payment-method-description">Pay using UPI apps like Google Pay, PhonePe</p>
                        </div>
                        <div class="payment-method-details">
                            <div class="mb-3">
                                <label class="form-label">UPI ID</label>
                                <input type="text" class="form-control" placeholder="username@upi">
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-method" data-method="netbanking">
                        <img src="https://cdn.razorpay.com/static/assets/method-netbanking.png" alt="Netbanking" class="payment-method-logo">
                        <div class="payment-method-info">
                            <h5 class="payment-method-title">Net Banking</h5>
                            <p class="payment-method-description">Pay directly from your bank account</p>
                        </div>
                        <div class="payment-method-details">
                            <div class="mb-3">
                                <label class="form-label">Select Bank</label>
                                <select class="form-control">
                                    <option>HDFC Bank</option>
                                    <option>ICICI Bank</option>
                                    <option>State Bank of India</option>
                                    <option>Axis Bank</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-method" data-method="wallet">
                        <img src="https://cdn.razorpay.com/static/assets/method-wallet.png" alt="Wallet" class="payment-method-logo">
                        <div class="payment-method-info">
                            <h5 class="payment-method-title">Wallet</h5>
                            <p class="payment-method-description">Pay using digital wallets</p>
                        </div>
                        <div class="payment-method-details">
                            <div class="mb-3">
                                <label class="form-label">Select Wallet</label>
                                <select class="form-control">
                                    <option>Paytm</option>
                                    <option>PhonePe</option>
                                    <option>Amazon Pay</option>
                                    <option>Mobikwik</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button id="pay-button" class="btn-pay">Pay ₹<?php echo number_format(floatval($amount), 2); ?></button>
                    
                    <div class="razorpay-secured mt-4 text-center">
                        <i class="fas fa-lock"></i> <span>Secured by Razorpay. Your payment information is secure.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Remove the confirmation modal and keep only the success modal -->
    <div id="success-modal" class="success-modal">
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="success-title">Payment Successful!</h3>
            <p class="success-message">Your payment of ₹<?php echo number_format(floatval($amount), 2); ?> has been processed successfully.</p>
            <button id="success-btn" class="success-btn">View My Bookings</button>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Store form data for submission
            const formData = {
                order_id: '<?php echo htmlspecialchars($order_id); ?>',
                amount: '<?php echo htmlspecialchars($amount); ?>',
                booking_type: '<?php echo htmlspecialchars($booking_type); ?>',
                booking_id: '<?php echo htmlspecialchars($booking_id); ?>',
                payment_method: 'card',
                transaction_id: '<?php echo $transaction_id; ?>'
            };
            
            // Handle payment method selection in main form
            $('.payment-method').click(function() {
                $('.payment-method').removeClass('selected');
                $(this).addClass('selected');
                formData.payment_method = $(this).data('method');
            });
            
            // Process payment directly when Pay button is clicked
            $('#pay-button').click(function(e) {
                e.preventDefault(); // Prevent any default button behavior
                
                // Get form field values based on payment method
                if (formData.payment_method === 'card') {
                    const cardNumber = $('.payment-method[data-method="card"] input[placeholder="Enter your card number"]').val();
                    if (cardNumber) {
                        formData.card_number = cardNumber;
                    } else {
                        formData.card_number = "4111111111111111"; // Default card number if empty
                    }
                } else if (formData.payment_method === 'upi') {
                    const upiId = $('.payment-method[data-method="upi"] input[placeholder="username@upi"]').val();
                    if (upiId) {
                        formData.upi_id = upiId;
                    } else {
                        formData.upi_id = "default@upi"; // Default UPI ID if empty
                    }
                }
                
                // Show success modal immediately
                $('#success-modal').addClass('active');
                
                // Process payment in background via AJAX
                $.ajax({
                    url: 'process_fake_payment.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json'
                });
                
                // Auto-redirect after 3 seconds
                setTimeout(function() {
                    window.location.href = 'my_bookings.php';
                }, 3000);
            });
            
            // Handle success button click - redirect to my bookings page
            $('#success-btn').click(function() {
                window.location.href = 'my_bookings.php';
            });
        });
    </script>
</body>
</html> 