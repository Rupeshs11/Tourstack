<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Fetch facilities from database - try/catch to prevent errors
try {
    $facilities_query = "SELECT * FROM facilities LIMIT 9";
    $facilities_result = $conn->query($facilities_query);
    $has_facilities = isset($facilities_result) && $facilities_result->num_rows > 0;
} catch (Exception $e) {
    $has_facilities = false;
    // Log error silently
    error_log("Error fetching facilities: " . $e->getMessage());
}

// Include header
include 'includes/header.php';
?>

<div class="facility-title-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="facility-title-heading">Our Facilities</h1>
        </div>
    </div>
</div>

<main class="main-content">
    <div class="container py-4">
        <div class="row text-center mb-4">
            <div class="col-12">
                <p class="lead">Discover our premium amenities designed for your comfort and convenience</p>
            </div>
        </div>
        
        <div class="facility-grid">
            <!-- First row of facilities (1-3) -->
            <div class="facility-row">
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-swimming-pool"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Swimming Pool</h5>
                        <p class="facility-description">Relax in our luxury infinity pool with spectacular views. Open daily from 6 AM to 10 PM.</p>
                    </div>
                </div>
                
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-dumbbell"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Fitness Center</h5>
                        <p class="facility-description">Stay fit with our state-of-the-art equipment and personal trainers available on request.</p>
                    </div>
                </div>
                
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-spa"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Spa & Wellness</h5>
                        <p class="facility-description">Indulge in rejuvenating treatments at our premium spa with trained therapists.</p>
                    </div>
                </div>
            </div>
            
            <!-- Second row of facilities (4-6) -->
            <div class="facility-row">
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Multi-cuisine Restaurant</h5>
                        <p class="facility-description">Enjoy delicious meals prepared by expert chefs offering various international cuisines.</p>
                    </div>
                </div>
                
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Conference Facilities</h5>
                        <p class="facility-description">Host business meetings with our well-equipped conference rooms and high-speed internet.</p>
                    </div>
                </div>
                
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-wifi"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Free Wi-Fi</h5>
                        <p class="facility-description">Stay connected with high-speed internet access available throughout the property.</p>
                    </div>
                </div>
            </div>
            
            <!-- Third row of facilities (7-9) -->
            <div class="facility-row">
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Concierge Service</h5>
                        <p class="facility-description">Our concierge team is available 24/7 to assist with all your needs and requests.</p>
                    </div>
                </div>
                
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-shuttle-van"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Airport Shuttle</h5>
                        <p class="facility-description">Complimentary airport transfers for a hassle-free journey to and from our hotel.</p>
                    </div>
                </div>
                
                <div class="facility-item">
                    <div class="facility-circle-card text-center">
                        <div class="facility-circle mx-auto">
                            <div class="facility-icon">
                                <i class="fas fa-parking"></i>
                            </div>
                        </div>
                        <h5 class="facility-name mt-3">Valet Parking</h5>
                        <p class="facility-description">Convenient valet parking service available for all our guests.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Facility title banner styling */
        .facility-title-banner {
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
        
        .facility-title-heading {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            color: #333;
            white-space: nowrap;
        }
        
        /* Grid layout styling */
        .facility-grid {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .facility-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        
        .facility-item {
            flex: 1;
            min-width: 0;
        }
        
        /* Circle layout styling */
        .facility-circle-card {
            transition: transform 0.3s ease;
            padding: 15px;
            height: 100%;
        }
        
        .facility-circle-card:hover {
            transform: translateY(-10px);
        }
        
        .facility-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin: 0 auto;
        }
        
        .facility-circle:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transform: scale(1.05);
        }
        
        .facility-circle::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #e74c3c 0%, #e74c3c 100%);
            opacity: 0.1;
            border-radius: 50%;
        }
        
        .facility-icon {
            font-size: 3rem;
            color: #e74c3c;
            z-index: 2;
        }
        
        .facility-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 15px;
            color: #333;
        }
        
        .facility-description {
            font-size: 0.9rem;
            color: #666;
            margin-top: 10px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991px) {
            .facility-row {
                flex-wrap: wrap;
            }
            
            .facility-item {
                flex-basis: calc(50% - 10px);
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 767px) {
            .facility-item {
                flex-basis: 100%;
            }
            
            .facility-circle {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</main>

<?php include 'includes/footer.php'; ?> 