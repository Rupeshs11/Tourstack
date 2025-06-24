<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email and password are required";
        header("Location: login.php");
        exit;
    }
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    if ($stmt === false) {
        $_SESSION['login_error'] = "Database error: " . $conn->error;
        header("Location: login.php");
        exit;
    }
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        $_SESSION['login_error'] = "Database error: " . $stmt->error;
        header("Location: login.php");
        exit;
    }
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Redirect to index page
            header("Location: index.php");
            exit;
        } else {
            // Password is incorrect
            $_SESSION['login_error'] = "Invalid email or password";
            header("Location: login.php");
            exit;
        }
    } else {
        // Email not found
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: login.php");
        exit;
    }
    
    $stmt->close();
} else {
    // If not POST request, redirect to index
    header("Location: login.php");
    exit;
}

$conn->close();
?> 