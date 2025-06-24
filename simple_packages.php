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

// Create packages table if not exists
$create_table_query = "CREATE TABLE IF NOT EXISTS packages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    included_items TEXT,
    image_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";
$conn->query($create_table_query);

// Debug table structure
$columns_query = "DESCRIBE packages";
$columns_result = $conn->query($columns_query);
$column_names = [];

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $column_names[] = $column['Field'] . " (" . $column['Type'] . ")";
    }
}
debug_to_console("Packages table structure: " . implode(", ", $column_names));

// Process form submissions
$message = '';
$error = '';

// Check if there's a message in the URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Delete package
if (isset($_GET['delete'])) {
    $package_id = $_GET['delete'];
    
    // Delete the package
    $delete_query = "DELETE FROM packages WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $package_id);
    
    if ($delete_stmt->execute()) {
        $message = "Package deleted successfully!";
    } else {
        $error = "Error deleting package: " . $conn->error;
    }
}

// Update package status
if (isset($_GET['toggle_status'])) {
    $package_id = $_GET['toggle_status'];
    $status_query = "SELECT status FROM packages WHERE id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("i", $package_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $package = $status_result->fetch_assoc();
        $new_status = ($package['status'] == 'active') ? 'inactive' : 'active';
        
        $update_query = "UPDATE packages SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $package_id);
        
        if ($update_stmt->execute()) {
            $message = "Package status updated successfully!";
        } else {
            $error = "Error updating package status: " . $conn->error;
        }
    }
}

// Get packages
$packages_query = "SELECT * FROM packages ORDER BY id DESC";
$packages_result = $conn->query($packages_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Management - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
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
        .package-thumbnail {
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
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .package-detail-img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .package-info {
            margin-bottom: 20px;
        }

        .package-info h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .included-items {
            list-style-type: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .included-items li {
            background-color: #f5f5f5;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }

        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 50px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error {
            color: #dc3545;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Package Management</h1>
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
                <a href="simple_tours.php" class="sidebar-nav-item">
                    <i class="fas fa-route"></i>
                    <span>Tours</span>
                </a>
                <a href="simple_rooms.php" class="sidebar-nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="simple_packages.php" class="sidebar-nav-item active">
                    <i class="fas fa-box"></i>
                    <span>Packages</span>
                </a>
                <a href="admin_users.php" class="sidebar-nav-item">
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
                    <h2 class="section-title">Packages Management</h2>
                    <div class="action-buttons">
                        <a href="add_package.php" class="action-btn edit-btn" style="background-color: #28a745;">
                            <i class="fas fa-plus"></i> Add New Package
                        </a>
                        <a href="#" class="action-btn delete-btn" style="background-color: #e74c3c;" onclick="return confirm('Are you sure you want to delete all packages? This action cannot be undone.')">
                            <i class="fas fa-trash-alt"></i> Remove All Packages
                        </a>
                    </div>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($packages_result && $packages_result->num_rows > 0): ?>
                            <?php while($package = $packages_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $package['id'] ?? ''; ?></td>
                                    <td>
                                        <?php if (isset($package['image_path']) && !empty($package['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($package['image_path']); ?>" alt="<?php echo htmlspecialchars($package['name'] ?? ''); ?>" class="package-thumbnail">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo isset($package['name']) ? htmlspecialchars($package['name']) : 'N/A'; ?></td>
                                    <td>₹<?php echo isset($package['price']) ? number_format($package['price'], 2) : '0.00'; ?></td>
                                    <td><?php echo isset($package['duration']) ? htmlspecialchars($package['duration']) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo isset($package['status']) ? strtolower($package['status']) : 'inactive'; ?>">
                                            <?php echo isset($package['status']) ? ucfirst($package['status']) : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="viewPackageDetails(<?php echo $package['id']; ?>)" class="action-btn edit-btn">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <a href="?toggle_status=<?php echo $package['id']; ?>" class="action-btn status-btn">
                                            <i class="fas fa-sync-alt"></i> Toggle Status
                                        </a>
                                        <a href="?delete=<?php echo $package['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this package? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">No packages found. Add your first package!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Package Details Modal -->
    <div id="packageDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="packageDetailsContent">
                <!-- Package details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
    // Get the modal
    var modal = document.getElementById("packageDetailsModal");
    var modalContent = document.getElementById("packageDetailsContent");
    var span = document.getElementsByClassName("close")[0];

    // Function to view package details
    function viewPackageDetails(packageId) {
        modal.style.display = "block";
        modalContent.innerHTML = '<div class="loader"></div>';
        
        // Fetch package details via AJAX
        fetch('get_package_details.php?id=' + packageId, {
            credentials: 'same-origin' // Include credentials to maintain the PHP session
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let package = data.package;
                    let content = '';
                    
                    // Add image if available
                    if (package.image_path) {
                        content += `<img src="${package.image_path}" alt="${package.name}" class="package-detail-img">`;
                    }
                    
                    // Package basic info
                    content += `
                    <div class="package-info">
                        <h3>${package.name}</h3>
                        <p><strong>Price:</strong> ₹${parseFloat(package.price).toFixed(2)}</p>
                        <p><strong>Duration:</strong> ${package.duration}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${package.status.toLowerCase()}">${package.status.charAt(0).toUpperCase() + package.status.slice(1)}</span></p>
                    </div>`;
                    
                    // Description
                    content += `
                    <div class="package-info">
                        <h3>Description</h3>
                        <p>${package.description}</p>
                    </div>`;
                    
                    // Included items if available
                    if (package.included_items) {
                        content += `
                        <div class="package-info">
                            <h3>What's Included</h3>
                            <ul class="included-items">`;
                        
                        // Split by commas or newlines
                        const items = package.included_items.split(/[,\n]+/).filter(item => item.trim() !== '');
                        
                        items.forEach(item => {
                            content += `<li>${item.trim()}</li>`;
                        });
                        
                        content += `</ul>
                        </div>`;
                    }
                    
                    modalContent.innerHTML = content;
                } else {
                    if (data.message === 'Unauthorized access') {
                        // Handle session timeout or unauthorized access
                        modalContent.innerHTML = '<p class="error">Your session has expired. <a href="admin_index.php">Please login again</a>.</p>';
                    } else {
                        modalContent.innerHTML = '<p class="error">Error loading package details: ' + data.message + '</p>';
                    }
                }
            })
            .catch(error => {
                modalContent.innerHTML = '<p class="error">Error: Could not load package details. Please try again.</p>';
                console.error('Error:', error);
            });
    }

    // Close modal when clicking the × button
    span.onclick = function() {
        modal.style.display = "none";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    </script>
</body>

</html> 