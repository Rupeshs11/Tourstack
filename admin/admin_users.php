<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin/admin_index.php");
    exit;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new user
    if (isset($_POST['add_user'])) {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $dob = filter_var($_POST['dob'], FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
        
        // Check if email already exists
        $check_query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        if ($stmt === false) {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
            header("Location: admin/admin_users.php");
            exit;
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "A user with this email already exists.";
        } else {
            // Check if users table has a phone or mobile column
            $check_phone_column = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
            $check_mobile_column = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
            $phone_column_exists = $check_phone_column && $check_phone_column->num_rows > 0;
            $mobile_column_exists = $check_mobile_column && $check_mobile_column->num_rows > 0;
            
            // Insert new user with the appropriate field (phone or mobile)
            if ($phone_column_exists) {
                $insert_query = "INSERT INTO users (name, email, phone, dob, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            } else if ($mobile_column_exists) {
                $insert_query = "INSERT INTO users (name, email, mobile, dob, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            } else {
                // If neither column exists, exclude it from the insert
                $insert_query = "INSERT INTO users (name, email, dob, password, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                if ($stmt === false) {
                    $_SESSION['error_message'] = "Database error: " . $conn->error;
                    header("Location: admin/admin_users.php");
                    exit;
                }
                $stmt->bind_param("ssss", $name, $email, $dob, $password);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "User added successfully!";
                } else {
                    $_SESSION['error_message'] = "Error adding user: " . $conn->error;
                }
                
                $stmt->close();
                header("Location: admin/admin_users.php");
                exit;
            }
            
            $stmt = $conn->prepare($insert_query);
            if ($stmt === false) {
                $_SESSION['error_message'] = "Database error: " . $conn->error;
                header("Location: admin/admin_users.php");
                exit;
            }
            $stmt->bind_param("sssss", $name, $email, $phone, $dob, $password);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "User added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding user: " . $conn->error;
            }
        }
        
        $stmt->close();
        
        // Redirect to refresh the page
        header("Location: admin/admin_users.php");
        exit;
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        
        // Check if user has bookings
        $table_exists = $conn->query("SHOW TABLES LIKE 'tour_bookings'");
        if ($table_exists && $table_exists->num_rows > 0) {
            // Table exists, check for bookings
            $check_query = "SELECT COUNT(*) as count FROM tour_bookings WHERE user_id = ?";
            $stmt = $conn->prepare($check_query);
            if ($stmt === false) {
                $_SESSION['error_message'] = "Database error: " . $conn->error;
                header("Location: admin/admin_users.php");
                exit;
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $bookings_count = $result->fetch_assoc()['count'];
            $stmt->close();
            
            if ($bookings_count > 0) {
                $_SESSION['error_message'] = "Cannot delete user as they have bookings.";
                header("Location: admin/admin_users.php");
                exit;
            }
        }
        
        // If we get here, either the table doesn't exist or the user has no bookings, so we can delete
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        if ($stmt === false) {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
            header("Location: admin/admin_users.php");
            exit;
        }
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting user: " . $conn->error;
        }
        
        $stmt->close();
        
        // Redirect to refresh the page
        header("Location: admin/admin_users.php");
        exit;
    }
}

// Check if users table exists and has the correct structure
function check_users_table_structure($conn) {
    // Check if users table exists
    $users_table_exists = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$users_table_exists || $users_table_exists->num_rows == 0) {
        // Create users table
        $create_users_table = "CREATE TABLE users (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            mobile VARCHAR(20),
            phone VARCHAR(20),
            dob DATE,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        
        if ($conn->query($create_users_table) === TRUE) {
            return "Users table created successfully.";
        } else {
            return "Error creating users table: " . $conn->error;
        }
    } else {
        // Table exists, check columns
        $column_updates = [];
        
        // Check if mobile column exists
        $mobile_column = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
        if (!$mobile_column || $mobile_column->num_rows == 0) {
            // Add mobile column
            if ($conn->query("ALTER TABLE users ADD mobile VARCHAR(20) AFTER email") === TRUE) {
                $column_updates[] = "Added 'mobile' column";
            } else {
                return "Error adding mobile column: " . $conn->error;
            }
        }
        
        // Check if phone column exists
        $phone_column = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if (!$phone_column || $phone_column->num_rows == 0) {
            // Add phone column
            if ($conn->query("ALTER TABLE users ADD phone VARCHAR(20) AFTER email") === TRUE) {
                $column_updates[] = "Added 'phone' column";
            } else {
                return "Error adding phone column: " . $conn->error;
            }
        }
        
        // Check if role column exists
        $role_column = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        if (!$role_column || $role_column->num_rows == 0) {
            // Add role column
            if ($conn->query("ALTER TABLE users ADD role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password") === TRUE) {
                $column_updates[] = "Added 'role' column";
            } else {
                return "Error adding role column: " . $conn->error;
            }
        }
        
        if (count($column_updates) > 0) {
            return "Users table updated: " . implode(", ", $column_updates);
        } else {
            return "";
        }
    }
}

// Check and update users table structure
$table_message = check_users_table_structure($conn);
if (!empty($table_message)) {
    $_SESSION['success_message'] = $table_message;
}

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_query);

// Get statistics
$total_users = 0;
$new_users_today = 0;

// Only get statistics if users table exists
$users_table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($users_table_check && $users_table_check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    if ($stmt === false) {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
        // Don't redirect, just set default values
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        $total_users = $result->fetch_assoc()['count'];
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
        if ($stmt === false) {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
            // Don't redirect, just keep default value
        } else {
            $stmt->execute();
            $result = $stmt->get_result();
            $new_users_today = $result->fetch_assoc()['count'];
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .section-title {
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff6600;
            color: #343a40;
            font-size: 1.5rem;
        }
        .admin-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Users Management</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="admin/admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                <a href="admin/admin_dashboard.php" class="sidebar-nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin/admin_customers.php" class="sidebar-nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="admin/admin_bookings.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="admin/admin_new_booking.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-plus"></i>
                    <span>New Booking</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-nav-title">Management</div>
                <a href="admin/simple_tours.php" class="sidebar-nav-item">
                    <i class="fas fa-route"></i>
                    <span>Tours</span>
                </a>
                <a href="admin/simple_rooms.php" class="sidebar-nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="admin/simple_packages.php" class="sidebar-nav-item">
                    <i class="fas fa-box"></i>
                    <span>Packages</span>
                </a>
                <a href="admin/admin_users.php" class="sidebar-nav-item active">
                    <i class="fas fa-user-shield"></i>
                    <span>Users</span>
                </a>
            </div>
        </aside>
        
        <main class="admin-main">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <section class="admin-section">
                <h2 class="section-title">User Statistics</h2>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Total Users</h3>
                            <p><?php echo $total_users; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-data">
                            <h3>New Users Today</h3>
                            <p><?php echo $new_users_today; ?></p>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">All Users</h2>
                    <div class="action-buttons">
                        <button class="add-btn" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New User</button>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="userSearch" placeholder="Search users...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <table class="admin-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date of Birth</th>
                            <th>Registration Date</th>
                            <th>Bookings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): 
                                // Count user's bookings
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tour_bookings WHERE user_id = ?");
                                $stmt->bind_param("i", $user['id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $bookings_count = $result->fetch_assoc()['count'];
                                $stmt->close();
                            ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo isset($user['dob']) ? date('d M Y', strtotime($user['dob'])) : 'N/A'; ?></td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td><?php echo $bookings_count; ?></td>
                                    <td class="actions-cell">
                                        <a href="view_user_bookings.php?user_id=<?php echo $user['id']; ?>" class="view-btn">
                                            <i class="fas fa-eye"></i> View Bookings
                                        </a>
                                        <?php if ($bookings_count == 0): ?>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="delete-btn">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    
    <!-- Add User Modal -->
    <div id="add-modal" class="admin-modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add New User</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="add_user" class="submit-btn" onclick="return validatePassword()">Add User</button>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('add-modal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('add-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('add-modal')) {
                closeAddModal();
            }
        }
        
        // Password validation
        function validatePassword() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            return true;
        }
        
        // Search functionality
        document.getElementById('userSearch').addEventListener('keyup', function() {
            let input = this.value.toLowerCase();
            let table = document.getElementById('usersTable');
            let rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                let showRow = false;
                let cells = rows[i].getElementsByTagName('td');
                
                for (let j = 0; j < cells.length; j++) {
                    let text = cells[j].textContent || cells[j].innerText;
                    if (text.toLowerCase().indexOf(input) > -1) {
                        showRow = true;
                        break;
                    }
                }
                
                rows[i].style.display = showRow ? '' : 'none';
            }
        });
    </script>
</body>

</html> 