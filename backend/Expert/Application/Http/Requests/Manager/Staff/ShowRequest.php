<?php

namespace Expert\Application\Http\Requests\Manager\Staff;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An expert's details are always read in the context of one of the manager's
     * halls: the hall decides both who may look and whose working hours are shown.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'hall_id' => 'required|integer',
        ];
    }
}
