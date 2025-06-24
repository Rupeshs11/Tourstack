<?php
// Start session
session_start();

// Initialize response array first thing
$response = [
    'status' => 'error',  // Default to error and update on success
    'message' => 'An error occurred while processing your request',
    'redirect' => 'my_bookings.php'
];

// Debug mode - leave enabled temporarily for troubleshooting
$debug_mode = true;

// Enable error reporting in debug mode
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    // Log request data for debugging
    error_log("POST data received: " . print_r($_POST, true));
    error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return JSON response for AJAX
    $response['status'] = 'error';
    $response['message'] = 'You must be logged in to book a package';
    $response['redirect'] = 'login.php';
    
    // Send response and exit
    handleResponse($response, $debug_mode);
    exit;
}

// Include database connection
require_once 'db_connection.php';

// Function to handle response (JSON or redirect)
function handleResponse($response, $debug_mode) {
    // Check if this is an AJAX request
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    
    // For debugging
    if ($debug_mode) {
        error_log("Is AJAX request: " . ($is_ajax ? 'yes' : 'no'));
        error_log("Response data: " . print_r($response, true));
    }
    
    // Return JSON response for AJAX requests, or redirect for regular requests
    if ($is_ajax) {
        // Ensure we have a clean output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set proper headers
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        // Send JSON response
        $json_response = json_encode($response);
        
        // Check for JSON encoding errors
        if ($json_response === false) {
            error_log("JSON encoding error: " . json_last_error_msg());
            // Try to send a simpler response
            echo json_encode(['status' => 'error', 'message' => 'Server error: Could not encode response']);
        } else {
            echo $json_response;
        }
        
        // For debugging
        if ($debug_mode) {
            error_log("JSON response sent: " . $json_response);
        }
        
        exit;
    } else {
        // For non-AJAX requests, set session message and redirect
        if ($response['status'] === 'success') {
            // Set success message in session
            $_SESSION['booking_message'] = $response['message'];
            $_SESSION['booking_status'] = 'success';
            header('Location: ' . ($response['redirect'] ?: 'my_bookings.php'));
            exit;
        } else {
            // Set error message in session
            $_SESSION['booking_message'] = $response['message'];
            $_SESSION['booking_status'] = 'error';
            header('Location: packages.php?error=' . urlencode($response['message']));
            exit;
        }
    }
}

// Function to ensure the table has all required columns
function ensureTableStructure($conn) {
    global $debug_mode;
    
    // Check if package_bookings table exists
    $result = $conn->query("SHOW TABLES LIKE 'package_bookings'");
    if ($result->num_rows == 0) {
        // Create the table if it doesn't exist
        $sql = "CREATE TABLE package_bookings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            package_id INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            booking_date DATE NOT NULL,
            number_of_guests INT(11) NOT NULL DEFAULT 1,
            special_requests TEXT NULL,
            total_amount DECIMAL(10,2) NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
            booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
            PRIMARY KEY (id)
        )";
        $conn->query($sql);
        
        if ($debug_mode) error_log("Created package_bookings table");
    }
}

// Ensure table structure is correct
ensureTableStructure($conn);

// Initialize variables
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$package_details = null;

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_SESSION['user_id'];
    $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
    $booking_date = isset($_POST['booking_date']) ? $_POST['booking_date'] : '';
    $adults = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
    $special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';
    $proceed_with_booking = isset($_POST['proceed_with_booking']) ? $_POST['proceed_with_booking'] === 'true' : false;
    
    // Validate input
    $errors = [];
    
    if (!$user_id) {
        $errors[] = "You must be logged in to book a package";
    }
    
    if (!$package_id) {
        $errors[] = "Invalid package selection";
    }
    
    if (!$booking_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
        $errors[] = "Invalid booking date format";
    }
    
    if ($adults < 1 || $adults > 10) {
        $errors[] = "Number of adults must be between 1 and 10";
    }
    
    if ($children < 0 || $children > 10) {
        $errors[] = "Number of children must be between 0 and 10";
    }
    
    // Fetch package details
    $package_details = null;
    if ($package_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $package_details = $result->fetch_assoc();
        } else {
            $errors[] = "Package not found or inactive";
        }
        $stmt->close();
    }
    
    // Check if booking date is in the future
    if (empty($errors) && $booking_date) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $package_date = new DateTime($booking_date);
        
        if ($package_date < $today) {
            $errors[] = "Booking date must be in the future";
        }
    }
    
    // Process if no errors
    if (empty($errors) && $package_details) {
        // Calculate total amount
        $total_guests = $adults + $children;
        $total_amount = $total_guests * $package_details['price'];
            
        try {
            // Insert booking
            $stmt = $conn->prepare("INSERT INTO package_bookings 
                    (user_id, package_id, booking_date, number_of_guests, special_requests, total_amount, payment_status, booking_status) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, 'pending', 'pending')");
            
            $stmt->bind_param("iisids", $user_id, $package_id, $booking_date, $total_guests, $special_requests, $total_amount);
            
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = "Your package has been booked successfully! A confirmation will be sent to your email.";
            } else {
                $response['status'] = 'error';
                $response['message'] = "Failed to book package: " . $stmt->error;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $response['status'] = 'error';
            $response['message'] = "Error: " . $e->getMessage();
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = empty($errors) ? "Unknown error occurred" : implode("<br>", $errors);
    }
    
    // Send response
    handleResponse($response, $debug_mode);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // If this is a GET request with id, just continue to display the booking form
    $package_id = intval($_GET['id']);
    
    // Get package details
    if ($package_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $package_details = $result->fetch_assoc();
        } else {
            $response['message'] = "Package not found or inactive";
        }
        $stmt->close();
    } else {
        $response['message'] = "Invalid package ID";
    }
} else {
    // Invalid request
    $response['message'] = "Invalid request method";
    handleResponse($response, $debug_mode);
    exit;
}

// Include header - this will only be reached for non-AJAX, non-redirect cases
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <?php if (isset($package_details)): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title h3"><?php echo htmlspecialchars($package_details['name']); ?></h1>
                        
                        <?php if (isset($_SESSION['booking_message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['booking_status'] == 'error' ? 'danger' : 'success'; ?>">
                                <?php echo $_SESSION['booking_message']; ?>
                                <?php if ($_SESSION['booking_status'] == 'success'): ?>
                                    <div class="mt-2">
                                        <a href="my_bookings.php" class="btn btn-primary">View My Bookings</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php 
                            // Clear the session messages after displaying
                            unset($_SESSION['booking_message']);
                            unset($_SESSION['booking_status']);
                            ?>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-5">
                                <img src="<?php echo !empty($package_details['image_path']) ? $package_details['image_path'] : 'images/packages/default-package.jpg'; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($package_details['name']); ?>">
                            </div>
                            <div class="col-md-7">
                                <p><?php echo nl2br(htmlspecialchars($package_details['description'])); ?></p>
                                <div class="package-details mt-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <p><i class="fas fa-map-marker-alt me-2"></i> <strong>Location:</strong> <?php echo htmlspecialchars($package_details['location'] ?? 'Various locations'); ?></p>
                                        </div>
                                        <div class="col-6">
                                            <p><i class="fas fa-clock me-2"></i> <strong>Duration:</strong> <?php echo htmlspecialchars($package_details['duration'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="col-6">
                                            <p><i class="fas fa-hotel me-2"></i> <strong>Accommodation:</strong> <?php echo htmlspecialchars($package_details['accommodation'] ?? 'Standard'); ?></p>
                                        </div>
                                        <div class="col-6">
                                            <p><i class="fas fa-utensils me-2"></i> <strong>Meals:</strong> <?php echo htmlspecialchars($package_details['meals'] ?? 'Not included'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="price-tag mt-3">
                                    <h3>₹<?php echo number_format($package_details['price'], 2); ?> <small>per person</small></h3>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!isset($_SESSION['booking_status']) || $_SESSION['booking_status'] != 'success'): ?>
                        <hr>
                        
                        <h3 class="h4 mb-4">Book This Package</h3>
                        
                        <form action="book_package.php?id=<?php echo $package_id; ?>" method="POST">
                            <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="booking_date" class="form-label">Booking Date</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="adults" class="form-label">Adults</label>
                                    <input type="number" class="form-control" id="adults" name="adults" min="1" max="10" value="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="children" class="form-label">Children</label>
                                    <input type="number" class="form-control" id="children" name="children" min="0" max="10" value="0">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="special_requests" class="form-label">Special Requests</label>
                                <textarea class="form-control" id="special_requests" name="special_requests" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Confirm Booking</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h4>Package Not Found</h4>
                    <p><?php echo $response['message'] ?? 'The requested package could not be found.'; ?></p>
                    <a href="packages.php" class="btn btn-primary mt-3">Browse All Packages</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="h5 mb-3">Why Book With Us</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Best price guarantee</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> No booking fees</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Instant confirmation</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Free cancellation policy</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> 24/7 customer service</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h3 class="h5 mb-3">Need Help?</h3>
                    <p>If you have any questions regarding your booking, please contact our customer support team.</p>
                    <p><strong>Phone:</strong> +1 (123) 456-7890</p>
                    <p><strong>Email:</strong> support@example.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calculate total price based on number of people
        const adultsInput = document.getElementById('adults');
        const childrenInput = document.getElementById('children');
        const pricePerPerson = <?php echo isset($package_details) ? $package_details['price'] : 0; ?>;
        
        if (adultsInput && childrenInput) {
            const updatePrice = function() {
                const adults = parseInt(adultsInput.value) || 0;
                const children = parseInt(childrenInput.value) || 0;
                const totalGuests = adults + children;
                const totalPrice = totalGuests * pricePerPerson;
                
                // Update price display if it exists
                const priceElement = document.querySelector('.price-tag h3');
                if (priceElement) {
                    priceElement.innerHTML = '₹' + totalPrice.toFixed(2) + ' <small>total</small>';
                }
            };
            
            adultsInput.addEventListener('change', updatePrice);
            childrenInput.addEventListener('change', updatePrice);
        }
    });
</script>

<!-- Add pending bookings check -->
<script src="js/pending_bookings.js"></script>

<?php include 'includes/footer.php'; ?>