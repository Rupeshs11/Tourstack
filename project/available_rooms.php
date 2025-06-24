<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);
$user_name = $logged_in ? $_SESSION['user_name'] : '';

// Check if search parameters exist
if (!isset($_SESSION['search_params']) || !isset($_SESSION['available_rooms'])) {
    header("Location: index.php");
    exit;
}

$search_params = $_SESSION['search_params'];
$available_rooms = $_SESSION['available_rooms'];

// Function to calculate number of nights
function calculateNights($check_in, $check_out) {
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $interval = $check_in_date->diff($check_out_date);
    return $interval->days;
}

$num_nights = calculateNights($search_params['check_in'], $search_params['check_out']);

// Format dates for display
$check_in_display = date("d M Y", strtotime($search_params['check_in']));
$check_out_display = date("d M Y", strtotime($search_params['check_out']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rooms - TOUR STACK</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .search-summary {
            background-color: #f5f5f5;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
        }
        
        .search-criteria {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .search-criteria div {
            display: flex;
            flex-direction: column;
        }
        
        .search-criteria label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .search-criteria span {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .modify-search-btn {
            padding: 10px 20px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .modify-search-btn:hover {
            background-color: #555;
        }
        
        .available-rooms-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .available-rooms-title {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .no-rooms-message {
            text-align: center;
            margin: 50px 0;
            color: #666;
        }
        
        .room-result {
            display: flex;
            margin-bottom: 30px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .room-image {
            width: 40%;
            position: relative;
        }
        
        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .room-details {
            width: 60%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .room-title {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.5rem;
            color: #333;
        }
        
        .room-description {
            margin-bottom: 15px;
            line-height: 1.6;
            flex-grow: 1;
        }
        
        .room-amenities {
            margin-bottom: 15px;
        }
        
        .room-amenities span {
            display: inline-block;
            background-color: #f1f1f1;
            padding: 5px 10px;
            margin-right: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            font-size: 0.9rem;
        }
        
        .booking-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .price-info {
            display: flex;
            flex-direction: column;
        }
        
        .price-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .price-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ff6600;
        }
        
        .price-total {
            font-size: 0.9rem;
            color: #333;
        }
        
        .book-now-btn {
            background-color: #ff6600;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .book-now-btn:hover {
            background-color: #e55c00;
        }
        
        @media (max-width: 768px) {
            .room-result {
                flex-direction: column;
            }
            
            .room-image, .room-details {
                width: 100%;
            }
            
            .search-summary {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-criteria {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <h1>TOUR STACK</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="rooms.php" class="active">Rooms</a></li>
                <li><a href="facilities.php">Facilities</a></li>
                <li><a href="packages.php">Packages</a></li>
                <li><a href="tours.php">Tours</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <?php if($logged_in): ?>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            <?php else: ?>
                <button class="login-btn" onclick="openLoginModal()">Login</button>
                <button class="register-btn" onclick="openRegisterModal()">Register</button>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <div class="available-rooms-container">
            <h1 class="available-rooms-title">Available Rooms</h1>
            
            <div class="search-summary">
                <div class="search-criteria">
                    <div>
                        <label>Check-in</label>
                        <span><?php echo $check_in_display; ?></span>
                    </div>
                    <div>
                        <label>Check-out</label>
                        <span><?php echo $check_out_display; ?></span>
                    </div>
                    <div>
                        <label>Guests</label>
                        <span><?php echo $search_params['adults']; ?> Adult(s), <?php echo $search_params['children']; ?> Child(ren)</span>
                    </div>
                    <div>
                        <label>Duration</label>
                        <span><?php echo $num_nights; ?> Night(s)</span>
                    </div>
                </div>
                <a href="index.php" class="modify-search-btn">Modify Search</a>
            </div>
            
            <?php if(empty($available_rooms)): ?>
            <div class="no-rooms-message">
                <h2>No rooms available for your search criteria</h2>
                <p>Please try different dates or number of guests.</p>
            </div>
            <?php else: ?>
                <?php foreach($available_rooms as $room): ?>
                <div class="room-result">
                    <div class="room-image">
                        <img src="<?php echo htmlspecialchars($room['image_path']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                    </div>
                    <div class="room-details">
                        <h2 class="room-title"><?php echo htmlspecialchars($room['name']); ?></h2>
                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                        
                        <div class="room-amenities">
                            <?php 
                            $amenities = explode(',', $room['amenities']);
                            foreach($amenities as $amenity): 
                                $amenity = trim($amenity);
                                if (!empty($amenity)):
                            ?>
                            <span><?php echo htmlspecialchars($amenity); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        
                        <div class="booking-details">
                            <div class="price-info">
                                <span class="price-label">Price per night</span>
                                <span class="price-value">₹<?php echo htmlspecialchars($room['price']); ?></span>
                                <span class="price-total">Total: ₹<?php echo htmlspecialchars($room['price'] * $num_nights); ?> for <?php echo $num_nights; ?> night(s)</span>
                            </div>
                            
                            <?php if($logged_in): ?>
                            <a href="book_room.php?id=<?php echo $room['id']; ?>&check_in=<?php echo urlencode($search_params['check_in']); ?>&check_out=<?php echo urlencode($search_params['check_out']); ?>&adults=<?php echo urlencode($search_params['adults']); ?>&children=<?php echo urlencode($search_params['children']); ?>" class="book-now-btn">Book Now</a>
                            <?php else: ?>
                            <button class="book-now-btn" data-room-id="<?php echo $room['id']; ?>">Book Now</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Beach Road, Coastal City</p>
                <p><i class="fas fa-phone"></i> +91 1234567890</p>
                <p><i class="fas fa-envelope"></i> info@tourstack.com</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="rooms.php">Rooms</a></li>
                    <li><a href="facilities.php">Facilities</a></li>
                    <li><a href="packages.php">Packages</a></li>
                    <li><a href="tours.php">Tours</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 TOUR STACK. All rights reserved.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLoginModal()">&times;</span>
            <h2><i class="fas fa-user"></i> User Login</h2>
            <form action="login_process.php" method="POST">
                <div class="form-group">
                    <label for="login-email">Email / Mobile</label>
                    <input type="text" id="login-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <button type="submit" class="login-submit-btn">LOGIN</button>
                <p class="forgot-password"><a href="forgot_password.php">Forgot Password?</a></p>
            </form>
            <div class="login-separator">
                <span>OR</span>
            </div>
            <div class="admin-login-option">
                <a href="admin.php" class="admin-login-btn">
                    <i class="fas fa-user-shield"></i> Login as Admin
                </a>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRegisterModal()">&times;</span>
            <h2><i class="fas fa-user-plus"></i> User Registration</h2>
            <p class="registration-note">Note: Your details must match with your ID (Aadhaar card, passport, driving license, etc.) that will be required during check-in.</p>
            <form action="register_process.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="reg-name">Name</label>
                        <input type="text" id="reg-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="dob">Date of birth</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="reg-password">Password</label>
                        <input type="password" id="reg-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="register-submit-btn">REGISTER</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html> 