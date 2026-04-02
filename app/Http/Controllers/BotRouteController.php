<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotRoute;
use App\Http\Requests\StoreBotRouteRequest;
use App\Http\Requests\UpdateBotRouteRequest;
use App\Http\Requests\ReorderBotRoutesRequest;

class BotRouteController extends Controller
{
    public function store(StoreBotRouteRequest $request, Bot $bot)
    {
        $maxOrder = $bot->routes()->max('sort_order') ?? -1;

        $bot->routes()->create([
            ...$request->validated(),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('bots.edit', $bot);
    }

    public function update(UpdateBotRouteRequest $request, Bot $bot, BotRoute $route)
    {
        $route->update($request->validated());

        return redirect()->route('bots.edit', $bot);
    }

    public function destroy(Bot $bot, BotRoute $route)
    {
        $this->authorize('update', $bot);

        $route->delete();

        return redirect()->route('bots.edit', $bot);
    }

    public function reorder(ReorderBotRoutesRequest $request, Bot $bot)
    {
        foreach ($request->ids as $index => $id) {
            $bot->routes()->where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }
}
