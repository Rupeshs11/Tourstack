<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $dob = filter_var($_POST['dob'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Validate phone number is exactly 10 digits
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be exactly 10 digits";
    }
    
    if (empty($dob)) {
        $errors[] = "Date of birth is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // Add password strength validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check for special character
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must contain at least one special character (e.g., !@#$%^&*(),.?\":{}|<>)";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($stmt === false) {
        // Prepare statement failed
        $_SESSION['register_error'] = "Database error: " . $conn->error;
        $_SESSION['register_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dob' => $dob
        ];
        header("Location: register.php");
        exit;
    }
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        // Execute failed
        $_SESSION['register_error'] = "Database error: " . $stmt->error;
        $_SESSION['register_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dob' => $dob
        ];
        header("Location: register.php");
        exit;
    }
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already registered";
    }
    
    $stmt->close();
    
    // If there are errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['register_error'] = $errors[0]; // Just use the first error
        $_SESSION['register_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dob' => $dob
        ];
        header("Location: register.php");
        exit;
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if users table has a phone or mobile column and a dob column
    $check_phone_column = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    $check_mobile_column = $conn->query("SHOW COLUMNS FROM users LIKE 'mobile'");
    $check_dob_column = $conn->query("SHOW COLUMNS FROM users LIKE 'dob'");
    $phone_column_exists = $check_phone_column && $check_phone_column->num_rows > 0;
    $mobile_column_exists = $check_mobile_column && $check_mobile_column->num_rows > 0;
    $dob_column_exists = $check_dob_column && $check_dob_column->num_rows > 0;

    // Insert new user into database with the appropriate fields
    if ($phone_column_exists) {
        if ($dob_column_exists) {
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, dob, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt === false) {
                // Prepare statement failed
                $_SESSION['register_error'] = "Database error: " . $conn->error;
                $_SESSION['register_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'dob' => $dob
                ];
                header("Location: register.php");
                exit;
            }
            $stmt->bind_param("sssss", $name, $email, $phone, $dob, $hashed_password);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt === false) {
                // Prepare statement failed
                $_SESSION['register_error'] = "Database error: " . $conn->error;
                $_SESSION['register_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'dob' => $dob
                ];
                header("Location: register.php");
                exit;
            }
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
        }
    } else if ($mobile_column_exists) {
        if ($dob_column_exists) {
            $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, dob, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt === false) {
                // Prepare statement failed
                $_SESSION['register_error'] = "Database error: " . $conn->error;
                $_SESSION['register_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'dob' => $dob
                ];
                header("Location: register.php");
                exit;
            }
            $stmt->bind_param("sssss", $name, $email, $phone, $dob, $hashed_password);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt === false) {
                // Prepare statement failed
                $_SESSION['register_error'] = "Database error: " . $conn->error;
                $_SESSION['register_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'dob' => $dob
                ];
                header("Location: register.php");
                exit;
            }
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
        }
    } else {
        // If neither column exists, redirect with error
        $_SESSION['register_error'] = "Database error: Missing required columns";
        $_SESSION['register_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dob' => $dob
        ];
        header("Location: register.php");
        exit;
    }

    if ($stmt->execute()) {
        // Registration successful
        $_SESSION['login_success'] = "Registration successful. Please login.";
        header("Location: login.php");
        exit;
    } else {
        // Registration failed
        $_SESSION['register_error'] = "Registration failed. Please try again.";
        $_SESSION['register_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dob' => $dob
        ];
        header("Location: register.php");
        exit;
    }
    
    $stmt->close();
} else {
    // If not POST request, redirect to register
    header("Location: register.php");
    exit;
}

$conn->close();
?> 