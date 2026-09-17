<?php

namespace App\Expert\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'workingHours' => 'required|array',
            'workingHours.*' => 'required|array',
            'workingHours.*.day' => ['required', Rule::in(['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'])],
            'workingHours.*.from' => 'required|string',
            'workingHours.*.to' => 'required|string',
        ];
    }
}
