<?php

namespace App\Expert\Requests\Reservation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'services' => 'required|array',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.name' => 'required|string|max:255',
            'services.*.price' => 'required',
            'services.*.duration' => 'required',
            'start_time' => 'required|date',
            'finish_time' => 'required|date',
            'total_price' => 'required',
        ];
    }
}
