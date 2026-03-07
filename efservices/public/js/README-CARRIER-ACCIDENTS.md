# Carrier Accidents Management - JavaScript Documentation

## Overview

This directory contains JavaScript files that provide interactivity for the Carrier Accidents Management feature. The code is organized into two main files for better maintainability and reusability.

## Files

### 1. carrier-accidents.js

**Purpose**: Handles form interactions for creating and editing accident records.

**Features**:
- **Conditional Fields**: Shows/hides injury and fatality count fields based on checkbox state
- **File Upload**: Drag-and-drop file upload with validation
- **File Preview**: Displays selected files before upload with ability to remove
- **Form Validation**: Client-side validation for dates and required fields

**Functions**:
- `initConditionalFields()` - Initializes injury/fatality checkbox handlers
- `initFileUpload()` - Sets up drag-and-drop file upload functionality
- `initFormValidation()` - Validates form before submission

**Usage**:
```html
<script src="{{ asset('js/carrier-accidents.js') }}"></script>
```

**Used in**:
- `create.blade.php` - Creating new accident records
- `edit.blade.php` - Editing existing accident records

---

### 2. carrier-accidents-documents.js

**Purpose**: Handles document-related operations including preview, deletion, and AJAX interactions.

**Features**:
- **Document Preview**: Opens documents (images/PDFs) in a modal
- **AJAX Deletion**: Deletes documents without page reload
- **File Upload Handling**: Validates and processes file uploads
- **Confirmation Dialogs**: Prompts user before destructive actions

**Global Functions**:

#### `previewDocument(documentId, baseUrl)`
Opens a document preview in a modal.
- **Parameters**:
  - `documentId` (string): Document ID with prefix (e.g., "media_123" or "doc_456")
  - `baseUrl` (string, optional): Base URL for preview endpoint
- **Usage**:
  ```javascript
  previewDocument('media_123');
  ```

#### `deleteMediaDocument(mediaId, csrfToken)`
Deletes a Media Library document via AJAX.
- **Parameters**:
  - `mediaId` (number): Media record ID
  - `csrfToken` (string): CSRF token for request
- **Usage**:
  ```javascript
  deleteMediaDocument(123, '{{ csrf_token() }}');
  ```

#### `deleteOldDocument(documentId, csrfToken)`
Deletes a legacy system document via AJAX.
- **Parameters**:
  - `documentId` (number): Document record ID
  - `csrfToken` (string): CSRF token for request
- **Usage**:
  ```javascript
  deleteOldDocument(456, '{{ csrf_token() }}');
  ```

#### `deleteDocument(documentId, documentType, csrfToken)`
Auto-detects document type and calls appropriate delete function.
- **Parameters**:
  - `documentId` (string): Document ID with prefix
  - `documentType` (string): Either "media" or "old"
  - `csrfToken` (string): CSRF token for request
- **Usage**:
  ```javascript
  deleteDocument('media_123', 'media', '{{ csrf_token() }}');
  ```

#### `handleFileUpload(event)`
Validates and processes file uploads for show_documents page.
- **Parameters**:
  - `event` (Event): File input change event
- **Usage**:
  ```html
  <input type="file" onchange="handleFileUpload(event)" />
  ```

#### `confirmDeleteAccident(accidentId)`
Shows confirmation modal before deleting an accident record.
- **Parameters**:
  - `accidentId` (number): Accident record ID
- **Usage**:
  ```javascript
  confirmDeleteAccident(789);
  ```

**Used in**:
- `index.blade.php` - Accident list with delete confirmation
- `edit.blade.php` - Editing with document management
- `documents.blade.php` - All documents view
- `show_documents.blade.php` - Single accident documents view

---

## Implementation Details

### Conditional Fields Logic

The injuries and fatalities checkboxes control the visibility of their respective count input fields:

1. When checkbox is **checked**:
   - Show the count input field
   - Make it required
   - Set default value to 1 if empty

2. When checkbox is **unchecked**:
   - Hide the count input field
   - Remove required attribute
   - Reset value to 0

### File Upload Validation

Files are validated on the client-side before upload:

**Size Limit**: 10MB per file
**Allowed Types**:
- Images: JPG, JPEG, PNG
- Documents: PDF, DOC, DOCX

**Validation Process**:
1. Check file size
2. Check file type (MIME type)
3. Check for duplicates (same name and size)
4. Display error if validation fails
5. Add to selected files list if valid

### Drag and Drop

The file upload area supports drag-and-drop:

1. **Dragover**: Highlights the drop zone
2. **Dragleave**: Removes highlight
3. **Drop**: Processes dropped files

Visual feedback is provided through CSS classes:
- `border-primary` - Changes border color
- `bg-primary/5` - Adds light background

### AJAX Document Deletion

Document deletion uses the Fetch API for AJAX requests:

**Request**:
```javascript
fetch(deleteUrl, {
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
})
```

**Response Handling**:
- Success: Show notification and reload page
- Error: Display error message
- Network Error: Show generic error message

### Document Preview

The preview modal supports different file types:

**Images**: Displayed directly in an `<img>` tag
**PDFs**: Embedded in an `<iframe>`
**Other**: Shows download link

**Loading States**:
1. Initial: Shows loading spinner
2. Success: Displays content
3. Error: Shows error message

---

## Browser Compatibility

The code uses modern JavaScript features:

- **ES6 Arrow Functions**: Supported in all modern browsers
- **Fetch API**: Requires polyfill for IE11
- **DataTransfer API**: For file upload handling
- **Template Literals**: For dynamic HTML generation

**Minimum Browser Versions**:
- Chrome 42+
- Firefox 39+
- Safari 10+
- Edge 14+

---

## Security Considerations

### XSS Prevention

All user-generated content is escaped before insertion:

```javascript
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
```

### CSRF Protection

All AJAX requests include CSRF token:

```javascript
headers: {
    'X-CSRF-TOKEN': csrfToken
}
```

### File Validation

Client-side validation prevents:
- Oversized files (>10MB)
- Unauthorized file types
- Duplicate uploads

**Note**: Server-side validation is still required as client-side validation can be bypassed.

---

## Error Handling

### User-Friendly Messages

Errors are displayed using browser alerts (can be enhanced with toast notifications):

```javascript
function showNotification(message, type = 'info') {
    alert(message);
}
```

### Console Logging

Errors are logged to console for debugging:

```javascript
console.error('Preview error:', error);
```

### Graceful Degradation

If JavaScript fails:
- Forms still submit normally
- Delete operations use standard form submission
- Preview falls back to download

---

## Future Enhancements

### Potential Improvements

1. **Toast Notifications**: Replace alerts with non-blocking toast messages
2. **Progress Indicators**: Show upload progress for large files
3. **Image Thumbnails**: Generate thumbnails for image previews
4. **Batch Operations**: Select multiple documents for deletion
5. **Keyboard Shortcuts**: Add keyboard navigation for power users
6. **Offline Support**: Cache documents for offline viewing
7. **Real-time Validation**: Validate fields as user types
8. **Accessibility**: Add ARIA labels and keyboard navigation

### Code Organization

Consider splitting into modules:
```
js/
├── carrier-accidents/
│   ├── forms.js          # Form handling
│   ├── uploads.js        # File upload logic
│   ├── documents.js      # Document operations
│   ├── validation.js     # Validation rules
│   └── utils.js          # Utility functions
```

---

## Testing

### Manual Testing Checklist

**Form Interactions**:
- [ ] Injuries checkbox shows/hides count field
- [ ] Fatalities checkbox shows/hides count field
- [ ] Date validation prevents future dates
- [ ] Required fields are validated

**File Upload**:
- [ ] Drag and drop works
- [ ] Click to browse works
- [ ] File size validation (>10MB rejected)
- [ ] File type validation (only allowed types)
- [ ] Duplicate detection works
- [ ] Files can be removed before upload

**Document Operations**:
- [ ] Preview opens for images
- [ ] Preview opens for PDFs
- [ ] Preview shows error for unsupported types
- [ ] Delete confirmation appears
- [ ] Delete removes document
- [ ] Page reloads after delete

**Error Handling**:
- [ ] Network errors show message
- [ ] Server errors show message
- [ ] Validation errors are clear

### Automated Testing

Consider adding:
- Unit tests with Jest
- Integration tests with Cypress
- E2E tests for critical flows

---

## Troubleshooting

### Common Issues

**Issue**: Files not uploading
- **Check**: File size under 10MB
- **Check**: File type is allowed
- **Check**: Browser console for errors

**Issue**: Preview not working
- **Check**: Document exists and is accessible
- **Check**: CORS settings if using external storage
- **Check**: Browser console for fetch errors

**Issue**: Delete not working
- **Check**: CSRF token is valid
- **Check**: User has permission
- **Check**: Network tab for request/response

**Issue**: Conditional fields not showing
- **Check**: JavaScript file is loaded
- **Check**: Element IDs match
- **Check**: No JavaScript errors in console

---

## Support

For issues or questions:
1. Check browser console for errors
2. Verify JavaScript files are loaded
3. Test in different browsers
4. Review server logs for AJAX errors

---

## Changelog

### Version 1.0.0 (Current)
- Initial implementation
- Conditional field logic
- File upload with drag-and-drop
- Document preview and deletion
- Form validation
- AJAX operations

---

## License

This code is part of the Carrier Accidents Management system and follows the same license as the main application.
