<?php

namespace App\Http\Requests;

use App\Enums\HandlerType;
use App\Enums\RouteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBotRouteRequest extends FormRequest
{
    /** Проверка авторизации для обновления маршрута бота.
     *
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    /** Правила валидации для обновления маршрута бота.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_column(RouteType::cases(), 'value'))],
            'match' => ['nullable', 'string', 'max:255'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['string', 'max:255'],
            'handler_type' => ['required', 'string', Rule::in(array_column(HandlerType::cases(), 'value'))],
            'flow_id' => ['nullable', 'integer', 'exists:bot_flows,id'],
            'handler_schema' => ['nullable', 'array'],
            'middleware' => ['nullable', 'array'],
        ];
    }
}
