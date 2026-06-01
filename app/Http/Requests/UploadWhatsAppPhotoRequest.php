<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация загрузки фото бизнес-профиля WhatsApp. */
class UploadWhatsAppPhotoRequest extends FormRequest
{
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
            'file' => ['required', 'file', 'max:5120', 'mimes:jpeg,jpg,png', 'mimetypes:image/jpeg,image/png'],
        ];
    }

    /** Сообщения об ошибках.
     * @return array<string, string> Сообщения
     */
    public function messages(): array
    {
        return [
            'file.max' => 'Файл не должен превышать 5 МБ.',
            'file.mimetypes' => 'Допустимы только JPEG или PNG.',
        ];
    }
}
