<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/** Валидация полей бизнес-профиля WhatsApp. */
class UpdateWhatsAppProfileRequest extends FormRequest
{
    private const VERTICALS = [
        'UNDEFINED', 'OTHER', 'AUTO', 'BEAUTY', 'APPAREL', 'EDU', 'ENTERTAIN',
        'EVENT_PLAN', 'FINANCE', 'GROCERY', 'GOVT', 'HOTEL', 'HEALTH', 'NONPROFIT',
        'PROF_SERVICES', 'RETAIL', 'TRAVEL', 'RESTAURANT', 'NOT_A_BIZ',
    ];

    /** Авторизация запроса.
     * @return bool Разрешено ли
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    /** Правила валидации.
     * @return array<string, mixed> Правила
     */
    public function rules(): array
    {
        return [
            'profile' => ['required', 'array'],
            'profile.about' => ['nullable', 'string', 'max:139'],
            'profile.description' => ['nullable', 'string', 'max:512'],
            'profile.address' => ['nullable', 'string', 'max:256'],
            'profile.email' => ['nullable', 'email', 'max:128'],
            'profile.vertical' => ['required', Rule::in(self::VERTICALS)],
            'profile.websites' => ['nullable', 'array', 'max:2'],
            'profile.websites.*' => ['string', 'url', 'max:256'],
            'profile.photo_path' => ['nullable', 'string', 'max:512'],
        ];
    }
}
