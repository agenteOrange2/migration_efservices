<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterCarrierRequest extends FormRequest
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
            // User information
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\'\.]+$/'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()
            ],
            'phone' => ['required', 'string', 'regex:/^\d{10}$|^\+1\d{10}$/'],

            // Carrier information
            'carrier_name' => ['required', 'string', 'max:255'],
            'dot_number' => ['required', 'string', 'max:50', 'unique:carriers,dot_number'],
            'mc_number' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'zip_code' => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],

            // Plan selection
            'plan_id' => ['required', 'exists:memberships,id'],

            // Terms
            'terms_accepted' => ['required', 'accepted'],
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
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.regex' => 'Please provide a valid 10-digit phone number.',
            'dot_number.unique' => 'This DOT number is already registered.',
            'state.regex' => 'State must be a valid 2-letter code (e.g., CA, NY).',
            'zip_code.regex' => 'Please provide a valid ZIP code (e.g., 12345 or 12345-6789).',
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
            'carrier_name' => 'company name',
            'dot_number' => 'DOT number',
            'mc_number' => 'MC number',
            'zip_code' => 'ZIP code',
            'plan_id' => 'membership plan',
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
            $this->merge(['phone' => $phone]);
        }

        // Normalize state to uppercase
        if ($this->has('state')) {
            $this->merge(['state' => strtoupper(trim($this->state))]);
        }

        // Trim string inputs
        $trimFields = ['name', 'carrier_name', 'dot_number', 'mc_number', 'address', 'city'];
        $mergeData = [];

        foreach ($trimFields as $field) {
            if ($this->has($field)) {
                $mergeData[$field] = trim($this->$field ?? '');
            }
        }

        // Lowercase email
        if ($this->has('email')) {
            $mergeData['email'] = strtolower(trim($this->email ?? ''));
        }

        $this->merge($mergeData);
    }
}
