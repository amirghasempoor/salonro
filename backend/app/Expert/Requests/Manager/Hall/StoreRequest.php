<?php

namespace App\Expert\Requests\Manager\Hall;

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
            'name' => 'required|string|max:255',
            'lat' => 'required|string',
            'lng' => 'required|string',
            'address' => 'required|string',
            'postal_code' => 'required|string',
            'telephone' => 'required|string',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'description' => 'string',
            'services' => 'required|array',
            'services.*.name' => 'required|string|max:255',
            'services.*.duration' => 'required',
            'services.*.price' => 'required',
            'services.*.category_id' => 'required',
        ];
    }
}
