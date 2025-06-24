<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);
$user_name = $logged_in ? $_SESSION['user_name'] : '';

// Fetch room data from database
$rooms_query = "SELECT * FROM rooms LIMIT 3";
$rooms_result = $conn->query($rooms_query);

// Fetch facilities from database
$facilities_query = "SELECT * FROM facilities LIMIT 3";
$facilities_result = $conn->query($facilities_query);

// Fetch top tours from database
$tours_query = "SELECT * FROM tours LIMIT 3";
$tours_result = $conn->query($tours_query);

// Fetch packages from database
$packages_query = "SELECT * FROM packages WHERE status = 'active' LIMIT 3";
$packages_result = $conn->query($packages_query);

// Include header
include 'includes/header.php';
?>

<style>
    /* Hero Section */
    .hero {
        padding: 80px 0;
        background-color: #f8f9fa;
    }
    
    .hero h1 {
        font-size: 2.8rem;
        margin-bottom: 20px;
        color: #343a40;
        font-weight: 700;
    }
    
    .hero-text {
        font-size: 1.2rem;
        color: #6c757d;
        margin-bottom: 30px;
    }
    
    .hero-buttons .btn {
        margin-right: 10px;
        margin-bottom: 10px;
        padding: 12px 24px;
    }
    
    .hero-img {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Package Cards */
    .package-card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .package-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    
    .tour-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    
    .tour-card {
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .tour-overlay {
        position: absolute;
        bottom: 10px;
        left: 10px;
    }
    
    .tour-details {
        font-size: 0.9rem;
    }
    
    .tour-location {
        color: #6c757d;
    }
    
    .tour-price {
        font-size: 1.2rem;
    }
    
    .section-header h2 {
        font-weight: 700;
        color: #343a40;
        margin-bottom: 10px;
    }
    
    .section-header p {
        color: #6c757d;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* Alternating section backgrounds */
    section:nth-child(odd) {
        background-color: #fff;
    }
    
    section:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    /* Package features styling */
    .package-features {
        font-size: 0.9rem;
    }
    
    .package-features i {
        width: 20px;
        text-align: center;
    }
    
    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .hero h1 {
            font-size: 2.3rem;
        }
        
        .hero-buttons .btn {
            width: 100%;
            margin-right: 0;
        }
        
        .tour-card, .package-card {
            margin-bottom: 20px;
        }
    }
    
    /* Custom container for tours and packages */
    .custom-container {
        max-width: 1140px;
        margin: 0 auto;
        padding: 0 15px;
    }
    
    /* Horizontal scrolling containers */
    .tours-scroll-container, .packages-scroll-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        margin: 0 auto;
        padding-bottom: 15px;
        max-width: 100%;
    }
    
    .tours-row, .packages-row {
        display: flex;
        justify-content: center;
        gap: 30px;
        padding: 10px 0;
    }
    
    .tour-item, .package-item {
        flex: 0 0 auto;
        width: 350px;
        max-width: 350px;
        margin: 0;
        padding: 5px;
    }
    
    .card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .card-img-top {
        height: 200px;
        object-fit: cover;
    }
    
    @media (max-width: 1200px) {
        .tours-row, .packages-row {
            justify-content: flex-start;
        }
    }
    
    @media (min-width: 1201px) {
        .tours-row, .packages-row {
            flex-wrap: wrap;
        }
    }
    
    /* Search form styles */
    .search-container {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    
    .search-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: space-between;
    }
    
    .search-item {
        flex: 1 0 180px;
    }
    
    .search-item label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .search-item {
            flex: 0 0 100%;
        }
    }
    
    /* Footer styling */
    .main-footer {
        padding: 15px 0;
    }
    
    .main-footer h5 {
        font-size: 1.1rem;
    }
    
    .main-footer .social-icons a {
        font-size: 1rem;
    }
    
    .main-footer a:hover {
        text-decoration: underline;
    }
    
    /* Booking Modal Styles */
    .modal-dialog {
        max-width: 450px;
    }
    
    .modal-header {
        border-bottom: 1px solid #eee;
        padding: 15px 20px;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-title {
        font-weight: 600;
        color: #333;
        font-size: 18px;
    }
    
    .modal-body h5 {
        font-size: 16px;
        font-weight: 600;
    }
    
    .modal-body h6 {
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        font-size: 14px;
    }
    
    .modal-body p {
        font-size: 14px;
    }
    
    .modal-body hr {
        margin: 15px 0;
        opacity: 0.1;
    }
    
    .modal-body label {
        font-size: 14px;
        display: block;
        margin-bottom: 5px;
    }
    
    .form-control, .form-select {
        border-radius: 4px;
        border: 1px solid #ddd;
        padding: 8px 12px;
        font-size: 14px;
        height: auto;
    }
    
    textarea.form-control {
        resize: none;
        min-height: 80px;
    }
    
    .btn-danger {
        background-color: #e74c3c;
        border-color: #e74c3c;
        padding: 8px 18px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .btn-danger:hover {
        background-color: #c0392b;
        border-color: #c0392b;
    }
    
    #tour-price-calc, #package-price-calc {
        color: #666;
        font-size: 14px;
    }
    
    #tour-price-calc strong, #package-price-calc strong {
        color: #e74c3c;
        font-size: 16px;
    }
    
    .modal-content {
        border: none;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .modal-body i {
        width: 16px;
        color: inherit;
        margin-right: 5px;
    }
    
    .text-danger {
        color: #e74c3c !important;
    }
    
    .text-secondary {
        color: #6c757d !important;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
    
    .fw-medium {
        font-weight: 500;
    }
</style>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto text-center">
                <h1>Discover Amazing Adventures</h1>
                <p class="hero-text mb-3">Experience breathtaking destinations with our guided tours and custom packages.</p>
                <div class="hero-buttons mt-3">
                    <a href="#tours" class="btn btn-primary btn-sm me-2">Explore Tours</a>
                    <a href="#packages" class="btn btn-outline-dark btn-sm">View Packages</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="search-section py-3 bg-light">
    <div class="container">
        <div class="section-header text-center mb-3">
            <h2>Check Availability</h2>
        </div>
        <div class="search-container">
            <form action="rooms.php" method="get" class="availability-form">
                <div class="search-row">
                    <div class="search-item">
                        <label for="check_in_date">Check In</label>
                        <input type="date" class="form-control" id="check_in_date" name="check_in_date" required>
                    </div>
                    <div class="search-item">
                        <label for="check_out_date">Check Out</label>
                        <input type="date" class="form-control" id="check_out_date" name="check_out_date" required>
                    </div>
                    <div class="search-item">
                        <label for="adults">Adults</label>
                        <select class="form-select" id="adults" name="adults">
                            <?php for ($i = 1; $i <= 10; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="search-item">
                        <label for="children">Children</label>
                        <select class="form-select" id="children" name="children">
                            <?php for ($i = 0; $i <= 10; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="search-item">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Check Availability</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Popular Tours Section -->
<section id="tours" class="tours-section py-4">
    <div class="custom-container">
        <div class="section-header text-center mb-4">
            <h2>Popular Tours</h2>
            <p>Discover our most booked experiences and unforgettable adventures</p>
        </div>
        
        <div class="tours-scroll-container">
            <div class="tours-row">
                <?php
                if ($tours_result && $tours_result->num_rows > 0) {
                    while ($tour = $tours_result->fetch_assoc()) {
                ?>
                    <div class="tour-item">
                        <div class="card h-100 shadow-sm">
                            <img src="<?php echo !empty($tour['image_path']) ? $tour['image_path'] : ($tour['image_url'] ?? 'images/tours/default-tour.jpg'); ?>" class="card-img-top" alt="<?php echo $tour['name'] ?? 'Tour Experience'; ?>">
                            <div class="card-body p-3">
                                <h5 class="card-title h6 mb-2"><?php echo $tour['name'] ?? 'Tour Experience'; ?></h5>
                                <p class="card-text small text-muted"><?php echo substr($tour['description'] ?? 'An exciting tour with amazing experiences.', 0, 80) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="small"><i class="fas fa-map-marker-alt text-primary me-1"></i> <?php echo $tour['location'] ?? 'Various locations'; ?></span>
                                    <span class="small"><i class="fas fa-users text-primary me-1"></i> Max: <?php echo $tour['max_people'] ?? 10; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="price small fw-bold">₹<?php echo number_format($tour['price'] ?? 0, 2); ?></span>
                                    <button data-tour-id="<?php echo $tour['id'] ?? 0; ?>" class="btn btn-sm btn-primary py-0 px-2 small book-tour-btn">Book Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="col-12 text-center">
                        <p>No tours available at the moment. Please check back later.</p>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Vacation Packages Section -->
<section id="packages" class="packages-section py-4 bg-light">
    <div class="custom-container">
        <div class="section-header text-center mb-4">
            <h2>Vacation Packages</h2>
            <p>Discover our specially curated vacation packages for unforgettable experiences</p>
        </div>
        
        <div class="packages-scroll-container">
            <div class="packages-row">
                <?php
                if ($packages_result && $packages_result->num_rows > 0) {
                    while ($package = $packages_result->fetch_assoc()) {
                ?>
                    <div class="package-item">
                        <div class="card h-100 shadow-sm">
                            <img src="<?php echo !empty($package['image_path']) ? $package['image_path'] : ($package['image_url'] ?? 'images/packages/default-package.jpg'); ?>" class="card-img-top" alt="<?php echo $package['name'] ?? 'Vacation Package'; ?>">
                            <div class="card-body p-3">
                                <h5 class="card-title h6 mb-2"><?php echo $package['name'] ?? 'Vacation Package'; ?></h5>
                                <p class="card-text small text-muted"><?php echo substr($package['description'] ?? 'A wonderful vacation package with amazing experiences.', 0, 60) . '...'; ?></p>
                                <div class="package-features mt-2">
                                    <p class="mb-1 small"><i class="far fa-clock me-1"></i><?php echo $package['duration'] ?? '1'; ?> Days</p>
                                    <p class="mb-1 small"><i class="fas fa-hotel me-1"></i><?php echo $package['accommodation'] ?? 'Standard'; ?></p>
                                    <p class="mb-1 small"><i class="fas fa-utensils me-1"></i><?php echo $package['meals'] ?? 'Not included'; ?></p>
                                    <p class="mb-1 small"><i class="fas fa-map-marker-alt me-1"></i><?php echo $package['location'] ?? 'Various locations'; ?></p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="price small fw-bold">₹<?php echo number_format($package['price'] ?? 0, 2); ?></span>
                                    <button data-package-id="<?php echo $package['id'] ?? 0; ?>" class="btn btn-sm btn-primary py-0 px-2 small book-package-btn">Book Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="col-12 text-center">
                        <p>No packages available at the moment. Please check back later.</p>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="tours-section py-5">
    <div class="custom-container">
        <div class="section-header text-center mb-5">
            <h2>Why Choose Us</h2>
            <p>We provide exceptional service and unforgettable experiences</p>
        </div>
        
        <div class="tours-scroll-container">
            <div class="tours-row">
                <div class="tour-item">
                    <div class="card h-100 shadow-sm text-center">
                        <div class="card-body">
                            <i class="fas fa-hotel fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Luxury Accommodations</h5>
                            <p class="card-text">Stay in carefully selected premium hotels and resorts that offer comfort and luxury.</p>
                        </div>
                    </div>
                </div>
                <div class="tour-item">
                    <div class="card h-100 shadow-sm text-center">
                        <div class="card-body">
                            <i class="fas fa-globe-americas fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Exciting Destinations</h5>
                            <p class="card-text">Explore unique and breathtaking destinations around the world with expert guides.</p>
                        </div>
                    </div>
                </div>
                <div class="tour-item">
                    <div class="card h-100 shadow-sm text-center">
                        <div class="card-body">
                            <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">24/7 Customer Support</h5>
                            <p class="card-text">Our dedicated team is always available to assist you before, during, and after your trip.</p>
                        </div>
                    </div>
                </div>
                <div class="tour-item">
                    <div class="card h-100 shadow-sm text-center">
                        <div class="card-body">
                            <i class="fas fa-tags fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Best Price Guarantee</h5>
                            <p class="card-text">We offer competitive prices and value for money on all our tours and packages.</p>
                        </div>
                    </div>
                </div>
                <div class="tour-item">
                    <div class="card h-100 shadow-sm text-center">
                        <div class="card-body">
                            <i class="fas fa-star fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Premium Experience</h5>
                            <p class="card-text">Enjoy personalized service and attention to detail on every tour we offer.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="packages-section py-5 bg-light">
    <div class="custom-container">
        <div class="section-header text-center mb-5">
            <h2>What Our Customers Say</h2>
            <p>Read testimonials from our satisfied travelers</p>
        </div>
        
        <div class="packages-scroll-container">
            <div class="packages-row">
                <div class="package-item">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-quote-left fa-2x text-primary mb-3"></i>
                            <p class="card-text">The tour exceeded all our expectations. The guides were knowledgeable and the accommodations were first-class. We'll definitely book with TourStack again!</p>
                            <div class="mt-3">
                                <h6 class="mb-0">Sarah Johnson</h6>
                                <small class="text-muted">Adventure Tour</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="package-item">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-quote-left fa-2x text-primary mb-3"></i>
                            <p class="card-text">Our family vacation package was perfectly planned. Every detail was taken care of, allowing us to relax and enjoy quality time together. Highly recommend!</p>
                            <div class="mt-3">
                                <h6 class="mb-0">Michael Rodriguez</h6>
                                <small class="text-muted">Family Package</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="package-item">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-quote-left fa-2x text-primary mb-3"></i>
                            <p class="card-text">The luxury tour was worth every penny. From airport pickup to drop-off, everything was seamless. The experiences were unique and memorable.</p>
                            <div class="mt-3">
                                <h6 class="mb-0">Emily Chen</h6>
                                <small class="text-muted">Luxury Tour</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="package-item">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-quote-left fa-2x text-primary mb-3"></i>
                            <p class="card-text">As a solo traveler, I felt safe and well taken care of. The group was friendly and the itinerary was perfectly balanced between activities and free time.</p>
                            <div class="mt-3">
                                <h6 class="mb-0">David Thompson</h6>
                                <small class="text-muted">Cultural Tour</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>

<script>
    // Set default dates for check-in and check-out
    document.addEventListener('DOMContentLoaded', function() {
        // Get tomorrow's date for check-in
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        // Get day after tomorrow for check-out
        const dayAfterTomorrow = new Date();
        dayAfterTomorrow.setDate(dayAfterTomorrow.getDate() + 2);
        
        // Format dates as YYYY-MM-DD for the date inputs
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        
        // Set the default values
        document.getElementById('check_in_date').value = formatDate(tomorrow);
        document.getElementById('check_out_date').value = formatDate(dayAfterTomorrow);
        
        // Tour booking modal functionality
        const tourButtons = document.querySelectorAll('.book-tour-btn');
        
        tourButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Check if user is logged in
                <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php?redirect=tours';
                return;
                <?php endif; ?>
                
                const tourId = this.getAttribute('data-tour-id');
                const tourCard = this.closest('.card');
                const tourName = tourCard.querySelector('.card-title').textContent.trim();
                const tourPrice = tourCard.querySelector('.price').textContent.trim();
                const tourLocation = tourCard.querySelector('.small i.fas.fa-map-marker-alt').parentNode.textContent.trim();
                const tourImage = tourCard.querySelector('.card-img-top').getAttribute('src');
                
                // Create or update modal
                let modal = document.getElementById('tour-booking-modal');
                let isNewModal = false;
                
                if (!modal) {
                    isNewModal = true;
                    modal = document.createElement('div');
                    modal.id = 'tour-booking-modal';
                    modal.className = 'modal fade';
                    modal.setAttribute('tabindex', '-1');
                    modal.setAttribute('aria-labelledby', 'tourBookingModalLabel');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(modal);
                }
                
                // Update modal content
                modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tourBookingModalLabel">Book ${tourName}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex mb-4">
                                <div class="me-3" style="width: 80px; height: 80px;">
                                    <img src="${tourImage}" class="img-fluid rounded" alt="${tourName}" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div>
                                    <h5 class="mb-2">${tourName}</h5>
                                    <p class="m-0 text-danger"><i class="fas fa-tag me-2"></i>₹${tourPrice.replace(/[₹,]/g, '')}</p>
                                    <p class="m-0 text-muted"><i class="fas fa-map-marker-alt me-2"></i>${tourLocation}</p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <form id="tour-booking-form">
                                <input type="hidden" name="tour_id" value="${tourId}">
                                
                                <div class="mb-3">
                                    <h6 class="mb-2">Tour Date</h6>
                                    <input type="date" class="form-control" id="tour_booking_date" name="booking_date" required min="${formatDate(tomorrow)}">
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="mb-2">Number of People</h6>
                                    <select class="form-select" id="tour_people" name="people">
                                        ${Array.from({length: 10}, (_, i) => i + 1).map(num => `<option value="${num}">${num}</option>`).join('')}
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="mb-2">Special Requests</h6>
                                    <textarea class="form-control" id="tour_special_requests" name="special_requests" rows="3" placeholder="Any special requirements?"></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div id="tour-price-calc" class="text-secondary"></div>
                                    <button type="submit" class="btn btn-danger">Book Now</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>`;
                
                // Initialize Bootstrap modal if it's new
                let bsModal;
                if (isNewModal) {
                    bsModal = new bootstrap.Modal(modal);
                } else {
                    bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                }
                
                // Show the modal
                bsModal.show();
                
                // Setup form handling after modal is shown
                modal.addEventListener('shown.bs.modal', function() {
                    // Get form reference
                    const form = document.getElementById('tour-booking-form');
                    const peopleSelect = document.getElementById('tour_people');
                    
                    // Calculate and display initial price
                    updateTourPriceDisplay();
                    
                    // Update price when people count changes
                    peopleSelect.addEventListener('change', updateTourPriceDisplay);
                    
                    // Handle form submission
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Show loading indicator
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalBtnText = submitBtn.innerHTML;
                        submitBtn.innerHTML = 'Processing...';
                        submitBtn.disabled = true;
                        
                        // Clear previous error messages
                        const existingAlert = form.querySelector('.alert-danger');
                        if (existingAlert) {
                            existingAlert.remove();
                        }
                        
                        const formData = new FormData(form);
                        
                        // Validate the form data before submission
                        const bookingDate = formData.get('booking_date');
                        const people = formData.get('people');
                        
                        if (!bookingDate) {
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger';
                            errorAlert.innerHTML = 'Please select a booking date';
                            form.prepend(errorAlert);
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                            return;
                        }
                        
                        // Send AJAX request for tour booking
                        fetch('book_tour.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(response => {
                            return response.json().catch(() => {
                                // If the response is not valid JSON
                                throw new Error('Invalid server response');
                            });
                        })
                        .then(data => {
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                            
                            if (data.status === 'success') {
                                // Show success message
                                const modalBody = modal.querySelector('.modal-body');
                                modalBody.innerHTML = `
                                <div class="alert alert-success">
                                    <h4>Booking Confirmed!</h4>
                                    <p>${data.message}</p>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="my_bookings.php" class="btn btn-primary">View My Bookings</a>
                                </div>`;
                            } else {
                                // Show error message
                                const errorAlert = document.createElement('div');
                                errorAlert.className = 'alert alert-danger';
                                errorAlert.innerHTML = data.message || 'An error occurred during booking. Please try again.';
                                form.prepend(errorAlert);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Reset button state
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                            
                            // Show generic error
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger';
                            errorAlert.innerHTML = 'Network error occurred. Please try again.';
                            form.prepend(errorAlert);
                        });
                    });
                    
                    // Function to update price display
                    function updateTourPriceDisplay() {
                        const people = parseInt(peopleSelect.value) || 1;
                        
                        // Extract numeric price value
                        const priceMatch = tourPrice.match(/₹([\d,]+(\.\d+)?)/);
                        let basePrice = 0;
                        if (priceMatch && priceMatch[1]) {
                            basePrice = parseFloat(priceMatch[1].replace(/,/g, ''));
                        }
                        
                        const totalPrice = people * basePrice;
                        
                        // Update display
                        const priceCalcElement = document.getElementById('tour-price-calc');
                        if (priceCalcElement) {
                            priceCalcElement.innerHTML = `₹${basePrice.toFixed(2)} × ${people} ${people > 1 ? 'people' : 'person'} = <strong class="text-danger">₹${totalPrice.toFixed(2)}</strong>`;
                        }
                    }
                });
            });
        });
        
        // Package booking modal functionality
        const packageButtons = document.querySelectorAll('.book-package-btn');
        
        packageButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Check if user is logged in
                <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php?redirect=packages';
                return;
                <?php endif; ?>
                
                const packageId = this.getAttribute('data-package-id');
                const packageCard = this.closest('.card');
                const packageName = packageCard.querySelector('.card-title').textContent.trim();
                const packagePrice = packageCard.querySelector('.price').textContent.trim();
                const packageDuration = packageCard.querySelector('.package-features p:first-child').textContent.trim();
                const packageImage = packageCard.querySelector('.card-img-top').getAttribute('src');
                
                // Create or update modal
                let modal = document.getElementById('package-booking-modal');
                let isNewModal = false;
                
                if (!modal) {
                    isNewModal = true;
                    modal = document.createElement('div');
                    modal.id = 'package-booking-modal';
                    modal.className = 'modal fade';
                    modal.setAttribute('tabindex', '-1');
                    modal.setAttribute('aria-labelledby', 'packageBookingModalLabel');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(modal);
                }
                
                // Update modal content
                modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="packageBookingModalLabel">Book ${packageName}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex mb-4">
                                <div class="me-3" style="width: 80px; height: 80px;">
                                    <img src="${packageImage}" class="img-fluid rounded" alt="${packageName}" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div>
                                    <h5 class="mb-2">${packageName}</h5>
                                    <p class="m-0 text-danger"><i class="fas fa-tag me-2"></i>₹${packagePrice.replace(/[₹,]/g, '')}/person</p>
                                    <p class="m-0 text-muted"><i class="far fa-clock me-2"></i>${packageDuration}</p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <form id="package-booking-form">
                                <input type="hidden" name="package_id" value="${packageId}">
                                
                                <div class="mb-3">
                                    <h6 class="mb-2">Select Booking Date</h6>
                                    <label class="fw-medium text-secondary mb-2">Starting Date</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" required min="${formatDate(tomorrow)}">
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="mb-2">Guest Details</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="fw-medium text-secondary mb-2">Adults</label>
                                            <select class="form-select" id="adults" name="adults">
                                                ${Array.from({length: 10}, (_, i) => i + 1).map(num => `<option value="${num}">${num}</option>`).join('')}
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="fw-medium text-secondary mb-2">Children</label>
                                            <select class="form-select" id="children" name="children">
                                                ${Array.from({length: 11}, (_, i) => i).map(num => `<option value="${num}">${num}</option>`).join('')}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="mb-2">Special Requests</h6>
                                    <textarea class="form-control" id="special_requests" name="special_requests" rows="3" placeholder="Any special requirements?"></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div id="package-price-calc" class="text-secondary"></div>
                                    <button type="submit" class="btn btn-danger">Book Now</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>`;
                
                // Initialize Bootstrap modal if it's new
                let bsModal;
                if (isNewModal) {
                    bsModal = new bootstrap.Modal(modal);
                } else {
                    bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                }
                
                // Show the modal
                bsModal.show();
                
                // Setup form handling after modal is shown
                modal.addEventListener('shown.bs.modal', function() {
                    // Get form references
                    const form = document.getElementById('package-booking-form');
                    const adultsSelect = document.getElementById('adults');
                    const childrenSelect = document.getElementById('children');
                    
                    // Calculate and display initial price
                    updatePackagePriceDisplay();
                    
                    // Update price when guest counts change
                    adultsSelect.addEventListener('change', updatePackagePriceDisplay);
                    childrenSelect.addEventListener('change', updatePackagePriceDisplay);
                    
                    // Handle form submission
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Show loading indicator
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalBtnText = submitBtn.innerHTML;
                        submitBtn.innerHTML = 'Processing...';
                        submitBtn.disabled = true;
                        
                        // Clear previous error messages
                        const existingAlert = form.querySelector('.alert-danger');
                        if (existingAlert) {
                            existingAlert.remove();
                        }
                        
                        const formData = new FormData(form);
                        
                        // Validate the form data before submission
                        const bookingDate = formData.get('booking_date');
                        const adults = formData.get('adults');
                        
                        if (!bookingDate) {
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger';
                            errorAlert.innerHTML = 'Please select a booking date';
                            form.prepend(errorAlert);
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                            return;
                        }
                        
                        // Send AJAX request for package booking
                        fetch('package_booking.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(response => {
                            return response.json().catch(() => {
                                // If the response is not valid JSON
                                throw new Error('Invalid server response');
                            });
                        })
                        .then(data => {
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                            
                            if (data.status === 'success') {
                                // Show success message
                                const modalBody = modal.querySelector('.modal-body');
                                modalBody.innerHTML = `
                                <div class="alert alert-success">
                                    <h4>Booking Confirmed!</h4>
                                    <p>${data.message}</p>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="${data.redirect || 'my_bookings.php'}" class="btn btn-primary">View My Bookings</a>
                                </div>`;
                            } else {
                                // Show error message
                                const errorAlert = document.createElement('div');
                                errorAlert.className = 'alert alert-danger';
                                errorAlert.innerHTML = data.message || 'An error occurred during booking. Please try again.';
                                form.prepend(errorAlert);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Reset button state
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                            
                            // Show generic error
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger';
                            errorAlert.innerHTML = 'Network error occurred. Please try again.';
                            form.prepend(errorAlert);
                        });
                    });
                    
                    // Function to update price display
                    function updatePackagePriceDisplay() {
                        const adults = parseInt(adultsSelect.value) || 1;
                        const children = parseInt(childrenSelect.value) || 0;
                        const totalGuests = adults + children;
                        
                        // Extract numeric price value
                        const priceMatch = packagePrice.match(/₹([\d,]+(\.\d+)?)/);
                        let basePrice = 0;
                        if (priceMatch && priceMatch[1]) {
                            basePrice = parseFloat(priceMatch[1].replace(/,/g, ''));
                        }
                        
                        const totalPrice = totalGuests * basePrice;
                        
                        // Update display
                        const priceCalcElement = document.getElementById('package-price-calc');
                        if (priceCalcElement) {
                            priceCalcElement.innerHTML = `₹${basePrice.toFixed(2)} × ${totalGuests} ${totalGuests > 1 ? 'people' : 'person'} = <strong class="text-danger">₹${totalPrice.toFixed(2)}</strong>`;
                        }
                    }
                });
            });
        });
    });
</script>
?> 