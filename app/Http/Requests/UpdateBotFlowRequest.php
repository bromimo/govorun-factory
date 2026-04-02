<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'graph' => ['sometimes', 'required', 'array'],
            'graph.nodes' => ['array'],
            'graph.edges' => ['array'],
            'interrupt_commands' => ['sometimes', 'nullable', 'array'],
            'interrupt_commands.*' => ['string'],
            'interrupt_on_event' => ['sometimes', 'boolean'],
        ];
    }
}
