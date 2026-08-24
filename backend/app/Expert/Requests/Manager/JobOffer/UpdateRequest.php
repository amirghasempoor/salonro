<?php

namespace App\Expert\Requests\Manager\JobOffer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profession_id' => 'sometimes|required|integer|exists:professions,id',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
