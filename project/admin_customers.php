<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Check if customers table exists
$check_table_query = "SHOW TABLES LIKE 'customers'";
$table_exists = $conn->query($check_table_query);
if ($table_exists && $table_exists->num_rows == 0) {
    // Create customers table
    $create_table_query = "CREATE TABLE customers (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        mobile VARCHAR(50) NOT NULL,
        address TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_query) === false) {
        $_SESSION['error_message'] = "Error creating customers table: " . $conn->error;
    }
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new customer
    if (isset($_POST['add_customer'])) {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $mobile = filter_var($_POST['mobile'], FILTER_SANITIZE_STRING);
        $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
        
        // Insert customer
        $query = "INSERT INTO customers (name, email, mobile, address, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $name, $email, $mobile, $address);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Customer added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding customer: " . $conn->error;
        }
        
        $stmt->close();
        header("Location: admin_customers.php");
        exit;
    }
    
    // Delete customer
    if (isset($_POST['delete_customer'])) {
        $customer_id = filter_var($_POST['customer_id'], FILTER_SANITIZE_NUMBER_INT);
        
        $query = "DELETE FROM customers WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Customer deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting customer: " . $conn->error;
        }
        
        $stmt->close();
        header("Location: admin_customers.php");
        exit;
    }
}

// Check if search was performed
$search_term = '';
if (isset($_GET['search'])) {
    $search_term = filter_var($_GET['search'], FILTER_SANITIZE_STRING);
    $customers_query = "SELECT * FROM customers WHERE 
                        name LIKE ? OR 
                        email LIKE ? OR 
                        mobile LIKE ? OR 
                        address LIKE ?
                        ORDER BY created_at DESC";
    $stmt = $conn->prepare($customers_query);
    $search_param = "%$search_term%";
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $customers_result = $stmt->get_result();
    $stmt->close();
} else {
    // Get all customers
    $customers_query = "SELECT * FROM customers ORDER BY created_at DESC";
    $customers_result = $conn->query($customers_query);
    
    // Check if the query was successful
    if ($customers_result === false) {
        // Query failed
        $_SESSION['error_message'] = "Error fetching customers: " . $conn->error;
        $customers_result = null; // Set to null to avoid the num_rows error
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management - TOUR STACK Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
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
        <h1 class="admin-page-title">Customers Management</h1>
        <div class="admin-user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
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
                <a href="admin_customers.php" class="sidebar-nav-item active">
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
                <a href="simple_packages.php" class="sidebar-nav-item">
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
                <div class="section-header">
                    <h2 class="section-title">Customer Management</h2>
                    <div class="action-buttons">
                        <button class="add-btn" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New Customer</button>
                    </div>
                </div>
                
                <div class="search-container">
                    <form action="" method="GET" class="search-form">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Full Name</th>
                            <th>Mobile Number</th>
                            <th>Complete Address</th>
                            <th>Email</th>
                            <th>Created On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                            <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                    <td class="actions-cell">
                                        <button class="edit-btn" onclick="openEditModal(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars(addslashes($customer['name'])); ?>', '<?php echo htmlspecialchars(addslashes($customer['email'])); ?>', '<?php echo htmlspecialchars(addslashes($customer['mobile'])); ?>', '<?php echo htmlspecialchars(addslashes($customer['address'])); ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this customer?')" style="display: inline;">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <button type="submit" name="delete_customer" class="delete-btn">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">No customers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    
    <!-- Add Customer Modal -->
    <div id="add-modal" class="admin-modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add New Customer</h2>
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
                    <label for="mobile">Mobile Number</label>
                    <input type="text" id="mobile" name="mobile" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Complete Address</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                
                <button type="submit" name="add_customer" class="submit-btn">Add Customer</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Customer Modal -->
    <div id="edit-modal" class="admin-modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Customer</h2>
            <form method="POST">
                <input type="hidden" id="edit_customer_id" name="customer_id">
                
                <div class="form-group">
                    <label for="edit_name">Full Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_mobile">Mobile Number</label>
                    <input type="text" id="edit_mobile" name="mobile" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_address">Complete Address</label>
                    <textarea id="edit_address" name="address" rows="3" required></textarea>
                </div>
                
                <button type="submit" name="update_customer" class="submit-btn">Update Customer</button>
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
        
        function openEditModal(id, name, email, mobile, address) {
            document.getElementById('edit_customer_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_mobile').value = mobile;
            document.getElementById('edit_address').value = address;
            document.getElementById('edit-modal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('edit-modal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('add-modal')) {
                closeAddModal();
            }
            if (event.target == document.getElementById('edit-modal')) {
                closeEditModal();
            }
        }
    </script>
</body>

</html> 