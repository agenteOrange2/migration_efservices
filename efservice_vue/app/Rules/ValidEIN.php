<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidEIN implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Clean the value - remove all non-digit characters
        $cleanEin = preg_replace('/[^\d]/', '', $value);
        
        // Must have exactly 9 digits
        if (strlen($cleanEin) !== 9) {
            return false;
        }
        
        // Validate prefix (first 2 digits)
        $prefix = (int) substr($cleanEin, 0, 2);
        
        // Invalid prefixes according to IRS
        $invalidPrefixes = [7, 8, 9, 17, 18, 19, 28, 29, 49, 69, 70, 78, 79, 89];
        
        return !in_array($prefix, $invalidPrefixes);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please enter a valid EIN number. Format: 12-3456789 (9 digits, first 2 digits must be a valid IRS prefix)';
    }
}