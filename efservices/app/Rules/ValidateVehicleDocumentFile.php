<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class ValidateVehicleDocumentFile implements Rule
{
    private $message;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!$value instanceof UploadedFile) {
            $this->message = 'The file must be a valid upload.';
            return false;
        }

        // Check if file was uploaded successfully
        if (!$value->isValid()) {
            $this->message = 'The file upload failed. Please try again.';
            return false;
        }

        // Validate file size (10MB maximum)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($value->getSize() > $maxSize) {
            $this->message = 'The file size must not exceed 10MB.';
            return false;
        }

        // Validate MIME type
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif'
        ];

        if (!in_array($value->getMimeType(), $allowedMimeTypes)) {
            $this->message = 'The file must be a PDF, JPG, PNG, or GIF.';
            return false;
        }

        // Validate file extension
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower($value->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            $this->message = 'The file extension must be pdf, jpg, jpeg, png, or gif.';
            return false;
        }

        // Additional security check: verify the file content matches the extension
        if ($extension === 'pdf' && $value->getMimeType() !== 'application/pdf') {
            $this->message = 'The file content does not match the PDF format.';
            return false;
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && !str_starts_with($value->getMimeType(), 'image/')) {
            $this->message = 'The file content does not match the image format.';
            return false;
        }

        // Check for minimum file size (to avoid empty files)
        if ($value->getSize() < 100) { // 100 bytes minimum
            $this->message = 'The file appears to be empty or corrupted.';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message ?? 'The file is not valid.';
    }
}