// Wait for the document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Add active class to current nav item
    const currentLocation = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (currentLocation.includes(href) && href !== 'index.php') {
            link.classList.add('active');
        } else if (currentLocation.endsWith('tourstack/') || currentLocation.endsWith('tourstack/index.php')) {
            // If we're on the home page
            if (href === 'index.php') {
                link.classList.add('active');
            }
        }
    });
    
    // Initialize dropdowns
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    if (typeof bootstrap !== 'undefined') {
        dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined') {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    if (typeof bootstrap !== 'undefined') {
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }
    
    // Add smooth scrolling to all links with hashes
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            if (this.hash !== '') {
                e.preventDefault();
                
                const target = document.querySelector(this.hash);
                if (target) {
                    const headerOffset = 100;
                    const elementPosition = target.getBoundingClientRect().top;
                    const offsetPosition = elementPosition - headerOffset;
                    
                    window.scrollBy({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert) {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                } else {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }
            }
        }, 5000);
    });
    
    // Handle filter forms in packages, rooms, and tours pages
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('input, select');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                applyFilters();
            });
        });
        
        function applyFilters() {
            const filters = {};
            
            // Collect all filter values
            filterInputs.forEach(input => {
                if (input.type === 'checkbox') {
                    if (input.checked) {
                        if (!filters[input.name]) {
                            filters[input.name] = [];
                        }
                        filters[input.name].push(input.value);
                    }
                } else if (input.value) {
                    filters[input.name] = input.value;
                }
            });
            
            // Get all items to filter
            const items = document.querySelectorAll('.filter-item');
            
            // Apply filters to each item
            items.forEach(item => {
                let visible = true;
                
                // Check each filter against the item's data attributes
                for (const filterName in filters) {
                    const filterValue = filters[filterName];
                    const itemValue = item.dataset[filterName];
                    
                    if (Array.isArray(filterValue)) {
                        // For checkbox groups (arrays)
                        if (filterValue.length > 0 && !filterValue.includes(itemValue)) {
                            visible = false;
                            break;
                        }
                    } else if (filterName === 'priceMin' && itemValue) {
                        // For price minimum
                        if (parseFloat(itemValue) < parseFloat(filterValue)) {
                            visible = false;
                            break;
                        }
                    } else if (filterName === 'priceMax' && itemValue) {
                        // For price maximum
                        if (parseFloat(itemValue) > parseFloat(filterValue)) {
                            visible = false;
                            break;
                        }
                    } else if (filterValue && itemValue !== filterValue) {
                        // For standard select filters
                        visible = false;
                        break;
                    }
                }
                
                // Show or hide the item
                if (visible) {
                    item.style.display = '';
                    item.classList.remove('d-none');
                } else {
                    item.style.display = 'none';
                    item.classList.add('d-none');
                }
            });
            
            // Check if any items are visible
            const visibleItems = document.querySelectorAll('.filter-item:not(.d-none)');
            const noResultsMessage = document.getElementById('no-results');
            
            if (visibleItems.length === 0 && noResultsMessage) {
                noResultsMessage.style.display = 'block';
            } else if (noResultsMessage) {
                noResultsMessage.style.display = 'none';
            }
        }
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    if (forms.length > 0) {
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Mobile menu toggle
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) {
                if (target.classList.contains('show')) {
                    target.classList.remove('show');
                } else {
                    target.classList.add('show');
                }
            }
        });
    }
    
    // Date calculations for booking
    const checkInDateInput = document.getElementById('check_in_date');
    const checkOutDateInput = document.getElementById('check_out_date');
    const totalNightsSpan = document.getElementById('total_nights');
    const totalPriceSpan = document.getElementById('total_price');
    const roomPriceInput = document.getElementById('room_price');
    
    if (checkInDateInput && checkOutDateInput && totalNightsSpan && totalPriceSpan && roomPriceInput) {
        const calculateTotal = function() {
            const checkInDate = new Date(checkInDateInput.value);
            const checkOutDate = new Date(checkOutDateInput.value);
            
            if (checkInDate && checkOutDate && checkInDate < checkOutDate) {
                const diffTime = Math.abs(checkOutDate - checkInDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                totalNightsSpan.textContent = diffDays;
                
                const roomPrice = parseFloat(roomPriceInput.value);
                const totalPrice = roomPrice * diffDays;
                
                totalPriceSpan.textContent = totalPrice.toFixed(2);
            }
        };
        
        checkInDateInput.addEventListener('change', calculateTotal);
        checkOutDateInput.addEventListener('change', calculateTotal);
    }
}); 