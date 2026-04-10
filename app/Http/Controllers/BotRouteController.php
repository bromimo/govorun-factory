<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderBotRoutesRequest;
use App\Http\Requests\StoreBotRouteRequest;
use App\Http\Requests\UpdateBotRouteRequest;
use App\Models\Bot;
use App\Models\BotRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class BotRouteController extends Controller
{
    /** Создание маршрута для бота.
     * @return RedirectResponse
     */
    public function store(StoreBotRouteRequest $request, Bot $bot)
    {
        $maxOrder = $bot->routes()->max('sort_order') ?? -1;

        $bot->routes()->create([
            ...$request->validated(),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('bots.edit', $bot);
    }

    /** Обновление маршрута.
     * @return RedirectResponse
     */
    public function update(UpdateBotRouteRequest $request, Bot $bot, BotRoute $route)
    {
        $route->update($request->validated());

        return redirect()->route('bots.edit', $bot);
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

        return response()->json(['ok' => true]);
    }
}
