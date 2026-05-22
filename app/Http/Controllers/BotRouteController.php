<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\RouteType;
use App\Models\BotRoute;
use App\Enums\EntityStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\SchemaValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreBotRouteRequest;
use App\Http\Requests\UpdateBotRouteRequest;
use App\Http\Requests\ReorderBotRoutesRequest;
use Illuminate\Validation\ValidationException;

class BotRouteController extends Controller
{
    /** Страница редактирования маршрута.
     */
    public function edit(Bot $bot, BotRoute $route): Response
    {
        $this->authorize('update', $bot);

        return Inertia::render('Routes/Edit', [
            'bot' => $bot->only('id', 'name'),
            'botRoute' => $route,
            'flows' => $bot->flows()->where('status', EntityStatus::Active->value)->select('id', 'name')->get(),
            'hasChildren' => $route->children()->exists(),
            'can' => ['update' => request()->user()->can('update', $bot)],
        ]);
    }

    /** Создание маршрута для бота.
     * @return RedirectResponse
     */
    public function store(StoreBotRouteRequest $request, Bot $bot)
    {
        $maxOrder = $bot->routes()->max('sort_order') ?? -1;

        $bot->routes()->create([
            ...$this->sanitizeAliases($request->validated()),
            'status' => EntityStatus::Draft->value,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->pinFallbackToEnd($bot);

        return redirect()->route('bots.edit', $bot);
    }

    /** Обновление маршрута.
     * @return RedirectResponse
     */
    public function update(UpdateBotRouteRequest $request, Bot $bot, BotRoute $route)
    {
        // Статус меняется только через PATCH /status — убираем из validated данных
        $data = array_diff_key($this->sanitizeAliases($request->validated()), ['status' => true]);
        $route->update($data);

        if (in_array($route->status, [EntityStatus::Active, EntityStatus::Inactive], true)) {
            $result = (new SchemaValidator($bot))->validateSingleRoute($route->fresh());
            if (! empty($result->errors)) {
                $route->update(['status' => EntityStatus::Draft]);
                session()->flash('auto_drafted_reasons', $result->errors);
            }
        }

        return redirect()->route('bot-routes.edit', [$bot, $route]);
    }

    /** Сменить статус маршрута.
     *
     * @throws ValidationException
     */
    public function changeStatus(Request $request, Bot $bot, BotRoute $route): JsonResponse
    {
        $this->authorize('update', $bot);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_column(EntityStatus::cases(), 'value'))],
        ]);

        $target = EntityStatus::from($data['status']);

        if ($target === EntityStatus::Active) {
            if ($route->parent_id !== null) {
                $parent = BotRoute::find($route->parent_id);
                if ($parent && $parent->status !== EntityStatus::Active) {
                    return response()->json([
                        'errors' => ['Сначала активируйте родительский маршрут'],
                    ], 422);
                }
            }

            $result = (new SchemaValidator($bot))->validateSingleRoute($route);
            if (! empty($result->errors)) {
                return response()->json(['errors' => $result->errors], 422);
            }
        }

        $cascaded = [];

        DB::transaction(function () use ($route, $target, &$cascaded) {
            $route->update(['status' => $target]);

            if (in_array($target, [EntityStatus::Inactive, EntityStatus::Draft], true)) {
                $children = $route->children()->where('status', EntityStatus::Active->value)->get();
                foreach ($children as $child) {
                    $child->update(['status' => EntityStatus::Inactive]);
                    $cascaded[] = $child->id;
                }
            }
        });

        return response()->json([
            'status' => $target->value,
            'cascaded_children' => $cascaded,
        ]);
    }

    /** Очистить данные маршрута: алиасы, controller_name и flow_id в зависимости от типа/обработчика.
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeAliases(array $data): array
    {
        if (empty($data['controller_name']) || ($data['type'] ?? '') === 'fallback') {
            $data['controller_name'] = null;
        }

        if (($data['handler_type'] ?? 'controller') !== 'flow') {
            $data['flow_id'] = null;
        }

        if (($data['handler_type'] ?? 'controller') === 'flow') {
            $data['handler_schema'] = null;
        }

        if (($data['type'] ?? '') !== 'phrase') {
            $data['aliases'] = null;

            return $data;
        }

        if (isset($data['aliases'])) {
            $data['aliases'] = array_values(array_filter(
                $data['aliases'],
                fn ($v) => is_string($v) && trim($v) !== '',
            ));
        }

        return $data;
    }

    /** Удаление маршрута.
     * @return RedirectResponse
     */
    public function destroy(Bot $bot, BotRoute $route)
    {
        $this->authorize('update', $bot);

        $route->delete();

        return redirect()->route('bots.edit', $bot);
    }

    /** Изменение порядка маршрутов.
     * @return JsonResponse
     */
    public function reorder(ReorderBotRoutesRequest $request, Bot $bot)
    {
        foreach ($request->ids as $index => $id) {
            $bot->routes()->where('id', $id)->update(['sort_order' => $index]);
        }

        $this->pinFallbackToEnd($bot);

        return response()->json(['ok' => true]);
    }

    /** Поставить fallback верхнего уровня в конец списка маршрутов. */
    private function pinFallbackToEnd(Bot $bot): void
    {
        $fallback = $bot->routes()
            ->whereNull('parent_id')
            ->where('type', RouteType::Fallback->value)
            ->first();

        if ($fallback === null) {
            return;
        }

        $maxOrder = $bot->routes()
            ->whereNull('parent_id')
            ->where('id', '!=', $fallback->id)
            ->max('sort_order');

        $fallback->update(['sort_order' => ($maxOrder ?? -1) + 1]);
    }
}
