<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\'\.]+$/'],
            'middle_name' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\'\.]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\'\.]+$/'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()
            ],
            'date_of_birth' => ['required', 'date', 'before:-18 years', 'after:1940-01-01'],
            'phone' => ['required', 'string', 'regex:/^\d{10}$|^\+1\d{10}$/'],
            'license_number' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/i'],
            'terms_accepted' => ['required', 'accepted'],
            'carrier_slug' => ['nullable', 'exists:carriers,slug']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name field can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'middle_name.regex' => 'The middle name field can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'last_name.regex' => 'The last name field can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Password must be at least 8 characters long.',
            'date_of_birth.before' => 'You must be at least 18 years old to register.',
            'date_of_birth.after' => 'Please provide a valid date of birth.',
            'phone.regex' => 'Please provide a valid 10-digit phone number.',
            'license_number.regex' => 'License number can only contain letters, numbers, and hyphens.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions to register.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date_of_birth' => 'date of birth',
            'license_number' => 'driver license number',
            'phone' => 'phone number',
            'terms_accepted' => 'terms and conditions',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove non-numeric characters from phone
        if ($this->has('phone')) {
            $phone = preg_replace('/[^0-9+]/', '', $this->phone);
            $this->merge([
                'phone' => $phone,
            ]);
        }

        // Normalize license number to uppercase
        if ($this->has('license_number')) {
            $this->merge([
                'license_number' => strtoupper(trim($this->license_number)),
            ]);
        }

        // Trim string inputs
        $this->merge([
            'name' => trim($this->name ?? ''),
            'middle_name' => trim($this->middle_name ?? ''),
            'last_name' => trim($this->last_name ?? ''),
            'email' => strtolower(trim($this->email ?? '')),
        ]);
    }
}
