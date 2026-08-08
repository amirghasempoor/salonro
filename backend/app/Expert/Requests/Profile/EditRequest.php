<?php

namespace App\Expert\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'avatar' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'bio' => 'string|max:255',
            'password' => 'required|string|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/|min:8',
        ];
    }
}
