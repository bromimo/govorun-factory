<?php

namespace App\Http\Requests;

use App\Enums\HandlerType;
use App\Enums\RouteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBotRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_column(RouteType::cases(), 'value'))],
            'match' => ['nullable', 'string', 'max:255'],
            'handler_type' => ['required', 'string', Rule::in(array_column(HandlerType::cases(), 'value'))],
            'flow_id' => ['nullable', 'integer', 'exists:bot_flows,id'],
            'handler_schema' => ['nullable', 'array'],
            'middleware' => ['nullable', 'array'],
        ];
    }
}
