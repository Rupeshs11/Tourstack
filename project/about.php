<?php
// Start session
session_start();

// Include header
include 'includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>About Us</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">About Us</li>
                </ol>
            </nav>
        </div>

        <section class="about-intro">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-content">
                        <h2>Welcome to <span class="text-primary">TourStack</span></h2>
                        <p class="lead">Your premier destination for exceptional travel experiences and luxurious accommodations.</p>
                        <p>Founded in 2010, TourStack has grown from a small local travel agency to a comprehensive hospitality service provider offering unique travel packages, comfortable accommodations, and unforgettable experiences around the world.</p>
                        <p>Our mission is to create personalized travel experiences that exceed our customers' expectations while promoting sustainable tourism practices that benefit local communities and preserve natural environments.</p>
                        <div class="about-stats">
                            <div class="stat-item">
                                <div class="stat-number">10+</div>
                                <div class="stat-label">Years Experience</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">500+</div>
                                <div class="stat-label">Happy Customers</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">100+</div>
                                <div class="stat-label">Destinations</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image">
                        <img src="image/about/about-main.jpg" alt="About TourStack" class="img-fluid rounded shadow">
                    </div>
                </div>
            </div>
        </section>

        <section class="why-choose-us">
            <h2 class="section-title">Why Choose Us</h2>
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-hand-holding-usd fa-3x text-primary"></i>
                                </div>
                                <h3 class="card-title">Best Price Guarantee</h3>
                                <p class="card-text">We offer competitive pricing and will match any comparable offer. Our goal is to provide exceptional value without compromising quality.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-headset fa-3x text-primary"></i>
                                </div>
                                <h3 class="card-title">24/7 Customer Support</h3>
                                <p class="card-text">Our dedicated support team is available around the clock to assist with any questions or concerns before, during, and after your trip.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-route fa-3x text-primary"></i>
                                </div>
                                <h3 class="card-title">Tailored Experiences</h3>
                                <p class="card-text">We create customized travel experiences that cater to your preferences, ensuring every journey is unique and memorable.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-shield-alt fa-3x text-primary"></i>
                                </div>
                                <h3 class="card-title">Safe & Secure</h3>
                                <p class="card-text">Your safety is our top priority. We maintain strict health and safety standards and work only with trusted partners and providers.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card feature-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-leaf fa-3x text-primary"></i>
                                </div>
                                <h3 class="card-title">Sustainable Tourism</h3>
                                <p class="card-text">We're committed to sustainable tourism practices that respect local cultures, support communities, and minimize environmental impact.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="our-team">
            <h2 class="section-title">Meet Our Team</h2>
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card team-card h-100 shadow-sm">
                            <div class="team-image">
                                <img src="image/team/team1.jpg" alt="Jane Doe" class="card-img-top">
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title">Jane Doe</h5>
                                <p class="position text-primary mb-3">Founder & CEO</p>
                                <div class="team-social">
                                    <a href="#" class="me-2"><i class="fab fa-linkedin"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card team-card h-100 shadow-sm">
                            <div class="team-image">
                                <img src="image/team/team2.jpg" alt="John Smith" class="card-img-top">
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title">John Smith</h5>
                                <p class="position text-primary mb-3">Travel Director</p>
                                <div class="team-social">
                                    <a href="#" class="me-2"><i class="fab fa-linkedin"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card team-card h-100 shadow-sm">
                            <div class="team-image">
                                <img src="image/team/team3.jpg" alt="Maria Garcia" class="card-img-top">
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title">Maria Garcia</h5>
                                <p class="position text-primary mb-3">Customer Experience</p>
                                <div class="team-social">
                                    <a href="#" class="me-2"><i class="fab fa-linkedin"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card team-card h-100 shadow-sm">
                            <div class="team-image">
                                <img src="image/team/team4.jpg" alt="Robert Johnson" class="card-img-top">
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title">Robert Johnson</h5>
                                <p class="position text-primary mb-3">Head of Operations</p>
                                <div class="team-social">
                                    <a href="#" class="me-2"><i class="fab fa-linkedin"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card team-card h-100 shadow-sm">
                            <div class="team-image">
                                <img src="image/team/team5.jpg" alt="Emily Chen" class="card-img-top">
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title">Emily Chen</h5>
                                <p class="position text-primary mb-3">Marketing Manager</p>
                                <div class="team-social">
                                    <a href="#" class="me-2"><i class="fab fa-linkedin"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="testimonials">
            <h2 class="section-title">What Our Customers Say</h2>
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card testimonial-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="testimonial-content">
                                    <p><i class="fas fa-quote-left text-primary me-2"></i>The service was exceptional from start to finish. Our tour guide was knowledgeable, friendly, and went above and beyond to make our vacation special. We'll definitely be booking with TourStack again!<i class="fas fa-quote-right text-primary ms-2"></i></p>
                                </div>
                                <div class="testimonial-author mt-4 d-flex align-items-center">
                                    <div class="testimonial-image me-3">
                                        <img src="image/testimonials/client1.jpg" alt="Michael Brown">
                                    </div>
                                    <div class="testimonial-info">
                                        <h5 class="mb-0">Michael Brown</h5>
                                        <p class="small text-muted mb-1">Adventure Package, July 2023</p>
                                        <div class="testimonial-rating">
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card testimonial-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="testimonial-content">
                                    <p><i class="fas fa-quote-left text-primary me-2"></i>The luxury suite exceeded all my expectations. The attention to detail, from the welcome package to the personalized services, made our anniversary trip truly memorable.<i class="fas fa-quote-right text-primary ms-2"></i></p>
                                </div>
                                <div class="testimonial-author mt-4 d-flex align-items-center">
                                    <div class="testimonial-image me-3">
                                        <img src="image/testimonials/client2.jpg" alt="Sarah Williams">
                                    </div>
                                    <div class="testimonial-info">
                                        <h5 class="mb-0">Sarah Williams</h5>
                                        <p class="small text-muted mb-1">Luxury Room, March 2023</p>
                                        <div class="testimonial-rating">
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card testimonial-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="testimonial-content">
                                    <p><i class="fas fa-quote-left text-primary me-2"></i>Our family vacation was perfect thanks to TourStack. The staff took care of everything, and the kids loved all the activities. The hotel was clean, comfortable, and in a great location.<i class="fas fa-quote-right text-primary ms-2"></i></p>
                                </div>
                                <div class="testimonial-author mt-4 d-flex align-items-center">
                                    <div class="testimonial-image me-3">
                                        <img src="image/testimonials/client3.jpg" alt="David Rodriguez">
                                    </div>
                                    <div class="testimonial-info">
                                        <h5 class="mb-0">David Rodriguez</h5>
                                        <p class="small text-muted mb-1">Family Package, June 2023</p>
                                        <div class="testimonial-rating">
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star-half-alt text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card testimonial-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="testimonial-content">
                                    <p><i class="fas fa-quote-left text-primary me-2"></i>As a solo traveler, I felt very safe and well taken care of. The guided tours were informative and fun, and I met some wonderful people. I highly recommend TourStack for anyone traveling alone.<i class="fas fa-quote-right text-primary ms-2"></i></p>
                                </div>
                                <div class="testimonial-author mt-4 d-flex align-items-center">
                                    <div class="testimonial-image me-3">
                                        <img src="image/testimonials/client4.jpg" alt="Lisa Chen">
                                    </div>
                                    <div class="testimonial-info">
                                        <h5 class="mb-0">Lisa Chen</h5>
                                        <p class="small text-muted mb-1">City Explorer Package, April 2023</p>
                                        <div class="testimonial-rating">
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                            <i class="fas fa-star text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
    .about-intro {
        padding: 50px 0;
    }
    
    .about-content {
        padding-right: 30px;
    }
    
    .about-content h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: var(--gray-800);
    }
    
    .about-content .lead {
        font-size: 1.25rem;
        margin-bottom: 25px;
        color: var(--gray-700);
    }
    
    .about-content p {
        margin-bottom: 15px;
        color: var(--gray-600);
        line-height: 1.7;
    }
    
    .about-stats {
        display: flex;
        margin-top: 40px;
        gap: 30px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin-top: 5px;
    }
    
    .about-image {
        position: relative;
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    
    .about-image img {
        width: 100%;
        height: auto;
        transition: transform 0.5s ease;
    }
    
    .about-image:hover img {
        transform: scale(1.05);
    }
    
    .section-title {
        position: relative;
        text-align: center;
        font-size: 2rem;
        margin-bottom: 50px;
        color: var(--gray-800);
        padding-bottom: 15px;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: var(--primary-color);
    }
    
    .why-choose-us, .our-team, .testimonials {
        padding: 70px 0;
        border-top: 1px solid var(--gray-200);
    }
    
    .features-scroll-container,
    .team-scroll-container,
    .testimonials-scroll-container,
    .features-scroll-container::-webkit-scrollbar,
    .team-scroll-container::-webkit-scrollbar,
    .testimonials-scroll-container::-webkit-scrollbar,
    .features-scroll-container::-webkit-scrollbar-track,
    .team-scroll-container::-webkit-scrollbar-track,
    .testimonials-scroll-container::-webkit-scrollbar-track,
    .features-scroll-container::-webkit-scrollbar-thumb,
    .team-scroll-container::-webkit-scrollbar-thumb,
    .testimonials-scroll-container::-webkit-scrollbar-thumb,
    .features-row,
    .team-row,
    .testimonials-row,
    .features-row > .col-md-4,
    .team-row > .col-md-3,
    .testimonials-row > .col-md-4 {
        /* Remove all these styles as we're not using horizontal scrolling anymore */
    }
    
    .feature-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .feature-icon {
        color: #0d6efd;
    }
    
    .team-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }
    
    .team-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .team-image {
        overflow: hidden;
    }
    
    .team-card .card-img-top {
        height: 260px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .team-card:hover .card-img-top {
        transform: scale(1.1);
    }
    
    .team-social a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #f8f9fa;
        color: #0d6efd;
        transition: all 0.3s ease;
    }
    
    .team-social a:hover {
        background-color: #0d6efd;
        color: white;
    }
    
    .testimonial-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }
    
    .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .testimonial-content {
        font-style: italic;
        color: #555;
        line-height: 1.6;
    }
    
    .testimonial-image {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid white;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .testimonial-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    @media (max-width: 992px) {
        .about-content {
            padding-right: 0;
            margin-bottom: 40px;
        }
        
        .about-stats {
            justify-content: center;
        }
    }
    
    @media (max-width: 768px) {
        .about-stats {
            flex-direction: column;
            gap: 20px;
        }
        
        .testimonials-row > .col-md-4 {
            min-width: 280px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?> 