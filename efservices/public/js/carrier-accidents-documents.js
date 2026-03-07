/**
 * Carrier Accidents Documents - JavaScript Interactivity
 * Handles document preview, deletion, and AJAX operations
 */

(function() {
    'use strict';

    /**
     * Preview document in modal
     * @param {string} documentId - Document ID with prefix (media_X or doc_X)
     * @param {string} baseUrl - Base URL for the preview endpoint
     */
    window.previewDocument = function(documentId, baseUrl) {
        if (!baseUrl) {
            baseUrl = window.location.origin + '/carrier/carrier-driver-accidents/documents';
        }
        
        const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#previewModal'));
        const previewContent = document.getElementById('preview-content');
        
        if (!modal || !previewContent) {
            console.error('Preview modal or content element not found');
            return;
        }
        
        // Show loading state
        previewContent.innerHTML = `
            <div class="flex items-center justify-center h-96">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-500">Loading preview...</p>
                </div>
            </div>
        `;
        
        modal.show();
        
        // Load preview
        const previewUrl = `${baseUrl}/${documentId}/preview`;
        
        fetch(previewUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.blob();
            })
            .then(blob => {
                const url = URL.createObjectURL(blob);
                const contentType = blob.type;
                
                if (contentType.startsWith('image/')) {
                    previewContent.innerHTML = `
                        <div class="p-4">
                            <img src="${url}" alt="Document Preview" class="w-full h-auto max-h-[600px] object-contain mx-auto">
                        </div>
                    `;
                } else if (contentType === 'application/pdf') {
                    previewContent.innerHTML = `
                        <iframe src="${url}" class="w-full" style="height: 600px;" frameborder="0"></iframe>
                    `;
                } else {
                    previewContent.innerHTML = `
                        <div class="flex items-center justify-center h-96">
                            <div class="text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 mb-3">Preview not available for this file type</p>
                                <a href="${url}" download class="inline-block px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 transition-colors">
                                    Download File
                                </a>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Preview error:', error);
                previewContent.innerHTML = `
                    <div class="flex items-center justify-center h-96">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-red-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-red-500 mb-2">Failed to load preview</p>
                            <p class="text-sm text-gray-500">${error.message}</p>
                        </div>
                    </div>
                `;
            });
    };

    /**
     * Delete media library document via AJAX
     * @param {number} mediaId - Media ID
     * @param {string} csrfToken - CSRF token for the request
     */
    window.deleteMediaDocument = function(mediaId, csrfToken) {
        if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
            return;
        }
        
        const deleteUrl = `${window.location.origin}/carrier/carrier-driver-accidents/ajax-destroy-media/${mediaId}`;
        
        // Show loading indicator
        const button = event.target.closest('button');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<svg class="w-3 h-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        }
        
        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('Document deleted successfully', 'success');
                
                // Reload page after short delay
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                throw new Error(data.error || 'Failed to delete document');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showNotification(error.message || 'An error occurred while deleting the document', 'error');
            
            // Re-enable button
            if (button) {
                button.disabled = false;
                button.innerHTML = '<svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>';
            }
        });
    };

    /**
     * Delete old system document via AJAX
     * @param {number} documentId - Document ID
     * @param {string} csrfToken - CSRF token for the request
     */
    window.deleteOldDocument = function(documentId, csrfToken) {
        if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
            return;
        }
        
        const deleteUrl = `${window.location.origin}/carrier/carrier-driver-accidents/documents/${documentId}`;
        
        // Show loading indicator
        const button = event.target.closest('button');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<svg class="w-3 h-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        }
        
        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('Document deleted successfully', 'success');
                
                // Reload page after short delay
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                throw new Error(data.error || 'Failed to delete document');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showNotification(error.message || 'An error occurred while deleting the document', 'error');
            
            // Re-enable button
            if (button) {
                button.disabled = false;
                button.innerHTML = '<svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>';
            }
        });
    };

    /**
     * Delete document (auto-detect type)
     * @param {string} documentId - Document ID with prefix (media_X or doc_X)
     * @param {string} documentType - Document type ('media' or 'old')
     * @param {string} csrfToken - CSRF token for the request
     */
    window.deleteDocument = function(documentId, documentType, csrfToken) {
        if (documentType === 'media') {
            const mediaId = documentId.replace('media_', '');
            deleteMediaDocument(mediaId, csrfToken);
        } else {
            const docId = documentId.replace('doc_', '');
            deleteOldDocument(docId, csrfToken);
        }
    };

    /**
     * Handle file upload for show_documents page
     * @param {Event} event - File input change event
     */
    window.handleFileUpload = function(event) {
        const files = event.target.files;
        if (files.length === 0) return;
        
        // Validate files
        let valid = true;
        const errors = [];
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Check file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                errors.push(`File "${file.name}" is too large. Maximum size is 10MB.`);
                valid = false;
                continue;
            }
            
            // Check file type
            const allowedTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/jpg',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            if (!allowedTypes.includes(file.type)) {
                errors.push(`File "${file.name}" has an unsupported format. Allowed: PDF, JPG, PNG, DOC, DOCX.`);
                valid = false;
            }
        }
        
        if (!valid) {
            alert(errors.join('\n'));
            event.target.value = '';
            return;
        }
        
        // Confirm upload
        const fileCount = files.length;
        const fileWord = fileCount === 1 ? 'file' : 'files';
        if (confirm(`Upload ${fileCount} ${fileWord}?`)) {
            document.getElementById('uploadForm').submit();
        } else {
            event.target.value = '';
        }
    };

    /**
     * Show notification message
     * @param {string} message - Message to display
     * @param {string} type - Type of notification ('success', 'error', 'warning', 'info')
     */
    function showNotification(message, type = 'info') {
        // Simple alert for now - can be enhanced with toast notifications
        alert(message);
    }

    /**
     * Confirm delete accident with modal
     * @param {number} accidentId - Accident ID
     */
    window.confirmDeleteAccident = function(accidentId) {
        const form = document.getElementById('deleteAccidentForm');
        if (!form) {
            console.error('Delete form not found');
            return;
        }
        
        const baseUrl = window.location.origin + '/carrier/carrier-driver-accidents';
        form.action = `${baseUrl}/${accidentId}`;
        
        const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteAccidentModal'));
        if (modal) {
            modal.show();
        } else {
            // Fallback to confirm dialog
            if (confirm('Are you sure you want to delete this accident record? This will permanently delete the accident and all associated documents. This action cannot be undone.')) {
                form.submit();
            }
        }
    };

})();
