<?php

namespace App\Http\Requests\Hos;

use Illuminate\Foundation\Http\FormRequest;

class ForgiveViolationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow superadmin and carrier users to forgive violations
        return $this->user()->hasAnyRole(['superadmin', 'user_carrier']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'forgiveness_reason' => 'required|string|min:10|max:1000',
            'adjusted_end_time' => 'nullable|date|before_or_equal:now',
            'confirm_forgiveness' => 'required|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'forgiveness_reason.required' => 'Please provide a reason for forgiving this violation.',
            'forgiveness_reason.min' => 'Please provide a detailed justification (at least 10 characters).',
            'forgiveness_reason.max' => 'The justification cannot exceed 1000 characters.',
            'adjusted_end_time.before_or_equal' => 'The adjusted end time cannot be in the future.',
            'adjusted_end_time.date' => 'Please provide a valid date and time.',
            'confirm_forgiveness.required' => 'You must confirm the forgiveness action.',
            'confirm_forgiveness.accepted' => 'You must confirm that you want to forgive this violation.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'forgiveness_reason' => 'justification',
            'adjusted_end_time' => 'adjusted end time',
            'confirm_forgiveness' => 'confirmation',
        ];
    }
}
