<?php

namespace App\Expert\Requests\Reservation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|integer|exists:services,id',
            'start_time' => 'required|date',
            'finish_time' => 'required|date',
        ];
    }
}
