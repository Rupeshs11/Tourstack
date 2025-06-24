<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to home page
    header("Location: index.php");
    exit;
}

// Include database connection
require_once 'db_connection.php';

// Check if there's an error message in session
$login_error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);

// Check if there's a booking attempt from session storage
$booking_item = '';
if (isset($_COOKIE['selectedItemName']) || isset($_COOKIE['selectedItemType'])) {
    $booking_item = $_COOKIE['selectedItemName'] ?? ($_COOKIE['selectedItemType'] ?? '');
}

// Include header
include 'includes/header.php';
?>

<main class="main-content py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-circle fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">User Login</h2>
                            <p class="text-muted">Welcome back! Please login to your account</p>
                        </div>
                        
                        <?php if (!empty($login_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($login_error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($booking_item)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                Please login to continue booking <?php echo htmlspecialchars($booking_item); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="login_process.php" method="POST">
                            <div class="mb-3">
                                <label for="login-email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control" id="login-email" name="email" placeholder="Enter your email" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="login-password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control" id="login-password" name="password" placeholder="Enter your password" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">LOGIN</button>
                            </div>
                            <div class="text-center mt-3">
                                <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                            </div>
                        </form>
                        
                        <div class="d-flex align-items-center my-4">
                            <hr class="flex-grow-1">
                            <div class="px-3 text-muted">OR</div>
                            <hr class="flex-grow-1">
                        </div>
                        
                        <div class="d-grid">
                            <a href="admin.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-shield me-2"></i> Login as Admin
                            </a>
                        </div>
                        
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-medium">Register Now</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['login_success'])): ?>
            Swal.fire({
                title: 'Success!',
                text: '<?php echo addslashes($_SESSION['login_success']); ?>',
                icon: 'success',
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['login_success']); ?>
        <?php endif; ?>
        
        <?php if (!empty($login_error)): ?>
            Swal.fire({
                title: 'Error',
                text: '<?php echo addslashes($login_error); ?>',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        <?php endif; ?>
    });
</script> 