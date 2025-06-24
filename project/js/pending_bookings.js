/**
 * Pending Bookings Handler
 * 
 * This script checks if the user has pending bookings and displays a warning
 * when they try to book a new tour, package, or room.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to check for pending bookings
    function checkPendingBookings() {
        return new Promise((resolve, reject) => {
            fetch('includes/check_pending_bookings.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        reject(data.error);
                    } else {
                        resolve(data.pending_bookings || []);
                    }
                })
                .catch(error => {
                    console.error('Error checking pending bookings:', error);
                    reject(error);
                });
        });
    }

    // Function to create and show the pending bookings popup
    function showPendingBookingsPopup(pendingBookings, formElement) {
        // If there are no pending bookings, do nothing
        if (!pendingBookings || pendingBookings.length === 0) {
            return;
        }

        // Create the modal backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);

        // Create the modal HTML
        const modalHTML = `
            <div class="modal fade show" id="pendingBookingsModal" tabindex="-1" aria-labelledby="pendingBookingsModalLabel" style="display: block; padding-right: 17px;" aria-modal="true" role="dialog">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title" id="pendingBookingsModalLabel">
                                <i class="fas fa-exclamation-triangle me-2"></i>Pending Bookings
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>You already have the following pending bookings:</p>
                            <ul class="list-group list-group-flush mb-3">
                                ${pendingBookings.map(booking => `
                                    <li class="list-group-item">
                                        <strong>${booking.name}</strong> (${booking.type})
                                        <br>
                                        <small>Booking Date: ${new Date(booking.booking_date).toLocaleDateString()}</small>
                                        <a href="view_booking.php?type=${booking.type}&id=${booking.id}" class="btn btn-sm btn-info float-end">View</a>
                                    </li>
                                `).join('')}
                            </ul>
                            <p>Do you still want to proceed with this new booking?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="pendingBookingsCancelBtn">Cancel</button>
                            <button type="button" class="btn btn-primary" id="pendingBookingsProceedBtn">Proceed with Booking</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add the modal to the page
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHTML;
        document.body.appendChild(modalContainer);

        // Handle the close button
        const closeBtn = document.querySelector('#pendingBookingsModal .btn-close');
        closeBtn.addEventListener('click', removePendingBookingsPopup);

        // Handle the cancel button
        const cancelBtn = document.getElementById('pendingBookingsCancelBtn');
        cancelBtn.addEventListener('click', removePendingBookingsPopup);

        // Handle the proceed button
        const proceedBtn = document.getElementById('pendingBookingsProceedBtn');
        proceedBtn.addEventListener('click', () => {
            removePendingBookingsPopup();
            // Find the proceed_with_booking input in the form and set it to true
            const proceedInput = formElement.querySelector('[name="proceed_with_booking"]');
            if (proceedInput) {
                proceedInput.value = 'true';
            }
            // Submit the form
            formElement.submit();
        });

        // Prevent scrolling of the background
        document.body.style.overflow = 'hidden';
    }

    // Function to remove the pending bookings popup
    function removePendingBookingsPopup() {
        const modal = document.getElementById('pendingBookingsModal');
        if (modal) {
            modal.parentNode.removeChild(modal);
        }
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
        document.body.style.overflow = '';
    }

    // Add event listener to all booking forms
    const setupBookingForm = (form) => {
        if (!form) return;
        
        // Make sure the form has a proceed_with_booking field
        let proceedField = form.querySelector('[name="proceed_with_booking"]');
        if (!proceedField) {
            proceedField = document.createElement('input');
            proceedField.type = 'hidden';
            proceedField.name = 'proceed_with_booking';
            proceedField.value = 'false';
            form.appendChild(proceedField);
        }

        form.addEventListener('submit', function(event) {
            // Only check for pending bookings if we haven't already decided to proceed
            if (proceedField.value !== 'true') {
                event.preventDefault();
                
                checkPendingBookings()
                    .then(pendingBookings => {
                        if (pendingBookings.length > 0) {
                            // Show the pending bookings popup
                            showPendingBookingsPopup(pendingBookings, form);
                        } else {
                            // No pending bookings, proceed with the form submission
                            proceedField.value = 'true';
                            form.submit();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // If there's an error, just proceed with the booking
                        proceedField.value = 'true';
                        form.submit();
                    });
            }
        });
    };

    // Standard booking form
    const standardBookingForm = document.getElementById('booking_form');
    if (standardBookingForm) {
        setupBookingForm(standardBookingForm);
    }

    // Room booking forms (which have dynamic IDs)
    const roomBookingForms = document.querySelectorAll('form.booking-form');
    roomBookingForms.forEach(form => {
        setupBookingForm(form);
    });
}); 