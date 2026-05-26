<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Запрос обновления настроек профиля Viber-бота. */
class UpdateViberProfileRequest extends FormRequest
{
    private const ALLOWED_EVENT_TYPES = [
        'message',
        'subscribed',
        'unsubscribed',
        'conversation_started',
        'delivered',
        'seen',
        'failed',
    ];

    /** Авторизация: разрешено только тем, кто может update бота. */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    /** Правила валидации формы профиля Viber.
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'profile' => ['required', 'array'],
            'profile.sender_name' => ['required', 'string', 'max:28'],
            'profile.public_account_uri' => ['nullable', 'string', 'alpha_dash', 'max:64'],
            'profile.event_types' => ['required', 'array', 'min:1'],
            'profile.event_types.*' => ['string', 'in:'.implode(',', self::ALLOWED_EVENT_TYPES)],
            'profile.avatar_path' => ['nullable', 'string', 'max:512'],
        ];
    }
}
