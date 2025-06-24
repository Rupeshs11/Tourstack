    <!-- Footer section -->
    <footer class="main-footer bg-dark text-white py-3">
        <div class="container">
            <div class="row">
                <!-- Contact Information Column -->
                <div class="col-sm-4 text-center mb-3 mb-sm-0">
                    <h6 class="mb-3">Contact Us</h6>
                    <p class="small mb-1"><i class="fas fa-map-marker-alt me-1"></i> Ssbt coet ,jalgaon</p>
                    <p class="small mb-1"><i class="fas fa-phone me-1"></i> 8529637410</p>
                    <p class="small mb-1"><i class="fas fa-envelope me-1"></i> info@tourstack.com</p>
                </div>
                
                <!-- Quick Links & Social Media Column -->
                <div class="col-sm-4 text-center mb-3 mb-sm-0">
                    <h6 class="mb-3">Quick Links</h6>
                    <div class="d-flex flex-column align-items-center">
                        <a href="index.php" class="text-white small mb-1">Home</a>
                        <a href="about.php" class="text-white small mb-1">About</a>
                        <div class="social-icons mt-2">
                            <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Newsletter Column -->
                <div class="col-sm-4 text-center mb-3 mb-sm-0">
                    <h6 class="mb-3">Newsletter</h6>
                    <form action="#" method="post" class="newsletter-form">
                        <div class="input-group input-group-sm mx-auto" style="max-width: 250px;">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> TourStack. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <style>
    @media (max-width: 576px) {
        /* For mobile devices, stack columns */
        .main-footer .col-sm-4 {
            margin-bottom: 20px;
        }
    }
    
    @media (min-width: 577px) {
        /* For larger devices, force columns to be side by side */
        .main-footer .row {
            display: flex;
        }
        .main-footer .col-sm-4 {
            width: 33.33%;
        }
    }
    </style>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
</body>
</html> 