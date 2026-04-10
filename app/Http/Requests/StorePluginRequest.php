<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Запрос на создание плагина. */
class StorePluginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:plugins'],
            'description' => ['nullable', 'string'],
            'block_schema' => ['nullable', 'array'],
            'vue_component' => ['nullable', 'string', 'max:255'],
            'php_stub' => ['nullable', 'string'],
            'active' => ['boolean'],
        ];
    }
}
