<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBotFlowRequest extends FormRequest
{
    /** Проверка авторизации для создания потока бота.
     *
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    /** Правила валидации для создания потока бота.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(array_column(EntityStatus::cases(), 'value'))],
        ];
    }
}
