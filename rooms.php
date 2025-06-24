<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Get rooms with room type info
$query = "SELECT * FROM rooms 
          WHERE status = 'active' 
          ORDER BY price ASC";
$result = $conn->query($query);

// Store rooms in an array for later use
$rooms = [];
if ($result && $result->num_rows > 0) {
    while($room = $result->fetch_assoc()) {
        $rooms[] = $room;
    }
    // Reset result pointer for later use
    mysqli_data_seek($result, 0);
}

// Handle search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$price_filter = isset($_GET['price']) ? $_GET['price'] : 'all';
$guests_filter = isset($_GET['guests']) ? $_GET['guests'] : 'all';

// Get distinct room types for filter
$types_query = "SHOW COLUMNS FROM rooms LIKE 'type'";
$type_column_exists = $conn->query($types_query);

if ($type_column_exists && $type_column_exists->num_rows > 0) {
    // If 'type' column exists, use it
    $types_query = "SELECT DISTINCT type FROM rooms WHERE status = 'active' AND type IS NOT NULL ORDER BY type";
} else {
    // Fallback to alternative approach
    $types_query = "SELECT DISTINCT name as type FROM room_types ORDER BY name";
}

$types_result = $conn->query($types_query);
$room_types = array();
if ($types_result && $types_result->num_rows > 0) {
    while($type = $types_result->fetch_assoc()) {
        if (!empty($type['type'])) {
            $room_types[] = $type['type'];
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="room-title-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="room-title-heading">Our Luxurious Rooms</h1>
            <button id="filterButton" class="btn btn-outline-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </div>
        </div>

<main class="main-content">
    <div class="container">
        <?php if(isset($_SESSION['booking_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['booking_success']; ?>
            </div>
            <?php unset($_SESSION['booking_success']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['booking_error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['booking_error']; ?>
            </div>
            <?php unset($_SESSION['booking_error']); ?>
        <?php endif; ?>

        <div class="container mt-4">
            <!-- Filter Modal -->
            <div id="filterModal" class="modal fade" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="filterModalLabel">Filter Rooms</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="room-filter-form" action="rooms.php" method="get">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                <div class="filter-group">
                                            <label for="search">Search:</label>
                                            <input type="text" id="search" name="search" class="form-control" placeholder="Search by name, type or amenities" value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                </div>
                                    <div class="col-md-6 mb-3">
                <div class="filter-group">
                    <label for="type-filter">Room Type:</label>
                                            <select id="type-filter" name="type" class="form-select">
                                                <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                                <?php foreach ($room_types as $type): ?>
                                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter == $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                                <?php endforeach; ?>
                    </select>
                </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                <div class="filter-group">
                    <label for="guests-filter">Guests:</label>
                                            <select id="guests-filter" name="guests" class="form-select">
                                                <option value="all" <?php echo $guests_filter == 'all' ? 'selected' : ''; ?>>Any</option>
                                                <option value="1" <?php echo $guests_filter == '1' ? 'selected' : ''; ?>>1 Guest</option>
                                                <option value="2" <?php echo $guests_filter == '2' ? 'selected' : ''; ?>>2 Guests</option>
                                                <option value="3-4" <?php echo $guests_filter == '3-4' ? 'selected' : ''; ?>>3-4 Guests</option>
                                                <option value="5+" <?php echo $guests_filter == '5+' ? 'selected' : ''; ?>>5+ Guests</option>
                    </select>
                </div>
            </div>
        </div>
                                <div class="modal-footer">
                                    <a href="rooms.php" class="btn btn-outline-secondary">Reset</a>
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                </div>
            </div>
        </div>

            <?php 
            // Group rooms by type
            $rooms_by_type = [];
            
            if ($result && $result->num_rows > 0) {
                mysqli_data_seek($result, 0); // Reset result pointer
                
                while($room = $result->fetch_assoc()) {
                    // Apply filters
                    if ($search && stripos($room['name'] . $room['description'] . $room['amenities'], $search) === false) {
                        continue;
                    }
                    
                    // Check for room type filtering
                    if ($type_filter != 'all') {
                        // Get the type value from the room
                        $room_type_value = !empty($room['type']) ? $room['type'] : 'Standard';
                        
                        // Compare with filter
                        if ($room_type_value != $type_filter) {
                            continue;
                        }
                    }
                    
                    $capacity = isset($room['capacity']) ? $room['capacity'] : 2;
                    
                    if ($guests_filter != 'all') {
                        if ($guests_filter == '1' && $capacity != 1) {
                            continue;
                        } else if ($guests_filter == '2' && $capacity != 2) {
                            continue;
                        } else if ($guests_filter == '3-4' && ($capacity < 3 || $capacity > 4)) {
                            continue;
                        } else if ($guests_filter == '5+' && $capacity < 5) {
                            continue;
                        }
                    }
                    
                    // Get the room type
                    $room_type = !empty($room['type']) ? $room['type'] : 'Standard';
                    
                    // Add room to the appropriate type group
                    if (!isset($rooms_by_type[$room_type])) {
                        $rooms_by_type[$room_type] = [];
                    }
                    
                    $rooms_by_type[$room_type][] = $room;
                }
            }
            
            // Display rooms grouped by type
            if (!empty($rooms_by_type)) {
                foreach ($rooms_by_type as $type => $type_rooms) {
            ?>
                <div class="room-type-section mb-5">
                    <div class="room-type-header">
                        <h2 class="room-type-heading"><?php echo htmlspecialchars($type); ?> Rooms</h2>
                        <div class="room-type-description">
                            <?php 
                            // Define descriptions for different room types
                            $type_descriptions = [
                                'Standard' => 'Our comfortable standard rooms offer essential amenities for a pleasant stay at great value.',
                                'Deluxe' => 'Experience enhanced comfort with our spacious deluxe rooms featuring premium amenities.',
                                'Executive Suite' => 'Luxurious suites with separate living areas, perfect for business travelers or longer stays.',
                                'Family Room' => 'Spacious accommodations designed for families, with multiple beds and kid-friendly amenities.',
                                'Premium Suite' => 'Our most luxurious option with exclusive amenities and services for an unforgettable experience.'
                            ];
                            
                            // Display the appropriate description or a default one
                            echo isset($type_descriptions[$type]) ? $type_descriptions[$type] : 'Discover our selection of quality accommodations.';
                            ?>
                        </div>
                    </div>
                    
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($type_rooms as $room) {
                            $image_path = !empty($room['image_path']) ? $room['image_path'] : 'images/rooms/default-room.jpg';
                            $amenities = !empty($room['amenities']) ? explode(', ', $room['amenities']) : [];
                            $capacity = isset($room['capacity']) ? $room['capacity'] : 2;
                        ?>
                            <div class="col">
                                <div class="room-card">
                                    <?php if (!empty($room['promo_text'])): ?>
                                        <div class="room-tag"><?php echo htmlspecialchars($room['promo_text']); ?></div>
                                    <?php endif; ?>
                                    <div class="square-img">
                                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                                    </div>
                                    <div class="room-details">
                                        <h3 class="room-title"><?php echo htmlspecialchars($room['name']); ?></h3>
                                        <div class="room-info">
                                            <p class="room-feature"><i class="fas fa-users"></i> Max <?php echo $capacity; ?> guests</p>
                                            <?php if (!empty($room['beds'])): ?><p class="room-feature"><i class="fas fa-bed"></i> <?php echo $room['beds']; ?> <?php echo $room['beds'] > 1 ? 'beds' : 'bed'; ?></p><?php endif; ?>
                                            <?php if (!empty($room['room_size'])): ?><p class="room-feature"><i class="fas fa-expand-arrows-alt"></i> <?php echo $room['room_size']; ?> sq ft</p><?php endif; ?>
                                        </div>
                                        <div class="price-book">
                                            <div class="price">₹<?php echo number_format($room['price'], 2); ?><small>/night</small></div>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bookRoomModal<?php echo $room['id']; ?>">
                                                Book Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo '<div class="col-12 text-center"><p>No rooms found.</p></div>';
            }
            ?>
        </div>
    </div>
</main>

<style>
    /* Room title banner styling */
    .room-title-banner {
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        padding: 15px 0;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        background-color: white;
    }
    
    .room-title-heading {
        margin: 0;
        font-size: 2.2rem;
        font-weight: 700;
        color: #333;
        white-space: nowrap;
    }
    
    .page-header {
        padding: 15px 0;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }
    
    .section-intro {
        text-align: left;
        margin-bottom: 30px;
        color: var(--gray-600);
        font-size: 1.1rem;
        line-height: 1.6;
    }
    
    .section-intro div {
        max-width: 700px;
    }
    
    .section-intro h2 {
        color: var(--dark-color);
        font-weight: 600;
        margin-bottom: 10px;
        text-align: left;
    }
    
    .section-intro p {
        margin-bottom: 0;
    }
    
    /* For the page header intro only */
    .container > .section-intro {
        text-align: center;
        max-width: 800px;
        margin: 0 auto 30px;
    }
    
    .filter-section {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .filter-group label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.4rem;
    }
    
    /* Room card styling for 3x3 grid */
    .row-cols-md-3 > * {
        padding: 10px;
    }
    
    .room-card {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        background-color: white;
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
        max-width: 100%;
        margin: 0 auto;
    }
    
    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.12);
    }
    
    .square-img {
        position: relative;
        width: 100%;
        height: 0;
        padding-bottom: 65%;
        overflow: hidden;
    }
    
    .square-img img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .room-card:hover .square-img img {
        transform: scale(1.05);
    }
    
    .room-tag {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: rgba(231, 76, 60, 0.8);
        color: white;
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 3px;
        font-weight: 500;
        z-index: 1;
    }
    
    .room-details {
        padding: 15px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .room-title {
        font-size: 1.1rem;
        line-height: 1.3;
        margin-bottom: 0.75rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .room-info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        margin-bottom: 0.5rem;
        padding: 0;
    }
    
    .room-feature {
        margin-bottom: 0.3rem;
        font-size: 0.8rem;
        line-height: 1.3;
        color: #666;
    }
    
    .room-feature i {
        width: 16px;
        color: #e74c3c;
        margin-right: 0.4rem;
    }
    
    .price-book {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    
    .price {
        font-weight: 600;
        color: #e74c3c;
        font-size: 1rem;
    }
    
    .price small {
        font-weight: 400;
        color: #777;
        font-size: 0.75rem;
    }
    
    .btn-sm {
        padding: 0.2rem 0.5rem;
        font-size: 0.8rem;
    }
    
    /* For mobile and smaller screens */
    @media (max-width: 767px) {
        .row-cols-1 > * {
            margin-bottom: 20px;
        }
        
        .room-card {
            max-width: 100%;
        }
    }

    /* Room type section styling */
    .room-type-section {
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #eee;
    }
    
    .room-type-section:last-child {
        border-bottom: none;
    }
    
    .room-type-header {
        margin-bottom: 1.5rem;
        position: relative;
        padding-left: 15px;
        border-left: 4px solid #e74c3c;
    }
    
    .room-type-heading {
        color: #333;
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .room-type-description {
        color: #666;
        font-size: 1rem;
        max-width: 800px;
        line-height: 1.5;
    }
    
    /* Filter button styles */
    #filterButton {
        border-radius: 50px;
        padding: 8px 16px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    #filterButton.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    /* Modal styles */
    .modal-header {
        border-bottom: 1px solid #eee;
        background-color: #f8f9fa;
    }
    
    .modal-title {
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .modal-footer {
        border-top: 1px solid #eee;
        padding: 15px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter button click event
        const filterButton = document.getElementById('filterButton');
        const filterModalEl = document.getElementById('filterModal');
        const filterModal = new bootstrap.Modal(filterModalEl);
        
        if (filterButton) {
            filterButton.addEventListener('click', function() {
                filterModal.show();
            });
        }
        
        // If there are active filters, show an indicator on the filter button
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('search') || urlParams.has('type') || urlParams.has('guests')) {
            const filterButton = document.getElementById('filterButton');
            if (filterButton) {
                filterButton.classList.add('active');
                
                // Create a badge to show the number of active filters
                let activeFilterCount = 0;
                if (urlParams.get('search')) activeFilterCount++;
                if (urlParams.get('type') && urlParams.get('type') !== 'all') activeFilterCount++;
                if (urlParams.get('guests') && urlParams.get('guests') !== 'all') activeFilterCount++;
                
                if (activeFilterCount > 0) {
                    filterButton.innerHTML = `<i class="fas fa-filter"></i> Filter <span class="badge bg-primary ms-1">${activeFilterCount}</span>`;
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>

<!-- Add this before the closing body tag -->
<?php foreach ($rooms as $room): ?>
<!-- Booking Modal for <?php echo $room['name']; ?> -->
<div class="modal fade" id="bookRoomModal<?php echo $room['id']; ?>" tabindex="-1" aria-labelledby="bookRoomModalLabel<?php echo $room['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="bookRoomModalLabel<?php echo $room['id']; ?>">Book <?php echo $room['name']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form method="post" action="book_room.php" class="booking-form" id="booking_form_<?php echo $room['id']; ?>">
                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                    <input type="hidden" name="proceed_with_booking" id="proceed_with_booking_<?php echo $room['id']; ?>" value="false">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex mb-2">
                                <div class="room-image me-2" style="width:70px; height:70px;">
                                    <img src="<?php echo $room['image_path'] ?? 'image/rooms/room1.jpg'; ?>" 
                                         alt="<?php echo $room['name']; ?>" 
                                         class="img-fluid rounded h-100 w-100 object-fit-cover">
                                </div>
                                <div class="room-details">
                                    <h6 class="mb-1"><?php echo $room['name']; ?></h6>
                                    <p class="mb-0 small"><i class="fas fa-tag me-1"></i> ₹<?php echo number_format($room['price'], 2); ?>/night</p>
                                    <p class="mb-0 small"><i class="fas fa-users me-1"></i> Max <?php echo $room['capacity'] ?? 2; ?> guests</p>
                                </div>
                            </div>
                            
                            <hr class="my-2">
                            
                            <div class="booking-dates mb-2">
                                <h6 class="mb-1">Select Dates</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="check_in_<?php echo $room['id']; ?>" class="form-label small">Check-in</label>
                                        <input type="date" id="check_in_<?php echo $room['id']; ?>" name="check_in" class="form-control form-control-sm" 
                                            min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="col-6">
                                        <label for="check_out_<?php echo $room['id']; ?>" class="form-label small">Check-out</label>
                                        <input type="date" id="check_out_<?php echo $room['id']; ?>" name="check_out" class="form-control form-control-sm" 
                                            min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mb-1">Guest Details</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label for="adults_<?php echo $room['id']; ?>" class="form-label small">Adults</label>
                                    <select id="adults_<?php echo $room['id']; ?>" name="adults" class="form-select form-select-sm">
                                        <?php for ($i = 1; $i <= min(10, $room['capacity'] ?? 4); $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-6">
                                    <label for="children_<?php echo $room['id']; ?>" class="form-label small">Children</label>
                                    <select id="children_<?php echo $room['id']; ?>" name="children" class="form-select form-select-sm">
                                        <?php for ($i = 0; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label for="special_requests_<?php echo $room['id']; ?>" class="form-label small">Special Requests</label>
                                    <textarea id="special_requests_<?php echo $room['id']; ?>" name="special_requests" class="form-control form-control-sm" rows="1" placeholder="Any special requirements?"></textarea>
                                </div>
                            </div>
                            
                            <div class="price-summary p-2 rounded bg-light mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 small">₹<?php echo number_format($room['price'], 2); ?> × <span id="total_nights_<?php echo $room['id']; ?>">1</span> nights</h6>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0 text-primary">₹<span id="total_price_<?php echo $room['id']; ?>"><?php echo number_format($room['price'], 2); ?></span></h5>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="book_room" class="btn btn-primary btn-sm py-1 px-3">Book Now</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Add SweetAlert2 and Confetti libraries -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>

<!-- Add pending bookings check -->
<script src="js/pending_bookings.js"></script>

<style>
/* Add these styles to your existing CSS */
.modal-content {
    border-radius: 8px;
    border: none;
}

.modal-header {
    border-bottom: 1px solid #f0f0f0;
    padding: 0.75rem 1rem;
}

.modal-body {
    padding: 1rem;
}

.modal-footer {
    border-top: 1px solid #f0f0f0;
    padding: 0.75rem 1rem;
}

.modal-title {
    font-size: 1.1rem;
}

.room-image {
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.object-fit-cover {
    object-fit: cover;
}

.price-summary {
    border: 1px solid #eee;
    border-radius: 6px !important;
}

.room-details p {
    margin-bottom: 0;
    color: #495057;
    font-size: 0.8rem;
}

.room-details p i {
    width: 12px;
    color: #e74c3c;
}

.form-label {
    font-weight: 500;
    color: #333;
    margin-bottom: 0.2rem;
    font-size: 0.85rem;
}

.form-control-sm, .form-select-sm {
    padding: 0.3rem 0.5rem;
    border-radius: 4px;
    border-color: #ddd;
    font-size: 0.85rem;
}

.form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.15);
    border-color: #e74c3c;
}

.btn-primary { 
    background-color: #e74c3c !important; 
    border-color: #e74c3c !important; 
    font-weight: 500;
    border-radius: 4px;
}

.btn-primary:hover { 
    background-color: #c0392b !important; 
    border-color: #c0392b !important; 
}

/* Add smaller button styles */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}

hr {
    margin: 0.5rem 0;
    opacity: 0.1;
    }
</style>

<script>
// Set min date for check-in to today
document.addEventListener('DOMContentLoaded', function() {
    // Format today's date as YYYY-MM-DD for input[type=date]
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');
    
    // Set minimum dates
    if (checkInInput) {
        checkInInput.min = formattedDate;
        
        // When check-in date changes, update check-out min date
        checkInInput.addEventListener('change', function() {
            if (this.value) {
                // Set check-out min date to day after check-in
                const checkInDate = new Date(this.value);
                checkInDate.setDate(checkInDate.getDate() + 1);
                const minCheckOutDate = checkInDate.toISOString().split('T')[0];
                
                checkOutInput.min = minCheckOutDate;
                
                // If check-out date is before the new min date, clear it
                if (checkOutInput.value && new Date(checkOutInput.value) < checkInDate) {
                    checkOutInput.value = '';
                }
                
                // Calculate and update the total price
                calculateTotalPrice();
            }
        });
        
        // When check-out date changes, recalculate price
        if (checkOutInput) {
            checkOutInput.addEventListener('change', calculateTotalPrice);
        }
    }
    
    // Clear any error messages when user starts to make changes
    const formInputs = document.querySelectorAll('#bookingForm input, #bookingForm select');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            const errorAlerts = document.querySelectorAll('.alert-danger');
            errorAlerts.forEach(alert => {
                alert.remove();
            });
        });
    });

    // Book Room Form Submission
    $('#bookingForm').submit(function(e) {
        e.preventDefault();
        
        // Clear any previous error messages
        $('.alert-danger').remove();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        
        // Validate dates
        const checkIn = new Date($('#check_in_date').val());
        const checkOut = new Date($('#check_out_date').val());
        
        if (isNaN(checkIn.getTime()) || isNaN(checkOut.getTime())) {
            $('#bookingForm').before('<div class="alert alert-danger">Please select both check-in and check-out dates.</div>');
            return;
        }
        
        if (checkIn >= checkOut) {
            $('#bookingForm').before('<div class="alert alert-danger">Check-out date must be after check-in date.</div>');
            return;
        }
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
        
        // AJAX submission
        $.ajax({
            url: 'book_room.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                if (response.status === 'success') {
                    // Clear any session errors
                    fetch('clear_session_error.php?type=booking_error', {
                        method: 'GET'
                    });
                    
                    // Show success message with SweetAlert
                    Swal.fire({
                        title: 'Booking Successful!',
                        text: 'Your room has been booked successfully.',
                        icon: 'success',
                        confirmButtonText: 'View My Bookings',
                        showCancelButton: true,
                        cancelButtonText: 'Close'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'my_bookings.php';
                        }
                    });
                    
                    // Reset form
                    $('#bookingForm')[0].reset();
                    
                    // Add confetti effect
                    const duration = 3000;
                    const end = Date.now() + duration;
                    
                    (function frame() {
                        confetti({
                            particleCount: 5,
                            angle: 60,
                            spread: 55,
                            origin: { x: 0 },
                            colors: ['#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d']
                        });
                        confetti({
                            particleCount: 5,
                            angle: 120,
                            spread: 55,
                            origin: { x: 1 },
                            colors: ['#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d']
                        });

                        if (Date.now() < end) {
                            requestAnimationFrame(frame);
                        }
                    }());
                } else {
                    // Show error message
                    $('#bookingForm').before('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                let errorMessage = 'An error occurred. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                
                $('#bookingForm').before('<div class="alert alert-danger">' + errorMessage + '</div>');
            }
        });
    });
});

// Calculate total price based on room price and number of nights
function calculateTotalPrice() {
    const checkInDate = new Date(document.getElementById('check_in_date').value);
    const checkOutDate = new Date(document.getElementById('check_out_date').value);
    const roomPriceElement = document.getElementById('room_price');
    const totalPriceElement = document.getElementById('total_price');
    
    if (!isNaN(checkInDate.getTime()) && !isNaN(checkOutDate.getTime()) && roomPriceElement && totalPriceElement) {
        // Calculate number of nights (check-out date - check-in date)
        const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
        
        if (nights > 0) {
            // Calculate total price
            const roomPrice = parseFloat(roomPriceElement.getAttribute('data-price'));
            const totalPrice = roomPrice * nights;
            
            // Update display
            totalPriceElement.textContent = '₹' + totalPrice.toFixed(2);
            document.getElementById('nights_count').textContent = nights;
            document.getElementById('price_breakdown').style.display = 'block';
        }
    }
}
</script>