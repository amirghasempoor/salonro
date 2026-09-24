<?php

namespace Expert\Application\Http\Requests\Reservation;

use Expert\Domain\DTOs\Reservation\RescheduleReservationDto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

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
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|integer|exists:services,id',
            'start_time' => 'required|date',
            'finish_time' => 'required|date|after:start_time',
        ];
    }

    public function toDto(): RescheduleReservationDto
    {
        return new RescheduleReservationDto(
            serviceIds: collect($this->services)->pluck('service_id')->all(),
            start: Carbon::parse($this->start_time)->toImmutable(),
            finish: Carbon::parse($this->finish_time)->toImmutable(),
        );
    }
}
