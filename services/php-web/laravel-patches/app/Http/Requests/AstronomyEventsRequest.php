<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AstronomyEventsRequest extends FormRequest
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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'from_date' => 'required|date|before_or_equal:to_date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'elevation' => 'nullable|numeric|between:-500,9000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude is required',
            'latitude.numeric' => 'Latitude must be a number',
            'latitude.between' => 'Latitude must be between -90 and 90',
            'longitude.required' => 'Longitude is required',
            'longitude.numeric' => 'Longitude must be a number',
            'longitude.between' => 'Longitude must be between -180 and 180',
            'from_date.required' => 'Start date is required',
            'from_date.date' => 'Start date must be a valid date',
            'from_date.before_or_equal' => 'Start date must be before or equal to end date',
            'to_date.required' => 'End date is required',
            'to_date.date' => 'End date must be a valid date',
            'to_date.after_or_equal' => 'End date must be after or equal to start date',
            'elevation.numeric' => 'Elevation must be a number',
            'elevation.between' => 'Elevation must be between -500 and 9000 meters',
        ];
    }
}
