/**
 * Carrier Trainings Notifications Module
 * Handles toast notifications and loading states for carrier training operations
 */

class CarrierTrainingsNotifications {
    constructor() {
        this.loadingToasts = new Map();
        this.init();
    }

    init() {
        this.setupAjaxDocumentDeletion();
        this.setupFormSubmissions();
        this.setupAsyncOperations();
    }

    /**
     * Show a toast notification
     */
    showToast(type, message, options = {}) {
        if (window.ToastNotifications) {
            return window.ToastNotifications[type](message, options);
        } else {
            console.error('ToastNotifications not available');
        }
    }

    /**
     * Show loading toast and store reference
     */
    showLoading(message, key = 'default') {
        const toast = this.showToast('loading', message);
        this.loadingToasts.set(key, toast);
        return toast;
    }

    /**
     * Hide loading toast
     */
    hideLoading(key = 'default') {
        const toast = this.loadingToasts.get(key);
        if (toast && toast.hideToast) {
            toast.hideToast();
            this.loadingToasts.delete(key);
        }
    }

    /**
     * Setup AJAX document deletion with notifications
     */
    setupAjaxDocumentDeletion() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-document-btn') || e.target.closest('.delete-document-btn')) {
                e.preventDefault();
                
                const button = e.target.matches('.delete-document-btn') ? e.target : e.target.closest('.delete-document-btn');
                const documentId = button.dataset.documentId;
                const documentName = button.dataset.documentName || 'document';
                
                if (!documentId) {
                    this.showToast('error', 'Document ID not found');
                    return;
                }

                // Show confirmation
                if (!confirm(`Are you sure you want to delete "${documentName}"? This action cannot be undone.`)) {
                    return;
                }

                this.deleteDocument(documentId, documentName, button);
            }
        });
    }

    /**
     * Delete document via AJAX
     */
    async deleteDocument(documentId, documentName, button) {
        const loadingKey = `delete-${documentId}`;
        
        try {
            // Show loading state
            this.showLoading(`Deleting ${documentName}...`, loadingKey);
            button.disabled = true;
            button.classList.add('opacity-50');

            const response = await fetch(`/carrier/trainings/documents/${documentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showToast('success', data.message || `${documentName} deleted successfully`);
                
                // Remove the document element from DOM
                const documentElement = button.closest('.document-item, .file-item, [data-document-id="' + documentId + '"]');
                if (documentElement) {
                    documentElement.remove();
                }
            } else {
                this.showToast('error', data.message || 'Failed to delete document');
            }

        } catch (error) {
            console.error('Error deleting document:', error);
            this.showToast('error', 'An error occurred while deleting the document');
        } finally {
            // Hide loading state
            this.hideLoading(loadingKey);
            button.disabled = false;
            button.classList.remove('opacity-50');
        }
    }

    /**
     * Setup form submissions with loading states
     */
    setupFormSubmissions() {
        // Training creation/update forms
        const trainingForms = document.querySelectorAll('form[data-training-form]');
        trainingForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitButton = form.querySelector('button[type="submit"]');
                const action = form.dataset.trainingForm || 'saving';
                
                if (submitButton) {
                    const originalText = submitButton.textContent;
                    submitButton.disabled = true;
                    submitButton.textContent = `${action.charAt(0).toUpperCase() + action.slice(1)}...`;
                    
                    this.showLoading(`${action.charAt(0).toUpperCase() + action.slice(1)} training...`);
                }
            });
        });

        // Assignment forms
        const assignmentForms = document.querySelectorAll('form[data-assignment-form]');
        assignmentForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitButton = form.querySelector('button[type="submit"]');
                
                if (submitButton) {
                    const originalText = submitButton.textContent;
                    submitButton.disabled = true;
                    submitButton.textContent = 'Assigning...';
                    
                    this.showLoading('Assigning training to drivers...');
                }
            });
        });
    }

    /**
     * Setup async operations (file uploads, etc.)
     */
    setupAsyncOperations() {
        // File upload progress (if using Livewire file uploads)
        document.addEventListener('livewire:upload-start', () => {
            this.showLoading('Uploading files...', 'file-upload');
        });

        document.addEventListener('livewire:upload-finish', () => {
            this.hideLoading('file-upload');
            this.showToast('success', 'Files uploaded successfully');
        });

        document.addEventListener('livewire:upload-error', () => {
            this.hideLoading('file-upload');
            this.showToast('error', 'File upload failed');
        });

        // Generic AJAX operations
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-async-action]') || e.target.closest('[data-async-action]')) {
                const element = e.target.matches('[data-async-action]') ? e.target : e.target.closest('[data-async-action]');
                const action = element.dataset.asyncAction;
                const message = element.dataset.loadingMessage || `Processing ${action}...`;
                
                this.showLoading(message, action);
                
                // Auto-hide after 10 seconds as fallback
                setTimeout(() => {
                    this.hideLoading(action);
                }, 10000);
            }
        });
    }

    /**
     * Handle validation errors
     */
    showValidationErrors(errors) {
        if (typeof errors === 'object') {
            const errorMessages = Object.values(errors).flat();
            errorMessages.forEach(message => {
                this.showToast('error', message);
            });
        } else if (typeof errors === 'string') {
            this.showToast('error', errors);
        }
    }

    /**
     * Handle success operations
     */
    showSuccess(message, options = {}) {
        this.showToast('success', message, options);
    }

    /**
     * Handle error operations
     */
    showError(message, options = {}) {
        this.showToast('error', message, options);
    }

    /**
     * Handle warning operations
     */
    showWarning(message, options = {}) {
        this.showToast('warning', message, options);
    }

    /**
     * Handle info operations
     */
    showInfo(message, options = {}) {
        this.showToast('info', message, options);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.carrierTrainingsNotifications = new CarrierTrainingsNotifications();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CarrierTrainingsNotifications;
}

/**
 * Mobile Enhancement JavaScript for Training Management
 * Provides better mobile user experience and touch interactions
 */

class MobileEnhancements {
    constructor() {
        this.init();
    }

    init() {
        // Mobile-specific enhancements
        this.initMobileEnhancements();
        
        // Touch-friendly interactions
        this.initTouchInteractions();
        
        // Mobile form improvements
        this.initMobileFormEnhancements();
        
        // Responsive table handling
        this.initResponsiveTableHandling();
    }

    /**
     * Initialize mobile-specific enhancements
     */
    initMobileEnhancements() {
        // Add mobile class to body for CSS targeting
        if (window.innerWidth <= 768) {
            document.body.classList.add('mobile-device');
        }
        
        // Handle orientation changes
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                // Recalculate heights and positions after orientation change
                this.adjustMobileLayout();
            }, 100);
        });
        
        // Handle window resize for responsive behavior
        window.addEventListener('resize', this.debounce(() => {
            if (window.innerWidth <= 768) {
                document.body.classList.add('mobile-device');
            } else {
                document.body.classList.remove('mobile-device');
            }
            this.adjustMobileLayout();
        }, 250));
    }

    /**
     * Initialize touch-friendly interactions
     */
    initTouchInteractions() {
        // Improve touch targets for small buttons
        const smallButtons = document.querySelectorAll('.btn-sm, .w-8.h-8');
        smallButtons.forEach(button => {
            if (window.innerWidth <= 768) {
                button.style.minWidth = '44px';
                button.style.minHeight = '44px';
            }
        });
        
        // Add touch feedback for interactive elements
        const interactiveElements = document.querySelectorAll('.btn, .form-control, .driver-item, .training-card');
        interactiveElements.forEach(element => {
            element.addEventListener('touchstart', function() {
                this.classList.add('touch-active');
            });
            
            element.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.classList.remove('touch-active');
                }, 150);
            });
        });
    }

    /**
     * Initialize mobile form enhancements
     */
    initMobileFormEnhancements() {
        // Prevent zoom on input focus for iOS
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.style.fontSize === '' || parseFloat(input.style.fontSize) < 16) {
                input.style.fontSize = '16px';
            }
        });
        
        // Improve file upload experience on mobile
        const fileUploaders = document.querySelectorAll('[data-file-uploader]');
        fileUploaders.forEach(uploader => {
            // Add mobile-specific styling
            uploader.classList.add('mobile-file-uploader');
            
            // Handle drag and drop on mobile (fallback to click)
            uploader.addEventListener('touchstart', function(e) {
                // Provide visual feedback for touch
                this.classList.add('touch-highlight');
            });
            
            uploader.addEventListener('touchend', function(e) {
                this.classList.remove('touch-highlight');
            });
        });
        
        // Improve select dropdowns on mobile
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            // Add mobile-friendly styling
            select.classList.add('mobile-select');
        });
    }

    /**
     * Initialize responsive table handling
     */
    initResponsiveTableHandling() {
        const tables = document.querySelectorAll('table');
        
        tables.forEach(table => {
            // Add horizontal scroll for tables on mobile
            if (!table.closest('.overflow-x-auto')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'overflow-x-auto';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
            
            // Add scroll indicators for mobile
            if (window.innerWidth <= 768) {
                this.addScrollIndicators(table);
            }
        });
    }

    /**
     * Add scroll indicators for horizontal scrolling
     */
    addScrollIndicators(table) {
        const wrapper = table.closest('.overflow-x-auto');
        if (!wrapper) return;
        
        // Create scroll indicators
        const leftIndicator = document.createElement('div');
        leftIndicator.className = 'scroll-indicator scroll-indicator-left';
        leftIndicator.innerHTML = '←';
        
        const rightIndicator = document.createElement('div');
        rightIndicator.className = 'scroll-indicator scroll-indicator-right';
        rightIndicator.innerHTML = '→';
        
        wrapper.appendChild(leftIndicator);
        wrapper.appendChild(rightIndicator);
        
        // Update indicators based on scroll position
        const updateScrollIndicators = () => {
            const scrollLeft = wrapper.scrollLeft;
            const scrollWidth = wrapper.scrollWidth;
            const clientWidth = wrapper.clientWidth;
            
            leftIndicator.style.opacity = scrollLeft > 0 ? '1' : '0';
            rightIndicator.style.opacity = scrollLeft < scrollWidth - clientWidth ? '1' : '0';
        };
        
        wrapper.addEventListener('scroll', updateScrollIndicators);
        updateScrollIndicators(); // Initial check
    }

    /**
     * Adjust mobile layout after orientation change or resize
     */
    adjustMobileLayout() {
        // Recalculate modal positions
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.classList.contains('show')) {
                // Recenter modal
                modal.style.paddingTop = '1rem';
                modal.style.paddingBottom = '1rem';
            }
        });
        
        // Adjust dropdown positions
        const dropdowns = document.querySelectorAll('.dropdown-menu.show');
        dropdowns.forEach(dropdown => {
            // Reset position and recalculate
            dropdown.style.transform = '';
            dropdown.style.top = '';
            dropdown.style.left = '';
        });
        
        // Recalculate fixed elements
        const fixedElements = document.querySelectorAll('.fixed, .sticky');
        fixedElements.forEach(element => {
            // Force reflow
            element.style.display = 'none';
            element.offsetHeight; // Trigger reflow
            element.style.display = '';
        });
    }

    /**
     * Debounce function to limit function calls
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Mobile-specific utilities
     */
    static get utils() {
        return {
            /**
             * Check if device is mobile
             */
            isMobile: function() {
                return window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            },
            
            /**
             * Check if device supports touch
             */
            isTouch: function() {
                return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            },
            
            /**
             * Get safe area insets for devices with notches
             */
            getSafeAreaInsets: function() {
                const style = getComputedStyle(document.documentElement);
                return {
                    top: parseInt(style.getPropertyValue('--safe-area-inset-top')) || 0,
                    right: parseInt(style.getPropertyValue('--safe-area-inset-right')) || 0,
                    bottom: parseInt(style.getPropertyValue('--safe-area-inset-bottom')) || 0,
                    left: parseInt(style.getPropertyValue('--safe-area-inset-left')) || 0
                };
            },
            
            /**
             * Smooth scroll to element (mobile-optimized)
             */
            scrollToElement: function(element, offset = 0) {
                if (typeof element === 'string') {
                    element = document.querySelector(element);
                }
                
                if (element) {
                    const elementTop = element.getBoundingClientRect().top + window.pageYOffset;
                    const offsetPosition = elementTop - offset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            },
            
            /**
             * Show mobile-friendly loading state
             */
            showMobileLoading: function(element, message = 'Loading...') {
                const loader = document.createElement('div');
                loader.className = 'mobile-loading-overlay';
                loader.innerHTML = `
                    <div class="mobile-loading-content">
                        <div class="mobile-loading-spinner"></div>
                        <div class="mobile-loading-text">${message}</div>
                    </div>
                `;
                
                element.style.position = 'relative';
                element.appendChild(loader);
                
                return loader;
            },
            
            /**
             * Hide mobile loading state
             */
            hideMobileLoading: function(element) {
                const loader = element.querySelector('.mobile-loading-overlay');
                if (loader) {
                    loader.remove();
                }
            }
        };
    }
}

// Initialize mobile enhancements when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.mobileEnhancements = new MobileEnhancements();
    window.MobileUtils = MobileEnhancements.utils;
});

// Add CSS for mobile enhancements
const mobileStyles = `
    .touch-active {
        opacity: 0.7;
        transform: scale(0.98);
        transition: all 0.1s ease;
    }
    
    .mobile-file-uploader {
        min-height: 120px;
        border: 2px dashed #cbd5e0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .mobile-file-uploader.touch-highlight {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }
    
    .scroll-indicator {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        padding: 8px;
        border-radius: 4px;
        font-size: 14px;
        z-index: 10;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    
    .scroll-indicator-left {
        left: 8px;
    }
    
    .scroll-indicator-right {
        right: 8px;
    }
    
    .mobile-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .mobile-loading-content {
        text-align: center;
        padding: 20px;
    }
    
    .mobile-loading-spinner {
        width: 32px;
        height: 32px;
        border: 3px solid #f3f4f6;
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 12px;
    }
    
    .mobile-loading-text {
        color: #6b7280;
        font-size: 14px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 640px) {
        .mobile-device .btn {
            min-height: 44px;
            padding: 12px 16px;
        }
        
        .mobile-device .form-control {
            min-height: 44px;
            padding: 12px;
            font-size: 16px;
        }
        
        .mobile-device .form-checkbox {
            width: 20px;
            height: 20px;
        }
    }
`;

// Inject mobile styles
const styleSheet = document.createElement('style');
styleSheet.textContent = mobileStyles;
document.head.appendChild(styleSheet);