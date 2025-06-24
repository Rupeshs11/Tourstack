<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Handle form submissions
$success_message = '';
$error_message = '';

// Get all users for dropdown
$users_query = "SELECT id, name, email FROM users ORDER BY name ASC";
$users_result = $conn->query($users_query);

// Get all tours for dropdown
$tours_query = "SELECT id, name, price, max_people FROM tours WHERE status = 'active' ORDER BY name ASC";
$tours_result = $conn->query($tours_query);

// Get all rooms for dropdown
$rooms_query = "SELECT id, name, price, capacity FROM rooms WHERE status = 'active' ORDER BY name ASC";
$rooms_result = $conn->query($rooms_query);

// Handle tour booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_tour'])) {
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $tour_id = filter_var($_POST['tour_id'], FILTER_SANITIZE_NUMBER_INT);
    $booking_date = filter_var($_POST['booking_date'], FILTER_SANITIZE_STRING);
    $people = filter_var($_POST['people'], FILTER_SANITIZE_NUMBER_INT);
    $special_requests = filter_var($_POST['special_requests'], FILTER_SANITIZE_STRING);
    
    // Validate input
    if (empty($user_id) || empty($tour_id) || empty($booking_date) || empty($people)) {
        $error_message = "All fields are required";
    } else {
        // Get tour price
        $tour_query = "SELECT price, max_people FROM tours WHERE id = ?";
        $stmt = $conn->prepare($tour_query);
        $stmt->bind_param("i", $tour_id);
        $stmt->execute();
        $tour_result = $stmt->get_result();
        $tour_data = $tour_result->fetch_assoc();
        $stmt->close();
        
        if ($people > $tour_data['max_people']) {
            $error_message = "Maximum people allowed for this tour is " . $tour_data['max_people'];
        } else {
            // Calculate total amount
            $total_amount = $tour_data['price'] * $people;
            
            // Insert booking
            $insert_query = "INSERT INTO tour_bookings (user_id, tour_id, booking_date, people, total_amount, special_requests, payment_status, booking_status, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, 'pending', 'confirmed', NOW())";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iisids", $user_id, $tour_id, $booking_date, $people, $total_amount, $special_requests);
            
            if ($stmt->execute()) {
                $success_message = "Tour booking created successfully!";
                
                // Refresh page to show new data and prevent resubmission
                header("Location: admin_new_booking.php?success=tour");
                exit;
            } else {
                $error_message = "Error creating booking: " . $conn->error;
            }
            
            $stmt->close();
        }
    }
}

// Handle room booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_room'])) {
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $room_id = filter_var($_POST['room_id'], FILTER_SANITIZE_NUMBER_INT);
    $check_in = filter_var($_POST['check_in'], FILTER_SANITIZE_STRING);
    $check_out = filter_var($_POST['check_out'], FILTER_SANITIZE_STRING);
    $adults = filter_var($_POST['adults'], FILTER_SANITIZE_NUMBER_INT);
    $children = filter_var($_POST['children'], FILTER_SANITIZE_NUMBER_INT);
    $special_requests = filter_var($_POST['special_requests'], FILTER_SANITIZE_STRING);
    
    // Validate input
    if (empty($user_id) || empty($room_id) || empty($check_in) || empty($check_out) || empty($adults)) {
        $error_message = "All required fields are required";
    } else {
        // Calculate number of nights
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $interval = $check_in_date->diff($check_out_date);
        $nights = $interval->days;
        
        if ($nights < 1) {
            $error_message = "Check-out date must be after check-in date";
        } else {
            // Get room price
            $room_query = "SELECT price, capacity FROM rooms WHERE id = ?";
            $stmt = $conn->prepare($room_query);
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $room_result = $stmt->get_result();
            $room_data = $room_result->fetch_assoc();
            $stmt->close();
            
            if (($adults + $children) > $room_data['capacity']) {
                $error_message = "Maximum occupancy for this room is " . $room_data['capacity'] . " guests";
            } else {
                // Calculate total amount
                $total_amount = $room_data['price'] * $nights;
                
                // Check if room is already booked for these dates
                $availability_query = "SELECT id FROM room_bookings 
                                      WHERE room_id = ? 
                                      AND ((check_in_date <= ? AND check_out_date >= ?) 
                                      OR (check_in_date <= ? AND check_out_date >= ?) 
                                      OR (check_in_date >= ? AND check_out_date <= ?))
                                      AND booking_status != 'cancelled'";
                $stmt = $conn->prepare($availability_query);
                $stmt->bind_param("issssss", $room_id, $check_out, $check_in, $check_in, $check_in, $check_in, $check_out);
                $stmt->execute();
                $availability_result = $stmt->get_result();
                
                if ($availability_result->num_rows > 0) {
                    $error_message = "Room is not available for the selected dates";
                } else {
                    // Insert booking
                    $insert_query = "INSERT INTO room_bookings (user_id, room_id, check_in_date, check_out_date, adults, children, total_amount, special_requests, payment_status, booking_status, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'confirmed', NOW())";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("iissiids", $user_id, $room_id, $check_in, $check_out, $adults, $children, $total_amount, $special_requests);
                    
                    if ($stmt->execute()) {
                        $success_message = "Room booking created successfully!";
                        
                        // Refresh page to show new data and prevent resubmission
                        header("Location: admin_new_booking.php?success=room");
                        exit;
                    } else {
                        $error_message = "Error creating booking: " . $conn->error;
                    }
                }
                
                $stmt->close();
            }
        }
    }
}

// Display success message from URL parameter
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'tour') {
        $success_message = "Tour booking created successfully!";
    } elseif ($_GET['success'] == 'room') {
        $success_message = "Room booking created successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Booking - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .tab-container {
            margin-bottom: 20px;
        }
        .tab-nav {
            display: flex;
            background-color: #f5f5f5;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }
        .tab-btn {
            padding: 12px 20px;
            cursor: pointer;
            background: #f0f0f0;
            border: none;
            flex: 1;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
        }
        .tab-btn.active {
            background: #e74c3c;
            color: white;
        }
        .tab-content {
            display: none;
            padding: 20px;
            background: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tab-content.active {
            display: block;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
            padding: 0 10px;
            margin-bottom: 15px;
        }
        .section-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff6600;
            color: #343a40;
            font-size: 1.5rem;
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Create New Booking</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-hotel"></i>
                <span>TOUR STACK</span>
            </div>
            <div class="sidebar-nav">
                <div class="sidebar-nav-title">Main</div>
                <a href="admin_dashboard.php" class="sidebar-nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_customers.php" class="sidebar-nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="admin_bookings.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="admin_new_booking.php" class="sidebar-nav-item active">
                    <i class="fas fa-calendar-plus"></i>
                    <span>New Booking</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-nav-title">Management</div>
                <a href="simple_tours.php" class="sidebar-nav-item">
                    <i class="fas fa-route"></i>
                    <span>Tours</span>
                </a>
                <a href="simple_rooms.php" class="sidebar-nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="simple_packages.php" class="sidebar-nav-item">
                    <i class="fas fa-box"></i>
                    <span>Packages</span>
                </a>
                <a href="admin_users.php" class="sidebar-nav-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Users</span>
                </a>
            </div>
        </aside>
        
        <main class="admin-main">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-container">
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="tour-booking">Tour Booking</button>
                    <button class="tab-btn" data-tab="room-booking">Room Booking</button>
                </div>
                
                <!-- Tour Booking Tab -->
                <div class="tab-content active" id="tour-booking">
                    <h2 class="section-title">Create New Tour Booking</h2>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="user_id">Customer</label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">Select Customer</option>
                                    <?php while($user = $users_result->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tour_id">Tour</label>
                                <select name="tour_id" id="tour_id" class="form-control" required>
                                    <option value="">Select Tour</option>
                                    <?php while($tour = $tours_result->fetch_assoc()): ?>
                                        <option value="<?php echo $tour['id']; ?>" data-price="<?php echo $tour['price']; ?>" data-max="<?php echo $tour['max_people']; ?>">
                                            <?php echo htmlspecialchars($tour['name']); ?> (₹<?php echo number_format($tour['price'], 2); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_date">Tour Date</label>
                                <input type="date" id="booking_date" name="booking_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="people">Number of People</label>
                                <input type="number" id="people" name="people" class="form-control" min="1" value="1" required>
                                <small id="max-people-info" class="form-text text-muted">Maximum allowed: <span id="max-people">-</span></small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="special_requests">Special Requests</label>
                            <textarea id="special_requests" name="special_requests" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Total Amount</label>
                            <div class="price-display">₹<span id="tour-total-price">0.00</span></div>
                        </div>
                        
                        <button type="submit" name="book_tour" class="btn btn-primary">Create Tour Booking</button>
                    </form>
                </div>
                
                <!-- Room Booking Tab -->
                <div class="tab-content" id="room-booking">
                    <h2 class="section-title">Create New Room Booking</h2>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="user_id_room">Customer</label>
                                <select name="user_id" id="user_id_room" class="form-control" required>
                                    <option value="">Select Customer</option>
                                    <?php 
                                    // Reset the result pointer
                                    $users_result->data_seek(0);
                                    while($user = $users_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="room_id">Room</label>
                                <select name="room_id" id="room_id" class="form-control" required>
                                    <option value="">Select Room</option>
                                    <?php while($room = $rooms_result->fetch_assoc()): ?>
                                        <option value="<?php echo $room['id']; ?>" data-price="<?php echo $room['price']; ?>" data-capacity="<?php echo $room['capacity']; ?>">
                                            <?php echo htmlspecialchars($room['name']); ?> (₹<?php echo number_format($room['price'], 2); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="check_in">Check-in Date</label>
                                <input type="date" id="check_in" name="check_in" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="check_out">Check-out Date</label>
                                <input type="date" id="check_out" name="check_out" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adults">Adults</label>
                                <input type="number" id="adults" name="adults" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="form-group">
                                <label for="children">Children</label>
                                <input type="number" id="children" name="children" class="form-control" min="0" value="0">
                                <small id="max-capacity-info" class="form-text text-muted">Maximum capacity: <span id="max-capacity">-</span></small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="special_requests_room">Special Requests</label>
                            <textarea id="special_requests_room" name="special_requests" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nights</label>
                                <div id="nights-count">0</div>
                            </div>
                            <div class="form-group">
                                <label>Total Amount</label>
                                <div class="price-display">₹<span id="room-total-price">0.00</span></div>
                            </div>
                        </div>
                        
                        <button type="submit" name="book_room" class="btn btn-primary">Create Room Booking</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    tabBtns.forEach(function(btn) {
                        btn.classList.remove('active');
                    });
                    
                    tabContents.forEach(function(content) {
                        content.classList.remove('active');
                    });
                    
                    // Add active class to selected tab
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Tour booking calculations
            const tourSelect = document.getElementById('tour_id');
            const peopleInput = document.getElementById('people');
            const maxPeopleSpan = document.getElementById('max-people');
            const tourTotalPriceSpan = document.getElementById('tour-total-price');
            
            function updateTourPrice() {
                if (tourSelect.value) {
                    const selectedOption = tourSelect.options[tourSelect.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price'));
                    const maxPeople = parseInt(selectedOption.getAttribute('data-max'));
                    const people = parseInt(peopleInput.value);
                    
                    maxPeopleSpan.textContent = maxPeople;
                    peopleInput.setAttribute('max', maxPeople);
                    
                    if (people > maxPeople) {
                        peopleInput.value = maxPeople;
                    }
                    
                    const totalPrice = price * parseInt(peopleInput.value);
                    tourTotalPriceSpan.textContent = totalPrice.toFixed(2);
                } else {
                    maxPeopleSpan.textContent = '-';
                    tourTotalPriceSpan.textContent = '0.00';
                }
            }
            
            tourSelect.addEventListener('change', updateTourPrice);
            peopleInput.addEventListener('input', updateTourPrice);
            
            // Room booking calculations
            const roomSelect = document.getElementById('room_id');
            const checkInInput = document.getElementById('check_in');
            const checkOutInput = document.getElementById('check_out');
            const adultsInput = document.getElementById('adults');
            const childrenInput = document.getElementById('children');
            const maxCapacitySpan = document.getElementById('max-capacity');
            const nightsCountDiv = document.getElementById('nights-count');
            const roomTotalPriceSpan = document.getElementById('room-total-price');
            
            function updateRoomPrice() {
                if (roomSelect.value) {
                    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price'));
                    const capacity = parseInt(selectedOption.getAttribute('data-capacity'));
                    
                    maxCapacitySpan.textContent = capacity;
                    
                    // Calculate nights
                    let nights = 0;
                    if (checkInInput.value && checkOutInput.value) {
                        const checkIn = new Date(checkInInput.value);
                        const checkOut = new Date(checkOutInput.value);
                        const timeDiff = checkOut.getTime() - checkIn.getTime();
                        nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    }
                    
                    if (nights < 0) {
                        nights = 0;
                        checkOutInput.value = checkInInput.value;
                    }
                    
                    nightsCountDiv.textContent = nights;
                    
                    // Calculate total price
                    const totalPrice = price * nights;
                    roomTotalPriceSpan.textContent = totalPrice.toFixed(2);
                } else {
                    maxCapacitySpan.textContent = '-';
                    nightsCountDiv.textContent = '0';
                    roomTotalPriceSpan.textContent = '0.00';
                }
            }
            
            roomSelect.addEventListener('change', updateRoomPrice);
            checkInInput.addEventListener('change', function() {
                // Update check-out min date
                const checkInDate = new Date(this.value);
                const nextDay = new Date(checkInDate);
                nextDay.setDate(nextDay.getDate() + 1);
                
                const yyyy = nextDay.getFullYear();
                const mm = String(nextDay.getMonth() + 1).padStart(2, '0');
                const dd = String(nextDay.getDate()).padStart(2, '0');
                const formattedDate = `${yyyy}-${mm}-${dd}`;
                
                checkOutInput.min = formattedDate;
                
                // If check-out date is before check-in date, update it
                if (checkOutInput.value && new Date(checkOutInput.value) <= new Date(this.value)) {
                    checkOutInput.value = formattedDate;
                }
                
                updateRoomPrice();
            });
            checkOutInput.addEventListener('change', updateRoomPrice);
            adultsInput.addEventListener('input', updateRoomPrice);
            childrenInput.addEventListener('input', updateRoomPrice);
        });
    </script>
</body>

</html> 