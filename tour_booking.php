<?php
// Start session
session_start();

// Check if tour id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to tours page if no tour id is provided
    header('Location: tours.php');
    exit;
}

$tour_id = intval($_GET['id']);

// Redirect to the book_tour.php page with the tour ID
header("Location: book_tour.php?id=$tour_id");
exit;
?> 