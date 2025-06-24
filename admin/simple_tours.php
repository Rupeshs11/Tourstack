<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Debug function
function debug_to_console($data) {
    $output = $data;
    if (is_array($output) || is_object($output)) {
        $output = json_encode($output);
    }
    echo "<script>console.log('DEBUG: " . addslashes($output) . "');</script>";
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin/admin_index.php");
    exit;
}

// Create tours table if not exists
$create_table_query = "CREATE TABLE IF NOT EXISTS tours (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    included_items TEXT,
    image_path VARCHAR(255),
    status ENUM('active', 'inactive', 'seasonal') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";
$conn->query($create_table_query);

// Debug table structure
$columns_query = "DESCRIBE tours";
$columns_result = $conn->query($columns_query);
$column_names = [];

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $column_names[] = $column['Field'] . " (" . $column['Type'] . ")";
    }
}
debug_to_console("Tours table structure: " . implode(", ", $column_names));

// Check if destination column exists
$has_destination_column = false;
foreach ($column_names as $column_info) {
    if (strpos($column_info, 'destination') === 0) {
        $has_destination_column = true;
        break;
    }
}

// Process form submissions
$message = '';
$error = '';

// Check if there's a message in the URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Delete tour
if (isset($_GET['delete'])) {
    $tour_id = $_GET['delete'];
    
    // Check if tour_bookings table exists and for constraints
    $table_check_query = "SHOW TABLES LIKE 'tour_bookings'";
    $table_check_result = $conn->query($table_check_query);
    $has_bookings = false;
    
    if ($table_check_result && $table_check_result->num_rows > 0) {
        // Table exists, check for records
        $booking_check_query = "SELECT COUNT(*) as booking_count FROM tour_bookings WHERE tour_id = ?";
        $booking_stmt = $conn->prepare($booking_check_query);
        $booking_stmt->bind_param("i", $tour_id);
        $booking_stmt->execute();
        $booking_result = $booking_stmt->get_result();
        $booking_count = $booking_result->fetch_assoc()['booking_count'];
        
        if ($booking_count > 0) {
            $error = "Cannot delete tour: There are bookings associated with this tour.";
            goto delete_end;
        }
    }
    
    // Delete the tour
    $delete_query = "DELETE FROM tours WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $tour_id);
    
    if ($delete_stmt->execute()) {
        $message = "Tour deleted successfully!";
    } else {
        $error = "Error deleting tour: " . $conn->error;
    }
    
    delete_end:
    // This is a label for goto
}

// Update tour status
if (isset($_GET['toggle_status'])) {
    $tour_id = $_GET['toggle_status'];
    $status_query = "SELECT status FROM tours WHERE id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("i", $tour_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $tour = $status_result->fetch_assoc();
        $new_status = '';
        
        // Rotate through statuses: active -> seasonal -> inactive -> active
        if ($tour['status'] == 'active') {
            $new_status = 'seasonal';
        } else if ($tour['status'] == 'seasonal') {
            $new_status = 'inactive';
        } else {
            $new_status = 'active';
        }
        
        $update_query = "UPDATE tours SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $tour_id);
        
        if ($update_stmt->execute()) {
            $message = "Tour status updated successfully!";
        } else {
            $error = "Error updating tour status: " . $conn->error;
        }
    }
}

// Get tours
$tours_query = "SELECT * FROM tours ORDER BY id DESC";
$tours_result = $conn->query($tours_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Management - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-seasonal {
            background-color: #fff3cd;
            color: #856404;
        }
        .action-btn {
            display: inline-block;
            margin-right: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 12px;
        }
        .edit-btn {
            background-color: #0d6efd;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        .status-btn {
            background-color: #6c757d;
        }
        .tour-thumbnail {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .success-message, .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Tour Management</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
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
                <a href="admin/simple_tours.php" class="sidebar-nav-item active">
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
                <a href="admin/admin_users.php" class="sidebar-nav-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Users</span>
                </a>
                
            </div>
        </aside>
        
        <main class="admin-main">
            <?php if (!empty($message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <section class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">Tours Management</h2>
                    <div class="action-buttons">
                        <a href="admin/add_tour.php" class="action-btn edit-btn" style="background-color: #28a745;">
                            <i class="fas fa-plus"></i> Add New Tour
                        </a>
                        <a href="admin/remove_all_tours.php" class="action-btn delete-btn" style="background-color: #e74c3c;">
                            <i class="fas fa-trash-alt"></i> Remove All Tours
                        </a>
                    </div>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <?php if ($has_destination_column): ?>
                            <th>Destination</th>
                            <?php endif; ?>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tours_result && $tours_result->num_rows > 0): ?>
                            <?php while($tour = $tours_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $tour['id'] ?? ''; ?></td>
                                    <td>
                                        <?php if (isset($tour['image_path']) && !empty($tour['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($tour['image_path']); ?>" alt="<?php echo htmlspecialchars($tour['name'] ?? ''); ?>" class="tour-thumbnail">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo isset($tour['name']) ? htmlspecialchars($tour['name']) : 'N/A'; ?></td>
                                    <?php if ($has_destination_column): ?>
                                    <td><?php echo isset($tour['destination']) ? htmlspecialchars($tour['destination']) : 'N/A'; ?></td>
                                    <?php endif; ?>
                                    <td><?php echo isset($tour['duration']) ? htmlspecialchars($tour['duration']) : 'N/A'; ?></td>
                                    <td>₹<?php echo isset($tour['price']) ? number_format($tour['price'], 2) : '0.00'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo isset($tour['status']) ? strtolower($tour['status']) : 'inactive'; ?>">
                                            <?php echo isset($tour['status']) ? ucfirst($tour['status']) : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="simple_tour_detail.php?id=<?php echo $tour['id']; ?>" class="action-btn edit-btn">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?toggle_status=<?php echo $tour['id']; ?>" class="action-btn status-btn">
                                            <i class="fas fa-sync-alt"></i> Toggle Status
                                        </a>
                                        <a href="?delete=<?php echo $tour['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this tour? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $has_destination_column ? 8 : 7; ?>" class="no-data">No tours found. Add your first tour!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>

</html> 