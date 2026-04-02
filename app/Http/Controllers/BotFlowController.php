<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotFlow;
use Inertia\Inertia;
use App\Http\Requests\StoreBotFlowRequest;
use App\Http\Requests\UpdateBotFlowRequest;

class BotFlowController extends Controller
{
    public function store(StoreBotFlowRequest $request, Bot $bot)
    {
        $flow = $bot->flows()->create([
            'name' => $request->name,
            'graph' => ['nodes' => [], 'edges' => []],
        ]);

        return redirect()->route('bot-flows.show', [$bot, $flow]);
    }

    public function show(Bot $bot, BotFlow $flow)
    {
        $this->authorize('view', $bot);

        return Inertia::render('Flows/Edit', [
            'bot' => $bot->only('id', 'name'),
            'flow' => $flow,
            'can' => [
                'update' => request()->user()->can('update', $bot),
            ],
        ]);
    }

    public function update(UpdateBotFlowRequest $request, Bot $bot, BotFlow $flow)
    {
        $flow->update($request->validated());

        return redirect()->route('bot-flows.show', [$bot, $flow]);
    }

    public function destroy(Bot $bot, BotFlow $flow)
    {
        $this->authorize('update', $bot);

        $flow->delete();

        return redirect()->route('bots.edit', $bot);
    }
}
