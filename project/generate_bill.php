<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'db_connection.php';

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

// Determine the table name based on booking type
$table_name = '';
switch ($booking_type) {
    case 'tour':
        $table_name = 'tour_bookings';
        $item_table = 'tours';
        $item_id_field = 'tour_id';
        $title = "Tour Booking Invoice";
        break;
    case 'room':
        $table_name = 'room_bookings';
        $item_table = 'rooms';
        $item_id_field = 'room_id';
        $title = "Room Booking Invoice";
        break;
    case 'package':
        $table_name = 'package_bookings';
        $item_table = 'packages';
        $item_id_field = 'package_id';
        $title = "Package Booking Invoice";
        break;
}

// Get booking details
$stmt = $conn->prepare("SELECT * FROM $table_name WHERE id = ? AND user_id = ?");
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

// Get item details (room, tour, or package)
$stmt = $conn->prepare("SELECT * FROM $item_table WHERE id = ?");
$stmt->bind_param("i", $booking[$item_id_field]);
$stmt->execute();
$item_result = $stmt->get_result();
$item = $item_result->fetch_assoc();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get payment details
$stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? AND booking_type = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("is", $booking_id, $booking_type);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();

// Generate invoice number
$invoice_number = 'INV-' . date('Ymd') . '-' . $booking_id;

// Generate invoice date (payment date)
$invoice_date = isset($payment['created_at']) ? date('d M Y', strtotime($payment['created_at'])) : date('d M Y');

// Include header
include 'includes/header.php';
?>

<div class="main-content py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-light py-3">
                        <h1 class="h4 mb-0">Invoice</h1>
                        <div>
                            <button id="print-btn" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <a href="view_booking.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4" id="invoice-content">
                        <div class="invoice-header d-flex justify-content-between mb-4">
                            <div>
                                <h2 class="h3">TOUR STACK</h2>
                                <p class="mb-0">123 Hotel Street</p>
                                <p class="mb-0">Tourism City, 12345</p>
                                <p class="mb-0">Phone: +123 456 7890</p>
                                <p class="mb-0">Email: info@tourstack.com</p>
                            </div>
                            <div class="text-end">
                                <h3 class="h4 text-primary"><?php echo $title; ?></h3>
                                <p class="mb-0"><strong>Invoice #:</strong> <?php echo $invoice_number; ?></p>
                                <p class="mb-0"><strong>Date:</strong> <?php echo $invoice_date; ?></p>
                                <p class="mb-0"><strong>Payment ID:</strong> <?php echo $payment['payment_id'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4 class="h6">Billed to:</h4>
                                <p class="mb-0"><strong><?php echo htmlspecialchars($user['name']); ?></strong></p>
                                <p class="mb-0">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                                <?php if (!empty($user['phone'])): ?>
                                <p class="mb-0">Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <h4 class="h6">Booking Details:</h4>
                                <p class="mb-0"><strong>Booking ID:</strong> <?php echo $booking_id; ?></p>
                                <p class="mb-0"><strong>Booking Type:</strong> <?php echo ucfirst($booking_type); ?></p>
                                <?php if ($booking_type == 'room'): ?>
                                <p class="mb-0"><strong>Check-in:</strong> <?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></p>
                                <p class="mb-0"><strong>Check-out:</strong> <?php echo date('d M Y', strtotime($booking['check_out_date'])); ?></p>
                                <?php elseif ($booking_type == 'tour'): ?>
                                <p class="mb-0"><strong>Tour Date:</strong> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?></p>
                                <?php elseif ($booking_type == 'package'): ?>
                                <p class="mb-0"><strong>Start Date:</strong> <?php echo date('d M Y', strtotime($booking['start_date'])); ?></p>
                                <p class="mb-0"><strong>Duration:</strong> <?php echo $booking['duration']; ?> days</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($booking_type == 'room'): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo $booking['total_nights']; ?> nights stay</small>
                                        </td>
                                        <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-center"><?php echo $booking['total_nights']; ?> nights</td>
                                        <td class="text-end">$<?php echo number_format($item['price'] * $booking['total_nights'], 2); ?></td>
                                    </tr>
                                    <?php elseif ($booking_type == 'tour'): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted">Tour booking for <?php echo $booking['people']; ?> people</small>
                                        </td>
                                        <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-center"><?php echo $booking['people']; ?> people</td>
                                        <td class="text-end">$<?php echo number_format($item['price'] * $booking['people'], 2); ?></td>
                                    </tr>
                                    <?php elseif ($booking_type == 'package'): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted">Package booking for <?php echo $booking['number_of_guests']; ?> guests</small>
                                        </td>
                                        <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-center"><?php echo $booking['number_of_guests']; ?> guests</td>
                                        <td class="text-end">$<?php echo number_format($item['price'] * $booking['number_of_guests'], 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Subtotal:</th>
                                        <td class="text-end">$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    </tr>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Tax:</th>
                                        <td class="text-end">Included</td>
                                    </tr>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Grand Total:</th>
                                        <td class="text-end fw-bold">$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-8">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h5 class="h6 mb-2">Payment Information</h5>
                                        <p class="mb-0"><strong>Payment Status:</strong> <span class="badge bg-success">Paid</span></p>
                                        <p class="mb-0"><strong>Payment Date:</strong> <?php echo $invoice_date; ?></p>
                                        <p class="mb-0"><strong>Payment Method:</strong> Razorpay</p>
                                    </div>
                                </div>
                                <p class="mt-3 small text-muted">
                                    <strong>Note:</strong> This is an electronically generated invoice and does not require a physical signature.
                                </p>
                            </div>
                            <div class="col-md-4 text-center text-md-end">
                                <div class="mt-4 pt-2">
                                    <p class="mb-1">Authorized by</p>
                                    <img src="images/signature.png" alt="Signature" width="120" height="60">
                                    <p class="mb-0"><strong>Tour Stack Management</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Print invoice functionality
        document.getElementById('print-btn').addEventListener('click', function() {
            const printContent = document.getElementById('invoice-content').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .invoice-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
                    hr { border: 1px solid #ddd; margin: 20px 0; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    table, th, td { border: 1px solid #ddd; }
                    th, td { padding: 10px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    .text-end { text-align: right; }
                    .text-center { text-align: center; }
                    @media print {
                        button { display: none !important; }
                    }
                </style>
                <div class="invoice-print">
                    ${printContent}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 