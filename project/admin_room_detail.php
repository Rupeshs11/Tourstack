<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_index.php");
    exit;
}

// Check if room ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['admin_error'] = "Invalid room ID.";
    header("Location: simple_rooms.php");
    exit;
}

$room_id = $_GET['id'];

// Get room details with room type info
$room_query = "SELECT r.*, f.floor_name 
               FROM rooms r
               LEFT JOIN floors f ON r.floor = f.floor_number
               WHERE r.id = ?";
$room_stmt = $conn->prepare($room_query);
$room_stmt->bind_param("i", $room_id);
$room_stmt->execute();
$result = $room_stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['admin_error'] = "Room not found.";
    header("Location: simple_rooms.php");
    exit;
}

$room = $result->fetch_assoc();

// Get booking history for this room
$bookings_query = "SELECT rb.*, u.name as user_name, u.email as user_email
                   FROM room_bookings rb
                   LEFT JOIN users u ON rb.user_id = u.id
                   WHERE rb.room_id = ?
                   ORDER BY rb.check_in_date DESC
                   LIMIT 10";
$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $room_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

// Get reviews for this room
$reviews_query = "SELECT rr.*, u.name as user_name
                  FROM room_reviews rr
                  LEFT JOIN users u ON rr.user_id = u.id
                  WHERE rr.room_id = ?
                  ORDER BY rr.created_at DESC";
$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->bind_param("i", $room_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Include header
include 'includes/admin_header.php';
?>

<div class="admin-container">
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Room Details: <?php echo htmlspecialchars($room['name']); ?></h2>
                <div class="admin-header-actions">
                    <a href="simple_rooms.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Back to Rooms
                    </a>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editRoomModal"
                       data-room-id="<?php echo $room['id']; ?>">
                        <i class="fas fa-edit"></i> Edit Room
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Room Information</h5>
                        <span class="status-badge <?php echo $room['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo ucfirst($room['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="room-image-container mb-3">
                                    <?php 
                                    $image_path = !empty($room['image_path']) ? $room['image_path'] : 
                                                (!empty($room['type_image']) ? $room['type_image'] : 'images/rooms/default-room.jpg');
                                    ?>
                                    <img src="<?php echo $image_path; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($room['name']); ?>">
                                </div>
                            </div>
                            <div class="col-md-7">
                                <table class="table table-borderless room-details-table">
                                    <tbody>
                                        <tr>
                                            <th width="40%">Room Number:</th>
                                            <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Room Type:</th>
                                            <td><?php echo htmlspecialchars($room['type']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Floor:</th>
                                            <td>
                                                <?php 
                                                if (!empty($room['floor_number']) && !empty($room['floor_name'])) {
                                                    echo "Floor " . $room['floor_number'] . " - " . htmlspecialchars($room['floor_name']);
                                                } else {
                                                    echo "Floor 1";
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Price per Night:</th>
                                            <td>₹<?php echo number_format($room['price'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Maximum Occupancy:</th>
                                            <td><?php echo $room['max_occupancy']; ?> guests</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Beds:</th>
                                            <td><?php echo $room['beds']; ?></td>
                                        </tr>
                                        <?php if (!empty($room['room_size'])): ?>
                                        <tr>
                                            <th>Room Size:</th>
                                            <td><?php echo $room['room_size']; ?> sq ft</td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($room['promo_text'])): ?>
                                        <tr>
                                            <th>Promotional Text:</th>
                                            <td><?php echo htmlspecialchars($room['promo_text']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="room-description mt-3">
                            <h6>Room Description</h6>
                            <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                        </div>
                        
                        <?php if (!empty($room['type_amenities'])): ?>
                        <div class="room-amenities mt-4">
                            <h6>Amenities</h6>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <?php 
                                $amenities = explode(',', $room['type_amenities']);
                                foreach ($amenities as $amenity): 
                                    if (trim($amenity)):
                                ?>
                                    <span class="amenity-badge">
                                        <i class="fas fa-check me-1"></i> <?php echo htmlspecialchars(trim($amenity)); ?>
                                    </span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Bookings</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($bookings_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Guest</th>
                                            <th>Dates</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $booking['id']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($booking['user_name']); ?><br>
                                                    <small class="text-muted"><?php echo $booking['user_email']; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                                </td>
                                                <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $booking['status'] == 'confirmed' ? 'status-active' : ($booking['status'] == 'cancelled' ? 'status-cancelled' : 'status-pending'); ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="admin_booking_detail.php?id=<?php echo $booking['id']; ?>&type=room" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($bookings_result->num_rows >= 10): ?>
                                <div class="text-center mt-3">
                                    <a href="admin_bookings.php?room_id=<?php echo $room_id; ?>" class="btn btn-outline-primary btn-sm">View All Bookings</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No bookings found for this room.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Room Statistics</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate statistics
                        $stats_query = "SELECT 
                                         COUNT(*) as total_bookings,
                                         SUM(total_amount) as total_revenue,
                                         AVG(DATEDIFF(check_out_date, check_in_date)) as avg_stay_length
                                         FROM room_bookings 
                                         WHERE room_id = ? AND status != 'cancelled'";
                        $stats_stmt = $conn->prepare($stats_query);
                        $stats_stmt->bind_param("i", $room_id);
                        $stats_stmt->execute();
                        $stats_result = $stats_stmt->get_result();
                        $stats = $stats_result->fetch_assoc();
                        
                        // Calculate occupancy rate
                        $occupancy_query = "SELECT 
                                          SUM(DATEDIFF(check_out_date, check_in_date)) as days_booked
                                          FROM room_bookings 
                                          WHERE room_id = ? 
                                          AND status != 'cancelled'
                                          AND check_in_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                        $occupancy_stmt = $conn->prepare($occupancy_query);
                        $occupancy_stmt->bind_param("i", $room_id);
                        $occupancy_stmt->execute();
                        $occupancy_result = $occupancy_stmt->get_result();
                        $occupancy_data = $occupancy_result->fetch_assoc();
                        
                        $days_booked = $occupancy_data['days_booked'] ?: 0;
                        $occupancy_rate = ($days_booked / 30) * 100;
                        ?>
                        
                        <div class="stats-container">
                            <div class="stat-item">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h6>Total Bookings</h6>
                                    <h3><?php echo $stats['total_bookings'] ?: 0; ?></h3>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                                <div class="stat-info">
                                    <h6>Total Revenue</h6>
                                    <h3>₹<?php echo number_format($stats['total_revenue'] ?: 0, 2); ?></h3>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div class="stat-info">
                                    <h6>Avg. Stay Length</h6>
                                    <h3><?php echo round($stats['avg_stay_length'] ?: 0, 1); ?> days</h3>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div class="stat-info">
                                    <h6>30-Day Occupancy</h6>
                                    <h3><?php echo round($occupancy_rate, 1); ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Guest Reviews</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($reviews_result->num_rows > 0): ?>
                            <div class="reviews-container">
                                <?php while ($review = $reviews_result->fetch_assoc()): ?>
                                    <div class="review-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                            </div>
                                            <div class="rating">
                                                <?php
                                                $rating = $review['rating'];
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fas fa-star text-warning"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-muted"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($review['comment']); ?></p>
                                        <?php if (!empty($review['admin_reply'])): ?>
                                            <div class="admin-reply">
                                                <small class="d-block text-muted">Admin Reply:</small>
                                                <p class="mb-0"><?php echo htmlspecialchars($review['admin_reply']); ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-outline-primary reply-btn" 
                                                        data-review-id="<?php echo $review['id']; ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#replyReviewModal">
                                                    <i class="fas fa-reply"></i> Reply
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No reviews found for this room.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoomModalLabel">Edit Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="admin_room_edit.php" method="post" id="editRoomForm">
                    <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                    <!-- Basic details -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Room Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Room Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <?php 
                                // Get distinct room types
                                $types_query = "SELECT DISTINCT type FROM rooms WHERE type IS NOT NULL AND type != '' ORDER BY type";
                                $types_result = $conn->query($types_query);
                                
                                if ($types_result && $types_result->num_rows > 0) {
                                    while($type = $types_result->fetch_assoc()) {
                                        $selected = ($room['type'] == $type['type']) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($type['type']) . "\" $selected>" . htmlspecialchars($type['type']) . "</option>";
                                    }
                                }
                                ?>
                                <option value="Standard" <?php echo ($room['type'] == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                <option value="Deluxe" <?php echo ($room['type'] == 'Deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                                <option value="Suite" <?php echo ($room['type'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                <option value="Family" <?php echo ($room['type'] == 'Family') ? 'selected' : ''; ?>>Family</option>
                                <option value="Premium" <?php echo ($room['type'] == 'Premium') ? 'selected' : ''; ?>>Premium</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="room_number" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="price" class="form-label">Price per Night (₹)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="<?php echo $room['price']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo $room['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $room['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="beds" class="form-label">Number of Beds</label>
                            <input type="number" class="form-control" id="beds" name="beds" min="1" value="<?php echo $room['beds']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="room_size" class="form-label">Room Size (sq ft)</label>
                            <input type="text" class="form-control" id="room_size" name="room_size" value="<?php echo $room['room_size']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="promo_text" class="form-label">Promotional Text</label>
                            <input type="text" class="form-control" id="promo_text" name="promo_text" value="<?php echo htmlspecialchars($room['promo_text'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($room['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Room Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                        <div class="form-text">Leave blank to keep the current image.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" form="editRoomForm">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Reply to Review Modal -->
<div class="modal fade" id="replyReviewModal" tabindex="-1" aria-labelledby="replyReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyReviewModalLabel">Reply to Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="admin_review_reply.php" method="post">
                <input type="hidden" name="review_id" id="review_id" value="">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin_reply" class="form-label">Your Reply</label>
                        <textarea class="form-control" id="admin_reply" name="admin_reply" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .room-image-container {
        height: 250px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
    
    .room-image-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    
    .room-details-table th {
        color: #6c757d;
    }
    
    .amenity-badge {
        background-color: #f0f0f0;
        color: #555;
        padding: 0.3rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.8rem;
    }
    
    .status-badge {
        padding: 0.3rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .status-active {
        background-color: #d1f8d9;
        color: #198754;
    }
    
    .status-inactive {
        background-color: #f5f5f5;
        color: #6c757d;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .stats-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border-radius: 8px;
        background-color: #f8f9fa;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    
    .stat-info h6 {
        margin-bottom: 0;
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .stat-info h3 {
        margin-bottom: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .reviews-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .review-item {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .admin-reply {
        margin-top: 10px;
        padding: 10px;
        background-color: #e9f5fb;
        border-radius: 4px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reply to review modal
    const replyReviewModal = document.getElementById('replyReviewModal');
    replyReviewModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const reviewId = button.getAttribute('data-review-id');
        document.getElementById('review_id').value = reviewId;
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?> 