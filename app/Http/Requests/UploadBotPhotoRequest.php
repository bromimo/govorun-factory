<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Запрос загрузки аватара бота для Telegram-профиля. */
class UploadBotPhotoRequest extends FormRequest
{
    /** Проверка авторизации.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    /** Правила валидации.
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpeg,jpg,png,mp4',
                'mimetypes:image/jpeg,image/png,video/mp4',
            ],
        ];
    }

    /** Сообщения валидации.
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'Размер файла не должен превышать 10 MB.',
            'file.mimetypes' => 'Поддерживаются только JPG, PNG и MP4.',
        ];
    }
}
