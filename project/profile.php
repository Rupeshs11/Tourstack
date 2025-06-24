<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user details
$user_id = $_SESSION['user_id'];
$user = null;

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
$user = $result->fetch_assoc();
}

// Handle profile update
$update_success = false;
$update_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $dob = filter_var($_POST['dob'], FILTER_SANITIZE_STRING);
    
    // Check if users table has a phone or mobile column
    $check_phone_column = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    $check_mobile_column = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
    $phone_column_exists = $check_phone_column && $check_phone_column->num_rows > 0;
    $mobile_column_exists = $check_mobile_column && $check_mobile_column->num_rows > 0;
    
    // Update user in database with the appropriate field (phone or mobile)
    if ($phone_column_exists) {
        $update_query = "UPDATE users SET name = ?, phone = ?, dob = ? WHERE id = ?";
    } else if ($mobile_column_exists) {
        $update_query = "UPDATE users SET name = ?, mobile = ?, dob = ? WHERE id = ?";
    } else {
        $update_error = "Database error: Missing required columns";
        // Continue with just name update
        $update_query = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $name, $user_id);
        
        if ($stmt->execute()) {
            $update_success = true;
            $_SESSION['user_name'] = $name; // Update session variable
            
            // Refresh user data
            $stmt->close();
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $update_error = "Error updating profile: " . $conn->error;
        }
        
        // Skip the rest of the update logic
        goto end_update;
    }
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $name, $phone, $dob, $user_id);
    
    if ($stmt->execute()) {
        $update_success = true;
        $_SESSION['user_name'] = $name; // Update session variable
        
        // Refresh user data
        $stmt->close();
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } else {
        $update_error = "Error updating profile: " . $conn->error;
    }
    
    end_update:
}

// Handle password change
$password_success = false;
$password_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $password_query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($password_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    
    if (password_verify($current_password, $user_data['password'])) {
        // Check if new passwords match
        if ($new_password === $confirm_password) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $password_success = true;
            } else {
                $password_error = "Error updating password: " . $conn->error;
            }
            $stmt->close();
        } else {
            $password_error = "New passwords do not match!";
        }
    } else {
        $password_error = "Current password is incorrect!";
    }
}

// Get user's bookings
$bookings_query = "SELECT tb.*, t.name as tour_name, t.image_path as tour_image
                   FROM tour_bookings tb
                   JOIN tours t ON tb.tour_id = t.id
                   WHERE tb.user_id = ?
                   ORDER BY tb.booking_date DESC";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$stmt->close();

// Include header
include 'includes/header.php';
?>

<main class="container mt-5 mb-5">
    <h1 class="display-4 text-center mb-4">My Profile</h1>
    
    <?php if(isset($_SESSION['profile_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['profile_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['profile_success']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['profile_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['profile_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['profile_error']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['profile_errors']) && is_array($_SESSION['profile_errors'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> 
            <ul class="mb-0">
                <?php foreach($_SESSION['profile_errors'] as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['profile_errors']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['password_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['password_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['password_success']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['password_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['password_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['password_error']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['password_errors']) && is_array($_SESSION['password_errors'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> 
            <ul class="mb-0">
                <?php foreach($_SESSION['password_errors'] as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
        <?php unset($_SESSION['password_errors']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Profile Picture</h3>
                    </div>
                <div class="card-body text-center">
                    <div class="profile-picture mb-4">
                        <i class="fas fa-user-circle fa-6x text-secondary"></i>
                    </div>
                    <h4 class="mb-0"><?php echo $user['name'] ?? $_SESSION['user_name'] ?? 'User'; ?></h4>
                    <p class="text-muted"><?php echo $user['email'] ?? ''; ?></p>
                    <p class="badge bg-info">Member since: <?php echo date('F Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Quick Links</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="my_bookings.php" class="text-decoration-none">
                                <i class="fas fa-calendar-check me-2"></i> My Bookings
                        </a>
                    </li>
                        <li class="list-group-item">
                            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-user-edit me-2"></i> Edit Profile
                        </a>
                    </li>
                        <li class="list-group-item">
                            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="fas fa-key me-2"></i> Change Password
                        </a>
                    </li>
                </ul>
            </div>
            </div>
            </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">User Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Username</label>
                            <p><?php echo $user['name'] ?? $_SESSION['user_name'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <p><?php echo $user['email'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phone Number</label>
                            <p><?php echo $user['phone'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Date of Birth</label>
                            <p><?php echo !empty($user['dob']) ? date('F d, Y', strtotime($user['dob'])) : 'N/A'; ?></p>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Member Since</label>
                            <p><?php echo date('F d, Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                        </div>
                    </div>
                </div>
                </div>
                
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Booking Statistics</h3>
                                            </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h1 class="display-4 text-primary">
                                    <?php
                                    // Count room bookings
                                    $sql = "SELECT COUNT(*) as count FROM room_booking WHERE user_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    echo $result->fetch_assoc()['count'] ?? 0;
                                    ?>
                                </h1>
                                <p class="mb-0">Room Bookings</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h1 class="display-4 text-success">
                                    <?php
                                    // Count tour bookings
                                    $sql = "SELECT COUNT(*) as count FROM tour_bookings WHERE user_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    echo $result->fetch_assoc()['count'] ?? 0;
                                    ?>
                                </h1>
                                <p class="mb-0">Tour Bookings</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h1 class="display-4 text-warning">
                                    <?php
                                    // Count package bookings
                                    $sql = "SELECT COUNT(*) as count FROM package_bookings WHERE user_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    echo $result->fetch_assoc()['count'] ?? 0;
                                    ?>
                                </h1>
                                <p class="mb-0">Package Bookings</p>
                        </div>
                        </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</main>

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
                        <input type="text" class="form-control" id="fullName" name="name" value="<?php echo $user['name'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" value="<?php echo $user['dob'] ?? ''; ?>">
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

<style>
    .profile-picture {
        width: 120px;
        height: 120px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #f8f9fa;
    }
    
    .card-header {
        background-color: #e74c3c !important;
    }
    
    .text-primary {
        color: #e74c3c !important;
    }
    
    .btn-primary {
        background-color: #e74c3c !important;
        border-color: #e74c3c !important;
    }
    
    .btn-primary:hover {
        background-color: #c0392b !important;
        border-color: #c0392b !important;
    }
    
    .list-group-item a {
        color: #333;
        display: block;
        padding: 5px 0;
    }
    
    .list-group-item a:hover {
        color: #e74c3c;
    }
</style>

<?php include 'footer.php'; ?> 