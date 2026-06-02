<?php

namespace App\Expert\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => 'required|string|regex:/[0-9]{10}/',
        ];
    }
}
