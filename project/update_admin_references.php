<?php
// Script to update references to admin files in non-admin files

// List of admin files that were moved
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

// List of non-admin files to update
$non_admin_files = [
    'index.php',
    'rooms.php',
    'tours.php',
    'packages.php',
    'book_room.php',
    'book_tour.php',
    'book_package.php',
    'login.php',
    'login_process.php',
    'register.php',
    'register_process.php',
    'profile.php',
    'my_bookings.php',
    'update_profile.php',
    'contact.php',
    'about.php',
    'facilities.php',
    'logout.php',
    'change_password.php'
];

// Counter for updated files
$updated_count = 0;

// Update non-admin files
foreach ($non_admin_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Update references to admin files
        foreach ($admin_files as $admin_file) {
            // Replace links to admin files
            $content = str_replace("href=\"$admin_file\"", "href=\"admin/$admin_file\"", $content);
            $content = str_replace("action=\"$admin_file\"", "action=\"admin/$admin_file\"", $content);
            
            // Replace PHP redirects to admin files
            $content = str_replace("header(\"Location: $admin_file\"", "header(\"Location: admin/$admin_file\"", $content);
            $content = str_replace("header('Location: $admin_file'", "header('Location: admin/$admin_file'", $content);
        }
        
        // Only save the file if there were changes
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            echo "Updated references in $file<br>";
            $updated_count++;
        } else {
            echo "No changes needed in $file<br>";
        }
    } else {
        echo "Warning: $file not found<br>";
    }
}

echo "<br>Updated $updated_count files with new references to admin files.<br>";
echo "Please test all functionality to ensure everything works correctly.<br>"; 