<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTravelRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $travelRequest = $this->route('travel_request');
        return $this->user()->can('update', $travelRequest);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $travelRequest = $this->route('travel_request');

        return [
            'status' => ['prohibited'],
            'approved_by' => ['prohibited'],
            'approved_at' => ['prohibited'],
            'cancelled_by' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'requester_user_id' => ['prohibited'],
            'requester_name' => ['sometimes', 'string', 'max:255'],
            'destination' => ['sometimes', 'string', 'max:255'],
            'departure_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'return_date' => [
                'sometimes',
                'date',
                function ($attribute, $value, $fail) use ($travelRequest) {
                    $departureDate = $this->input('departure_date')
                        ? $this->input('departure_date')
                        : $travelRequest->departure_date->format('Y-m-d');

                    if (strtotime($value) <= strtotime($departureDate)) {
                        $fail('The return date must be after the departure date.');
                    }
                },
            ],
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
            'departure_date.after_or_equal' => 'The departure date must be today or a future date.',
            'return_date.after' => 'The return date must be after the departure date.',
        ];
    }
}
