<?php

namespace User\Application\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteRequest extends FormRequest
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
            'email' => ['string', 'email', Rule::unique('users', 'email')->ignore($this->user('user')?->id)],
            'password' => ['required', 'string', 'min:8', 'max:72', 'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'gender' => 'in:0,1',
            'birth_date' => 'date',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
        ];
    }
}
