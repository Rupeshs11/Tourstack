<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin/admin.php");
    exit;
}

// Fetch statistics
// Count users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total_users'];

// Count tour bookings
$bookings_query = "SELECT COUNT(*) as total_bookings FROM tour_bookings";
$bookings_result = $conn->query($bookings_query);
$total_bookings = $bookings_result->fetch_assoc()['total_bookings'];

// Get pending bookings
$pending_query = "SELECT COUNT(*) as pending_bookings FROM tour_bookings WHERE booking_status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_bookings = $pending_result->fetch_assoc()['pending_bookings'];

// Calculate total revenue
$revenue_query = "SELECT SUM(total_amount) as total_revenue FROM tour_bookings WHERE payment_status = 'paid'";
$revenue_result = $conn->query($revenue_query);
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

// Get recent bookings
$recent_bookings_query = "SELECT tb.*, u.name as user_name, t.name as tour_name 
                          FROM tour_bookings tb 
                          JOIN users u ON tb.user_id = u.id 
                          JOIN tours t ON tb.tour_id = t.id 
                          ORDER BY tb.created_at DESC LIMIT 5";
$recent_bookings_result = $conn->query($recent_bookings_query);

// Get booking statistics by month for the current year
$bookings_by_month_query = "SELECT MONTH(booking_date) as month, COUNT(*) as count 
                           FROM tour_bookings 
                           WHERE YEAR(booking_date) = YEAR(CURDATE()) 
                           GROUP BY MONTH(booking_date)";
$bookings_by_month_result = $conn->query($bookings_by_month_query);

$bookings_by_month = array_fill(1, 12, 0); // Initialize all months with 0
if ($bookings_by_month_result->num_rows > 0) {
    while ($row = $bookings_by_month_result->fetch_assoc()) {
        $bookings_by_month[$row['month']] = (int)$row['count'];
    }
}

// Get booking status statistics
$booking_status_query = "SELECT booking_status, COUNT(*) as count 
                        FROM tour_bookings 
                        GROUP BY booking_status";
$booking_status_result = $conn->query($booking_status_query);

$booking_status_data = [
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

if ($booking_status_result->num_rows > 0) {
    while ($row = $booking_status_result->fetch_assoc()) {
        $status = $row['booking_status'];
        if (array_key_exists($status, $booking_status_data)) {
            $booking_status_data[$status] = (int)$row['count'];
        }
    }
}

// Get tour popularity data
$tour_popularity_query = "SELECT t.name, COUNT(tb.id) as booking_count 
                         FROM tours t 
                         LEFT JOIN tour_bookings tb ON t.id = tb.tour_id 
                         GROUP BY t.id 
                         ORDER BY booking_count DESC 
                         LIMIT 5";
$tour_popularity_result = $conn->query($tour_popularity_query);

$tour_names = [];
$tour_bookings = [];

if ($tour_popularity_result->num_rows > 0) {
    while ($row = $tour_popularity_result->fetch_assoc()) {
        $tour_names[] = $row['name'];
        $tour_bookings[] = (int)$row['booking_count'];
    }
}

// Get payment statistics
$payment_query = "SELECT 
    (SELECT COUNT(*) FROM payments WHERE status = 'completed') as completed_payments,
    (SELECT COUNT(*) FROM payments WHERE status = 'pending') as pending_payments,
    (SELECT COUNT(*) FROM payments WHERE status = 'failed') as failed_payments,
    (SELECT SUM(amount) FROM payments WHERE status = 'completed') as total_payment_amount";
$payment_result = $conn->query($payment_query);
$payment_stats = $payment_result->fetch_assoc();

$completed_payments = $payment_stats['completed_payments'] ?? 0;
$pending_payments = $payment_stats['pending_payments'] ?? 0;
$failed_payments = $payment_stats['failed_payments'] ?? 0;
$total_payment_amount = $payment_stats['total_payment_amount'] ?? 0;

// Get payment method statistics
$payment_method_query = "SELECT 
    COUNT(*) as count,
    DATE_FORMAT(created_at, '%Y-%m') as month
    FROM payments 
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6";
$payment_method_result = $conn->query($payment_method_query);

$payment_months = [];
$payment_counts = [];

if ($payment_method_result && $payment_method_result->num_rows > 0) {
    while ($row = $payment_method_result->fetch_assoc()) {
        $payment_months[] = date('M Y', strtotime($row['month'] . '-01'));
        $payment_counts[] = (int)$row['count'];
    }
}

// Reverse the arrays to show chronological order
$payment_months = array_reverse($payment_months);
$payment_counts = array_reverse($payment_counts);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TOUR STACK</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .chart-container {
            height: 350px;
            margin: 20px 0;
        }
        .chart-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .chart-col {
            flex: 1;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 100%;
        }
        .chart-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #343a40;
            text-align: center;
        }
        
        /* Single chart row styling */
        .single-chart {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .full-width {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }
        
        @media (max-width: 992px) {
            .chart-row {
                flex-direction: column;
            }
            .chart-col {
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body class="admin-body">
    <header>
        <h1 class="admin-page-title">Admin Dashboard</h1>
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
                <a href="admin/admin_dashboard.php" class="sidebar-nav-item active">
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
                <a href="admin/admin_users.php" class="sidebar-nav-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Users</span>
                </a>
                
            </div>
        </aside>

        <main class="admin-main">
            <section class="admin-section">
                <h2 class="section-title">System Statistics</h2>
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
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Total Bookings</h3>
                            <p><?php echo $total_bookings; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Total Revenue</h3>
                            <p>₹<?php echo number_format($total_payment_amount, 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Pending Bookings</h3>
                            <p><?php echo $pending_bookings; ?></p>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="admin-section">
                <h2 class="section-title">Payment Statistics</h2>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Completed Payments</h3>
                            <p><?php echo $completed_payments; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Pending Payments</h3>
                            <p><?php echo $pending_payments; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Failed Payments</h3>
                            <p><?php echo $failed_payments; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-data">
                            <h3>Conversion Rate</h3>
                            <p><?php echo ($total_bookings > 0) ? round(($completed_payments / $total_bookings) * 100) : 0; ?>%</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="admin-section">
                <h2 class="section-title">Performance Analytics</h2>
                
                <div class="chart-row">
                    <div class="chart-col">
                        <div class="chart-title">Monthly Bookings</div>
                        <canvas id="monthlyBookingsChart"></canvas>
                    </div>
                    <div class="chart-col">
                        <div class="chart-title">Booking Status</div>
                        <canvas id="bookingStatusChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-row">
                    <div class="chart-col">
                        <div class="chart-title">Monthly Payments</div>
                        <canvas id="monthlyPaymentsChart"></canvas>
                    </div>
                    <div class="chart-col">
                        <div class="chart-title">Payment Status</div>
                        <canvas id="paymentStatusChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-row single-chart">
                    <div class="chart-col full-width">
                        <div class="chart-title">Most Popular Tours</div>
                        <div class="chart-container">
                            <canvas id="toursChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="admin-section">
                <h2 class="section-title">Recent Bookings</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Tour</th>
                            <th>Date</th>
                            <th>People</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_bookings_result->num_rows > 0): ?>
                            <?php while ($booking = $recent_bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['tour_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                    <td><?php echo $booking['people']; ?></td>
                                    <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['booking_status']); ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">No bookings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="view-all">
                    <a href="admin/admin_bookings.php" class="view-all-btn">View All Bookings</a>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Charts initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Monthly Bookings Chart
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
            new Chart(bookingsCtx, {
                type: 'bar',
                data: {
                    labels: monthNames,
                    datasets: [{
                        label: 'Bookings',
                        data: <?php echo json_encode(array_values($bookings_by_month)); ?>,
                        backgroundColor: 'rgba(255, 102, 0, 0.7)',
                        borderColor: '#ff6600',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: false,
                            text: 'Monthly Bookings (<?php echo date('Y'); ?>)'
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: 'Number of Bookings'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
            
            // Booking Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?php echo $booking_status_data['pending']; ?>,
                            <?php echo $booking_status_data['confirmed']; ?>,
                            <?php echo $booking_status_data['completed']; ?>,
                            <?php echo $booking_status_data['cancelled']; ?>
                        ],
                        backgroundColor: [
                            '#FFC107', // Pending - yellow
                            '#0D6EFD', // Confirmed - blue
                            '#198754', // Completed - green
                            '#DC3545'  // Cancelled - red
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
            
            // Popular Tours Chart
            const toursCtx = document.getElementById('toursChart').getContext('2d');
            new Chart(toursCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($tour_names); ?>,
                    datasets: [{
                        label: 'Bookings',
                        data: <?php echo json_encode($tour_bookings); ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: '#0D6EFD',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',  // Horizontal bar chart
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: 'Number of Bookings'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Tour Name'
                            }
                        }
                    }
                }
            });

            // Create payment status chart
            var paymentStatusChart = new Chart(document.getElementById('paymentStatusChart'), {
                type: 'pie',
                data: {
                    labels: ['Completed', 'Pending', 'Failed'],
                    datasets: [{
                        label: 'Payment Status',
                        data: [<?php echo $completed_payments; ?>, <?php echo $pending_payments; ?>, <?php echo $failed_payments; ?>],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Create monthly payments chart
            var monthlyPaymentsChart = new Chart(document.getElementById('monthlyPaymentsChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($payment_months); ?>,
                    datasets: [{
                        label: 'Completed Payments',
                        data: <?php echo json_encode($payment_counts); ?>,
                        backgroundColor: 'rgba(255, 102, 0, 0.6)',
                        borderColor: 'rgba(255, 102, 0, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
        
        // Function to handle sidebar toggle on mobile
        const toggleBtn = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-collapsed');
            });
        }
    </script>
</body>

</html> 