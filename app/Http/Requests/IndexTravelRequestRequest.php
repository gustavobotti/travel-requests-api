<?php

namespace App\Http\Requests;

use App\Enums\TravelRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTravelRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', \App\Models\TravelRequest::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TravelRequestStatus::class)],
            'destination' => ['sometimes', 'string', 'max:255'],
            'departure_from' => ['required_with:departure_to', 'date'],
            'departure_to' => ['required_with:departure_from', 'date', 'after_or_equal:departure_from'],
            'return_from' => ['required_with:return_to', 'date'],
            'return_to' => ['required_with:return_from', 'date', 'after_or_equal:return_from'],
            'created_from' => ['required_with:created_to', 'date'],
            'created_to' => ['required_with:created_from', 'date', 'after_or_equal:created_from'],
            'travel_from' => ['required_with:travel_to', 'date'],
            'travel_to' => ['required_with:travel_from', 'date', 'after_or_equal:travel_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
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
            'status.enum' => 'The selected status is invalid.',
            'departure_to.after_or_equal' => 'The departure end date must be after or equal to the departure start date.',
            'return_to.after_or_equal' => 'The return end date must be after or equal to the return start date.',
            'created_to.after_or_equal' => 'The created end date must be after or equal to the created start date.',
            'travel_to.after_or_equal' => 'The travel end date must be after or equal to the travel start date.',
            'per_page.max' => 'The per page may not be greater than 100.',
        ];
    }
}

