ADMIN FOLDER MIGRATION INSTRUCTIONS
=================================

The admin files have been moved to the 'admin' folder to better organize the codebase.
Here are some important points to ensure everything works correctly:

1. IMPORTANT: Test thoroughly before deleting the original files.
   - Navigate to http://localhost/Hotel-Management-System-main/tourstack/admin/admin_index.php
   - Login with your admin credentials
   - Verify that all admin pages load correctly
   - Test all functionality (creating, updating, deleting records)

2. Update any remaining file references:
   - If you encounter 'file not found' errors, check if they're related to paths
   - For admin files, change paths from 'file.php' to 'admin/file.php'
   - For non-admin files accessed from admin, change paths to '../file.php'

3. Path adjustments:
   - Database connection is now '../db_connection.php'
   - Image paths should be '../images/...'
   - CSS/JS includes should be '../style.css' or 'admin.css'

4. Once everything is working, you can delete the original admin files from the main directory.
   CAUTION: Keep a backup or use version control before deleting.

5. Update links in any documentation or external resources that might point to the old admin file locations.

Files Moved:
- All admin_*.php files
- simple_*.php files (admin interfaces)
- add_*.php files (admin creation interfaces)
- remove_all_*.php files

Files Copied:
- admin.css
- admin.js

Migration Scripts:
- move_admin_files.php: Created admin copies with updated paths
- update_admin_references.php: Updated links in non-admin files 