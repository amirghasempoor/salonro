<?php

namespace Expert\Application\Http\Requests\Reservation;

use App\Models\Hall;
use Expert\Domain\DTOs\Reservation\BookReservationDto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

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
            'phone_number' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|integer|exists:services,id',
            'start_time' => 'required|date',
            'finish_time' => 'required|date|after:start_time',
        ];
    }

    public function toDto(Hall $hall, int $expertId): BookReservationDto
    {
        return new BookReservationDto(
            hallId: $hall->id,
            expertId: $expertId,
            phoneNumber: $this->phone_number,
            firstName: $this->first_name,
            lastName: $this->last_name,
            serviceIds: collect($this->services)->pluck('service_id')->all(),
            start: Carbon::parse($this->start_time)->toImmutable(),
            finish: Carbon::parse($this->finish_time)->toImmutable(),
        );
    }
}
