<?php

namespace Expert\Application\Http\Requests\MyService;

use Expert\Domain\DTOs\DefineExpertServiceDto;
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
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|integer|exists:services,id|distinct',
            'services.*.price' => 'required|numeric',
            'services.*.duration' => 'required|numeric|between:1,1000000',
        ];
    }

    public function toDto(): DefineExpertServiceDto
    {
        return new DefineExpertServiceDto(
            services: array_map(
                fn (array $service) => [
                    'service_id' => (int) $service['service_id'],
                    'price' => (float) $service['price'],
                    'duration' => (float) $service['duration'],
                ],
                $this->services,
            ),
        );
    }
}
