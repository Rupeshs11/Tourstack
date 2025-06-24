<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

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
    header("Location: admin_index.php");
    exit;
}

$message = '';
$error = '';
$tours_deleted = 0;
$constraints_removed = 0;

// Check if the form was submitted and confirmation was given
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'YES') {
    
    // First, identify all related tables that might have foreign key constraints
    $potential_constraint_tables = ['tour_bookings', 'tour_images', 'tour_reviews', 'tour_schedules'];
    $constraint_tables = [];
    
    // Check which tables actually exist
    foreach ($potential_constraint_tables as $table) {
        $table_check_query = "SHOW TABLES LIKE '$table'";
        $table_check_result = $conn->query($table_check_query);
        
        if ($table_check_result && $table_check_result->num_rows > 0) {
            $constraint_tables[] = $table;
        }
    }
    
    debug_to_console("Found constraint tables: " . implode(", ", $constraint_tables));
    
    // Begin a transaction to ensure data integrity
    $conn->begin_transaction();
    
    try {
        // Step 1: Clear constraints in related tables
        foreach ($constraint_tables as $table) {
            $clear_query = "DELETE FROM $table WHERE tour_id IN (SELECT id FROM tours)";
            $result = $conn->query($clear_query);
            
            if ($result) {
                $affected = $conn->affected_rows;
                $constraints_removed += $affected;
                debug_to_console("Removed $affected constraints from $table");
            } else {
                throw new Exception("Error clearing constraints in $table: " . $conn->error);
            }
        }
        
        // Step 2: Remove all tours
        $delete_tours_query = "DELETE FROM tours";
        $result = $conn->query($delete_tours_query);
        
        if ($result) {
            $tours_deleted = $conn->affected_rows;
            debug_to_console("Deleted $tours_deleted tours");
            
            // Commit transaction if all operations succeeded
            $conn->commit();
            $message = "Successfully removed $tours_deleted tours and $constraints_removed related records.";
        } else {
            throw new Exception("Error deleting tours: " . $conn->error);
        }
        
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        $error = "An error occurred: " . $e->getMessage();
        debug_to_console("Transaction rolled back: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove All Tours - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .danger-zone {
            background-color: #fee;
            border: 2px solid #f44336;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .danger-zone h3 {
            color: #d32f2f;
            margin-top: 0;
        }
        .warning-text {
            color: #d32f2f;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .confirmation-box {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .confirmation-input {
            margin-bottom: 15px;
        }
        .confirmation-input input {
            padding: 8px;
            width: 100%;
            max-width: 200px;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        .back-btn {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-right: 10px;
        }
        .success-message, .error-message {
            padding: 15px;
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
        <h1 class="admin-page-title">Remove All Tours</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
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
                <a href="admin_new_booking.php" class="sidebar-nav-item">
                    <i class="fas fa-calendar-plus"></i>
                    <span>New Booking</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-nav-title">Management</div>
                <a href="simple_tours.php" class="sidebar-nav-item active">
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
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-nav-title">Reports</div>
                <a href="admin_reports.php" class="sidebar-nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                <a href="admin_tasks.php" class="sidebar-nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Tasks</span>
                </a>
                <a href="admin_reopened_issue.php" class="sidebar-nav-item">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Reopened Issues</span>
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
                    <h2 class="section-title">Danger Zone</h2>
                    <a href="simple_tours.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Tours
                    </a>
                </div>
                
                <div class="danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Remove All Tours</h3>
                    
                    <div class="warning-text">
                        <p>WARNING: This action will remove ALL tours from the database and cannot be undone!</p>
                        <p>This process will:</p>
                        <ul>
                            <li>Delete all related records in booking tables</li>
                            <li>Remove all tour data</li>
                            <li>Clear any associated tour images, schedules, reviews</li>
                        </ul>
                        <p>This is intended for resetting the system or cleaning up test data.</p>
                    </div>
                    
                    <div class="confirmation-box">
                        <form method="POST" onsubmit="return confirm('Are you ABSOLUTELY sure you want to delete ALL tours? This cannot be undone!');">
                            <div class="confirmation-input">
                                <label for="confirm_delete">To confirm, type "YES" in the box below:</label>
                                <input type="text" id="confirm_delete" name="confirm_delete" required>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="simple_tours.php" class="back-btn">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="delete-btn">
                                    <i class="fas fa-trash"></i> Delete All Tours
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>

</html> 