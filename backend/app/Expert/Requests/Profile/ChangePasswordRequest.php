<?php

namespace App\Expert\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => 'required|string|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/|min:8',
            'new_password' => 'required|string|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/|min:8',
        ];
    }
}
