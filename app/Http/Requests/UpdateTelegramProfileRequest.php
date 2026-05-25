<?php

namespace App\Http\Requests;

use App\Models\Bot;
use Illuminate\Foundation\Http\FormRequest;

/** Запрос обновления настроек профиля Telegram-бота. */
class UpdateTelegramProfileRequest extends FormRequest
{
    /** Авторизация: разрешено только тем, кто может update бота.
     */
    public function authorize(): bool
    {
        $bot = $this->route('bot');

        return $bot instanceof Bot && $this->user()->can('update', $bot);
    }

    /** Правила валидации формы профиля Telegram.
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'profile' => ['required', 'array'],
            'profile.name' => ['nullable', 'string', 'max:64'],
            'profile.short_description' => ['nullable', 'string', 'max:120'],
            'profile.description' => ['nullable', 'string', 'max:512'],
            'profile.photo_path' => ['nullable', 'string', 'max:512'],
        ];
    }
}
