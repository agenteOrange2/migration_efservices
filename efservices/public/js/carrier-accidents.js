/**
 * Carrier Accidents Management - JavaScript Interactivity
 * Handles form interactions, file uploads, document previews, and AJAX operations
 */

(function() {
    'use strict';

    /**
     * Initialize conditional fields for injuries and fatalities
     */
    function initConditionalFields() {
        // Handle injuries checkbox
        const injuriesCheckbox = document.getElementById('had_injuries');
        const injuriesCountSection = document.getElementById('injuries_count_section');
        const injuriesCountInput = document.getElementById('number_of_injuries');
        
        if (injuriesCheckbox && injuriesCountSection && injuriesCountInput) {
            function toggleInjuriesCount() {
                if (injuriesCheckbox.checked) {
                    injuriesCountSection.classList.remove('hidden');
                    injuriesCountInput.required = true;
                    if (injuriesCountInput.value === '0' || injuriesCountInput.value === '') {
                        injuriesCountInput.value = '1';
                    }
                } else {
                    injuriesCountSection.classList.add('hidden');
                    injuriesCountInput.required = false;
                    injuriesCountInput.value = '0';
                }
            }
            
            // Initialize state
            toggleInjuriesCount();
            
            // Listen for changes
            injuriesCheckbox.addEventListener('change', toggleInjuriesCount);
        }
        
        // Handle fatalities checkbox
        const fatalitiesCheckbox = document.getElementById('had_fatalities');
        const fatalitiesCountSection = document.getElementById('fatalities_count_section');
        const fatalitiesCountInput = document.getElementById('number_of_fatalities');
        
        if (fatalitiesCheckbox && fatalitiesCountSection && fatalitiesCountInput) {
            function toggleFatalitiesCount() {
                if (fatalitiesCheckbox.checked) {
                    fatalitiesCountSection.classList.remove('hidden');
                    fatalitiesCountInput.required = true;
                    if (fatalitiesCountInput.value === '0' || fatalitiesCountInput.value === '') {
                        fatalitiesCountInput.value = '1';
                    }
                } else {
                    fatalitiesCountSection.classList.add('hidden');
                    fatalitiesCountInput.required = false;
                    fatalitiesCountInput.value = '0';
                }
            }
            
            // Initialize state
            toggleFatalitiesCount();
            
            // Listen for changes
            fatalitiesCheckbox.addEventListener('change', toggleFatalitiesCount);
        }
    }

    /**
     * Initialize file upload functionality with drag-and-drop
     */
    function initFileUpload() {
        const accidentFilesInput = document.getElementById('accident_files');
        const fileList = document.getElementById('file-list');
        const uploadArea = document.getElementById('file-upload-area');
        
        if (!accidentFilesInput || !fileList || !uploadArea) return;
        
        let selectedFiles = [];
        
        // Click to upload
        uploadArea.addEventListener('click', function(e) {
            if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'INPUT') {
                accidentFilesInput.click();
            }
        });
        
        // Drag and drop handlers
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add('border-primary', 'bg-primary/5');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('border-primary', 'bg-primary/5');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('border-primary', 'bg-primary/5');
            
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });
        
        // File input change handler
        accidentFilesInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });
        
        /**
         * Handle file selection and validation
         */
        function handleFiles(files) {
            files.forEach(file => {
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    showAlert(`File "${file.name}" is too large. Maximum size is 10MB.`, 'error');
                    return;
                }
                
                // Validate file type
                const allowedTypes = [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/jpg',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                
                if (!allowedTypes.includes(file.type)) {
                    showAlert(`File "${file.name}" has an unsupported format. Allowed: PDF, JPG, PNG, DOC, DOCX.`, 'error');
                    return;
                }
                
                // Check for duplicates
                const isDuplicate = selectedFiles.some(f => f.name === file.name && f.size === file.size);
                if (isDuplicate) {
                    showAlert(`File "${file.name}" is already selected.`, 'warning');
                    return;
                }
                
                selectedFiles.push(file);
                displayFile(file);
            });
        }
        
        /**
         * Display file in the file list
         */
        function displayFile(file) {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors';
            fileItem.dataset.fileName = file.name;
            
            const fileInfo = document.createElement('div');
            fileInfo.className = 'flex items-center gap-3 flex-1 min-w-0';
            
            // File icon
            const icon = document.createElement('div');
            icon.className = 'flex-shrink-0';
            const iconSvg = getFileIcon(file.type);
            icon.innerHTML = iconSvg;
            
            // File details
            const fileDetails = document.createElement('div');
            fileDetails.className = 'flex-1 min-w-0';
            fileDetails.innerHTML = `
                <p class="text-sm font-medium text-gray-900 truncate" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</p>
                <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
            `;
            
            fileInfo.appendChild(icon);
            fileInfo.appendChild(fileDetails);
            
            // Remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'flex-shrink-0 text-red-500 hover:text-red-700 transition-colors p-1 rounded hover:bg-red-50';
            removeBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            `;
            removeBtn.onclick = function() {
                selectedFiles = selectedFiles.filter(f => f !== file);
                fileItem.remove();
                
                // Show message if no files left
                if (selectedFiles.length === 0) {
                    fileList.innerHTML = '';
                }
            };
            
            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            fileList.appendChild(fileItem);
        }
        
        /**
         * Get appropriate icon for file type
         */
        function getFileIcon(mimeType) {
            if (mimeType.startsWith('image/')) {
                return '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>';
            } else if (mimeType === 'application/pdf') {
                return '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>';
            } else {
                return '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';
            }
        }
        
        /**
         * Format file size for display
         */
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        /**
         * Update file input with selected files before form submission
         */
        const form = accidentFilesInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (selectedFiles.length > 0) {
                    const dataTransfer = new DataTransfer();
                    selectedFiles.forEach(file => {
                        dataTransfer.items.add(file);
                    });
                    accidentFilesInput.files = dataTransfer.files;
                }
            });
        }
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const form = document.getElementById('accidentForm');
        if (!form) return;
        
        form.addEventListener('submit', function(event) {
            // Validate accident date is not in the future
            const accidentDateEl = form.querySelector('input[name="accident_date"]');
            if (accidentDateEl && accidentDateEl.value) {
                const accidentDate = new Date(accidentDateEl.value);
                const today = new Date();
                today.setHours(23, 59, 59, 999);
                
                if (accidentDate > today) {
                    event.preventDefault();
                    showAlert('Accident date cannot be in the future.', 'error');
                    accidentDateEl.focus();
                    return false;
                }
            }
            
            // Validate injuries count if checkbox is checked
            const injuriesCheckbox = document.getElementById('had_injuries');
            const injuriesCountInput = document.getElementById('number_of_injuries');
            if (injuriesCheckbox && injuriesCheckbox.checked && injuriesCountInput) {
                const count = parseInt(injuriesCountInput.value);
                if (isNaN(count) || count < 1) {
                    event.preventDefault();
                    showAlert('Please enter a valid number of injuries (at least 1).', 'error');
                    injuriesCountInput.focus();
                    return false;
                }
            }
            
            // Validate fatalities count if checkbox is checked
            const fatalitiesCheckbox = document.getElementById('had_fatalities');
            const fatalitiesCountInput = document.getElementById('number_of_fatalities');
            if (fatalitiesCheckbox && fatalitiesCheckbox.checked && fatalitiesCountInput) {
                const count = parseInt(fatalitiesCountInput.value);
                if (isNaN(count) || count < 1) {
                    event.preventDefault();
                    showAlert('Please enter a valid number of fatalities (at least 1).', 'error');
                    fatalitiesCountInput.focus();
                    return false;
                }
            }
        });
    }

    /**
     * Show alert message
     */
    function showAlert(message, type = 'info') {
        // Use browser alert for now - can be enhanced with custom modal
        alert(message);
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Initialize all functionality when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initConditionalFields();
        initFileUpload();
        initFormValidation();
    });

})();
