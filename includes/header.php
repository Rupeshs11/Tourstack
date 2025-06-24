<?php
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) ? true : false;
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TourStack - Your Ultimate Travel Experience</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Custom SweetAlert Styles */
        .swal2-popup {
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            font-family: 'Poppins', sans-serif;
        }
        
        .swal2-title {
            color: #333;
            font-weight: 600;
            font-size: 26px;
        }
        
        .swal2-html-container {
            font-size: 16px;
            color: #555;
        }
        
        .swal2-confirm {
            background-color: #e74c3c !important;
            border-radius: 4px !important;
            padding: 12px 25px !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2) !important;
            transition: all 0.3s ease !important;
        }
        
        .swal2-confirm:hover {
            background-color: #c0392b !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 8px rgba(231, 76, 60, 0.3) !important;
        }
        
        .swal2-cancel {
            background-color: #7f8c8d !important;
            border-radius: 4px !important;
            padding: 12px 25px !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 6px rgba(127, 140, 141, 0.2) !important;
            transition: all 0.3s ease !important;
        }
        
        .swal2-cancel:hover {
            background-color: #636e72 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 8px rgba(127, 140, 141, 0.3) !important;
        }
        
        .swal2-icon.swal2-success {
            color: #27ae60 !important;
            border-color: #27ae60 !important;
        }
        
        .swal2-icon.swal2-error {
            color: #e74c3c !important;
            border-color: #e74c3c !important;
        }
        
        .swal2-icon.swal2-warning {
            color: #f39c12 !important;
            border-color: #f39c12 !important;
        }
        
        .swal2-icon.swal2-info {
            color: #3498db !important;
            border-color: #3498db !important;
        }
        
        .swal2-success-circular-line-left,
        .swal2-success-circular-line-right,
        .swal2-success-fix {
            background-color: transparent !important;
        }
        
        .swal2-styled.swal2-confirm:focus,
        .swal2-styled.swal2-cancel:focus {
            box-shadow: none !important;
        }
        
        /* Custom header styles */
        .main-header {
            background-color: #333;
            padding: 10px 0;
        }
        .navbar-brand {
            color: #fff;
            font-weight: bold;
            font-size: 24px;
        }
        .navbar-brand:hover {
            color: #f8f9fa;
        }
        .nav-link {
            color: #fff;
            font-weight: 500;
            transition: color 0.3s;
            padding: 10px 15px;
        }
        .nav-link:hover {
            color: #e74c3c;
        }
        .user-info {
            color: #fff;
            display: flex;
            align-items: center;
            position: relative;
        }
        .user-name {
            margin-right: 10px;
            font-weight: 500;
        }
        .login-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .login-btn:hover {
            background: #c0392b;
            color: white;
        }
        /* Profile dropdown styling */
        .dropdown-toggle::after {
            display: none;
        }
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
            padding: 10px 0;
        }
        .profile-dropdown {
            width: 320px;
            padding: 0;
            right: 0;
            left: auto;
            top: 100%;
            margin-top: 15px !important;
            animation: fadeIn 0.2s ease-out;
            position: absolute;
        }
        .profile-dropdown::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background-color: #fcfcfc;
            transform: rotate(45deg);
            border-top: 1px solid rgba(0,0,0,0.05);
            border-left: 1px solid rgba(0,0,0,0.05);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .profile-card {
            padding: 15px;
            border-radius: 8px 8px 0 0;
            background-color: #fcfcfc;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .profile-avatar {
            margin-right: 15px;
            color: #666;
            background-color: #f5f5f5;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-info h6 {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .profile-body {
            margin-bottom: 10px;
        }
        .profile-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 12px;
            border: 1px solid #eee;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .detail-item:last-child {
            margin-bottom: 0;
        }
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 500;
        }
        .dropdown-item {
            padding: 10px 20px;
            font-size: 14px;
            color: #333;
            transition: all 0.2s;
        }
        .dropdown-item i {
            width: 20px;
            margin-right: 8px;
            color: #666;
        }
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: #f8f9fa;
            color: #e74c3c;
        }
        .dropdown-item:hover i {
            color: #e74c3c;
        }
        .dropdown-divider {
            margin: 5px 0;
            border-top: 1px solid #eeeeee;
        }
        .fa-user-circle {
            font-size: 24px;
            color: #fff;
            transition: color 0.3s;
            cursor: pointer;
        }
        .nav-link.dropdown-toggle:hover .fa-user-circle {
            color: #e74c3c;
        }
        .profile-dropdown {
            margin-top: 10px !important;
        }
        /* Better mobile support */
        .btn-link.nav-link {
            background: none;
            border: none;
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
        }
        .btn-link.nav-link:focus {
            box-shadow: none;
        }
        /* For touch devices */
        .touch-device .dropdown-toggle::after {
            display: inline-block;
        }
        .touch-device .dropdown-menu {
            margin-top: 0;
        }
        /* Manual dropdown display */
        .dropdown-menu.show {
            display: block;
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
            margin-top: 15px !important;
            transform: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        .profile-dropdown.show {
            z-index: 1021;
        }
        /* Improve click area for mobile */
        #profileDropdown {
            padding: 10px;
            margin: -10px;
        }
        @media (max-width: 767px) {
            .profile-dropdown {
                width: 290px;
                right: -15px;
            }
            
            .profile-dropdown::before {
                right: 25px;
            }
            
            .user-name {
                font-size: 14px;
            }
            
            #profileDropdown {
                padding: 8px;
                margin: -8px;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="index.php">TOUR STACK</a>
                <nav>
                    <ul class="nav">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="rooms.php">Rooms</a></li>
                        <li class="nav-item"><a class="nav-link" href="facilities.php">Facilities</a></li>
                        <li class="nav-item"><a class="nav-link" href="packages.php">Packages</a></li>
                        <li class="nav-item"><a class="nav-link" href="tours.php">Tours</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <?php if($isLoggedIn): ?>
                        <div class="user-info">
                            <span class="user-name">Welcome, <?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
                            <div class="dropdown">
                                <button class="btn btn-link nav-link p-0" type="button" id="profileDropdown" onclick="toggleProfileDropdown(event)">
                                    <i class="fas fa-user-circle fa-lg"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end profile-dropdown" id="profileDropdownMenu" aria-labelledby="profileDropdown">
                                    <div class="profile-card">
                                        <div class="profile-header">
                                            <div class="profile-avatar">
                                                <i class="fas fa-user-circle fa-2x"></i>
                                            </div>
                                            <div class="profile-info">
                                                <h6 class="mb-0"><?php echo $_SESSION['user_name'] ?? 'User'; ?></h6>
                                                <small class="text-muted"><?php echo $_SESSION['user_email'] ?? ''; ?></small>
                                            </div>
                                        </div>
                                        <div class="profile-body">
                                            <?php
                                            // Fetch user details for the profile card
                                            $profile_user_id = $_SESSION['user_id'];
                                            $profile_sql = "SELECT * FROM users WHERE id = ?";
                                            $profile_stmt = $conn->prepare($profile_sql);
                                            $profile_stmt->bind_param("i", $profile_user_id);
                                            $profile_stmt->execute();
                                            $profile_result = $profile_stmt->get_result();
                                            $profile_user = $profile_result->fetch_assoc();
                                            ?>
                                            
                                            <div class="profile-details">
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class="fas fa-phone me-2"></i>Phone:</span>
                                                    <span class="detail-value"><?php echo $profile_user['phone'] ?? 'Not provided'; ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label"><i class="fas fa-calendar me-2"></i>Joined:</span>
                                                    <span class="detail-value"><?php echo date('M Y', strtotime($profile_user['created_at'] ?? 'now')); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="my_bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="fas fa-user-edit"></i> Edit Profile</a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="fas fa-key"></i> Change Password</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a class="login-btn" href="login.php">Login / Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Alert Messages -->
    <?php if(isset($_SESSION['profile_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show container mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['profile_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['profile_success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['profile_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show container mt-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['profile_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['profile_error']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['password_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show container mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['password_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['password_success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['password_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show container mt-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['password_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['password_error']); ?>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // If any issues with dropdowns on mobile, add this class for touch devices
        if ('ontouchstart' in document.documentElement) {
            document.body.classList.add('touch-device');
        }
        
        // Simple function to toggle dropdown manually
        function toggleProfileDropdown(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const dropdownMenu = document.getElementById('profileDropdownMenu');
            dropdownMenu.classList.toggle('show');
            
            // Close dropdown when clicking outside
            function closeDropdown(e) {
                if (!e.target.closest('#profileDropdown') && !e.target.closest('#profileDropdownMenu')) {
                    dropdownMenu.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                    document.removeEventListener('touchstart', closeDropdown);
                }
            }
            
            // Remove existing event listeners first to prevent duplicates
            document.removeEventListener('click', closeDropdown);
            document.removeEventListener('touchstart', closeDropdown);
            
            // Add event listeners for both click and touch
            setTimeout(() => {
                document.addEventListener('click', closeDropdown);
                document.addEventListener('touchstart', closeDropdown);
            }, 100);
        }
    </script>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="update_profile.php" method="post">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" name="name" value="<?php echo $profile_user['name'] ?? ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $profile_user['email'] ?? ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $profile_user['phone'] ?? ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" value="<?php echo $profile_user['dob'] ?? ''; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="change_password.php" method="post">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Content will be placed here --> 