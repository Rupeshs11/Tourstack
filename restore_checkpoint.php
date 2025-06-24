<?php
// Start session
session_start();

// Include header
include 'includes/header.php';
?>

<main class="container mt-5 mb-5">
    <h1 class="display-4 text-center mb-4">Restore Website Checkpoint</h1>
    
    <div class="alert alert-info">
        <p>Use this page to restore the website to the checkpoint state. Follow the steps below in order.</p>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Step 1: Create Tour Data</h2>
        </div>
        <div class="card-body">
            <p>Create the required tour data by clicking the button below:</p>
            <a href="create_specific_tours.php" class="btn btn-primary">Set Up Tours</a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Step 2: Create Tour Images</h2>
        </div>
        <div class="card-body">
            <p>Create placeholder images for tours:</p>
            <a href="placeholder_images.php" class="btn btn-primary">Create Tour Images</a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Step 3: Create Package Data</h2>
        </div>
        <div class="card-body">
            <p>Create the required package data by clicking the button below:</p>
            <a href="create_specific_packages.php" class="btn btn-primary">Set Up Packages</a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Step 4: Create Package Images</h2>
        </div>
        <div class="card-body">
            <p>Create placeholder images for packages:</p>
            <a href="placeholder_packages.php" class="btn btn-primary">Create Package Images</a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Step 5: View Restored Pages</h2>
        </div>
        <div class="card-body">
            <p>After completing the above steps, you can view the following pages:</p>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <a href="tours.php" class="btn btn-success w-100">View Tours Page</a>
                </div>
                <div class="col-md-6 mb-2">
                    <a href="packages.php" class="btn btn-success w-100">View Packages Page</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h2 class="h5 mb-0">Main Pages</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <a href="index.php" class="btn btn-outline-primary w-100">Home</a>
                </div>
                <div class="col-md-4 mb-2">
                    <a href="rooms.php" class="btn btn-outline-primary w-100">Rooms</a>
                </div>
                <div class="col-md-4 mb-2">
                    <a href="my_bookings.php" class="btn btn-outline-primary w-100">My Bookings</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 