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

// Get tour ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin/simple_tours.php");
    exit;
}

$tour_id = $_GET['id'];
$tour = null;
$message = '';
$error = '';
$has_bookings = false;
$booking_count = 0;

// Get tour details
$tour_query = "SELECT * FROM tours WHERE id = ?";
$tour_stmt = $conn->prepare($tour_query);
$tour_stmt->bind_param("i", $tour_id);
$tour_stmt->execute();
$tour_result = $tour_stmt->get_result();

if ($tour_result->num_rows === 0) {
    $error = "Tour not found.";
} else {
    $tour = $tour_result->fetch_assoc();
    
    // Check if tour_bookings table exists
    $table_check_query = "SHOW TABLES LIKE 'tour_bookings'";
    $table_check_result = $conn->query($table_check_query);
    $booking_table_exists = $table_check_result->num_rows > 0;
    
    if ($booking_table_exists) {
        // Check if tour has any bookings
        $booking_check_query = "SELECT COUNT(*) as booking_count FROM tour_bookings WHERE tour_id = ?";
        $booking_stmt = $conn->prepare($booking_check_query);
        $booking_stmt->bind_param("i", $tour_id);
        $booking_stmt->execute();
        $booking_result = $booking_stmt->get_result();
        $booking_count = $booking_result->fetch_assoc()['booking_count'];
        $has_bookings = $booking_count > 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Details - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .tour-detail {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .tour-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .tour-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tour-info {
            flex: 1;
        }
        .tour-name {
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        .tour-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .meta-item {
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
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
        .tour-description {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .section-title {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .included-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .included-item {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .action-btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        .edit-btn {
            background-color: #0d6efd;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        .back-btn {
            background-color: #6c757d;
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
        .warning-badge {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: inline-block;
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Tour Details</h1>
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
            
            <?php if ($tour): ?>
                <section class="admin-section">
                    <div class="section-header">
                        <h2 class="section-title">Tour Details</h2>
                        <a href="admin/simple_tours.php" class="action-btn back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Tours
                        </a>
                    </div>
                    
                    <div class="tour-detail">
                        <div class="tour-header">
                            <?php if (isset($tour['image_path']) && !empty($tour['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($tour['image_path']); ?>" alt="<?php echo htmlspecialchars($tour['name'] ?? 'Tour'); ?>" class="tour-image">
                            <?php else: ?>
                                <div class="tour-image" style="display: flex; align-items: center; justify-content: center; background-color: #e9ecef;">
                                    <i class="fas fa-route" style="font-size: 48px; color: #6c757d;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tour-info">
                                <h3 class="tour-name"><?php echo htmlspecialchars($tour['name'] ?? 'Tour'); ?></h3>
                                
                                <div class="tour-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        Destination: <?php echo isset($tour['destination']) ? htmlspecialchars($tour['destination']) : 'N/A'; ?>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i> 
                                        Duration: <?php echo isset($tour['duration']) ? htmlspecialchars($tour['duration']) : 'N/A'; ?>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <i class="fas fa-money-bill-wave"></i> 
                                        Price: <?php echo isset($tour['price']) ? '₹' . number_format($tour['price'], 2) : '₹0.00'; ?>
                                    </div>
                                </div>
                                
                                <span class="status-badge status-<?php echo isset($tour['status']) ? strtolower($tour['status']) : 'inactive'; ?>">
                                    <?php echo isset($tour['status']) ? ucfirst($tour['status']) : 'Inactive'; ?>
                                </span>
                                
                                <?php if ($has_bookings): ?>
                                    <div class="warning-badge">
                                        <i class="fas fa-exclamation-triangle"></i> This tour has <?php echo $booking_count; ?> booking(s) associated with it.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="tour-description">
                            <h4 class="section-title">Description</h4>
                            <p><?php echo isset($tour['description']) ? nl2br(htmlspecialchars($tour['description'])) : 'No description available.'; ?></p>
                        </div>
                        
                        <?php if (isset($tour['included_items']) && !empty($tour['included_items'])): ?>
                            <div class="included-items">
                                <h4 class="section-title">Included Items</h4>
                                <div class="included-list">
                                    <?php 
                                    $included_array = explode(',', $tour['included_items']);
                                    foreach ($included_array as $item): ?>
                                        <div class="included-item">
                                            <i class="fas fa-check"></i> <?php echo htmlspecialchars(trim($item)); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tour-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i> Created: <?php echo isset($tour['created_at']) ? date('M d, Y', strtotime($tour['created_at'])) : 'N/A'; ?>
                            </div>
                            
                            <div class="meta-item">
                                <i class="fas fa-edit"></i> Last Updated: <?php echo isset($tour['updated_at']) ? date('M d, Y', strtotime($tour['updated_at'])) : 'N/A'; ?>
                            </div>
                            
                            <div class="meta-item">
                                <i class="fas fa-calendar-check"></i> Bookings: <?php echo $booking_count; ?>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="admin/simple_tours.php" class="action-btn back-btn">
                                <i class="fas fa-arrow-left"></i> Back to Tours
                            </a>
                            <a href="simple_tours.php?toggle_status=<?php echo $tour['id']; ?>" class="action-btn status-btn" style="background-color: #6c757d;">
                                <i class="fas fa-sync-alt"></i> Toggle Status
                            </a>
                            <?php if (!$has_bookings): ?>
                                <a href="simple_tours.php?delete=<?php echo $tour['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this tour? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            <?php else: ?>
                                <a href="simple_tours.php?delete=<?php echo $tour['id']; ?>&force_delete=1" class="action-btn delete-btn" style="background-color: #e74c3c;" onclick="return confirm('WARNING: Force delete will remove all related bookings. This action CANNOT be undone and may affect booking records. Are you REALLY sure?')">
                                    <i class="fas fa-exclamation-triangle"></i> Force Delete
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> Tour not found. <a href="admin/simple_tours.php">Return to tours list</a>.
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html> 