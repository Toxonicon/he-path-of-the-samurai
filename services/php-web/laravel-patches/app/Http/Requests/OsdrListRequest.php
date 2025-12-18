<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OsdrListRequest extends FormRequest
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
            'limit' => 'nullable|integer|min:1|max:500',
            'offset' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:active,archived,processing',
            'dataset_id' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'limit.integer' => 'Limit must be an integer',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 500',
            'offset.integer' => 'Offset must be an integer',
            'offset.min' => 'Offset must be at least 0',
            'status.in' => 'Status must be one of: active, archived, processing',
            'dataset_id.max' => 'Dataset ID cannot exceed 100 characters',
        ];
    }
}
