// Modal Functions
function openLoginModal() {
    window.location.href = 'login.php';
}

function closeLoginModal() {
    document.getElementById('login-modal').style.display = 'none';
}

function openRegisterModal() {
    window.location.href = 'register.php';
}

function closeRegisterModal() {
    document.getElementById('register-modal').style.display = 'none';
}

// Navigation functionality
document.addEventListener('DOMContentLoaded', function() {
    // Navigation click handlers for both header and footer links
    const navLinks = document.querySelectorAll('nav ul li a, .footer-section ul li a');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // If the link is for rooms, facilities, packages, tours, or index/home, let the default action happen (redirect to the page)
            if (href === 'rooms.php' || href === 'facilities.php' || href === 'packages.php' || href === 'tours.php' || href === 'index.php') {
                return;
            }
            
            // For other links, prevent default and handle with JavaScript
            e.preventDefault();
            
            // Remove active class from all header nav links
            document.querySelectorAll('nav ul li a').forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link if it's in the header
            if (this.closest('nav')) {
                this.classList.add('active');
            }
            
            // Get the section to show from the href attribute
            const sectionId = href.replace('.php', '');
            
            // Handle navigation
            if (sectionId === 'index' || sectionId === '' || sectionId === '#') {
                // Scroll to top for home
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else if (sectionId === 'about') {
                // Scroll to the specific section
                const targetSection = document.getElementById(sectionId + '-section');
                if (targetSection) {
                    // Calculate header height for offset
                    const headerHeight = document.querySelector('header').offsetHeight;
                    const targetPosition = targetSection.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                    
                    window.scrollTo({ top: targetPosition, behavior: 'smooth' });
                }
            }
        });
    });

    // Display error messages if they exist in session
    if (document.querySelector('.login-error')) {
        openLoginModal();
    }

    if (document.querySelector('.register-error')) {
        openRegisterModal();
    }
});

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

// Date input formatting
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for check-in and check-out to today
    const today = new Date().toISOString().split('T')[0];
    const checkInInput = document.getElementById('check-in');
    const checkOutInput = document.getElementById('check-out');

    if (checkInInput && checkOutInput) {
        checkInInput.min = today;
        checkOutInput.min = today;

        // Update check-out min date when check-in date changes
        checkInInput.addEventListener('change', function() {
            checkOutInput.min = this.value;

            // If check-out date is before new check-in date, reset it
            if (checkOutInput.value < checkInInput.value) {
                checkOutInput.value = checkInInput.value;
            }
        });
    }

    // Admin panel submenu toggle
    const menuItems = document.querySelectorAll('.sidebar-menu > li');

    if (menuItems) {
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                const hasSubmenu = this.querySelector('.sidebar-submenu');

                if (hasSubmenu) {
                    this.classList.toggle('active');
                }
            });
        });
    }
});

// Book Now button functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get all "Book Now" buttons
    const bookNowButtons = document.querySelectorAll('.book-now-btn');
    
    // Add click event listener to each "Book Now" button
    bookNowButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Check if user is logged in
            const isLoggedIn = document.querySelector('.user-profile') !== null;
            
            if (!isLoggedIn) {
                // Get item details
                let itemName = "";
                let itemPrice = "";
                let itemType = "";
                let itemId = this.getAttribute('data-room-id') || this.getAttribute('data-tour-id') || this.getAttribute('data-package-id') || "";

                // Find the closest card to get details
                const roomCard = this.closest('.room-card');
                const tourCard = this.closest('.tour-card');
                const packageCard = this.closest('.package-card');
                
                if (roomCard) {
                    itemName = roomCard.querySelector('h3')?.textContent || "";
                    itemPrice = roomCard.querySelector('.price')?.textContent || "";
                    itemType = "room";
                } else if (tourCard) {
                    itemName = tourCard.querySelector('h2')?.textContent || "";
                    itemPrice = tourCard.querySelector('.tour-price')?.textContent || "";
                    itemType = "tour";
                } else if (packageCard) {
                    itemName = packageCard.querySelector('h3')?.textContent || "";
                    itemPrice = packageCard.querySelector('.package-price')?.textContent || "";
                    itemType = "package";
                }
                
                // Store the booking details in sessionStorage to retrieve after login
                sessionStorage.setItem('selectedItemName', itemName);
                sessionStorage.setItem('selectedItemPrice', itemPrice);
                sessionStorage.setItem('selectedItemType', itemType);
                sessionStorage.setItem('selectedItemId', itemId);
                
                // Redirect to login page
                window.location.href = 'login.php';
            } else {
                // User is logged in, handle booking
                let itemId = this.getAttribute('data-room-id') || this.getAttribute('data-tour-id') || this.getAttribute('data-package-id') || "";
                let bookingType = "";
                
                if (this.getAttribute('data-room-id')) {
                    bookingType = "room";
                } else if (this.getAttribute('data-tour-id')) {
                    bookingType = "tour";
                } else if (this.getAttribute('data-package-id')) {
                    bookingType = "package";
                } else {
                    // Determine by parent element
                    if (this.closest('.room-card')) {
                        bookingType = "room";
                    } else if (this.closest('.tour-card')) {
                        bookingType = "tour";
                    } else if (this.closest('.package-card')) {
                        bookingType = "package";
                    }
                }
                
                // Redirect to appropriate booking page
                if (bookingType === "room") {
                    window.location.href = `book_room.php?id=${itemId}`;
                } else if (bookingType === "tour") {
                    window.location.href = `book_tour.php?id=${itemId}`;
                } else if (bookingType === "package") {
                    window.location.href = `book_package.php?id=${itemId}`;
                }
            }
        });
    });
    
    // Close modals when clicking the close button or outside the modal
    const closeButtons = document.querySelectorAll('.close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // Check for redirect after login
    if (sessionStorage.getItem('selectedItemType') && sessionStorage.getItem('selectedItemId') && document.querySelector('.user-profile')) {
        const itemType = sessionStorage.getItem('selectedItemType');
        const itemId = sessionStorage.getItem('selectedItemId');
        
        // Clear the session storage
        sessionStorage.removeItem('selectedItemName');
        sessionStorage.removeItem('selectedItemPrice');
        sessionStorage.removeItem('selectedItemType');
        sessionStorage.removeItem('selectedItemId');
        
        // Redirect to appropriate booking page
        if (itemType === "room") {
            window.location.href = `book_room.php?id=${itemId}`;
        } else if (itemType === "tour") {
            window.location.href = `book_tour.php?id=${itemId}`;
        } else if (itemType === "package") {
            window.location.href = `book_package.php?id=${itemId}`;
        }
    }
});

// Form validation
function validateRegistrationForm() {
    const form = document.querySelector('#register-modal form');

    if (form) {
        form.addEventListener('submit', function(event) {
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }
}

// Initialize validation
validateRegistrationForm();