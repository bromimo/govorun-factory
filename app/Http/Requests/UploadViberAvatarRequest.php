<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Запрос загрузки аватара Viber-бота (только изображения, без видео). */
class UploadViberAvatarRequest extends FormRequest
{
    /** Проверка авторизации. */
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
                'mimes:jpeg,jpg,png',
                'mimetypes:image/jpeg,image/png',
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
            'file.mimetypes' => 'Поддерживаются только JPG и PNG (Viber не принимает видео-аватары).',
        ];
    }
}