<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotFlowRequest extends FormRequest
{
    /** Проверка авторизации для обновления потока бота.
     *
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
            'graph' => ['sometimes', 'required', 'array', function (string $attribute, mixed $value, \Closure $fail) {
                $startNodes = collect($value['nodes'] ?? [])->where('type', 'start');
                if ($startNodes->count() !== 1) {
                    $fail('Граф должен содержать ровно один блок «Начало».');
                }
            }],
            'graph.nodes' => ['array'],
            'graph.edges' => ['array'],
            'graph.viewport' => ['sometimes', 'array'],
            'interrupt_commands' => ['sometimes', 'nullable', 'array'],
            'interrupt_commands.*' => ['string'],
            'interrupt_on_event' => ['sometimes', 'boolean'],
        ];
    }
}
