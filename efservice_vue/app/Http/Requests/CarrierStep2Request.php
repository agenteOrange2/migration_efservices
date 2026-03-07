<?php

namespace App\Http\Requests;

use App\Rules\ValidEIN;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CarrierStep2Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user() && Auth::user()->hasRole('user_carrier');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'carrier_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-\.\'\"&,()\/]+$/', // More permissive for company names
                Rule::unique('carriers', 'name')
            ],
            'address' => [
                'required',
                'string',
                'min:5', // Reduced from 10 to 5
                'max:500',
                'regex:/^[a-zA-Z0-9\s\-\.\'\"#,()\/]+$/' // More permissive for addresses
            ],
            'state' => [
                'required',
                'string',
                'size:2',
                'in:' . $this->getValidStates()
            ],
            'zip_code' => [
                'required',
                'string',
                'regex:/^\d{5}(-\d{4})?$/' // ZIP format: 12345 or 12345-6789
            ],
            'ein_number' => [
                'required',
                'string',
                'regex:/^\d{2}-?\d{7}$/', // Accept with or without dash
                new ValidEIN(),
                Rule::unique('carriers', 'ein_number')
            ],
            'dot_number' => [
                'nullable',
                'string',
                'regex:/^\d{1,8}$/', // DOT numbers up to 8 digits
                Rule::unique('carriers', 'dot_number')->whereNotNull('dot_number'),
                function ($attribute, $value, $fail) {
                    $businessType = $this->input('business_type');
                    if (in_array($businessType, ['Corporation', 'LLC']) && !$value) {
                        $fail('DOT number is required for corporations and LLCs.');
                    }
                }
            ],
            'mc_number' => [
                'nullable',
                'string',
                'regex:/^\d{1,8}$/', // Only numbers, formatting handled in prepareForValidation
                Rule::unique('carriers', 'mc_number')->whereNotNull('mc_number')
            ],
            'state_dot' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\-]+$/' // Variable format by state
            ],
            'ifta_account' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\-]+$/' // Variable IFTA format
            ],
            'business_type' => [
                'required',
                'string',
                'in:LLC,Corporation,Partnership,Sole Proprietorship'
            ],
            'years_in_business' => [
                'required',
                'string',
                'in:0-1,1-3,3-5,5-10,10+'
            ],
            'fleet_size' => [
                'required',
                'string',
                'in:1-5,6-10,11-25,26-50,50+'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'carrier_name.required' => 'Please enter your company name.',
            'carrier_name.min' => 'Company name must be at least 2 characters.',
            'carrier_name.max' => 'Company name cannot exceed 255 characters.',
            'carrier_name.regex' => 'Company name contains invalid characters. Only letters, numbers, spaces, and common punctuation are allowed.',
            'carrier_name.unique' => 'This company name is already registered. Please use a different name or contact support if this is your company.',
            
            'address.required' => 'Please enter your company address.',
            'address.min' => 'Address must be at least 5 characters.',
            'address.max' => 'Address cannot exceed 500 characters.',
            'address.regex' => 'Address contains invalid characters. Please use only letters, numbers, spaces, and common punctuation.',
            
            'state.required' => 'Please select your state.',
            'state.in' => 'Please select a valid US state.',
            
            'zip_code.required' => 'Please enter your ZIP code.',
            'zip_code.regex' => 'Please enter a valid ZIP code. Examples: 12345 or 12345-6789',
            
            'ein_number.required' => 'Please enter your EIN number.',
            'ein_number.regex' => 'Please enter a valid EIN number. Format: 12-3456789 (9 digits with dash after first 2 digits)',
            'ein_number.unique' => 'This EIN number is already registered in our system. Please verify your EIN or contact support if you believe this is an error.',
            
            'dot_number.regex' => 'Please enter a valid DOT number (1-8 digits only). Example: 1234567',
            'dot_number.unique' => 'This DOT number is already registered. Please verify your DOT number.',
            
            'mc_number.regex' => 'Please enter a valid MC number (1-8 digits only). Example: 123456',
            'mc_number.unique' => 'This MC number is already registered. Please verify your MC number.',
            
            'state_dot.regex' => 'Please enter a valid State DOT number (letters, numbers, and dashes only).',
            'ifta_account.regex' => 'Please enter a valid IFTA account number (letters, numbers, and dashes only).',
            
            'business_type.required' => 'Please select your business type.',
            'business_type.in' => 'Please select a valid business type from the available options.',
            
            'years_in_business.required' => 'Please select how many years you have been in business.',
            'years_in_business.in' => 'Please select a valid range for years in business.',
            
            'fleet_size.required' => 'Please select your fleet size.',
            'fleet_size.in' => 'Please select a valid fleet size range.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'carrier_name' => 'company name',
            'address' => 'address',
            'state' => 'state',
            'zip_code' => 'ZIP code',
            'ein_number' => 'EIN number',
            'dot_number' => 'DOT number',
            'mc_number' => 'MC number',
            'state_dot' => 'State DOT number',
            'ifta_account' => 'IFTA account number',
            'business_type' => 'business type',
            'years_in_business' => 'years in business',
            'fleet_size' => 'fleet size'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'carrier_name' => trim($this->carrier_name),
            'address' => trim($this->address),
            'state' => strtoupper(trim($this->state ?? '')),
            'zip_code' => preg_replace('/[^\d-]/', '', $this->zip_code ?? ''),
            'ein_number' => $this->formatEIN($this->ein_number ?? ''),
            'dot_number' => $this->cleanNumericField($this->dot_number),
            'mc_number' => $this->cleanMCNumber($this->mc_number),
            'state_dot' => $this->cleanAlphanumericField($this->state_dot),
            'ifta_account' => $this->cleanAlphanumericField($this->ifta_account),
            'business_type' => $this->normalizeBusinessType($this->business_type),
        ]);
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Illuminate\Support\Facades\Log::info('Carrier step 2 validation failed', [
            'user_id' => Auth::check() ? Auth::id() : null,
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['ein_number']) // No loggear EIN por seguridad
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get processed data for carrier creation.
     */
    public function getProcessedData(): array
    {
        $validated = $this->validated();
        
        // Generar slug único para el carrier
        $validated['slug'] = $this->generateUniqueSlug($validated['carrier_name']);
        
        // Mapear campos al modelo Carrier
        return [
            'name' => $validated['carrier_name'],
            'address' => $validated['address'],
            'state' => $validated['state'],
            'zipcode' => $validated['zip_code'],
            'ein_number' => $validated['ein_number'],
            'dot_number' => $validated['dot_number'],
            'mc_number' => $validated['mc_number'],
            'state_dot' => $validated['state_dot'],
            'ifta_account' => $validated['ifta_account'],
            'business_type' => $validated['business_type'],
            'years_in_business' => $validated['years_in_business'],
            'fleet_size' => $validated['fleet_size'],
            'slug' => $validated['slug'],
            'status' => 0 // pending
        ];
    }

    /**
     * Get valid US states.
     */
    private function getValidStates(): string
    {
        $states = [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
            'DC' // District of Columbia
        ];
        
        return implode(',', $states);
    }

    /**
     * Format EIN number.
     */
    private function formatEIN(?string $ein): string
    {
        if (!$ein) return '';
        
        // Remove all non-digit characters
        $cleanEin = preg_replace('/[^\d]/', '', $ein);
        
        // If we have exactly 9 digits, format as XX-XXXXXXX
        if (strlen($cleanEin) === 9) {
            return substr($cleanEin, 0, 2) . '-' . substr($cleanEin, 2);
        }
        
        // If it's already in the correct format XX-XXXXXXX, return as is
        if (preg_match('/^\d{2}-\d{7}$/', $ein)) {
            return $ein;
        }
        
        // Return the original input for validation to handle
        return $ein;
    }

    /**
     * Clean numeric field.
     */
    private function cleanNumericField(?string $value): ?string
    {
        if (!$value) return null;
        
        return preg_replace('/[^\d]/', '', $value) ?: null;
    }

    /**
     * Clean MC number field.
     */
    private function normalizeBusinessType(?string $value): ?string
    {
        if (!$value) return null;

        $map = [
            'llc' => 'LLC',
            'corporation' => 'Corporation',
            'partnership' => 'Partnership',
            'sole_proprietorship' => 'Sole Proprietorship',
            'sole proprietorship' => 'Sole Proprietorship',
        ];

        return $map[strtolower(trim($value))] ?? $value;
    }

    private function cleanMCNumber(?string $value): ?string
    {
        if (!$value) return null;

        $value = strtoupper(trim($value));

        // Extract only digits regardless of MC- prefix
        $numbers = preg_replace('/[^\d]/', '', $value);

        return ($numbers && strlen($numbers) <= 8) ? $numbers : null;
    }

    /**
     * Clean alphanumeric field.
     */
    private function cleanAlphanumericField(?string $value): ?string
    {
        if (!$value) return null;
        
        return preg_replace('/[^a-zA-Z0-9\-]/', '', $value) ?: null;
    }



    /**
     * Generate unique slug for carrier.
     */
    private function generateUniqueSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        while (\App\Models\Carrier::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}