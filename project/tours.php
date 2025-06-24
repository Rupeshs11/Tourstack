<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Apply filters if provided
$where_conditions = ["status = 'active'"];
$params = [];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$duration = isset($_GET['duration']) ? $_GET['duration'] : '';

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ? OR location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($location) && $location != 'all') {
    $where_conditions[] = "location = ?";
    $params[] = $location;
}

if (!empty($duration) && $duration != 'all') {
    switch ($duration) {
        case '1-3':
            $where_conditions[] = "duration LIKE '%1-3%' OR duration LIKE '%1 to 3%'";
            break;
        case '4-8':
            $where_conditions[] = "duration LIKE '%4-8%' OR duration LIKE '%4 to 8%'";
            break;
        case 'fullday':
            $where_conditions[] = "duration LIKE '%full day%' OR duration LIKE '%8+%' OR duration LIKE '%8 hours%'";
            break;
        case 'multi':
            $where_conditions[] = "duration LIKE '%multi%' OR duration LIKE '%day%'";
            break;
    }
}

// Get tours from database
$tours = [];
$where_clause = implode(' AND ', $where_conditions);
$query = "SELECT * FROM tours WHERE $where_clause ORDER BY id ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Include header
include 'includes/header.php';
?>

<div class="tour-title-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="tour-title-heading">Guided Tours & Experiences</h1>
            <button id="filterButton" class="btn btn-outline-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </div>
</div>

<main class="main-content">
    <div class="container">
        <!-- Filter Modal -->
        <div id="filterModal" class="modal fade" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filter Tours</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="filterForm" action="tours.php" method="GET">
                            <div class="mb-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Search by name, location or features" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <select id="location" name="location" class="form-select">
                                        <option value="all">All Locations</option>
                                        <option value="Beach" <?php echo $location == 'Beach' ? 'selected' : ''; ?>>Beach</option>
                                        <option value="Mountain" <?php echo $location == 'Mountain' ? 'selected' : ''; ?>>Mountain</option>
                                        <option value="City" <?php echo $location == 'City' ? 'selected' : ''; ?>>City</option>
                                        <option value="Countryside" <?php echo $location == 'Countryside' ? 'selected' : ''; ?>>Countryside</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="duration" class="form-label">Duration</label>
                                    <select id="duration" name="duration" class="form-select">
                                        <option value="all">Any Duration</option>
                                        <option value="1-3" <?php echo $duration == '1-3' ? 'selected' : ''; ?>>1-3 Hours</option>
                                        <option value="4-8" <?php echo $duration == '4-8' ? 'selected' : ''; ?>>4-8 Hours</option>
                                        <option value="fullday" <?php echo $duration == 'fullday' ? 'selected' : ''; ?>>Full Day</option>
                                        <option value="multi" <?php echo $duration == 'multi' ? 'selected' : ''; ?>>Multi-Day</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset Filters</button>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('filterForm').submit();">Apply Filters</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($tour = $result->fetch_assoc()): 
                        $image_path = !empty($tour['image_path']) ? $tour['image_path'] : (!empty($tour['image_url']) ? $tour['image_url'] : 'images/tours/default-tour.jpg');
                    ?>
                        <div class="col-12 mb-4">
                            <div class="tour-card-horizontal">
                                <?php if (!empty($tour['tag'])): ?>
                                    <div class="tour-tag">
                                        <?php echo htmlspecialchars($tour['tag']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="tour-image">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($tour['name']); ?>">
                                    <?php if (isset($tour['location']) && !empty($tour['location'])): ?>
                                    <div class="location-badge"><?php echo htmlspecialchars($tour['location']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="tour-details">
                                    <h3 class="tour-title"><?php echo htmlspecialchars($tour['name']); ?></h3>
                                    <?php if(isset($tour['description']) && !empty($tour['description'])): ?>
                                    <p class="tour-description"><?php echo substr(htmlspecialchars($tour['description']), 0, 150) . '...'; ?></p>
                                    <?php endif; ?>
                                    <div class="tour-info">
                                        <?php if(isset($tour['duration']) && !empty($tour['duration'])): ?>
                                        <div class="tour-feature"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($tour['duration']); ?></div>
                                        <?php endif; ?>
                                        <?php if(isset($tour['max_people'])): ?>
                                        <div class="tour-feature"><i class="fas fa-users"></i> Max <?php echo (int)$tour['max_people']; ?> people</div>
                                        <?php endif; ?>
                                        <?php if(isset($tour['location']) && !empty($tour['location'])): ?>
                                        <div class="tour-feature"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($tour['location']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="price-book">
                                        <?php if(isset($tour['price'])): ?>
                                        <div class="price">₹<?php echo number_format($tour['price'], 2); ?><small>/person</small></div>
                                        <?php endif; ?>
                                        <a href="book_tour.php?id=<?php echo (int)$tour['id']; ?>" class="btn btn-primary">Book Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center"><p>No tours found.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            /* Tour title banner styling */
            .tour-title-banner {
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
            
            .tour-title-heading {
                margin: 0;
                font-size: 2.2rem;
                font-weight: 700;
                color: #333;
                white-space: nowrap;
            }
            
            .section-intro {
                text-align: center;
                max-width: 800px;
                margin: 0 auto 30px;
                color: var(--gray-600);
                font-size: 1.1rem;
                line-height: 1.6;
            }
            
            /* For secondary section intro */
            .container .section-intro {
                max-width: 800px;
                margin: 0 auto 30px;
            }
            
            .section-intro h2 {
                color: var(--dark-color);
                font-weight: 600;
                margin-bottom: 15px;
            }
            
            /* Tour horizontal card styling */
            .tour-card-horizontal {
                display: flex;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                background-color: #fff;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                position: relative;
                border: none;
            }
            
            .tour-card-horizontal:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            }
            
            .tour-image {
                flex: 0 0 40%;
                position: relative;
                overflow: hidden;
            }
            
            .tour-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }
            
            .tour-card-horizontal:hover .tour-image img {
                transform: scale(1.05);
            }
            
            .location-badge {
                position: absolute;
                bottom: 15px;
                left: 15px;
                background-color: rgba(0, 0, 0, 0.7);
                color: white;
                font-size: 0.8rem;
                padding: 4px 10px;
                border-radius: 20px;
                font-weight: 500;
            }
            
            .tour-tag {
                position: absolute;
                top: 15px;
                left: 15px;
                background-color: #e74c3c;
                color: white;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                z-index: 1;
            }
            
            .tour-details {
                flex: 0 0 60%;
                padding: 25px;
                display: flex;
                flex-direction: column;
            }
            
            .tour-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 10px;
                color: #333;
            }
            
            .tour-description {
                font-size: 0.95rem;
                color: #666;
                margin-bottom: 15px;
                line-height: 1.5;
            }
            
            .tour-info {
                display: flex;
                flex-wrap: wrap;
                margin-bottom: 20px;
                gap: 15px;
            }
            
            .tour-feature {
                color: #555;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
            }
            
            .tour-feature i {
                color: #e74c3c;
                margin-right: 8px;
                font-size: 1rem;
                width: 20px;
            }
            
            .price-book {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: auto;
                padding-top: 15px;
                border-top: 1px solid rgba(0,0,0,0.05);
            }
            
            .price {
                font-weight: 700;
                color: #e74c3c;
                font-size: 1.5rem;
                display: flex;
                align-items: baseline;
            }
            
            .price small {
                font-weight: 400;
                color: #777;
                font-size: 0.9rem;
                margin-left: 4px;
            }
            
            .btn-primary {
                background-color: #e74c3c;
                border-color: #e74c3c;
                padding: 8px 20px;
                font-weight: 600;
                font-size: 0.9rem;
                border-radius: 4px;
            }
            
            .btn-primary:hover {
                background-color: #c0392b;
                border-color: #c0392b;
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .tour-card-horizontal {
                    flex-direction: column;
                }
                
                .tour-image, .tour-details {
                    flex: 0 0 100%;
                }
                
                .tour-image {
                    height: 200px;
                }
                
                .location-badge {
                    bottom: 10px;
                    left: 10px;
                }
                
                .tour-tag {
                    top: 10px;
                    left: 10px;
                }
                
                .tour-details {
                    padding: 15px;
                }
                
                .tour-title {
                    font-size: 1.2rem;
                }
                
                .price {
                    font-size: 1.2rem;
                }
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
                if (urlParams.has('search') || urlParams.has('location') || urlParams.has('duration')) {
                    const filterButton = document.getElementById('filterButton');
                    if (filterButton) {
                        filterButton.classList.add('active');
                        
                        // Create a badge to show the number of active filters
                        let activeFilterCount = 0;
                        if (urlParams.get('search')) activeFilterCount++;
                        if (urlParams.get('location') && urlParams.get('location') !== 'all') activeFilterCount++;
                        if (urlParams.get('duration') && urlParams.get('duration') !== 'all') activeFilterCount++;
                        
                        if (activeFilterCount > 0) {
                            filterButton.innerHTML = `<i class="fas fa-filter"></i> Filter <span class="badge bg-primary ms-1">${activeFilterCount}</span>`;
                        }
                    }
                }
                
                // Function to reset all filters
                window.resetFilters = function() {
                    document.getElementById('search').value = '';
                    document.getElementById('location').value = 'all';
                    document.getElementById('duration').value = 'all';
                    document.getElementById('filterForm').submit();
                };
            });
        </script>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 