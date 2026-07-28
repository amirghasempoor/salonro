<?php

namespace App\Expert\Requests\Manager\ServiceCategory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'cat_id' => 'required|int',
            'cat_name' => 'required|string',
            'sub_cat_id' => 'required|int',
            'sub_cat_name' => 'required|string',
            'icon' => 'image|mimes:jpeg,png,jpg,gif,svg',
        ];
    }
}
