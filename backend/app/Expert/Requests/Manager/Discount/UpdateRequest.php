<?php

namespace App\Expert\Requests\Manager\Discount;

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
            'type' => 'required|in:manual,holiday',
            'title' => 'nullable|string|max:255',
            'user_id' => 'required_if:type,manual|nullable|integer|exists:users,id',
            'amount_type' => 'required|in:percentage,fixed',
            'amount' => $this->input('amount_type') === 'percentage'
                ? 'required|integer|between:1,100'
                : 'required|integer|min:1',
            'starts_at' => 'required_if:type,holiday|nullable|date',
            'ends_at' => 'required_if:type,holiday|nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'required|boolean',
        ];
    }
}
