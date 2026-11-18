<?php

namespace Expert\Application\Http\Requests\Manager\HallService;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'service_id' => [
                'required', 'integer', 'exists:services,id',
                Rule::unique('hall_service', 'service_id')
                    ->where('hall_id', $this->route('hallService')->hall_id)
                    ->ignore($this->route('hallService')->id),
            ],
            'is_active' => 'required|boolean',
        ];
    }
}
