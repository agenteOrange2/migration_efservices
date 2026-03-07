<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
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
            'status' => ['nullable', 'in:pending,in_progress,completed,cancelled'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'scheduled_start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'scheduled_end_date' => ['nullable', 'date', 'after_or_equal:scheduled_start_date'],
            'actual_start_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date', 'after_or_equal:actual_start_date'],
            'origin_address' => ['nullable', 'string', 'max:500'],
            'destination_address' => ['nullable', 'string', 'max:500'],
            'odometer_start' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'odometer_end' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'gte:odometer_start'],
            'location_start' => ['nullable', 'string', 'max:255'],
            'location_end' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'post_inspection' => ['nullable', 'array'],
            'post_inspection.*.item' => ['required_with:post_inspection', 'string', 'max:255'],
            'post_inspection.*.status' => ['required_with:post_inspection', 'in:pass,fail,na'],
            'post_inspection.*.notes' => ['nullable', 'string', 'max:500'],
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
            'status.in' => 'Trip status must be pending, in progress, completed, or cancelled.',
            'vehicle_id.exists' => 'The selected vehicle is invalid.',
            'scheduled_end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'actual_end_date.after_or_equal' => 'Actual end date must be after or equal to actual start date.',
            'odometer_end.gte' => 'Ending odometer reading must be greater than or equal to starting reading.',
            'post_inspection.*.status.in' => 'Inspection status must be pass, fail, or N/A.',
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
            'vehicle_id' => 'vehicle',
            'scheduled_start_date' => 'scheduled start date',
            'scheduled_end_date' => 'scheduled end date',
            'actual_start_date' => 'actual start date',
            'actual_end_date' => 'actual end date',
            'origin_address' => 'origin',
            'destination_address' => 'destination',
            'odometer_start' => 'starting odometer reading',
            'odometer_end' => 'ending odometer reading',
            'location_start' => 'starting location',
            'location_end' => 'ending location',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim string inputs
        $trimFields = [
            'origin_address',
            'destination_address',
            'location_start',
            'location_end',
            'notes'
        ];

        $mergeData = [];
        foreach ($trimFields as $field) {
            if ($this->has($field)) {
                $mergeData[$field] = trim($this->$field ?? '');
            }
        }

        $this->merge($mergeData);

        // Ensure post_inspection is an array
        if ($this->has('post_inspection') && !is_array($this->post_inspection)) {
            $this->merge(['post_inspection' => []]);
        }
    }
}
