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
$register_error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : '';
unset($_SESSION['register_error']);

// Include header
include 'includes/header.php';
?>

<main class="main-content py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">Create an Account</h2>
                            <p class="text-muted">Join TourStack to book your dream tours and rooms</p>
                        </div>
                        
                        <?php if (!empty($register_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($register_error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Your details must match with your ID (Aadhaar card, passport, driving license, etc.) that will be required during check-in.
                        </p>
                        
                        <form action="register_process.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reg-name" class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                        <input type="text" class="form-control" id="reg-name" name="name" placeholder="Enter your full name" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="register-email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" class="form-control" id="register-email" name="email" placeholder="Enter your email" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="Enter your 10-digit phone number" 
                                               pattern="[0-9]{10}" 
                                               title="Phone number must be exactly 10 digits" 
                                               maxlength="10"
                                               required>
                                    </div>
                                    <div class="text-muted mt-1 small">
                                        <span><i class="fas fa-info-circle"></i> Please enter a 10-digit mobile number without spaces or dashes</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-calendar text-muted"></i></span>
                                        <input type="date" class="form-control" id="dob" name="dob" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reg-password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control" id="reg-password" name="password" placeholder="Create a password" required>
                                    </div>
                                    <div class="password-requirements text-muted mt-1 small">
                                        <p class="mb-1">Password must contain at least:</p>
                                        <ul class="ps-3 mb-0">
                                            <li id="length-check"><span class="check-icon">✓</span> 8 characters</li>
                                            <li id="uppercase-check"><span class="check-icon">✓</span> 1 uppercase letter</li>
                                            <li id="lowercase-check"><span class="check-icon">✓</span> 1 lowercase letter</li>
                                            <li id="number-check"><span class="check-icon">✓</span> 1 number</li>
                                            <li id="special-check"><span class="check-icon">✓</span> 1 special character</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="confirm-password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">REGISTER</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-medium">Login Here</a></p>
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
        <?php if (!empty($register_error)): ?>
            Swal.fire({
                title: 'Error',
                text: '<?php echo addslashes($register_error); ?>',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        <?php endif; ?>
        
        // Phone validation
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
        
        // Password validation
        const passwordInput = document.getElementById('reg-password');
        const lengthCheck = document.getElementById('length-check');
        const uppercaseCheck = document.getElementById('uppercase-check');
        const lowercaseCheck = document.getElementById('lowercase-check');
        const numberCheck = document.getElementById('number-check');
        const specialCheck = document.getElementById('special-check');
        
        // Initially hide the checkmarks
        document.querySelectorAll('.check-icon').forEach(icon => {
            icon.style.visibility = 'hidden';
            icon.style.color = '#dc3545'; // red
        });
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Check length
            if (password.length >= 8) {
                lengthCheck.querySelector('.check-icon').style.visibility = 'visible';
                lengthCheck.querySelector('.check-icon').style.color = '#28a745'; // green
            } else {
                lengthCheck.querySelector('.check-icon').style.visibility = 'visible';
                lengthCheck.querySelector('.check-icon').style.color = '#dc3545'; // red
            }
            
            // Check uppercase
            if (password.match(/[A-Z]/)) {
                uppercaseCheck.querySelector('.check-icon').style.visibility = 'visible';
                uppercaseCheck.querySelector('.check-icon').style.color = '#28a745';
            } else {
                uppercaseCheck.querySelector('.check-icon').style.visibility = 'visible';
                uppercaseCheck.querySelector('.check-icon').style.color = '#dc3545';
            }
            
            // Check lowercase
            if (password.match(/[a-z]/)) {
                lowercaseCheck.querySelector('.check-icon').style.visibility = 'visible';
                lowercaseCheck.querySelector('.check-icon').style.color = '#28a745';
            } else {
                lowercaseCheck.querySelector('.check-icon').style.visibility = 'visible';
                lowercaseCheck.querySelector('.check-icon').style.color = '#dc3545';
            }
            
            // Check number
            if (password.match(/[0-9]/)) {
                numberCheck.querySelector('.check-icon').style.visibility = 'visible';
                numberCheck.querySelector('.check-icon').style.color = '#28a745';
            } else {
                numberCheck.querySelector('.check-icon').style.visibility = 'visible';
                numberCheck.querySelector('.check-icon').style.color = '#dc3545';
            }
            
            // Check special character
            if (password.match(/[!@#$%^&*(),.?":{}|<>]/)) {
                specialCheck.querySelector('.check-icon').style.visibility = 'visible';
                specialCheck.querySelector('.check-icon').style.color = '#28a745';
            } else {
                specialCheck.querySelector('.check-icon').style.visibility = 'visible';
                specialCheck.querySelector('.check-icon').style.color = '#dc3545';
            }
        });
        
        // Client-side validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            let isValid = true;
            let errorMessage = '';
            
            // Check password criteria
            if (password.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long';
            } else if (!password.match(/[A-Z]/)) {
                isValid = false;
                errorMessage = 'Password must contain at least one uppercase letter';
            } else if (!password.match(/[a-z]/)) {
                isValid = false;
                errorMessage = 'Password must contain at least one lowercase letter';
            } else if (!password.match(/[0-9]/)) {
                isValid = false;
                errorMessage = 'Password must contain at least one number';
            } else if (!password.match(/[!@#$%^&*(),.?":{}|<>]/)) {
                isValid = false;
                errorMessage = 'Password must contain at least one special character';
            } else if (password !== confirmPassword) {
                isValid = false;
                errorMessage = 'Passwords do not match';
            }
            
            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
</script>

<style>
    .password-requirements {
        font-size: 0.8rem;
    }
    
    .password-requirements ul {
        list-style-type: none;
        padding-left: 0;
    }
    
    .password-requirements li {
        margin-bottom: 0.2rem;
        display: flex;
        align-items: center;
    }
    
    .check-icon {
        margin-right: 0.3rem;
        display: inline-block;
        width: 16px;
    }
</style> 