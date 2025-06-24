<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid package ID']);
    exit;
}

$package_id = $_GET['id'];

// Get package details
$query = "SELECT * FROM packages WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Package not found']);
    exit;
}

$package = $result->fetch_assoc();

// Convert any potential NULL values to empty strings for JSON encoding
foreach ($package as $key => $value) {
    if ($value === null) {
        $package[$key] = '';
    }
}

// Return package details
echo json_encode([
    'success' => true,
    'package' => $package
]); 