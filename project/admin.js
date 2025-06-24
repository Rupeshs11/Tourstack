document.addEventListener('DOMContentLoaded', function() {
    // Submenu toggle
    const menuItems = document.querySelectorAll('.admin-nav .has-submenu > a');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            
            // Toggle active class for the clicked menu item
            parent.classList.toggle('active');
            
            // Close other submenus
            menuItems.forEach(otherItem => {
                if (otherItem !== this) {
                    otherItem.parentElement.classList.remove('active');
                }
            });
        });
    });
    
    // Main Navigation
    const navLinks = document.querySelectorAll('.admin-nav > ul > li > a');
    const contentSections = document.querySelectorAll('.content-section');
    const sectionTitle = document.getElementById('section-title');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Only prevent default for main menu items that don't have submenus
            if (!this.parentElement.classList.contains('has-submenu')) {
                e.preventDefault();
                
                // Remove active class from all links
                navLinks.forEach(navLink => {
                    navLink.parentElement.classList.remove('active');
                });
                
                // Add active class to clicked link
                this.parentElement.classList.add('active');
                
                // Get section ID from href
                const sectionId = this.getAttribute('href').substring(1);
                
                // Hide all content sections
                contentSections.forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show the selected section
                const selectedSection = document.getElementById(sectionId + '-section');
                if (selectedSection) {
                    selectedSection.style.display = 'block';
                }
                
                // Update header title
                updateHeaderTitle(sectionId);
                
                // On mobile, close the sidebar when a menu item is clicked
                const adminSidebar = document.querySelector('.admin-sidebar');
                if (window.innerWidth <= 768) {
                    adminSidebar.classList.remove('mobile-active');
                    document.getElementById('mobile-menu-toggle').classList.remove('active');
                }
            }
        });
    });
    
    // Submenu Navigation
    const submenuLinks = document.querySelectorAll('.admin-nav .submenu a');
    
    submenuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all submenu links
            submenuLinks.forEach(submenuLink => {
                submenuLink.classList.remove('active');
            });
            
            // Add active class to clicked submenu link
            this.classList.add('active');
            
            // Get section ID from href
            const sectionId = this.getAttribute('href').substring(1);
            
            // Update header title based on submenu item
            updateHeaderTitle(sectionId);
            
            // You would typically load content related to the submenu here
            // For this demo, we'll just update the bookings section
            
            // On mobile, close the sidebar
            if (window.innerWidth <= 768) {
                document.querySelector('.admin-sidebar').classList.remove('mobile-active');
                document.getElementById('mobile-menu-toggle').classList.remove('active');
            }
        });
    });
    
    // Booking action buttons
    const cancelButtons = document.querySelectorAll('.cancel-btn');
    const downloadButtons = document.querySelectorAll('.download-btn');
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                alert('Booking cancelled successfully!');
                // In a real app, this would send a request to the server
            }
        });
    });
    
    downloadButtons.forEach(button => {
        button.addEventListener('click', function() {
            alert('Booking details will be downloaded as PDF');
            // In a real app, this would generate and download a PDF
        });
    });
    
    // User action buttons
    const editButtons = document.querySelectorAll('.edit-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            alert('User edit form would open here');
            // In a real app, this would open a modal with the user edit form
        });
    });
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this user?')) {
                alert('User deleted successfully!');
                // In a real app, this would send a request to the server
            }
        });
    });
    
    // Helper function to update the header title
    function updateHeaderTitle(sectionId) {
        switch(sectionId) {
            case 'dashboard':
                sectionTitle.textContent = 'Dashboard';
                break;
            case 'bookings':
                sectionTitle.textContent = 'Bookings Management';
                break;
            case 'new-bookings':
                sectionTitle.textContent = 'New Bookings';
                break;
            case 'refund-bookings':
                sectionTitle.textContent = 'Refund Bookings';
                break;
            case 'booking-records':
                sectionTitle.textContent = 'Booking Records';
                break;
            case 'users':
                sectionTitle.textContent = 'User Management';
                break;
            case 'user-queries':
                sectionTitle.textContent = 'User Queries';
                break;
            case 'ratings-reviews':
                sectionTitle.textContent = 'Ratings & Reviews';
                break;
            case 'rooms':
                sectionTitle.textContent = 'Room Management';
                break;
            case 'features-facilities':
                sectionTitle.textContent = 'Features & Facilities';
                break;
            case 'tours':
                sectionTitle.textContent = 'Tours Management';
                break;
            case 'carousel':
                sectionTitle.textContent = 'Carousel Management';
                break;
            case 'settings':
                sectionTitle.textContent = 'Settings';
                break;
            default:
                sectionTitle.textContent = 'Dashboard';
        }
    }
    
    // Add New buttons
    const addNewBookingBtn = document.querySelector('.bookings-management .add-new-btn');
    const addNewUserBtn = document.querySelector('.user-management .add-new-btn');
    
    if (addNewBookingBtn) {
        addNewBookingBtn.addEventListener('click', function() {
            alert('New booking form would open here');
            // In a real app, this would open a modal with the booking form
        });
    }
    
    if (addNewUserBtn) {
        addNewUserBtn.addEventListener('click', function() {
            alert('New user form would open here');
            // In a real app, this would open a modal with the user form
        });
    }
    
    // Search functionality
    const searchBtn = document.querySelector('.search-bar button');
    const searchInput = document.querySelector('.search-bar input');
    
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            alert('Searching for: ' + searchInput.value);
            // In a real application, this would filter the user table or fetch search results
        });
    }
    
    // Quick Access Cards functionality
    const quickCards = document.querySelectorAll('.quick-card');
    
    quickCards.forEach(card => {
        card.addEventListener('click', function() {
            const targetSection = this.getAttribute('data-target');
            
            // Find the corresponding nav link and simulate a click
            const navLink = document.querySelector(`.admin-nav a[href="#${targetSection}"]`);
            
            if (navLink) {
                // For submenu items inside a parent menu
                if (navLink.closest('.submenu')) {
                    // First activate the parent menu
                    const parentMenu = navLink.closest('.has-submenu');
                    if (parentMenu && !parentMenu.classList.contains('active')) {
                        parentMenu.classList.add('active');
                    }
                    
                    // Then simulate a click on the submenu item
                    navLink.click();
                } else {
                    // For main menu items, just click
                    navLink.click();
                }
            }
            
            // Hide all content sections
            contentSections.forEach(section => {
                section.style.display = 'none';
            });
            
            // Show the target section
            const selectedSection = document.getElementById(targetSection + '-section');
            if (selectedSection) {
                selectedSection.style.display = 'block';
                
                // Update header title
                updateHeaderTitle(targetSection);
            }
        });
    });
    
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('mobile-active');
            this.classList.toggle('active');
        });
    }
}); 