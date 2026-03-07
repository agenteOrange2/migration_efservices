<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('superadmin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'import_type' => 'required|string|in:drivers,driver_addresses,driver_licenses,driver_medical,driver_employment,driver_training,carriers,user_carriers,vehicles,maintenance,repairs,hos_entries',
            'carrier_id' => 'exclude_if:import_type,carriers|required|exists:carriers,id',
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'import_type.required' => 'Please select an import type.',
            'import_type.in' => 'Invalid import type selected.',
            'carrier_id.required' => 'Please select a carrier.',
            'carrier_id.exists' => 'The selected carrier does not exist.',
            'csv_file.required' => 'Please upload a CSV file.',
            'csv_file.file' => 'The uploaded file is invalid.',
            'csv_file.mimes' => 'Please upload a valid CSV or Excel file.',
            'csv_file.max' => 'The file must not exceed 10MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'import_type' => 'import type',
            'carrier_id' => 'carrier',
            'csv_file' => 'file',
        ];
    }
}
