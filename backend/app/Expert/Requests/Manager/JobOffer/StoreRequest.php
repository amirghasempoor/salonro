<?php

namespace App\Expert\Requests\Manager\JobOffer;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profession_id' => 'required|integer|exists:professions,id',
            'description' => 'nullable|string',
        ];
    }
}
