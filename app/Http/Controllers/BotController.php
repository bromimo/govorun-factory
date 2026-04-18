<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBotRequest;
use App\Http\Requests\UpdateBotRequest;
use App\Models\Bot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BotController extends Controller
{
    /** Список ботов с поиском.
     * @return Response
     */
    public function index(Request $request)
    {
        $bots = Bot::query()
            ->with('updater:id,name')
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
     * @return RedirectResponse
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
     * @return Response
     */
    public function edit(Bot $bot)
    {
        $this->authorize('view', $bot);

        $bot->load(['routes' => fn ($q) => $q->whereNull('parent_id')->orderBy('sort_order')->with('children'), 'flows']);

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
     * @return RedirectResponse
     */
    public function update(UpdateBotRequest $request, Bot $bot)
    {
        $bot->update($request->validated());

        return redirect()->route('bots.edit', $bot);
    }

    /** Удаление бота.
     * @return RedirectResponse
     */
    public function destroy(Bot $bot)
    {
        $this->authorize('delete', $bot);

        $bot->delete();

        return redirect()->route('dashboard');
    }
}
