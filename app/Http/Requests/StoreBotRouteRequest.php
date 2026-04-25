<?php

namespace App\Http\Requests;

use Closure;
use App\Enums\RouteType;
use App\Models\BotRoute;
use App\Enums\HandlerType;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBotRouteRequest extends FormRequest
{
    /** Проверка авторизации для создания маршрута бота.
     *
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bot'));
    }

    /** Правила валидации для создания маршрута бота.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $parentId = $this->input('parent_id');
        $botId = $this->route('bot')->id;

        $uniqueRule = Rule::unique('bot_routes', 'controller_name')
            ->where('bot_id', $botId)
            ->where('parent_id', $parentId);

        return [
            'parent_id' => ['nullable', 'integer', 'exists:bot_routes,id'],
            'type' => [
                'required', 'string',
                Rule::in(array_column(RouteType::cases(), 'value')),
                function (string $attribute, mixed $value, Closure $fail) use ($botId, $parentId) {
                    if ($value !== RouteType::Fallback->value) {
                        return;
                    }

                    if ($parentId !== null) {
                        $fail('Fallback не может быть вложенным маршрутом');

                        return;
                    }

                    $exists = BotRoute::query()
                        ->where('bot_id', $botId)
                        ->where('type', RouteType::Fallback->value)
                        ->exists();

                    if ($exists) {
                        $fail('У бота уже есть fallback-маршрут');
                    }
                },
            ],
            'match' => ['nullable', 'string', 'max:255'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['nullable', 'string', 'max:255'],
            'controller_name' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z][a-zA-Z0-9]*$/', $uniqueRule],
            'handler_type' => ['required', 'string', Rule::in(array_column(HandlerType::cases(), 'value'))],
            'flow_id' => ['nullable', 'integer', 'exists:bot_flows,id'],
            'handler_schema' => ['nullable', 'array'],
            'middleware' => ['nullable', 'array'],
        ];
    }

    /** Сообщения валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'controller_name.unique' => 'Это имя уже используется',
            'controller_name.regex' => 'Только латиница и цифры, начинается с буквы',
        ];
    }
}
