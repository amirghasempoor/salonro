<?php

namespace App\Expert\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'phone_number' => 'required|string|max:11|unique:experts,phone_number',
            'email' => 'string|email|max:255|unique:experts,email',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'avatar' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role' => 'required|in:1,2',
        ];
    }
}
