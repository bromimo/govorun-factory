<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\ConnectionAuthType;
use Illuminate\Foundation\Http\FormRequest;

/** Валидация изменения подключения бота. */
class UpdateBotConnectionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $connection = $this->route('connection');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('bot_connections', 'slug')
                    ->where('bot_id', $connection->bot_id)
                    ->ignore($connection->id),
            ],
            'base_url' => ['sometimes', 'url', 'max:2048'],
            'auth_type' => ['sometimes', Rule::enum(ConnectionAuthType::class)],
            'auth_config' => ['sometimes', 'array'],
            'auth_config.token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'auth_config.login' => ['sometimes', 'nullable', 'string', 'max:255'],
            'auth_config.password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'auth_config.key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'auth_config.value' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'auth_config.in' => ['sometimes', 'nullable', 'in:header,query'],
            'default_headers' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
