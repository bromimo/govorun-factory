<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBotRequest;
use App\Http\Requests\UpdateBotRequest;

class BotController extends Controller
{
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

    public function store(StoreBotRequest $request)
    {
        $bot = Bot::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('bots.edit', $bot);
    }

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

    public function update(UpdateBotRequest $request, Bot $bot)
    {
        $bot->update($request->validated());

        return redirect()->route('bots.edit', $bot);
    }

    public function destroy(Bot $bot)
    {
        $this->authorize('delete', $bot);

        $bot->delete();

        return redirect()->route('dashboard');
    }
}
