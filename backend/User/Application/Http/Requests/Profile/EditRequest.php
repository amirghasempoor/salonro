<?php

namespace User\Application\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
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
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone_number' => [
                'required', 'regex:/^09\d{9}$/', 'numeric', 'digits:11',
                Rule::unique('users', 'phone_number')->ignore($this->user('user')?->id),
            ],
            'email' => 'required|string|email|unique:users,email',
            'gender' => 'required|in:0,1',
            'birth_date' => 'required|date',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
        ];
    }
}
