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

// Debug mode - set to false for production, true for troubleshooting
// To enable debugging temporarily, add ?debug=1 to the URL
$debug_mode = true; // Debug mode enabled for troubleshooting

// Enable error reporting in debug mode
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Function to ensure the table has all required columns
function ensureTableStructure($conn) {
    global $debug_mode;
    
    // Check if tour_bookings table exists
    $result = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
    if ($result->num_rows == 0) {
        // Create the table if it doesn't exist
        $sql = "CREATE TABLE tour_bookings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            tour_id INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            booking_date DATE NOT NULL,
            people INT(11) NOT NULL DEFAULT 1,
            special_requests TEXT NULL,
            total_amount DECIMAL(10,2) NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
            booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
            PRIMARY KEY (id)
        )";
        $conn->query($sql);
        
        if ($debug_mode) error_log("Created tour_bookings table");
    }
    
    // Check if special_requests column exists
    $result = $conn->query("SHOW COLUMNS FROM tour_bookings LIKE 'special_requests'");
    if ($result->num_rows == 0) {
        if ($debug_mode) error_log("Adding special_requests column");
        $conn->query("ALTER TABLE tour_bookings ADD COLUMN special_requests TEXT NULL AFTER people");
    }
    
    // Check if total_amount column exists
    $result = $conn->query("SHOW COLUMNS FROM tour_bookings LIKE 'total_amount'");
    if ($result->num_rows == 0) {
        if ($debug_mode) error_log("Adding total_amount column");
        $conn->query("ALTER TABLE tour_bookings ADD COLUMN total_amount DECIMAL(10,2) NULL AFTER special_requests");
    }
    
    // Check if created_at column exists and has correct type
    $result = $conn->query("SHOW COLUMNS FROM tour_bookings LIKE 'created_at'");
    if ($result->num_rows == 0) {
        if ($debug_mode) error_log("Adding created_at column");
        $conn->query("ALTER TABLE tour_bookings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER tour_id");
    } else {
        $col_info = $result->fetch_assoc();
        if ($col_info['Type'] != 'timestamp') {
            if ($debug_mode) error_log("Fixing created_at column type");
            $conn->query("ALTER TABLE tour_bookings MODIFY created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
    }
    
    if ($debug_mode) {
        $columns = array();
        $result = $conn->query("DESCRIBE tour_bookings");
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        error_log("Tour bookings columns after check: " . implode(", ", $columns));
    }
}

// Ensure table structure is correct
ensureTableStructure($conn);

// Initialize variables
$tour_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$booking_date = '';
$people = 1;
$booking_message = '';
$booking_status = '';
$tour_details = null;
$booking_success = false;

// Get tour details
if ($tour_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tours WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $tour_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $tour_details = $result->fetch_assoc();
    } else {
        $booking_message = "Tour not found or inactive";
        $booking_status = "error";
    }
    $stmt->close();
} else {
    $booking_message = "Invalid tour ID";
    $booking_status = "error";
}

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($debug_mode) {
        error_log("POST data received: " . print_r($_POST, true));
    }
    
    // Get form data
    $user_id = $_SESSION['user_id'];
    $tour_id = isset($_POST['tour_id']) ? intval($_POST['tour_id']) : 0;
    $booking_date = isset($_POST['booking_date']) ? $_POST['booking_date'] : '';
    $people = isset($_POST['people']) ? intval($_POST['people']) : 1;
    $special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';
    $proceed_with_booking = isset($_POST['proceed_with_booking']) ? $_POST['proceed_with_booking'] === 'true' : false;
    
    $booking_message = "";
    $booking_status = "";
    
    // Validate input
    $errors = [];
    
    if (!$user_id) {
        $errors[] = "You must be logged in to book a tour";
    }
    
    if (!$tour_id) {
        $errors[] = "Invalid tour selection";
    }
    
    if (!$booking_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
        $errors[] = "Invalid booking date format";
    }
    
    if ($people < 1 || $people > 20) {
        $errors[] = "Number of people must be between 1 and 20";
    }
    
    if (strlen($special_requests) > 500) {
        $errors[] = "Special requests must be less than 500 characters";
    }
    
    // Check if booking date is in the future
    if (empty($errors) && $booking_date) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $tour_date = new DateTime($booking_date);
        
        if ($tour_date < $today) {
            $errors[] = "Booking date must be in the future";
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Calculate total amount
        $total_amount = $tour_details['price'] * $people;
        
        try {
            // Verify database connection
            if (!$conn) {
                throw new Exception("Database connection failed");
            }
            
            // Log debugging info for troubleshooting
            if ($debug_mode) {
                error_log("Booking attempt - Tour ID: $tour_id, User ID: $user_id, Date: $booking_date, People: $people");
                error_log("Tour details: " . print_r($tour_details, true));
            }
            
            // Direct SQL approach with simpler query
            $sql = "INSERT INTO tour_bookings 
                    (user_id, tour_id, booking_date, people, special_requests, total_amount, payment_status, booking_status) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, 'pending', 'pending')";
            
            // Use prepared statement for more security and reliability
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("iisids", 
                $user_id, $tour_id, $booking_date, 
                $people, $special_requests, $total_amount);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $booking_success = true;
            $booking_message = "Your tour has been booked successfully! A confirmation will be sent to your email.";
            $booking_status = "success";
            
            // Check if this is an AJAX request
            $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                      (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            
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
                echo json_encode([
                    'status' => 'success',
                    'message' => $booking_message,
                    'redirect' => 'my_bookings.php'
                ]);
                exit;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $booking_message = "Error processing booking: " . $e->getMessage();
            $booking_status = "error";
            
            // Check if this is an AJAX request
            $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                      (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            
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
                echo json_encode([
                    'status' => 'error',
                    'message' => $booking_message
                ]);
                exit;
            }
            
            if ($debug_mode) {
                error_log("Booking error: " . $e->getMessage());
            }
        }
    } else {
        $booking_message = implode("<br>", $errors);
        $booking_status = "error";
        
        // Check if this is an AJAX request
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
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
            echo json_encode([
                'status' => 'error',
                'message' => $booking_message
            ]);
            exit;
        }
    }
}

// Include header
include 'includes/header.php';
?>

<main class="main-content py-3">
    <div class="container container-narrow">
        <div class="page-header pb-2 mb-3 border-bottom">
            <h1 class="h4 mb-0">Book Tour</h1>
        </div>

        <?php if (!empty($booking_message)): ?>
            <div class="alert alert-<?php echo $booking_status == 'success' ? 'success' : 'danger'; ?> py-2 px-3 mb-3">
                <?php echo $booking_message; ?>
            </div>
            
            <?php if ($booking_status == 'success'): ?>
                <div class="text-center mt-3 mb-3">
                    <a href="my_bookings.php" class="btn btn-primary py-2 px-4">View My Bookings</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="card border-0 shadow mb-3">
            <div class="card-body p-4">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-warning py-2 px-3 mb-0">
                        <p class="mb-0">You need to <a href="login.php" class="fw-bold">login</a> to book this tour.</p>
                    </div>
                <?php elseif(!$tour_details): ?>
                    <div class="alert alert-danger py-2 px-3 mb-0">
                        <p class="mb-0">Tour not found or inactive. Please <a href="tours.php" class="fw-bold">browse available tours</a>.</p>
                    </div>
                <?php else: ?>
                    <form method="post" action="" class="booking-form" id="booking_form">
                        <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">
                        
                        <div class="row g-4">
                            <div class="col-md-5">
                                <div class="d-flex mb-3">
                                    <div class="tour-image me-3" style="width:120px; height:120px;">
                                        <img src="<?php echo $tour_details['image_path']; ?>" alt="<?php echo $tour_details['name']; ?>" class="img-fluid rounded h-100 w-100 object-fit-cover">
                                    </div>
                                    <div class="tour-details">
                                        <h5 class="mb-2"><?php echo $tour_details['name']; ?></h5>
                                        <p class="mb-1"><i class="fas fa-clock me-2"></i> <?php echo $tour_details['duration']; ?></p>
                                        <?php if(!empty($tour_details['location'])): ?>
                                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i> <?php echo $tour_details['location']; ?></p>
                                        <?php endif; ?>
                                        <p class="mb-1"><i class="fas fa-tag me-2"></i> ₹<?php echo number_format($tour_details['price'], 2); ?>/person</p>
                                    </div>
                                </div>
                                
                                <div class="booking-date mb-3">
                                    <h6 class="mb-2">Tour Date</h6>
                                    <div class="date-picker">
                                        <label for="booking_date" class="form-label">Select Date</label>
                                        <input type="date" class="form-control" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-7">
                                <h6 class="mb-2">Booking Details</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6">
                                        <label for="number_of_people" class="form-label">Number of People</label>
                                        <input type="number" class="form-control" id="number_of_people" name="people" min="1" max="<?php echo $tour_details['max_people'] ?? 20; ?>" value="1" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="special_requests" class="form-label">Special Requests</label>
                                        <textarea class="form-control" id="special_requests" name="special_requests" rows="2" placeholder="Any special requirements or preferences?"></textarea>
                                    </div>
                                </div>
                                
                                <div class="price-summary p-3 rounded bg-light mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Price Summary</h6>
                                            <p class="mb-0">₹<?php echo number_format($tour_details['price'], 2); ?> × <span id="people_count">1</span> people</p>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0 text-primary">₹<span id="total_price"><?php echo number_format($tour_details['price'], 2); ?></span></h5>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary py-2 px-4">Book Now</button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
    /* Main styling for the booking page */
    .container-narrow {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .page-header {
        border-color: #f0f0f0;
    }
    
    .card {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05) !important;
        border: none !important;
        border-radius: 12px !important;
        overflow: hidden;
        }
        
        .booking-form {
        background-color: #fff;
        border-radius: 8px;
    }
    
    .form-label {
        font-weight: 500;
        color: #333;
        margin-bottom: 0.3rem;
    }
    
    .form-control, .form-select {
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        border-color: #ddd;
        height: calc(2.5rem);
    }
    
    textarea.form-control {
        height: auto;
    }
    
    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.15);
        border-color: #e74c3c;
    }
    
    .btn-primary { 
        background-color: #e74c3c !important; 
        border-color: #e74c3c !important; 
        font-weight: 500;
        border-radius: 6px;
    }
    
    .btn-primary:hover { 
        background-color: #c0392b !important; 
        border-color: #c0392b !important; 
    }
    
    .tour-image {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    }
    
    .object-fit-cover {
        object-fit: cover;
    }
    
    .price-summary {
        border: 1px solid #eee;
        border-radius: 8px !important;
    }
    
    .tour-details p {
        margin-bottom: 0.5rem;
        color: #495057;
        font-size: 0.9rem;
    }
    
    .tour-details p i {
        width: 18px;
        color: #e74c3c;
    }
    
    .alert {
        border-radius: 8px;
    }
    
    .main-content {
        padding-bottom: 3rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const peopleInput = document.getElementById('number_of_people');
        const totalPriceElement = document.getElementById('total_price');
        const peopleCountElement = document.getElementById('people_count');
        const tourPrice = <?php echo $tour_details['price']; ?>;
        
        // Update total price when number of people changes
        if (peopleInput && totalPriceElement) {
            peopleInput.addEventListener('change', updatePriceSummary);
            peopleInput.addEventListener('input', updatePriceSummary);
            
            function updatePriceSummary() {
                const people = parseInt(peopleInput.value);
                const total = tourPrice * people;
                
                if (peopleCountElement) peopleCountElement.textContent = people;
                totalPriceElement.textContent = total.toFixed(2);
            }
        }
        
        // Add form validation - prevent submission if date is empty or invalid
        const bookingForm = document.getElementById('booking_form');
        const bookingDateInput = document.getElementById('booking_date');
        
        if (bookingForm && bookingDateInput) {
            bookingForm.addEventListener('submit', function(e) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                const selectedDate = new Date(bookingDateInput.value);
                selectedDate.setHours(0, 0, 0, 0);
                
                if (!bookingDateInput.value) {
                    e.preventDefault();
                    alert('Please select a booking date');
                    bookingDateInput.focus();
                } else if (selectedDate < today) {
                    e.preventDefault();
                    alert('Please select a future date');
                    bookingDateInput.focus();
                }
            });
        }
    });
</script>

<!-- Add pending bookings check -->
<script src="js/pending_bookings.js"></script>

<?php include 'includes/footer.php'; ?>
</body>
</html> 