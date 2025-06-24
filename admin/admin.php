<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Handle login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Already logged in, redirect to dashboard
    header("Location: admin/admin_dashboard.php");
    exit;
}

// Redirect to admin_index.php
header("Location: admin/admin_index.php");
exit;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TOUR STACK</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
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
    </style>
</head>

<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-login-header">
                <h1>TOUR STACK</h1>
                <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="admin-login-btn">LOGIN</button>
            </form>
            
            <div class="back-to-home">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($error)): ?>
                Swal.fire({
                    title: 'Error',
                    text: '<?php echo addslashes($error); ?>',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
            
            // Add form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                
                if (!username || !password) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error',
                        text: 'Username and password are required',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
</body>

</html> 