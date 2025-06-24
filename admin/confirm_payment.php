<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// Include database connection
require_once '../db_connection.php';

// Process payment confirmation if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $booking_type = isset($_POST['booking_type']) ? $_POST['booking_type'] : '';
    
    if ($payment_id > 0 && $booking_id > 0 && !empty($booking_type)) {
        // Update the payment record
        $update_payment = $conn->prepare("UPDATE payments SET status = 'completed', admin_confirmed = 1, admin_confirmed_date = NOW() WHERE id = ?");
        $update_payment->bind_param("i", $payment_id);
        
        // Update the booking record
        $table_name = '';
        switch ($booking_type) {
            case 'room': $table_name = 'room_bookings'; break;
            case 'tour': $table_name = 'tour_bookings'; break;
            case 'package': $table_name = 'package_bookings'; break;
            default: $table_name = ''; break;
        }
        
        if (!empty($table_name)) {
            $update_booking = $conn->prepare("UPDATE $table_name SET booking_status = 'confirmed' WHERE id = ?");
            $update_booking->bind_param("i", $booking_id);
            
            // Execute both updates
            if ($update_payment->execute() && $update_booking->execute()) {
                $_SESSION['admin_message'] = "Payment #$payment_id for $booking_type booking #$booking_id has been confirmed.";
                $_SESSION['admin_message_type'] = "success";
            } else {
                $_SESSION['admin_message'] = "Error confirming payment: " . $conn->error;
                $_SESSION['admin_message_type'] = "danger";
            }
        } else {
            $_SESSION['admin_message'] = "Invalid booking type.";
            $_SESSION['admin_message_type'] = "danger";
        }
    } else {
        $_SESSION['admin_message'] = "Invalid payment data.";
        $_SESSION['admin_message_type'] = "danger";
    }
    
    // Redirect to avoid form resubmission
    header("Location: confirm_payment.php");
    exit;
}

// Get all pending payments
$pending_payments = [];
$query = "SELECT p.*, 
         CASE
             WHEN p.booking_type = 'room' THEN (SELECT name FROM rooms WHERE id = (SELECT room_id FROM room_bookings WHERE id = p.booking_id))
             WHEN p.booking_type = 'tour' THEN (SELECT name FROM tours WHERE id = (SELECT tour_id FROM tour_bookings WHERE id = p.booking_id))
             WHEN p.booking_type = 'package' THEN (SELECT name FROM packages WHERE id = (SELECT package_id FROM package_bookings WHERE id = p.booking_id))
             ELSE 'Unknown'
         END as item_name,
         u.name as customer_name, u.email as customer_email
         FROM payments p
         LEFT JOIN users u ON (
             SELECT user_id FROM room_bookings WHERE id = p.booking_id AND p.booking_type = 'room'
             UNION
             SELECT user_id FROM tour_bookings WHERE id = p.booking_id AND p.booking_type = 'tour'
             UNION
             SELECT user_id FROM package_bookings WHERE id = p.booking_id AND p.booking_type = 'package'
         ) = u.id
         WHERE p.status = 'pending' AND p.admin_confirmed = 0
         ORDER BY p.created_at DESC";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_payments[] = $row;
    }
}

// Get recent confirmed payments (for reference)
$confirmed_payments = [];
$confirmed_query = "SELECT p.*, 
                  CASE
                      WHEN p.booking_type = 'room' THEN (SELECT name FROM rooms WHERE id = (SELECT room_id FROM room_bookings WHERE id = p.booking_id))
                      WHEN p.booking_type = 'tour' THEN (SELECT name FROM tours WHERE id = (SELECT tour_id FROM tour_bookings WHERE id = p.booking_id))
                      WHEN p.booking_type = 'package' THEN (SELECT name FROM packages WHERE id = (SELECT package_id FROM package_bookings WHERE id = p.booking_id))
                      ELSE 'Unknown'
                  END as item_name,
                  u.name as customer_name, u.email as customer_email
                  FROM payments p
                  LEFT JOIN users u ON (
                      SELECT user_id FROM room_bookings WHERE id = p.booking_id AND p.booking_type = 'room'
                      UNION
                      SELECT user_id FROM tour_bookings WHERE id = p.booking_id AND p.booking_type = 'tour'
                      UNION
                      SELECT user_id FROM package_bookings WHERE id = p.booking_id AND p.booking_type = 'package'
                  ) = u.id
                  WHERE p.status = 'completed' AND p.admin_confirmed = 1
                  ORDER BY p.admin_confirmed_date DESC
                  LIMIT 10";

$confirmed_result = $conn->query($confirmed_query);
if ($confirmed_result && $confirmed_result->num_rows > 0) {
    while ($row = $confirmed_result->fetch_assoc()) {
        $confirmed_payments[] = $row;
    }
}

// Include header
include 'header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Confirm Payments</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Confirm Payments</li>
    </ol>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['admin_message_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['admin_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['admin_message']); unset($_SESSION['admin_message_type']); endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-money-bill-wave me-1"></i>
            Pending Payments
        </div>
        <div class="card-body">
            <?php if (empty($pending_payments)): ?>
                <div class="alert alert-info">
                    No pending payments to confirm at this time.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="pendingPaymentsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Booking</th>
                                <th>Item</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Payment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td>
                                    <?php echo ucfirst($payment['booking_type']); ?> #<?php echo $payment['booking_id']; ?>
                                    <a href="../view_booking.php?type=<?php echo $payment['booking_type']; ?>&id=<?php echo $payment['booking_id']; ?>" target="_blank" class="text-decoration-none">
                                        <i class="fas fa-external-link-alt ms-1 small"></i>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($payment['item_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['customer_name']); ?><br>
                                    <small><?php echo htmlspecialchars($payment['customer_email']); ?></small>
                                </td>
                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <form method="post" action="confirm_payment.php" class="d-inline">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                        <input type="hidden" name="booking_id" value="<?php echo $payment['booking_id']; ?>">
                                        <input type="hidden" name="booking_type" value="<?php echo $payment['booking_type']; ?>">
                                        <button type="submit" name="confirm_payment" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to confirm this payment?');">
                                            <i class="fas fa-check me-1"></i> Confirm
                                        </button>
                                    </form>
                                    
                                    <button class="btn btn-info btn-sm view-details" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal" 
                                        data-payment-id="<?php echo $payment['id']; ?>"
                                        data-payment-method="<?php echo $payment['payment_method']; ?>"
                                        data-payment-date="<?php echo $payment['payment_date']; ?>"
                                        data-payment-amount="<?php echo $payment['amount']; ?>"
                                        data-transaction-id="<?php echo $payment['payment_id']; ?>">
                                        <i class="fas fa-eye me-1"></i> Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            Recently Confirmed Payments
        </div>
        <div class="card-body">
            <?php if (empty($confirmed_payments)): ?>
                <div class="alert alert-info">
                    No confirmed payments to display.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="confirmedPaymentsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Booking</th>
                                <th>Item</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Confirmed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($confirmed_payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td>
                                    <?php echo ucfirst($payment['booking_type']); ?> #<?php echo $payment['booking_id']; ?>
                                </td>
                                <td><?php echo htmlspecialchars($payment['item_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['customer_name']); ?><br>
                                    <small><?php echo htmlspecialchars($payment['customer_email']); ?></small>
                                </td>
                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($payment['admin_confirmed_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-4 fw-bold">Payment ID:</div>
                    <div class="col-8" id="modal-payment-id"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-bold">Transaction ID:</div>
                    <div class="col-8" id="modal-transaction-id"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-bold">Amount:</div>
                    <div class="col-8" id="modal-amount"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-bold">Payment Method:</div>
                    <div class="col-8" id="modal-payment-method"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-bold">Payment Date:</div>
                    <div class="col-8" id="modal-payment-date"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables
        if (document.getElementById('pendingPaymentsTable')) {
            $('#pendingPaymentsTable').DataTable({
                order: [[6, 'desc']]
            });
        }
        
        if (document.getElementById('confirmedPaymentsTable')) {
            $('#confirmedPaymentsTable').DataTable({
                order: [[5, 'desc']]
            });
        }
        
        // Payment details modal
        const viewButtons = document.querySelectorAll('.view-details');
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const paymentId = this.getAttribute('data-payment-id');
                const transactionId = this.getAttribute('data-transaction-id');
                const amount = this.getAttribute('data-payment-amount');
                const paymentMethod = this.getAttribute('data-payment-method');
                const paymentDate = this.getAttribute('data-payment-date');
                
                document.getElementById('modal-payment-id').textContent = paymentId;
                document.getElementById('modal-transaction-id').textContent = transactionId;
                document.getElementById('modal-amount').textContent = '₹' + parseFloat(amount).toFixed(2);
                document.getElementById('modal-payment-method').textContent = paymentMethod.replace('_', ' ');
                
                // Format date
                const date = new Date(paymentDate);
                const formattedDate = date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('modal-payment-date').textContent = formattedDate;
            });
        });
    });
</script>

<?php include 'footer.php'; ?> 