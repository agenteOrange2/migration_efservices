<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Rules\ValidateDocumentFile;
use App\Models\Admin\Driver\DriverTesting;
use Illuminate\Foundation\Http\FormRequest;

class StoreDriverTestingRequest extends FormRequest
{

        
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carrier_id' => [
                'required',
                'integer',
                'exists:carriers,id'
            ],
            'user_driver_detail_id' => [
                'required',
                'integer',
                'exists:user_driver_details,id'
            ],
            'test_type' => [
                'required',
                'string',
                Rule::in(array_keys(DriverTesting::getDrugTestTypes()))
            ],
            'test_date' => [
                'required',
                'date'
                // Removed 'before_or_equal:today' to allow scheduling tests for future dates
            ],
            'test_time' => [
                'nullable',
                'date_format:H:i'
            ],
            'location' => [
                'required',
                'string',
                'max:255'
            ],
            'administered_by' => [
                'required',
                'string',
                'max:255'
            ],
            'requester_name' => [
                'required',
                'string',
                'max:255'
            ],
            'test_result' => [
                'nullable',
                Rule::in(['Positive', 'Negative', 'Refusal'])
            ],
            'substances_tested' => [
                'nullable',
                'array'
            ],
            'substances_tested.*' => [
                'string',
                'max:100'
            ],
            'mro' => [
                'required',
                'string',
                'max:255'
            ],
            'scheduled_time' => [
                'required',
                'date_format:Y-m-d\TH:i'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'status' => [
                'nullable',
                Rule::in(['Schedule', 'In Progress', 'Completed', 'Cancelled'])
            ],
            'bill_to' => [
                'required',
                'string',
                'max:255'
            ],
            'next_test_due' => [
                'nullable',
                'date',
                'after:test_date'
            ],
            // Test Details checkboxes
            'is_random_test' => 'nullable|boolean',
            'is_post_accident_test' => 'nullable|boolean',
            'is_reasonable_suspicion_test' => 'nullable|boolean',
            'is_pre_employment_test' => 'nullable|boolean',
            'is_follow_up_test' => 'nullable|boolean',
            'is_return_to_duty_test' => 'nullable|boolean',
            'is_other_reason_test' => 'nullable|boolean',
            'other_reason_description' => [
                'nullable',
                'string',
                'max:255',
                'required_if:is_other_reason_test,1'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'carrier_id.required' => 'Please select a carrier.',
            'carrier_id.exists' => 'The selected carrier is invalid.',
            'user_driver_detail_id.required' => 'Please select a driver.',
            'user_driver_detail_id.exists' => 'The selected driver is invalid.',
            'test_type.required' => 'Please select a test type.',
            'test_type.in' => 'The selected test type is invalid.',
            'test_date.required' => 'Test date is required.',
            'test_date.date' => 'Please provide a valid test date.',
            'test_time.date_format' => 'Please provide a valid time format (HH:MM).',
            'location.required' => 'Location is required.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'administered_by.required' => 'Please specify who administered the test.',
            'administered_by.max' => 'Administered by field cannot exceed 255 characters.',
            'requester_name.required' => 'Requester name is required.',
            'requester_name.max' => 'Requester name cannot exceed 255 characters.',
            'test_result.in' => 'Please select a valid test result.',
            'substances_tested.array' => 'Substances tested must be a list.',
            'substances_tested.*.max' => 'Each substance name cannot exceed 100 characters.',
            'mro.required' => 'MRO (Medical Review Officer) is required.',
            'mro.max' => 'MRO field cannot exceed 255 characters.',
            'scheduled_time.required' => 'Scheduled time is required.',
            'scheduled_time.date_format' => 'Please provide a valid scheduled time.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'status.in' => 'Please select a valid status.',
            'bill_to.required' => 'Please select who to bill for this test.',
            'bill_to.max' => 'Bill to field cannot exceed 255 characters.',
            'next_test_due.date' => 'Please provide a valid date for next test due.',
            'next_test_due.after' => 'Next test due date must be after the test date.',
            'other_reason_description.required_if' => 'Please provide a description when selecting "Other" as test reason.',
            'other_reason_description.max' => 'Other reason description cannot exceed 255 characters.',
            'document_attachments.max' => 'You can upload a maximum of 10 files.',
            'document_attachments.*.file' => 'Each attachment must be a valid file.',
            'document_attachments.*.max' => 'Each file must not exceed 20MB.',
            'document_attachments.*.mimes' => 'Only PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF, and TXT files are allowed.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'carrier_id' => 'carrier',
            'user_driver_detail_id' => 'driver',
            'test_type' => 'test type',
            'test_date' => 'test date',
            'test_time' => 'test time',
            'collection_site' => 'collection site',
            'collector_name' => 'collector name',
            'specimen_id' => 'specimen ID',
            'test_result' => 'test result',
            'mro_name' => 'MRO name',
            'mro_phone' => 'MRO phone',
            'laboratory_name' => 'laboratory name',
            'laboratory_address' => 'laboratory address',
            'chain_of_custody_number' => 'chain of custody number',
            'reason_for_test' => 'reason for test',
            'other_reason_description' => 'other reason description',
            'document_attachments' => 'document attachments'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir substances_tested de string JSON a array si es necesario
        if ($this->has('substances_tested') && is_string($this->substances_tested)) {
            $this->merge([
                'substances_tested' => json_decode($this->substances_tested, true) ?? []
            ]);
        }

        // Limpiar y formatear el teléfono del MRO
        if ($this->has('mro_phone')) {
            $this->merge([
                'mro_phone' => preg_replace('/[^\d\+]/', '', $this->mro_phone)
            ]);
        }

        // Procesar checkboxes de Test Details - convertir a booleanos
        $testDetailsFields = [
            'is_random_test',
            'is_post_accident_test', 
            'is_reasonable_suspicion_test',
            'is_pre_employment_test',
            'is_follow_up_test',
            'is_return_to_duty_test',
            'is_other_reason_test'
        ];

        $processedFields = [];
        foreach ($testDetailsFields as $field) {
            // Si el checkbox está presente en el request, convertir a boolean
            if ($this->has($field)) {
                $processedFields[$field] = (bool) $this->input($field);
            } else {
                // Si no está presente (checkbox no marcado), establecer como false
                $processedFields[$field] = false;
            }
        }

        $this->merge($processedFields);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validación personalizada: al menos un tipo de test debe estar seleccionado
            $testTypes = [
                'is_random_test',
                'is_post_accident_test',
                'is_reasonable_suspicion_test',
                'is_pre_employment_test',
                'is_follow_up_test',
                'is_return_to_duty_test',
                'is_other_reason_test'
            ];

            $hasTestType = false;
            foreach ($testTypes as $type) {
                if ($this->input($type)) {
                    $hasTestType = true;
                    break;
                }
            }

            if (!$hasTestType) {
                $validator->errors()->add('test_details', 'Please select at least one test detail type.');
            }

            // Validación de carrier ownership: verificar que el conductor pertenece al carrier
            // Solo validar si AMBOS campos tienen valores válidos (no vacíos)
            $driverId = $this->user_driver_detail_id;
            $carrierId = $this->carrier_id;
            
            if (!empty($driverId) && !empty($carrierId) && is_numeric($driverId) && is_numeric($carrierId)) {
                $driver = \App\Models\UserDriverDetail::find($driverId);
                // Use == instead of !== to allow type coercion (string "51" == int 51)
                if ($driver && (int) $driver->carrier_id !== (int) $carrierId) {
                    $validator->errors()->add('user_driver_detail_id', 'The selected driver does not belong to the specified carrier.');
                }
            }
        });
    }
}