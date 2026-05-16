<?php

namespace App\Http\Requests;

use App\Enums\ConnectionAuthType;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/** Валидация создания подключения бота к внешнему API. */
class StoreBotConnectionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $bot = $this->route('bot');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('bot_connections', 'slug')->where('bot_id', $bot->id),
            ],
            'base_url' => ['required', 'url', 'max:2048'],
            'auth_type' => ['required', Rule::enum(ConnectionAuthType::class)],
            'auth_config' => ['nullable', 'array'],
            'auth_config.token' => ['nullable', 'string', 'max:4096'],
            'auth_config.login' => ['nullable', 'string', 'max:255'],
            'auth_config.password' => ['nullable', 'string', 'max:255'],
            'auth_config.key' => ['nullable', 'string', 'max:255'],
            'auth_config.value' => ['nullable', 'string', 'max:4096'],
            'auth_config.in' => ['nullable', 'in:header,query'],
            'default_headers' => ['nullable', 'array'],
            'default_headers.*.key' => ['required_with:default_headers.*.value', 'string', 'max:255'],
            'default_headers.*.value' => ['nullable', 'string', 'max:4096'],
        ];
    }
}
