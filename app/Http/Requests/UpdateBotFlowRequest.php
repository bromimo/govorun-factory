<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotFlowRequest extends FormRequest
{
    /** Проверка авторизации для обновления потока бота.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    /** Правила валидации для обновления потока бота.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'graph' => ['sometimes', 'required', 'array'],
            'graph.nodes' => ['array'],
            'graph.edges' => ['array'],
            'interrupt_commands' => ['sometimes', 'nullable', 'array'],
            'interrupt_commands.*' => ['string'],
            'interrupt_on_event' => ['sometimes', 'boolean'],
        ];
    }
}
