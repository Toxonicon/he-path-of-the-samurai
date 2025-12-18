<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssRangeRequest extends FormRequest
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
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date|after_or_equal:start',
            'limit' => 'nullable|integer|min:1|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start.date' => 'Start date must be a valid date format',
            'start.before_or_equal' => 'Start date must be before or equal to end date',
            'end.date' => 'End date must be a valid date format',
            'end.after_or_equal' => 'End date must be after or equal to start date',
            'limit.integer' => 'Limit must be an integer',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 1000',
        ];
    }
}
