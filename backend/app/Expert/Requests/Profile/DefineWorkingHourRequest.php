<?php

namespace App\Expert\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DefineWorkingHourRequest extends FormRequest
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
            'hall_id' => 'required|integer|exists:halls,id',
            'workingHours' => 'required|array',
            'workingHours.*' => 'required|array',
            'workingHours.*.day' => 'required|string',
            'workingHours.*.from' => 'required|string',
            'workingHours.*.to' => 'required|string',
        ];
    }
}
