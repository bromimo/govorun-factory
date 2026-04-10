<?php

namespace App\Http\Requests;

use App\Models\Bot;
use Illuminate\Foundation\Http\FormRequest;

class StoreBotRequest extends FormRequest
{
    /** Проверка авторизации для создания бота.
     *
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Bot::class);
    }

    /** Правила валидации для создания бота.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
