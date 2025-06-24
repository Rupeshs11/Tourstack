<?php
// Script to delete original admin files after migration

// List of admin files to delete
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

// Final confirmation
echo "<h2>WARNING: This will delete the original admin files.</h2>";
echo "<p>Before proceeding, make sure you have:</p>";
echo "<ol>";
echo "<li>Tested the admin section at /tourstack/admin/ thoroughly</li>";
echo "<li>Verified all functionality works correctly</li>";
echo "<li>Made a backup of your files or have version control</li>";
echo "</ol>";

// Check if confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $deleted_count = 0;
    $failed_count = 0;
    
    // Delete each file
    foreach ($admin_files as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "Deleted: $file<br>";
                $deleted_count++;
            } else {
                echo "Failed to delete: $file<br>";
                $failed_count++;
            }
        } else {
            echo "File not found: $file<br>";
        }
    }
    
    echo "<br>Deleted $deleted_count files. Failed to delete $failed_count files.<br>";
    echo "Admin migration is now complete!<br>";
} else {
    // Show confirmation link
    echo "<p><a href='?confirm=yes' style='color: red; font-weight: bold;'>Click here to confirm deletion</a></p>";
    echo "<p><strong>This action cannot be undone!</strong></p>";
}
?> 