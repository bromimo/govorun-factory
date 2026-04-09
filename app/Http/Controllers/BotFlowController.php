<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use App\Models\BotFlow;
use App\Http\Requests\StoreBotFlowRequest;
use App\Http\Requests\UpdateBotFlowRequest;

class BotFlowController extends Controller
{
    /** Создание нового flow-диалога.
     * @param StoreBotFlowRequest $request
     * @param Bot $bot
     * @return \Illuminate\Http\RedirectResponse
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
     * @param Bot $bot
     * @param BotFlow $flow
     * @return \Inertia\Response
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
     * @param UpdateBotFlowRequest $request
     * @param Bot $bot
     * @param BotFlow $flow
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateBotFlowRequest $request, Bot $bot, BotFlow $flow)
    {
        $flow->update($request->validated());

        return redirect()->route('bot-flows.show', [$bot, $flow]);
    }

    /** Удаление flow-диалога.
     * @param Bot $bot
     * @param BotFlow $flow
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Bot $bot, BotFlow $flow)
    {
        $this->authorize('update', $bot);

        $flow->delete();

        return redirect()->route('bots.edit', $bot);
    }
}
