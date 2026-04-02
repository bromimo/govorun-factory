<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'config' => ['sometimes', 'nullable', 'array'],
            'config.environment' => ['nullable', 'string', 'in:production,development'],
            'config.debug' => ['nullable', 'boolean'],
            'config.state_storage' => ['nullable', 'string', 'in:file,database,cache'],
            'messenger_config' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
