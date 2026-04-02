<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBotRequest;
use App\Http\Requests\UpdateBotRequest;

class BotController extends Controller
{
    /** Список ботов с поиском.
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $bots = Bot::query()
            ->with('creator:id,name')
            ->withCount(['routes', 'flows'])
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->latest('updated_at')
            ->get();

        return Inertia::render('Dashboard/Index', [
            'bots' => $bots,
            'filters' => ['search' => $request->search],
            'can' => [
                'createBot' => $request->user()->can('create', Bot::class),
            ],
        ]);
    }

    /** Создание нового бота.
     * @param StoreBotRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreBotRequest $request)
    {
        $bot = Bot::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('bots.edit', $bot);
    }

    /** Страница редактора бота.
     * @param Bot $bot
     * @return \Inertia\Response
     */
    public function edit(Bot $bot)
    {
        $this->authorize('view', $bot);

        $bot->load(['routes', 'flows']);

        return Inertia::render('Bots/Edit', [
            'bot' => $bot,
            'can' => [
                'update' => request()->user()->can('update', $bot),
                'delete' => request()->user()->can('delete', $bot),
                'export' => request()->user()->can('export', $bot),
            ],
        ]);
    }

    /** Обновление настроек бота.
     * @param UpdateBotRequest $request
     * @param Bot $bot
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateBotRequest $request, Bot $bot)
    {
        $bot->update($request->validated());

        return redirect()->route('bots.edit', $bot);
    }

    /** Удаление бота.
     * @param Bot $bot
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Bot $bot)
    {
        $this->authorize('delete', $bot);

        $bot->delete();

        return redirect()->route('dashboard');
    }
}
