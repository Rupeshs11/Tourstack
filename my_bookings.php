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

// First check if necessary tables exist, create them if they don't
$tables_to_check = ['tour_bookings', 'room_bookings', 'package_bookings'];
foreach ($tables_to_check as $table) {
    $table_exists = $conn->query("SHOW TABLES LIKE '$table'");
    
    if (!$table_exists || $table_exists->num_rows == 0) {
        // Table doesn't exist, create it
        $create_table_query = "";
        
        if ($table == 'tour_bookings') {
            $create_table_query = "CREATE TABLE tour_bookings (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) UNSIGNED NOT NULL,
                tour_id INT(11) UNSIGNED NOT NULL,
                booking_date DATE,
                people INT(3) NOT NULL DEFAULT 1,
                total_amount DECIMAL(10,2) NOT NULL,
                booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
                special_requests TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else if ($table == 'room_bookings') {
            $create_table_query = "CREATE TABLE room_bookings (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) UNSIGNED NOT NULL,
                room_id INT(11) UNSIGNED NOT NULL,
                check_in_date DATE NOT NULL,
                check_out_date DATE NOT NULL,
                adults INT(2) NOT NULL DEFAULT 1,
                children INT(2) NOT NULL DEFAULT 0,
                total_nights INT(3) NOT NULL DEFAULT 1,
                total_amount DECIMAL(10,2) NOT NULL,
                booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
                special_requests TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else if ($table == 'package_bookings') {
            $create_table_query = "CREATE TABLE package_bookings (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) UNSIGNED NOT NULL,
                package_id INT(11) UNSIGNED NOT NULL,
                booking_date DATE,
                number_of_guests INT(3) NOT NULL DEFAULT 1,
                total_amount DECIMAL(10,2) NOT NULL,
                booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
                special_requests TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        }
        
        if (!empty($create_table_query)) {
            if ($conn->query($create_table_query) === FALSE) {
                $_SESSION['booking_error'] = "Error creating $table table: " . $conn->error;
            }
        }
    }
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get all bookings for the user
$tour_bookings_query = "SELECT tb.*, 
                  t.name as tour_name, t.price as tour_price, t.image_path as tour_image
                  FROM tour_bookings tb 
                  LEFT JOIN tours t ON tb.tour_id = t.id 
                  WHERE tb.user_id = ?
                  ORDER BY tb.created_at DESC";
                  
$room_bookings_query = "SELECT rb.*, 
                  r.name as room_name, r.price as room_price, r.image_path as room_image
                  FROM room_bookings rb 
                  LEFT JOIN rooms r ON rb.room_id = r.id 
                  WHERE rb.user_id = ?
                  ORDER BY rb.created_at DESC";

$package_bookings_query = "SELECT pb.*, 
                  p.name as package_name, p.price as package_price, p.image_path as package_image, p.duration as package_duration
                  FROM package_bookings pb 
                  LEFT JOIN packages p ON pb.package_id = p.id 
                  WHERE pb.user_id = ?
                  ORDER BY pb.booking_date DESC";

// Get tour bookings                  
$stmt = $conn->prepare($tour_bookings_query);
if ($stmt === false) {
    $_SESSION['booking_error'] = "Failed to prepare tour bookings query: " . $conn->error;
    $tour_bookings_result = null;
} else {
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        $_SESSION['booking_error'] = "Error retrieving tour bookings: " . $stmt->error;
        $tour_bookings_result = null;
    } else {
        $tour_bookings_result = $stmt->get_result();
    }
    $stmt->close();
}

// Get room bookings
$stmt = $conn->prepare($room_bookings_query);
if ($stmt === false) {
    $_SESSION['booking_error'] = "Failed to prepare room bookings query: " . $conn->error;
    $room_bookings_result = null;
} else {
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        $_SESSION['booking_error'] = "Error retrieving room bookings: " . $stmt->error;
        $room_bookings_result = null;
    } else {
        $room_bookings_result = $stmt->get_result();
    }
    $stmt->close();
}

// Get package bookings
$stmt = $conn->prepare($package_bookings_query);
if ($stmt === false) {
    $_SESSION['booking_error'] = "Failed to prepare package bookings query: " . $conn->error;
    $package_bookings_result = null;
} else {
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        $_SESSION['booking_error'] = "Error retrieving package bookings: " . $stmt->error;
        $package_bookings_result = null;
    } else {
        $package_bookings_result = $stmt->get_result();
    }
    $stmt->close();
}

$has_bookings = ($tour_bookings_result && $tour_bookings_result->num_rows > 0) || 
                ($room_bookings_result && $room_bookings_result->num_rows > 0) || 
                ($package_bookings_result && $package_bookings_result->num_rows > 0);

// Include header
include 'includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header mb-4">
            <h1>My Bookings</h1>
        </div>
        
        <div class="bookings-container">
            <?php if ($has_bookings): ?>
                <?php if ($tour_bookings_result && $tour_bookings_result->num_rows > 0): ?>
                    <h2 class="section-title mb-3">Tour Bookings</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
                        <?php while ($booking = $tour_bookings_result->fetch_assoc()): ?>
                            <div class="col">
                                <div class="booking-card h-100">
                                    <div class="booking-image square-img">
                                        <img src="<?php echo $booking['tour_image'] ?? 'images/placeholder.jpg'; ?>" alt="<?php echo $booking['tour_name']; ?>">
                                        <div class="booking-type">Tour</div>
                                    </div>
                                    <div class="booking-details p-3">
                                        <h3 class="fs-5 mb-2"><?php echo $booking['tour_name']; ?></h3>
                                        <div class="booking-info">
                                            <p class="mb-1 small"><i class="fas fa-calendar me-1"></i> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?></p>
                                            <p class="mb-1 small"><i class="fas fa-users me-1"></i> <?php echo $booking['people']; ?> people</p>
                                            <p class="mb-1 small"><i class="fas fa-rupee-sign me-1"></i> <?php echo number_format($booking['total_amount'], 2); ?></p>
                                            <div class="booking-status-container d-flex justify-content-between align-items-center">
                                                <span class="status-badge status-<?php echo strtolower($booking['booking_status']); ?>">
                                                    <i class="status-icon fas <?php 
                                                    $status = strtolower($booking['booking_status']);
                                                    if ($status == 'pending') echo 'fa-hourglass-half';
                                                    else if ($status == 'confirmed') echo 'fa-check-circle'; 
                                                    else if ($status == 'completed') echo 'fa-flag-checkered';
                                                    else if ($status == 'cancelled') echo 'fa-ban';
                                                    else echo 'fa-info-circle';
                                                    ?>"></i>
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                                
                                                <?php if ($booking['payment_status'] == 'paid'): ?>
                                                <span class="status-badge payment-paid">
                                                    <i class="status-icon fas fa-check-circle"></i>
                                                    Paid
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="booking-actions mt-2 d-flex justify-content-between">
                                                <a href="view_booking.php?type=tour&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                                <?php if ($booking['payment_status'] == 'pending' && $booking['booking_status'] != 'cancelled'): ?>
                                                <a href="payment.php?type=tour&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-success">Pay Now</a>
                                                <?php endif; ?>
                                                <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                                <a href="cancel_tour_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($room_bookings_result && $room_bookings_result->num_rows > 0): ?>
                    <h2 class="section-title mb-3">Room Bookings</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
                        <?php while ($booking = $room_bookings_result->fetch_assoc()): ?>
                            <div class="col">
                                <div class="booking-card h-100">
                                    <div class="booking-image square-img">
                                        <img src="<?php echo $booking['room_image'] ?? 'images/placeholder.jpg'; ?>" alt="<?php echo $booking['room_name']; ?>">
                                        <div class="booking-type">Room</div>
                                    </div>
                                    <div class="booking-details p-3">
                                        <h3 class="fs-5 mb-2"><?php echo $booking['room_name']; ?></h3>
                                        <div class="booking-info">
                                            <p class="mb-1 small"><i class="fas fa-calendar-check me-1"></i> <?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></p>
                                            <p class="mb-1 small"><i class="fas fa-calendar-times me-1"></i> <?php echo date('d M Y', strtotime($booking['check_out_date'])); ?></p>
                                            <p class="mb-1 small"><i class="fas fa-moon me-1"></i> <?php echo $booking['total_nights']; ?> nights</p>
                                            <p class="mb-1 small"><i class="fas fa-rupee-sign me-1"></i> <?php echo number_format($booking['total_amount'], 2); ?></p>
                                            <div class="booking-status-container d-flex justify-content-between align-items-center">
                                                <span class="status-badge status-<?php echo strtolower($booking['booking_status']); ?>">
                                                    <i class="status-icon fas <?php 
                                                    $status = strtolower($booking['booking_status']);
                                                    if ($status == 'pending') echo 'fa-hourglass-half';
                                                    else if ($status == 'confirmed') echo 'fa-check-circle'; 
                                                    else if ($status == 'completed') echo 'fa-flag-checkered';
                                                    else if ($status == 'cancelled') echo 'fa-ban';
                                                    else echo 'fa-info-circle';
                                                    ?>"></i>
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                                
                                                <?php if ($booking['payment_status'] == 'paid'): ?>
                                                <span class="status-badge payment-paid">
                                                    <i class="status-icon fas fa-check-circle"></i>
                                                    Paid
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="booking-actions mt-2 d-flex justify-content-between">
                                                <a href="view_booking.php?type=room&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                                <?php if ($booking['payment_status'] == 'pending' && $booking['booking_status'] != 'cancelled'): ?>
                                                <a href="payment.php?type=room&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-success">Pay Now</a>
                                                <?php endif; ?>
                                                <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                                <a href="cancel_booking.php?type=room&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($package_bookings_result && $package_bookings_result->num_rows > 0): ?>
                    <h2 class="section-title mb-3">Package Bookings</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
                        <?php while ($booking = $package_bookings_result->fetch_assoc()): ?>
                            <div class="col">
                                <div class="booking-card h-100">
                                    <div class="booking-image square-img">
                                        <img src="<?php echo $booking['package_image'] ?? 'images/placeholder.jpg'; ?>" alt="<?php echo $booking['package_name']; ?>">
                                        <div class="booking-type">Package</div>
                                    </div>
                                    <div class="booking-details p-3">
                                        <h3 class="fs-5 mb-2"><?php echo $booking['package_name']; ?></h3>
                                        <div class="booking-info">
                                            <p class="mb-1 small"><i class="fas fa-calendar me-1"></i> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?></p>
                                            <p class="mb-1 small"><i class="fas fa-users me-1"></i> <?php echo $booking['number_of_guests']; ?> guests</p>
                                            <p class="mb-1 small"><i class="fas fa-clock me-1"></i> <?php echo $booking['package_duration']; ?> days</p>
                                            <p class="mb-1 small"><i class="fas fa-rupee-sign me-1"></i> <?php echo number_format($booking['total_amount'], 2); ?></p>
                                            <div class="booking-status-container d-flex justify-content-between align-items-center">
                                                <span class="status-badge status-<?php echo strtolower($booking['booking_status']); ?>">
                                                    <i class="status-icon fas <?php 
                                                    $status = strtolower($booking['booking_status']);
                                                    if ($status == 'pending') echo 'fa-hourglass-half';
                                                    else if ($status == 'confirmed') echo 'fa-check-circle'; 
                                                    else if ($status == 'completed') echo 'fa-flag-checkered';
                                                    else if ($status == 'cancelled') echo 'fa-ban';
                                                    else echo 'fa-info-circle';
                                                    ?>"></i>
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                                
                                                <?php if ($booking['payment_status'] == 'paid'): ?>
                                                <span class="status-badge payment-paid">
                                                    <i class="status-icon fas fa-check-circle"></i>
                                                    Paid
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="booking-actions mt-2 d-flex justify-content-between">
                                                <a href="view_booking.php?type=package&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                                <?php if ($booking['payment_status'] == 'pending' && $booking['booking_status'] != 'cancelled'): ?>
                                                <a href="payment.php?type=package&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-success">Pay Now</a>
                                                <?php endif; ?>
                                                <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                                <a href="cancel_package_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="far fa-calendar-times"></i>
                    <h3>No bookings found</h3>
                    <p>You haven't made any bookings yet. Explore our <a href="tours.php">tours</a>, <a href="rooms.php">rooms</a>, and <a href="packages.php">vacation packages</a> to start planning your adventure!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Display success message with SweetAlert if it exists
        <?php if (isset($_SESSION['booking_success'])): ?>
            Swal.fire({
                title: 'Success!',
                text: '<?php echo addslashes($_SESSION['booking_success']); ?>',
                icon: 'success',
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['booking_success']); ?>
        <?php endif; ?>
        
        // Display error message with SweetAlert if it exists
        <?php if (isset($_SESSION['booking_error'])): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?php echo addslashes($_SESSION['booking_error']); ?>',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['booking_error']); ?>
        <?php endif; ?>
        
        // Handle booking cancellation
        const cancelButtons = document.querySelectorAll('.cancel-booking');
        cancelButtons.forEach(button => {
            button.addEventListener('click', function(e) {
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
        });
    });
</script>

<style>
    .booking-card {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: none;
        height: 100%;
        display: flex;
        flex-direction: column;
        max-width: 280px;
        margin: 0 auto;
    }
    
    .booking-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(231, 76, 60, 0.15);
    }
    
    .square-img {
        position: relative;
        width: 100%;
        padding-top: 75%; /* 4:3 aspect ratio, more compact than 1:1 */
        overflow: hidden;
    }
    
    .square-img img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .booking-type {
        position: absolute;
        top: 12px;
        right: 12px;
        background-color: #e74c3c;
        color: white;
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 600;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .booking-details {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        padding: 1.25rem !important;
        background: white;
    }
    
    .booking-details h3 {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #2c3e50;
        margin-bottom: 0.75rem !important;
    }
    
    .booking-info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .booking-info p {
        margin-bottom: 0.5rem !important;
        font-size: 0.85rem !important;
        color: #555;
    }
    
    .booking-info p i {
        color: #e74c3c;
        width: 18px;
        margin-right: 5px;
    }
    
    .booking-status-container {
        display: flex;
        justify-content: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed rgba(231, 76, 60, 0.2);
    }
    
    .status-badge {
        font-size: 0.9rem;
        padding: 8px 16px;
        border-radius: 30px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        min-width: 120px;
        justify-content: center;
    }
    
    .status-badge:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }
    
    .status-icon {
        margin-right: 8px;
        font-size: 1.1rem;
    }
    
    .status-pending {
        background: linear-gradient(135deg, #FFEFBA, #FFFFFF);
        color: #d35400;
        border: none;
    }
    
    .status-confirmed {
        background: linear-gradient(135deg, #d4fc79, #96e6a1);
        color: #145721;
        border: none;
    }
    
    .status-completed {
        background: linear-gradient(135deg, #2193b0, #6dd5ed);
        color: #fff;
        border: none;
    }
    
    .status-cancelled {
        background: linear-gradient(135deg, #ee9ca7, #ffdde1);
        color: #721c24;
        border: none;
    }
    
    .status-paid {
        background: linear-gradient(135deg, #a8ff78, #78ffd6);
        color: #0f5132;
        border: none;
    }
    
    .section-title {
        font-size: 1.2rem;
        border-bottom: 2px solid #e74c3c;
        padding-bottom: 5px;
        display: inline-block;
        margin-bottom: 1rem !important;
    }
    
    .btn-sm {
        padding: 0.15rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .no-bookings {
        text-align: center;
        padding: 2rem 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 1.5rem 0;
    }
    
    .no-bookings i {
        font-size: 3rem;
        color: #ced4da;
        margin-bottom: 0.75rem;
    }
    
    /* Add responsive grid adjustments */
    @media (min-width: 768px) {
        .row-cols-md-2 > * {
            flex: 0 0 auto;
            width: 33.333333%;
        }
    }
    
    @media (min-width: 992px) {
        .row-cols-lg-3 > * {
            flex: 0 0 auto;
            width: 25%;
        }
    }
    
    @media (max-width: 767px) {
        .booking-card {
            max-width: 100%;
        }
        .status-badge {
            width: 100%;
            max-width: 200px;
        }
    }
    
    /* Add consistent spacing */
    .bookings-container h2 {
        margin-top: 1.5rem;
    }
    
    .bookings-container .row {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }
    
    .bookings-container .col {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        margin-bottom: 1rem;
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 