<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class StartTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization logic is handled in the controller/policy
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
            'driver_id' => ['required', 'exists:user_driver_details,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'pre_inspection' => ['nullable', 'array'],
            'pre_inspection.*.item' => ['required_with:pre_inspection', 'string', 'max:255'],
            'pre_inspection.*.status' => ['required_with:pre_inspection', 'in:pass,fail,na'],
            'pre_inspection.*.notes' => ['nullable', 'string', 'max:500'],
            'odometer_start' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'location_start' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'driver_id.required' => 'Driver selection is required.',
            'driver_id.exists' => 'The selected driver is invalid.',
            'vehicle_id.exists' => 'The selected vehicle is invalid.',
            'pre_inspection.*.status.in' => 'Inspection status must be pass, fail, or N/A.',
            'odometer_start.numeric' => 'Odometer reading must be a number.',
            'odometer_start.min' => 'Odometer reading cannot be negative.',
            'odometer_start.max' => 'Odometer reading exceeds maximum value.',
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
            'driver_id' => 'driver',
            'vehicle_id' => 'vehicle',
            'odometer_start' => 'starting odometer reading',
            'location_start' => 'starting location',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim string inputs
        if ($this->has('location_start')) {
            $this->merge(['location_start' => trim($this->location_start ?? '')]);
        }

        if ($this->has('notes')) {
            $this->merge(['notes' => trim($this->notes ?? '')]);
        }

        // Ensure pre_inspection is an array
        if ($this->has('pre_inspection') && !is_array($this->pre_inspection)) {
            $this->merge(['pre_inspection' => []]);
        }
    }
}
