<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Enums\RouteType;
use App\Models\BotRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreBotRouteRequest;
use App\Http\Requests\UpdateBotRouteRequest;
use App\Http\Requests\ReorderBotRoutesRequest;

class BotRouteController extends Controller
{
    /** Создание маршрута для бота.
     * @return RedirectResponse
     */
    public function store(StoreBotRouteRequest $request, Bot $bot)
    {
        $maxOrder = $bot->routes()->max('sort_order') ?? -1;

        $bot->routes()->create([
            ...$this->sanitizeAliases($request->validated()),
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
        $route->update($this->sanitizeAliases($request->validated()));

        return redirect()->route('bots.edit', $bot);
    }

    /** Очистить данные маршрута: алиасы и controller_name.
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeAliases(array $data): array
    {
        if (empty($data['controller_name'])) {
            $data['controller_name'] = null;
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
