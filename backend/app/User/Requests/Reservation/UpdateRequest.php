<?php

namespace App\User\Requests\Reservation;

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
            'expert_id' => 'required|exists:experts,id',
            'hall_id' => 'required|exists:halls,id',
            'services' => 'required|array',
            'services.*.id' => 'required|exists:services,id',
            'services.*.name' => 'required|string|max:255',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ];
    }
}
