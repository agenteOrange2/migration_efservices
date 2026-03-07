<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->carrierDetails;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vehicle = $this->route('vehicle');
        
        return [
            // Basic vehicle information
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'company_unit_number' => 'nullable|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            
            // VIN validation - exactly 17 characters, unique except for current vehicle
            'vin' => [
                'required',
                'string',
                'size:17',
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/', // Valid VIN format (excludes I, O, Q)
                Rule::unique('vehicles', 'vin')->ignore($vehicle->id)
            ],
            
            // Registration information
            'registration_state' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255',
            'registration_expiration_date' => 'required|date',
            'permanent_tag' => 'boolean',
            
            // Vehicle specifications
            'gvwr' => 'nullable|string|max:255',
            'tire_size' => 'nullable|string|max:255',
            'fuel_type' => 'required|string|max:255',
            'irp_apportioned_plate' => 'boolean',
            'location' => 'nullable|string|max:255',
            
            // Driver assignment
            'user_driver_detail_id' => [
                'nullable',
                'exists:user_driver_details,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $carrier = Auth::user()->carrierDetails->carrier;
                        $driver = \App\Models\UserDriverDetail::find($value);
                        // Cast both to int to avoid type mismatch
                        if (!$driver || (int) $driver->carrier_id !== (int) $carrier->id) {
                            $fail('The selected driver does not belong to your carrier.');
                        }
                    }
                }
            ],
            
            // Inspection dates
            'annual_inspection_expiration_date' => 'nullable|date',
            
            // Status fields - conditional validation for status changes
            'out_of_service' => 'boolean',
            'out_of_service_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    // If changing to out of service, require date
                    if ($this->boolean('out_of_service') && !$value) {
                        $fail('The out of service date is required when marking a vehicle as out of service.');
                    }
                    // If removing out of service status, clear the date
                    if (!$this->boolean('out_of_service') && $value) {
                        $this->merge(['out_of_service_date' => null]);
                    }
                }
            ],
            'suspended' => 'boolean',
            'suspended_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    // If changing to suspended, require date
                    if ($this->boolean('suspended') && !$value) {
                        $fail('The suspended date is required when marking a vehicle as suspended.');
                    }
                    // If removing suspended status, clear the date
                    if (!$this->boolean('suspended') && $value) {
                        $this->merge(['suspended_date' => null]);
                    }
                }
            ],
            
            // Additional information
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'vin.size' => 'The VIN must be exactly 17 characters long.',
            'vin.regex' => 'The VIN format is invalid. VIN cannot contain letters I, O, or Q.',
            'vin.unique' => 'This VIN is already registered in the system.',
            'year.min' => 'The vehicle year must be 1900 or later.',
            'year.max' => 'The vehicle year cannot be more than one year in the future.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'vin' => 'VIN',
            'gvwr' => 'GVWR',
            'user_driver_detail_id' => 'assigned driver',
            'out_of_service_date' => 'out of service date',
            'suspended_date' => 'suspended date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize VIN to uppercase and remove spaces/dashes
        if ($this->has('vin')) {
            $this->merge([
                'vin' => strtoupper(str_replace([' ', '-'], '', $this->vin))
            ]);
        }

        // Convert boolean fields
        $this->merge([
            'permanent_tag' => $this->boolean('permanent_tag'),
            'irp_apportioned_plate' => $this->boolean('irp_apportioned_plate'),
            'out_of_service' => $this->boolean('out_of_service'),
            'suspended' => $this->boolean('suspended'),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure data integrity - preserve unchanged fields
            $vehicle = $this->route('vehicle');
            $originalData = $vehicle->getOriginal();
            
            // Check if critical fields are being changed and log for audit
            $criticalFields = ['vin', 'make', 'model', 'year'];
            foreach ($criticalFields as $field) {
                if (isset($originalData[$field]) && 
                    $this->input($field) !== $originalData[$field]) {
                    \Log::info("Vehicle {$vehicle->id} critical field '{$field}' changed", [
                        'old_value' => $originalData[$field],
                        'new_value' => $this->input($field),
                        'user_id' => Auth::id()
                    ]);
                }
            }
        });
    }
}