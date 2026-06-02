<?php

namespace App\Expert\Requests\Manager\HallService;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'hall_id' => 'required|integer|exists:halls,id',
            'category_id' => 'required|integer|exists:service_categories,id',
            'description' => 'string|max:255',
            'duration' => 'required|integer|between:1,1000000',
            'price' => 'required|integer|between:1,1000000',
            'is_active' => 'required|boolean',
        ];
    }
}
