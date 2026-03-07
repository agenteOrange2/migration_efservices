/**
 * Driver Testing Form Management
 * Centralizes form logic for create and edit views
 */
class DriverTestingForm {
    constructor(options = {}) {
        this.isEditMode = options.isEditMode || false;
        this.currentDriverId = options.currentDriverId || null;
        this.uploadedFiles = [];
        
        this.initializeElements();
        this.bindEvents();
        this.initializeLivewire();
        
        // Sync hidden fields with select values on initialization
        this.syncHiddenFields();
        
        if (this.isEditMode && this.carrierSelect.value) {
            this.loadDrivers(this.carrierSelect.value, () => {
                this.selectCurrentDriver();
            });
        }
    }

    /**
     * Sync hidden fields with current select values
     * This ensures hidden fields are populated when page loads with pre-selected values
     */
    syncHiddenFields() {
        // Always sync carrier hidden field from select
        if (this.carrierSelect && this.carrierIdHidden) {
            this.carrierIdHidden.value = this.carrierSelect.value || '';
        }
        
        // Always sync driver hidden field from select
        if (this.driverSelect && this.userDriverDetailIdHidden) {
            this.userDriverDetailIdHidden.value = this.driverSelect.value || '';
        }
    }

    init() {
        // Method for backward compatibility
        // All initialization is already done in constructor
        console.log('DriverTestingForm initialized');
    }

    initializeElements() {
        // Form elements
        this.form = document.getElementById(this.isEditMode ? 'edit-test-form' : 'create-test-form');
        this.carrierSelect = document.getElementById('carrier_id');
        this.driverSelect = document.getElementById('user_driver_detail_id');
        this.carrierIdHidden = document.getElementById('carrier_id_hidden');
        this.userDriverDetailIdHidden = document.getElementById('user_driver_detail_id_hidden');
        
        // Driver detail elements
        this.driverDetailCard = document.getElementById('driver-detail-card');
        this.driverName = document.getElementById('driver-name');
        this.driverEmail = document.getElementById('driver-email');
        this.driverPhone = document.getElementById('driver-phone');
        this.driverLicense = document.getElementById('driver-license');
        this.driverLicenseClass = document.getElementById('driver-license-class');
        this.driverLicenseExpiration = document.getElementById('driver-license-expiration');
        
        // Other reason elements
        this.otherReasonCheckbox = document.getElementById('is_other_reason_test');
        this.otherReasonContainer = document.getElementById('other_reason_container');
        
        // File upload elements
        this.filesInput = document.getElementById('driver_testing_files_input');
        
        // Initialize files input
        if (this.filesInput) {
            this.filesInput.value = JSON.stringify(this.uploadedFiles);
        }
    }

    bindEvents() {
        // Carrier selection
        if (this.carrierSelect) {
            this.carrierSelect.addEventListener('change', (e) => {
                // Update hidden field immediately when carrier is selected
                if (this.carrierIdHidden) {
                    this.carrierIdHidden.value = e.target.value;
                }
                // Clear driver hidden field when carrier changes (driver list will be reloaded)
                if (this.userDriverDetailIdHidden) {
                    this.userDriverDetailIdHidden.value = '';
                }
                this.loadDrivers(e.target.value);
            });
        }

        // Driver selection
        if (this.driverSelect) {
            this.driverSelect.addEventListener('change', (e) => {
                this.showDriverDetails();
                if (this.userDriverDetailIdHidden) {
                    this.userDriverDetailIdHidden.value = e.target.value;
                }
            });
        }

        // Form submission
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                    return;
                }
                
                // Show form submission loading
                this.showFormLoading();
            });
        }

        // Other reason toggle
        if (this.otherReasonCheckbox) {
            this.otherReasonCheckbox.addEventListener('change', () => {
                this.toggleOtherReasonField();
            });
            // Initialize state
            this.toggleOtherReasonField();
        }
    }

    /**
     * Load active drivers for a specific carrier via API
     * 
     * This method fetches drivers from the API endpoint with enhanced error handling,
     * including timeout management and differentiated error messages for various failure scenarios.
     * 
     * @param {number|string} carrierId - The carrier ID to fetch drivers for
     * @param {function|null} callback - Optional callback to execute after successful load
     */
    async loadDrivers(carrierId, callback = null) {
        if (!carrierId) {
            this.clearDriverSelect();
            return;
        }

        // Show loading state
        this.showDriversLoading();

        try {
            // Create AbortController for timeout management
            // This allows us to cancel the request if it takes too long (10 seconds)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
            
            const response = await fetch(`/api/active-drivers-by-carrier/${carrierId}`, {
                signal: controller.signal
            });
            
            // Clear timeout once response is received
            clearTimeout(timeoutId);
            
            // Check response status and provide specific error messages
            if (!response.ok) {
                // Differentiate between server error types for better user feedback
                if (response.status >= 500) {
                    throw new Error(`Server error (${response.status}). Please try again later.`);
                } else if (response.status === 404) {
                    throw new Error('Carrier not found. Please select a valid carrier.');
                } else if (response.status === 403) {
                    throw new Error('Access denied. You do not have permission to view these drivers.');
                } else {
                    throw new Error(`Request failed with status ${response.status}`);
                }
            }
            
            const data = await response.json();

            // The API returns drivers directly as an array (not wrapped in a data property)
            if (Array.isArray(data)) {
                if (data.length > 0) {
                    // Populate dropdown with driver options
                    this.populateDriverSelect(data);
                    // Execute callback if provided (used in edit mode to pre-select driver)
                    if (callback) callback();
                    this.showSuccess(`${data.length} driver${data.length !== 1 ? 's' : ''} loaded successfully`);
                } else {
                    // Handle empty driver list with warning (not an error)
                    this.clearDriverSelect();
                    this.showWarning('No active drivers found for this carrier');
                }
            } else {
                // Invalid response format - log for debugging
                console.error('Error loading drivers: Invalid response format', data);
                this.showError('Invalid response format. Please contact support.');
                this.clearDriverSelect();
            }
        } catch (error) {
            // Differentiate between timeout, network, and server errors for better UX
            if (error.name === 'AbortError') {
                // Request was aborted due to timeout
                console.error('Request timeout loading drivers');
                this.showError('Request timeout. Please check your connection and try again.');
            } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                // Network connectivity issue
                console.error('Network error loading drivers:', error);
                this.showError('Network error. Please check your internet connection.');
            } else {
                // Other errors (validation, server, etc.)
                console.error('Error loading drivers:', error);
                this.showError(error.message || 'Unable to load drivers. Please try again or contact support.');
            }
            this.clearDriverSelect();
        } finally {
            // Always hide loading state, regardless of success or failure
            this.hideDriversLoading();
        }
    }

    clearDriverSelect() {
        if (this.driverSelect) {
            this.driverSelect.innerHTML = '<option value="">Select a driver...</option>';
            this.hideDriverDetails();
        }
    }

    /**
     * Populate driver dropdown with fetched driver data
     * 
     * This method creates option elements with embedded data attributes that are used
     * to display driver details when a driver is selected. The data structure matches
     * the UserDriverDetail model with nested user and licenses relationships.
     * 
     * @param {Array} drivers - Array of driver objects from API
     */
    populateDriverSelect(drivers) {
        if (!this.driverSelect) return;

        // Reset dropdown with placeholder option
        this.driverSelect.innerHTML = '<option value="">Select a driver...</option>';
        
        drivers.forEach(driver => {
            const option = document.createElement('option');
            option.value = driver.id;
            
            // Handle UserDriverDetail structure with user relationship
            // The API returns: { id, user: { name, email, ... }, licenses: [...], phone, ... }
            const user = driver.user || {};
            const fullName = this.formatDriverName(driver);
            option.textContent = fullName;
            
            // Get license info from licenses array (most recent active license is first)
            // Licenses are pre-sorted by expiration_date DESC in the API
            const license = driver.licenses && driver.licenses.length > 0 ? driver.licenses[0] : {};
            
            // Format license expiration date for display
            let licenseExpiration = license.expiration_date || '';
            if (licenseExpiration) {
                const expDate = new Date(licenseExpiration);
                licenseExpiration = expDate.toLocaleDateString();
            }
            
            // Set data attributes for driver details card display
            // These attributes are read by showDriverDetails() when a driver is selected
            option.setAttribute('data-full-name', fullName);
            option.setAttribute('data-name', fullName);
            option.setAttribute('data-email', user.email || '');
            option.setAttribute('data-phone', driver.phone || '');
            option.setAttribute('data-license', license.license_number || '');
            option.setAttribute('data-license-class', license.license_class || '');
            option.setAttribute('data-license-expiration', licenseExpiration);
            option.setAttribute('data-first-name', user.name || '');
            option.setAttribute('data-middle-name', user.middle_name || '');
            option.setAttribute('data-last-name', user.last_name || '');
            
            this.driverSelect.appendChild(option);
        });
    }

    selectCurrentDriver() {
        if (!this.currentDriverId || !this.driverSelect) return;

        const option = this.driverSelect.querySelector(`option[value="${this.currentDriverId}"]`);
        if (option) {
            this.driverSelect.value = this.currentDriverId;
            this.showDriverDetails();
        }
    }

    showDriverDetails() {
        const selectedOption = this.driverSelect?.options[this.driverSelect.selectedIndex];
        
        if (!selectedOption || !selectedOption.value) {
            this.hideDriverDetails();
            return;
        }

        try {
            const driverData = this.extractDriverData(selectedOption);
            this.updateDriverDisplay(driverData);
            this.showDriverCard();
        } catch (error) {
            console.error('Error displaying driver details:', error);
        }
    }

    extractDriverData(option) {
        return {
            id: option.value,
            name: option.getAttribute('data-full-name') || option.getAttribute('data-name') || option.textContent,
            email: option.getAttribute('data-email') || 'N/A',
            phone: option.getAttribute('data-phone') || 'N/A',
            licenseNumber: option.getAttribute('data-license') || 'N/A',
            licenseClass: option.getAttribute('data-license-class') || 'N/A',
            licenseExpiration: option.getAttribute('data-license-expiration') || 'N/A',
            firstName: option.getAttribute('data-first-name') || '',
            middleName: option.getAttribute('data-middle-name') || '',
            lastName: option.getAttribute('data-last-name') || ''
        };
    }

    updateDriverDisplay(driverData) {
        if (!this.driverName || !this.driverEmail || !this.driverPhone) {
            console.warn('Driver display elements not found');
            return;
        }

        const formattedName = this.formatDriverName(driverData);
        this.driverName.innerHTML = formattedName;
        this.driverEmail.textContent = driverData.email || 'N/A';
        this.driverPhone.textContent = driverData.phone || 'N/A';
        
        // Update license information if elements exist
        if (this.driverLicense) {
            this.driverLicense.textContent = driverData.licenseNumber || 'N/A';
        }
        if (this.driverLicenseClass) {
            this.driverLicenseClass.textContent = driverData.licenseClass || 'N/A';
        }
        if (this.driverLicenseExpiration) {
            this.driverLicenseExpiration.textContent = driverData.licenseExpiration || 'N/A';
        }
    }

    /**
     * Format driver name from various data structures
     * 
     * This method handles three different data structures:
     * 1. Direct full_name property (from some API responses)
     * 2. UserDriverDetail with nested user object (from API)
     * 3. Extracted data attributes with HTML formatting (for display)
     * 
     * @param {Object} data - Driver data object
     * @returns {string} Formatted driver name or 'N/A'
     */
    formatDriverName(data) {
        // Case 1: If data already has full_name property, use it directly
        if (data.full_name && data.full_name.trim()) {
            return data.full_name.trim();
        }
        
        // Case 2: If data has user object (from UserDriverDetail API response)
        // Structure: { user: { name: 'First', middle_name: 'Middle', last_name: 'Last' } }
        if (data.user) {
            const parts = [
                data.user.name,
                data.user.middle_name,
                data.user.last_name
            ].filter(part => part && part.trim());
            
            return parts.length > 0 ? parts.join(' ') : 'N/A';
        }
        
        // Case 3: If data has direct name properties (from extracted data attributes)
        // This case supports HTML formatting for visual emphasis in the UI
        const parts = [
            data.firstName,
            data.middleName ? `<span class="text-gray-700">${data.middleName}</span>` : '',
            data.lastName ? `<span class="font-semibold">${data.lastName}</span>` : ''
        ].filter(Boolean);
        
        return parts.length > 0 ? parts.join(' ') : (data.name || 'N/A');
    }

    showDriverCard() {
        if (this.driverDetailCard) {
            this.driverDetailCard.classList.remove('hidden');
        }
    }

    hideDriverDetails() {
        if (this.driverDetailCard) {
            this.driverDetailCard.classList.add('hidden');
        }
    }

    toggleOtherReasonField() {
        if (!this.otherReasonContainer) return;
        
        const isVisible = this.otherReasonCheckbox?.checked;
        this.otherReasonContainer.style.display = isVisible ? 'block' : 'none';
    }

    validateForm() {
        const carrierId = this.carrierSelect?.value;
        const driverId = this.driverSelect?.value;

        if (!carrierId) {
            this.showError('Please select a carrier');
            return false;
        }

        if (!driverId) {
            this.showError('Please select a driver');
            return false;
        }

        // Update hidden fields
        if (this.carrierIdHidden) this.carrierIdHidden.value = carrierId;
        if (this.userDriverDetailIdHidden) this.userDriverDetailIdHidden.value = driverId;

        // Validate administered by field for edit mode
        if (this.isEditMode) {
            const administeredBySelect = document.getElementById('administered_by_select');
            if (administeredBySelect?.value === 'other') {
                const otherValue = document.getElementById('administered_by_other')?.value?.trim();
                if (!otherValue) {
                    this.showError('Please specify who administered the test');
                    return false;
                }
                const administeredByHidden = document.getElementById('administered_by');
                if (administeredByHidden) administeredByHidden.value = otherValue;
            }
        }

        return true;
    }

    /**
     * Initialize Livewire event listeners for file uploads
     * 
     * This method sets up listeners for Livewire file upload events.
     * The file upload component emits events when files are uploaded or removed,
     * and we track these files to include them in the form submission.
     */
    initializeLivewire() {
        window.addEventListener('livewire:initialized', () => {
            console.log('Livewire initialized - registering file upload listeners');

            // Listen for file upload completion events
            // Livewire emits this event when a file is successfully uploaded to temp storage
            Livewire.on('fileUploaded', (eventData) => {
                const data = eventData[0];
                // Only handle events for our specific file upload component
                if (data.modelName === 'driver_testing_files') {
                    this.handleFileUploaded(data);
                }
            });

            // Listen for file removal events
            // Livewire emits this event when a user removes an uploaded file
            Livewire.on('fileRemoved', (eventData) => {
                const data = eventData[0];
                // Only handle events for our specific file upload component
                if (data.modelName === 'driver_testing_files') {
                    this.handleFileRemoved(data);
                }
            });
        });
    }

    /**
     * Handle file upload completion
     * 
     * When a file is uploaded via Livewire, we store its metadata in an array
     * and update a hidden input field with the JSON-encoded file list.
     * This allows the form submission to include all uploaded files.
     * 
     * @param {Object} data - File upload data from Livewire event
     */
    handleFileUploaded(data) {
        this.uploadedFiles.push({
            path: data.tempPath,           // Temporary storage path
            original_name: data.originalName,
            mime_type: data.mimeType,
            size: data.size
        });
        
        // Update hidden input with current file list
        this.updateFilesInput();
        this.showSuccess(`File "${data.originalName}" uploaded successfully`);
        console.log('File uploaded:', data.originalName);
    }

    /**
     * Handle file removal
     * 
     * When a user removes an uploaded file, we remove it from our tracking array.
     * For temporary files (identified by 'temp_' prefix), we remove the last added file.
     * 
     * @param {Object} data - File removal data from Livewire event
     */
    handleFileRemoved(data) {
        const fileId = data.fileId;
        if (fileId.startsWith('temp_')) {
            // Remove the last added file for temporary files
            // This is a simple LIFO approach since Livewire doesn't provide the exact index
            this.uploadedFiles.pop();
        }
        
        // Update hidden input with current file list
        this.updateFilesInput();
        this.showSuccess('File removed successfully');
        console.log('File removed:', fileId);
    }

    /**
     * Update hidden input field with current file list
     * 
     * This method serializes the uploaded files array to JSON and stores it
     * in a hidden input field, making it available during form submission.
     */
    updateFilesInput() {
        if (this.filesInput) {
            this.filesInput.value = JSON.stringify(this.uploadedFiles);
        }
    }

    showError(message, duration = 5000) {
        this.showNotification(message, 'error', duration);
    }

    showSuccess(message, duration = 3000) {
        this.showNotification(message, 'success', duration);
    }

    showWarning(message, duration = 4000) {
        this.showNotification(message, 'warning', duration);
    }

    /**
     * Display toast notification with icon and auto-dismiss
     * 
     * This method creates a toast notification that slides in from the right,
     * displays for a configurable duration, and automatically dismisses.
     * Notifications of the same type are replaced to avoid clutter.
     * 
     * Features:
     * - Type-specific colors and icons (success, error, info, warning)
     * - Auto-dismiss with configurable duration
     * - Manual dismiss button
     * - Smooth slide-in/out animations
     * - Prevents notification stacking
     * 
     * @param {string} message - The message to display
     * @param {string} type - Notification type: 'success', 'error', 'info', or 'warning'
     * @param {number} duration - Auto-dismiss duration in milliseconds (0 = no auto-dismiss)
     */
    showNotification(message, type = 'info', duration = 3000) {
        // Remove existing notifications of the same type to avoid clutter
        document.querySelectorAll(`.toast-notification[data-type="${type}"]`).forEach(n => {
            n.classList.add('translate-x-full');
            setTimeout(() => n.remove(), 300);
        });
        
        // Create notification element with initial off-screen position
        const notification = document.createElement('div');
        notification.className = 'toast-notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full max-w-md';
        notification.setAttribute('data-type', type);
        
        // Configuration for different notification types
        // Each type has specific colors and SVG icons for visual distinction
        const config = {
            success: {
                bgColor: 'bg-green-500',
                textColor: 'text-white',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`
            },
            error: {
                bgColor: 'bg-red-500',
                textColor: 'text-white',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`
            },
            info: {
                bgColor: 'bg-blue-500',
                textColor: 'text-white',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`
            },
            warning: {
                bgColor: 'bg-yellow-500',
                textColor: 'text-gray-900',
                icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>`
            }
        };
        
        const typeConfig = config[type] || config.info;
        notification.className += ` ${typeConfig.bgColor} ${typeConfig.textColor}`;
        
        // Build notification HTML with icon and close button
        notification.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    ${typeConfig.icon}
                </div>
                <div class="flex-1 pt-0.5">
                    <p class="font-medium text-sm leading-5">${message}</p>
                </div>
                <button type="button" class="flex-shrink-0 inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 hover:bg-black hover:bg-opacity-10 transition-colors" onclick="this.closest('.toast-notification').remove()">
                    <span class="sr-only">Dismiss</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto-dismiss with configurable duration
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, duration);
        }
    }

    showDriversLoading() {
        if (this.driverSelect) {
            this.driverSelect.disabled = true;
            this.driverSelect.innerHTML = '<option value="">Loading drivers...</option>';
            
            // Add loading spinner to carrier select
            this.addLoadingSpinner(this.carrierSelect);
        }
    }

    hideDriversLoading() {
        if (this.driverSelect) {
            this.driverSelect.disabled = false;
            this.removeLoadingSpinner(this.carrierSelect);
        }
    }

    showFormLoading() {
        const submitButton = this.form?.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            const originalText = submitButton.textContent;
            submitButton.setAttribute('data-original-text', originalText);
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;
        }
    }

    addLoadingSpinner(element) {
        if (!element || element.querySelector('.loading-spinner')) return;
        
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner absolute right-2 top-1/2 transform -translate-y-1/2';
        spinner.innerHTML = `
            <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        `;
        
        // Make parent relative if not already
        const parentStyle = window.getComputedStyle(element.parentElement);
        if (parentStyle.position === 'static') {
            element.parentElement.style.position = 'relative';
        }
        
        element.parentElement.appendChild(spinner);
    }

    removeLoadingSpinner(element) {
        if (!element) return;
        
        const spinner = element.parentElement?.querySelector('.loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
}

// Export for use in views
window.DriverTestingForm = DriverTestingForm;