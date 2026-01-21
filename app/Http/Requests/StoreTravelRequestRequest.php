<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

    class StoreTravelRequestRequest extends FormRequest
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
            'requester_name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after:departure_date'],
            'requester_user_id' => ['required', 'exists:users,id'],
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
            'requester_name.required' => 'The requester name is required.',
            'destination.required' => 'The destination is required.',
            'departure_date.required' => 'The departure date is required.',
            'departure_date.after_or_equal' => 'The departure date must be today or a future date.',
            'return_date.required' => 'The return date is required.',
            'return_date.after' => 'The return date must be after the departure date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'requester_user_id' => $this->user()->id,
        ]);
    }
}
