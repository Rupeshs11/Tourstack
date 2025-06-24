<?php
// Script to move admin files to admin folder and update references

// List of admin files to move
$admin_files = [
    // Admin interface files
    'admin_dashboard.php',
    'admin_users.php',
    'admin_customers.php',
    'admin_bookings.php',
    'admin_new_booking.php',
    'admin_index.php',
    'admin_sidebar.php',
    'admin_logout.php',
    'admin_room_detail.php',
    'admin_room_edit.php',
    'admin_room_types.php',
    'admin_room_type_edit.php',
    'admin_package_edit.php',
    'admin_review_reply.php',
    'admin.php',
    
    // Simple admin files
    'simple_rooms.php',
    'simple_tours.php',
    'simple_packages.php',
    'simple_room_detail.php',
    'simple_tour_detail.php',
    
    // Additional admin-related files
    'add_room.php',
    'add_tour.php',
    'add_package.php',
    'remove_all_rooms.php',
    'remove_all_tours.php'
];

// Create admin folder if it doesn't exist
if (!is_dir('admin')) {
    mkdir('admin', 0755);
    echo "Created admin directory<br>";
}

// CSS and JS files to copy (not move)
$copy_files = [
    'admin.css',
    'admin.js'
];

// Copy CSS and JS files to admin folder
foreach ($copy_files as $file) {
    if (file_exists($file)) {
        copy($file, 'admin/' . $file);
        echo "Copied $file to admin/$file<br>";
    } else {
        echo "Warning: $file not found<br>";
    }
}

// Move admin files to admin folder
foreach ($admin_files as $file) {
    if (file_exists($file)) {
        // Read the file content
        $content = file_get_contents($file);
        
        // Update links to other admin files
        foreach ($admin_files as $admin_file) {
            // Replace direct links to admin files
            $content = str_replace("href=\"$admin_file\"", "href=\"admin/$admin_file\"", $content);
            $content = str_replace("action=\"$admin_file\"", "action=\"admin/$admin_file\"", $content);
            
            // Replace PHP redirects
            $content = str_replace("header(\"Location: $admin_file\"", "header(\"Location: admin/$admin_file\"", $content);
            $content = str_replace("header('Location: $admin_file'", "header('Location: admin/$admin_file'", $content);
        }
        
        // Update links to CSS and JS files
        foreach ($copy_files as $css_js_file) {
            $content = str_replace("href=\"$css_js_file\"", "href=\"admin/$css_js_file\"", $content);
            $content = str_replace("src=\"$css_js_file\"", "src=\"admin/$css_js_file\"", $content);
        }
        
        // Update include/require paths for db_connection.php
        $content = str_replace("require_once 'db_connection.php'", "require_once '../db_connection.php'", $content);
        $content = str_replace("require 'db_connection.php'", "require '../db_connection.php'", $content);
        $content = str_replace("include_once 'db_connection.php'", "include_once '../db_connection.php'", $content);
        $content = str_replace("include 'db_connection.php'", "include '../db_connection.php'", $content);
        
        // Handle paths for includes directory
        $content = str_replace("require_once 'includes/", "require_once '../includes/", $content);
        $content = str_replace("require 'includes/", "require '../includes/", $content);
        $content = str_replace("include_once 'includes/", "include_once '../includes/", $content);
        $content = str_replace("include 'includes/", "include '../includes/", $content);
        
        // Handle image paths
        $content = str_replace("src=\"images/", "src=\"../images/", $content);
        $content = str_replace("\"images/", "\"../images/", $content);

        // Save the updated content to the admin folder
        file_put_contents('admin/' . $file, $content);
        echo "Moved and updated $file to admin/$file<br>";
        
        // Don't delete the original files yet for safety
        // We'll do that manually after verifying everything works
    } else {
        echo "Warning: $file not found<br>";
    }
}

// Update index.php to redirect admin links to the admin folder
if (file_exists('index.php')) {
    $content = file_get_contents('index.php');
    
    // Update admin links
    foreach ($admin_files as $admin_file) {
        $content = str_replace("href=\"$admin_file\"", "href=\"admin/$admin_file\"", $content);
    }
    
    file_put_contents('index.php', $content);
    echo "Updated index.php with admin folder references<br>";
}

echo "<br>Admin files have been moved to the admin folder.<br>";
echo "Please verify that everything works correctly before deleting the original files.<br>";
echo "You may also need to manually update some links or paths that were not caught by this script."; 