<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\BotFlow;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreBotFlowRequest;
use App\Http\Requests\UpdateBotFlowRequest;

class BotFlowController extends Controller
{
    /** Создание нового flow-диалога.
     * @return RedirectResponse
     */
    public function store(StoreBotFlowRequest $request, Bot $bot)
    {
        $flow = $bot->flows()->create([
            'name' => $request->name,
            'description' => $request->description,
            'graph' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start', 'position' => ['x' => 250, 'y' => 50], 'data' => (object) []],
                ],
                'edges' => [],
            ],
        ]);

        return redirect()->route('bot-flows.show', [$bot, $flow]);
    }

    /** Страница редактора flow-диалога.
     * @return Response
     */
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

    /** Обновление графа и настроек диалога.
     * @return RedirectResponse
     */
    public function update(UpdateBotFlowRequest $request, Bot $bot, BotFlow $flow)
    {
        $flow->update($request->validated());

        if (! $request->has('graph')) {
            return redirect()->back();
        }

        return redirect()->route('bot-flows.show', [$bot, $flow]);
    }

    /** Удаление flow-диалога.
     * @return RedirectResponse
     */
    public function destroy(Bot $bot, BotFlow $flow)
    {
        $this->authorize('update', $bot);

        $flow->delete();

        return redirect()->route('bots.edit', $bot);
    }
}
