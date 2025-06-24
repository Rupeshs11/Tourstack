<?php
// Start session
session_start();

// Include header
include 'includes/header.php';

// Process contact form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $messageContent = trim($_POST['message']);
    
    // Simple validation
    if (empty($name) || empty($email) || empty($subject) || empty($messageContent)) {
        $message = 'Please fill in all fields';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address';
        $messageType = 'danger';
    } else {
        // In a real application, you would send an email or save to database here
        // For demo purposes, we'll just show a success message
        $message = 'Thank you for your message! We will get back to you soon.';
        $messageType = 'success';
        
        // Reset form data after successful submission
        $name = $email = $subject = $messageContent = '';
    }
}
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Contact Us</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact</li>
                </ol>
            </nav>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="contact-wrapper">
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h2 class="section-title">Get In Touch</h2>
                        <p class="contact-intro">We're here to answer any questions you may have about our services, booking process, or anything else. Feel free to reach out to us using any of the methods below or fill out the contact form.</p>
                        
                        <div class="contact-details">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <h3>Address</h3>
                                    <p>123 Tourism Street, Travel City, TC 45678</p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <h3>Phone</h3>
                                    <p>+1 (555) 123-4567</p>
                                    <p>+1 (555) 987-6543</p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-text">
                                    <h3>Email</h3>
                                    <p>info@tourstack.com</p>
                                    <p>support@tourstack.com</p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-text">
                                    <h3>Working Hours</h3>
                                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                                    <p>Saturday: 10:00 AM - 4:00 PM</p>
                                    <p>Sunday: Closed</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="social-links">
                            <h3>Follow Us</h3>
                            <div class="social-icons">
                                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="contact-form-container">
                        <h2 class="section-title">Send Us a Message</h2>
                        <form class="contact-form" method="post" action="">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" class="form-control" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" class="form-control" rows="5" required><?php echo isset($messageContent) ? htmlspecialchars($messageContent) : ''; ?></textarea>
                            </div>
                            
                            <button type="submit" name="submit_contact" class="btn btn-primary submit-btn">
                                Send Message <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="map-container">
            <h2 class="section-title">Find Us</h2>
            <div class="map">
                <!-- Replace with your Google Maps embed code -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3151.8351288872545!2d144.9556518!3d-37.8173276!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad65d4c2b349649%3A0xb6899234e561db11!2sA%20Generic%20Place!5e0!3m2!1sen!2sus!4v1594043250402!5m2!1sen!2sus" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
            </div>
        </div>
    </div>
</main>

<style>
    .contact-wrapper {
        padding: 50px 0;
    }
    
    .section-title {
        position: relative;
        font-size: 2rem;
        margin-bottom: 30px;
        color: var(--gray-800);
        padding-bottom: 15px;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 70px;
        height: 3px;
        background-color: var(--primary-color);
    }
    
    .contact-intro {
        color: var(--gray-600);
        margin-bottom: 30px;
        line-height: 1.7;
    }
    
    .contact-details {
        margin-bottom: 40px;
    }
    
    .contact-item {
        display: flex;
        margin-bottom: 25px;
    }
    
    .contact-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background-color: var(--primary-color);
        border-radius: 50%;
        margin-right: 20px;
        flex-shrink: 0;
    }
    
    .contact-icon i {
        color: white;
        font-size: 1.2rem;
    }
    
    .contact-text h3 {
        font-size: 1.2rem;
        margin: 0 0 8px;
        color: var(--gray-800);
    }
    
    .contact-text p {
        margin: 0 0 5px;
        color: var(--gray-600);
    }
    
    .social-links h3 {
        font-size: 1.2rem;
        margin-bottom: 15px;
        color: var(--gray-800);
    }
    
    .social-icons {
        display: flex;
        gap: 15px;
    }
    
    .social-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: var(--gray-100);
        border-radius: 50%;
        color: var(--gray-600);
        transition: all 0.3s ease;
    }
    
    .social-icon:hover {
        background-color: var(--primary-color);
        color: white;
    }
    
    .contact-form-container {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 30px;
        box-shadow: var(--box-shadow);
        margin-bottom: 30px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--gray-700);
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--gray-300);
        border-radius: var(--border-radius);
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
    }
    
    .submit-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 500;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .submit-btn i {
        font-size: 0.9rem;
        transition: transform 0.3s ease;
    }
    
    .submit-btn:hover i {
        transform: translateX(5px);
    }
    
    .map-container {
        margin: 50px 0;
    }
    
    .map-container .section-title {
        text-align: center;
    }
    
    .map-container .section-title:after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .map {
        overflow: hidden;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }
    
    .alert {
        margin-bottom: 30px;
    }
    
    @media (max-width: 992px) {
        .contact-info {
            margin-bottom: 50px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?> 