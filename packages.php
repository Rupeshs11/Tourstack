<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if packages table exists
$tableExists = false;
$result = $conn->query("SHOW TABLES LIKE 'packages'");
if ($result->num_rows > 0) {
    $tableExists = true;
}

// Apply filters if provided
$where_conditions = ["status = 'active'"];
$params = [];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$duration = isset($_GET['duration']) ? $_GET['duration'] : '';

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($destination) && $destination != 'all') {
    $where_conditions[] = "destination = ?";
    $params[] = $destination;
}

if (!empty($duration) && $duration != 'all') {
    switch ($duration) {
        case '1-3':
            $where_conditions[] = "duration BETWEEN 1 AND 3";
            break;
        case '4-7':
            $where_conditions[] = "duration BETWEEN 4 AND 7";
            break;
        case '8-14':
            $where_conditions[] = "duration BETWEEN 8 AND 14";
            break;
        case '15+':
            $where_conditions[] = "duration >= 15";
            break;
    }
}

// Get packages from database if table exists
$packages = [];
if ($tableExists) {
    $where_clause = implode(' AND ', $where_conditions);
    $query = "SELECT * FROM packages WHERE $where_clause ORDER BY price ASC";
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="package-title-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="package-title-heading">Vacation Packages</h1>
            <button id="filterButton" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </div>
</div>

<main class="main-content">
    <div class="container">
        <?php if(isset($_SESSION['booking_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['booking_status'] === 'success' ? 'success' : 'danger'; ?> mt-3">
                <i class="fas fa-<?php echo $_SESSION['booking_status'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
                <?php echo $_SESSION['booking_message']; ?>
                <?php if($_SESSION['booking_status'] === 'success'): ?>
                    <div class="mt-2">
                        <a href="my_bookings.php" class="btn btn-sm btn-primary">View My Bookings</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php 
            // Clear the session message
            unset($_SESSION['booking_message']); 
            unset($_SESSION['booking_status']);
            ?>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger mt-3">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Modal -->
        <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filter Packages</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
                    <div class="modal-body">
                        <form id="filterForm" action="packages.php" method="GET">
                            <div class="mb-3">
                                <label for="search" class="form-label">Search by Name or Description</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Enter keywords..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
                            <div class="mb-3">
                                <label for="destination" class="form-label">Destination</label>
                                <select id="destination" name="destination" class="form-select">
                                    <option value="all">All Destinations</option>
                                    <option value="Beach" <?php echo $destination == 'Beach' ? 'selected' : ''; ?>>Beach</option>
                                    <option value="Mountain" <?php echo $destination == 'Mountain' ? 'selected' : ''; ?>>Mountain</option>
                                    <option value="City" <?php echo $destination == 'City' ? 'selected' : ''; ?>>City</option>
                                    <option value="Countryside" <?php echo $destination == 'Countryside' ? 'selected' : ''; ?>>Countryside</option>
                    </select>
                </div>
                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration</label>
                                <select id="duration" name="duration" class="form-select">
                        <option value="all">Any Duration</option>
                                    <option value="1-3" <?php echo $duration == '1-3' ? 'selected' : ''; ?>>1-3 Days</option>
                                    <option value="4-7" <?php echo $duration == '4-7' ? 'selected' : ''; ?>>4-7 Days</option>
                                    <option value="8-14" <?php echo $duration == '8-14' ? 'selected' : ''; ?>>8-14 Days</option>
                                    <option value="15+" <?php echo $duration == '15+' ? 'selected' : ''; ?>>15+ Days</option>
                    </select>
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
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php if (!empty($packages)): ?>
                    <?php foreach($packages as $package): 
                        $image_path = !empty($package['image_path']) ? $package['image_path'] : (!empty($package['image_url']) ? $package['image_url'] : 'images/packages/default-package.jpg');
                    ?>
                        <div class="col">
                            <div class="package-card">
                        <?php if (!empty($package['tag'])): ?>
                                    <div class="package-tag">
                                        <?php echo htmlspecialchars($package['tag']); ?>
                                    </div>
                        <?php endif; ?>
                                <div class="package-img-container">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" class="package-img" alt="<?php echo htmlspecialchars($package['name']); ?>">
                                    <div class="destination-badge"><?php echo htmlspecialchars($package['destination'] ?? 'Vacation'); ?></div>
                    </div>
                                <div class="package-details">
                        <h3 class="package-title"><?php echo htmlspecialchars($package['name']); ?></h3>
                                    <p class="package-description"><?php echo substr(htmlspecialchars($package['description']), 0, 100) . '...'; ?></p>
                                    
                                    <div class="package-info">
                                        <p class="package-feature"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($package['duration'] ?? '3'); ?> Days</p>
                                        <p class="package-feature"><i class="fas fa-users"></i> Max <?php echo (int)($package['max_guests'] ?? 2); ?> guests</p>
                                        <?php if(!empty($package['destination'])): ?>
                                        <p class="package-feature"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($package['destination']); ?></p>
                                        <?php endif; ?>
                        </div>
                        
                                    <div class="price-book">
                                        <div class="price">₹<?php echo number_format($package['price'], 2); ?><small>/package</small></div>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bookPackageModal<?php echo $package['id']; ?>">
                                            Book Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>No packages available at the moment. Please check back later.
                        </div>
                    </div>
                <?php endif; ?>
                    </div>
                </div>

        <style>
            /* Package title banner styling */
            .package-title-banner {
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
            
            .package-title-heading {
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
    
            /* Package grid styling */
            .package-card {
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                background-color: #fff;
                height: 100%;
        display: flex;
        flex-direction: column;
                border: none;
                max-width: 450px;
                margin: 0 auto;
    }
    
    .package-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    
            .package-img-container {
        position: relative;
                width: 100%;
                padding-top: 66%; /* 3:2 aspect ratio */
        overflow: hidden;
    }
    
            .package-img {
                position: absolute;
                top: 0;
                left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
            .package-card:hover .package-img {
        transform: scale(1.05);
    }
            
            .destination-badge {
                position: absolute;
                bottom: 15px;
                left: 15px;
                background-color: rgba(0, 0, 0, 0.7);
                color: white;
                font-size: 0.9rem;
                padding: 5px 12px;
                border-radius: 20px;
                font-weight: 500;
                z-index: 1;
    }
    
    .package-tag {
        position: absolute;
        top: 15px;
                right: 15px;
                background-color: #e74c3c;
        color: white;
        padding: 5px 12px;
                border-radius: 20px;
                font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
                z-index: 1;
    }
    
    .package-details {
                flex-grow: 1;
        display: flex;
                flex-direction: column;
                padding: 1.5rem;
            }
            
            .package-title {
                font-size: 1.3rem;
                line-height: 1.3;
                margin-bottom: 0.75rem;
                font-weight: 600;
                color: #333;
    }
    
    .package-description {
                font-size: 0.95rem;
                color: #666;
                margin-bottom: 1rem;
                line-height: 1.5;
            }
            
            .package-info {
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                margin-bottom: 1rem;
            }
            
            .package-feature {
                margin-bottom: 0.5rem;
                font-size: 0.95rem;
                line-height: 1.4;
                color: #555;
            }
            
            .package-feature i {
                width: 18px;
                color: #e74c3c;
                margin-right: 0.5rem;
            }
            
            .price-book {
        display: flex;
                justify-content: space-between;
        align-items: center;
                margin-top: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid rgba(0,0,0,0.05);
    }
    
    .price {
                font-weight: 700;
                color: #e74c3c;
                font-size: 1.25rem;
            }
            
            .price small {
                font-weight: 400;
                color: #777;
        font-size: 0.9rem;
                margin-left: 0.25rem;
            }
            
            .btn-sm {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
                font-weight: 500;
            }
            
            /* Responsive adjustments */
            @media (min-width: 768px) {
                .row-cols-md-2 > * {
                    flex: 0 0 auto;
                    width: 50%;
                }
            }
            
            @media (max-width: 767px) {
                .package-card {
                    max-width: 100%;
                }
                .package-img-container {
                    padding-top: 60%;
                }
            }
            
            /* Add these additional styles for better responsiveness */
            @media (min-width: 992px) and (max-width: 1199px) {
                .package-details {
                    padding: 1.25rem;
                }
                .package-title {
                    font-size: 1.2rem;
                }
            }
            
            @media (min-width: 768px) and (max-width: 991px) {
                .package-details {
                    padding: 1rem;
                }
                .package-title {
                    font-size: 1.1rem;
                }
                .package-description {
                    font-size: 0.9rem;
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
    </div>
</main>

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
        if (urlParams.has('search') || urlParams.has('destination') || urlParams.has('duration')) {
            const filterButton = document.getElementById('filterButton');
            if (filterButton) {
                filterButton.classList.add('active');
                
                // Create a badge to show the number of active filters
                let activeFilterCount = 0;
                if (urlParams.get('search')) activeFilterCount++;
                if (urlParams.get('destination') && urlParams.get('destination') !== 'all') activeFilterCount++;
                if (urlParams.get('duration') && urlParams.get('duration') !== 'all') activeFilterCount++;
                
                if (activeFilterCount > 0) {
                    filterButton.innerHTML = `<i class="fas fa-filter"></i> Filter <span class="badge bg-primary ms-1">${activeFilterCount}</span>`;
                }
            }
        }
        
        // Function to reset all filters
        window.resetFilters = function() {
            document.getElementById('search').value = '';
            document.getElementById('destination').value = 'all';
            document.getElementById('duration').value = 'all';
            document.getElementById('filterForm').submit();
        };
    });
</script>

<?php foreach ($packages as $package): ?>
<!-- Booking Modal for <?php echo $package['name']; ?> -->
<div class="modal fade" id="bookPackageModal<?php echo $package['id']; ?>" tabindex="-1" aria-labelledby="bookPackageModalLabel<?php echo $package['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="bookPackageModalLabel<?php echo $package['id']; ?>">Book <?php echo $package['name']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form method="post" action="book_package.php" class="booking-form">
                    <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex mb-2">
                                <div class="package-image me-2" style="width:70px; height:70px;">
                                    <img src="<?php echo $package['image_path'] ?? 'image/packages/package1.jpg'; ?>" 
                                         alt="<?php echo $package['name']; ?>" 
                                         class="img-fluid rounded h-100 w-100 object-fit-cover">
                                </div>
                                <div class="package-details">
                                    <h6 class="mb-1"><?php echo $package['name']; ?></h6>
                                    <p class="mb-0 small"><i class="fas fa-tag me-1"></i> ₹<?php echo number_format($package['price'], 2); ?>/person</p>
                                    <p class="mb-0 small"><i class="fas fa-clock me-1"></i> <?php echo $package['duration'] ?? '3 days'; ?></p>
                                </div>
                            </div>
                            
                            <hr class="my-2">
                            
                            <div class="booking-dates mb-2">
                                <h6 class="mb-1">Select Booking Date</h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="booking_date_<?php echo $package['id']; ?>" class="form-label small">Starting Date</label>
                                        <input type="date" id="booking_date_<?php echo $package['id']; ?>" name="booking_date" class="form-control form-control-sm" 
                                            min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mb-1">Guest Details</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label for="adults_<?php echo $package['id']; ?>" class="form-label small">Adults</label>
                                    <select id="adults_<?php echo $package['id']; ?>" name="adults" class="form-select form-select-sm">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-6">
                                    <label for="children_<?php echo $package['id']; ?>" class="form-label small">Children</label>
                                    <select id="children_<?php echo $package['id']; ?>" name="children" class="form-select form-select-sm">
                                        <?php for ($i = 0; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label for="special_requests_<?php echo $package['id']; ?>" class="form-label small">Special Requests</label>
                                    <textarea id="special_requests_<?php echo $package['id']; ?>" name="special_requests" class="form-control form-control-sm" rows="1" placeholder="Any special requirements?"></textarea>
                                </div>
                            </div>
                            
                            <div class="price-summary p-2 rounded bg-light mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 small">₹<?php echo number_format($package['price'], 2); ?> × <span id="total_people_<?php echo $package['id']; ?>">1</span> people</h6>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0 text-primary">₹<span id="total_price_<?php echo $package['id']; ?>"><?php echo number_format($package['price'], 2); ?></span></h5>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="book_package" class="btn btn-primary btn-sm py-1 px-3">Book Now</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all package booking modals
    <?php foreach ($packages as $package): ?>
    initializePackageBooking(<?php echo $package['id']; ?>, <?php echo $package['price']; ?>);
    <?php endforeach; ?>
    
    // Handle form submissions
    document.querySelectorAll('.booking-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submission event triggered');
            
            // Get the package ID from the form
            const packageId = this.querySelector('input[name="package_id"]').value;
            console.log('Package ID:', packageId);
            
            // Get booking date input
            const bookingDateInput = this.querySelector('input[name="booking_date"]');
            
            // Validate required fields
            if (!bookingDateInput || !bookingDateInput.value) {
                alert('Please select a booking date');
                return;
            }
            
            const formData = new FormData(this);
            
            // Log form data for debugging
            console.log('Form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            
            // Send AJAX request
            fetch('book_package.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(formData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response OK:', response.ok);
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status + ' ' + response.statusText);
                }
                // Try to parse as JSON, but don't fail if it's not valid JSON
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Failed to parse JSON response:", text);
                        return { 
                            status: 'error', 
                            message: 'Invalid response from server. Please try again.' 
                        };
                    }
                });
            })
            .then(data => {
                console.log('Response data:', data);
                // Remove any existing alerts
                const existingAlert = this.previousElementSibling;
                if (existingAlert && existingAlert.classList.contains('alert')) {
                    existingAlert.remove();
                }
                
                // Create and show alert
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${data.status === 'success' ? 'success' : 'danger'} py-2 px-3 mb-3`;
                alertDiv.innerHTML = data.message;
                
                // Insert alert before the form
                this.insertAdjacentElement('beforebegin', alertDiv);
                
                if (data.status === 'success') {
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = data.redirect || 'my_bookings.php';
                    }, 2000);
                } else {
                    // Reset the button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                // Try traditional form submission as fallback
                console.log('Falling back to traditional form submission');
                
                // Create a hidden form and submit it
                const backupForm = document.createElement('form');
                backupForm.method = 'POST';
                backupForm.action = 'book_package.php';
                backupForm.style.display = 'none';
                
                // Copy all form data to the backup form
                for (let pair of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = pair[0];
                    input.value = pair[1];
                    backupForm.appendChild(input);
                }
                
                // Add to document and submit
                document.body.appendChild(backupForm);
                backupForm.submit();
            });
        });
    });
});

function initializePackageBooking(packageId, pricePerPerson) {
    const adultsSelect = document.getElementById(`adults_${packageId}`);
    const childrenSelect = document.getElementById(`children_${packageId}`);
    const totalPeopleElement = document.getElementById(`total_people_${packageId}`);
    const totalPriceElement = document.getElementById(`total_price_${packageId}`);
    const bookingDateInput = document.getElementById(`booking_date_${packageId}`);
    
    function updateTotalPrice() {
        if (adultsSelect && childrenSelect && totalPeopleElement && totalPriceElement) {
            const adults = parseInt(adultsSelect.value) || 0;
            const children = parseInt(childrenSelect.value) || 0;
            const totalPeople = adults + children;
            
            totalPeopleElement.textContent = totalPeople;
            const totalPrice = totalPeople * pricePerPerson;
            totalPriceElement.textContent = totalPrice.toFixed(2);
        }
    }
    
    // Set event listeners for select elements
    if (adultsSelect) {
        adultsSelect.addEventListener('change', updateTotalPrice);
    }
    
    if (childrenSelect) {
        childrenSelect.addEventListener('change', updateTotalPrice);
    }
    
    // Set minimum booking date to today
    if (bookingDateInput) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const formattedToday = `${yyyy}-${mm}-${dd}`;
        
        bookingDateInput.setAttribute('min', formattedToday);
        
        // Add an input event to validate date selection
        bookingDateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Please select a future date');
                this.value = '';
            }
        });
    }
    
    // Initialize price calculation
    updateTotalPrice();
}
</script>

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

.package-image {
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

.package-details p {
    margin-bottom: 0;
    color: #495057;
    font-size: 0.8rem;
}

.package-details p i {
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

<?php include 'includes/footer.php'; ?> 