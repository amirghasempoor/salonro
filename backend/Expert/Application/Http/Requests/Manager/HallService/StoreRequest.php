<?php

namespace Expert\Application\Http\Requests\Manager\HallService;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'service_id' => [
                'required', 'integer', 'exists:services,id',
                // Already in this hall's catalog: switch it on or off instead of adding it twice.
                Rule::unique('hall_service', 'service_id')->where('hall_id', $this->route('hall')?->id ?? $this->route('hall')),
            ],
        ];
    }
}
