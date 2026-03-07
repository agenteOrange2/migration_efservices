/**
 * Carrier Driver Testing Delete Confirmation
 * Handles delete confirmation dialogs for driver testing records
 */

/**
 * Initialize delete confirmation for testing records in index view
 * This function sets up event listeners for delete buttons and configures the modal
 */
function initializeTestingDeleteConfirmation() {
    const deleteButtons = document.querySelectorAll('.delete-testing');
    const deleteForm = document.getElementById('delete_testing_form');
    
    if (!deleteForm) {
        console.warn('Delete form not found');
        return;
    }
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const testingId = this.getAttribute('data-testing-id');
            const driverName = this.getAttribute('data-driver-name') || 'this driver';
            const testType = this.getAttribute('data-test-type') || 'this test';
            
            // Update the form action with the testing ID
            deleteForm.action = `${window.location.origin}/carrier/drivers/testings/${testingId}`;
            
            // Optionally update modal message with driver/test info
            const modalMessage = document.querySelector('#delete-confirmation-modal .modal-message');
            if (modalMessage) {
                modalMessage.textContent = `Are you sure you want to delete the ${testType} record for ${driverName}? This action cannot be undone.`;
            }
        });
    });
}

/**
 * Show delete confirmation modal for a specific testing record
 * Used in driver history and show views
 * 
 * @param {number} testingId - The ID of the testing record to delete
 * @param {string} context - The context from which delete is called ('history' or 'show')
 */
function confirmDeleteTesting(testingId, context = 'history') {
    const form = document.getElementById('deleteTestingForm');
    
    if (!form) {
        console.error('Delete form not found');
        return;
    }
    
    // Set the form action to the correct URL
    form.action = `${window.location.origin}/carrier/drivers/testings/${testingId}`;
    
    // Get the modal element
    const modalElement = document.querySelector('#deleteTestingModal');
    
    if (!modalElement) {
        console.error('Delete modal not found');
        return;
    }
    
    // Show the modal using Tailwind's modal component
    try {
        const modal = tailwind.Modal.getOrCreateInstance(modalElement);
        modal.show();
    } catch (error) {
        console.error('Error showing modal:', error);
        
        // Fallback: use native confirm dialog
        if (confirm('Are you sure you want to delete this testing record? This action cannot be undone.')) {
            form.submit();
        }
    }
}

/**
 * Show delete confirmation with custom message
 * Provides more flexibility for different contexts
 * 
 * @param {number} testingId - The ID of the testing record to delete
 * @param {Object} options - Configuration options
 * @param {string} options.driverName - Name of the driver
 * @param {string} options.testType - Type of test
 * @param {string} options.testDate - Date of test
 * @param {string} options.redirectUrl - URL to redirect after deletion (optional)
 */
function confirmDeleteTestingWithDetails(testingId, options = {}) {
    const {
        driverName = 'this driver',
        testType = 'test',
        testDate = '',
        redirectUrl = null
    } = options;
    
    // Build confirmation message
    let message = `Are you sure you want to delete the ${testType} record for ${driverName}`;
    if (testDate) {
        message += ` from ${testDate}`;
    }
    message += '? This action cannot be undone.';
    
    // Update modal message if modal exists
    const modalMessage = document.querySelector('#deleteTestingModal .modal-message');
    if (modalMessage) {
        modalMessage.textContent = message;
    }
    
    // Store redirect URL if provided
    if (redirectUrl) {
        const form = document.getElementById('deleteTestingForm');
        if (form) {
            // Add hidden input for redirect URL
            let redirectInput = form.querySelector('input[name="redirect_url"]');
            if (!redirectInput) {
                redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_url';
                form.appendChild(redirectInput);
            }
            redirectInput.value = redirectUrl;
        }
    }
    
    // Show the modal
    confirmDeleteTesting(testingId);
}

/**
 * Initialize delete confirmation on page load
 * Automatically detects which view is loaded and initializes accordingly
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the index page
    if (document.querySelector('.delete-testing')) {
        initializeTestingDeleteConfirmation();
    }
    
    // Add keyboard shortcut for modal dismissal (ESC key)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('[id*="delete"][id*="modal"]');
            modals.forEach(modal => {
                if (modal.classList.contains('show')) {
                    const modalInstance = tailwind.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            });
        }
    });
});

// Export functions for use in inline scripts
window.confirmDeleteTesting = confirmDeleteTesting;
window.confirmDeleteTestingWithDetails = confirmDeleteTestingWithDetails;
window.initializeTestingDeleteConfirmation = initializeTestingDeleteConfirmation;
