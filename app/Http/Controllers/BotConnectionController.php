<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\BotConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreBotConnectionRequest;
use App\Http\Requests\UpdateBotConnectionRequest;

/** CRUD подключений бота к внешним API. */
class BotConnectionController extends Controller
{
    /** Список подключений бота (JSON, для flow-редактора). */
    public function index(Bot $bot): JsonResponse
    {
        $this->authorize('view', $bot);

        return response()->json([
            'connections' => $bot->connections()->get()->map->toApiArray()->values(),
        ]);
    }

    /** Страница создания нового подключения. */
    public function create(Bot $bot): Response
    {
        $this->authorize('update', $bot);

        return Inertia::render('Bots/Connections/Edit', [
            'bot' => $bot,
            'connection' => null,
            'can' => [
                'update' => request()->user()->can('update', $bot),
            ],
        ]);
    }

    /** Страница редактирования подключения. */
    public function edit(Bot $bot, BotConnection $connection): Response
    {
        $this->authorize('view', $bot);
        abort_if($connection->bot_id !== $bot->id, 404);

        return Inertia::render('Bots/Connections/Edit', [
            'bot' => $bot,
            'connection' => $connection,
            'can' => [
                'update' => request()->user()->can('update', $connection),
            ],
        ]);
    }

    /** Создать подключение. */
    public function store(StoreBotConnectionRequest $request, Bot $bot): RedirectResponse
    {
        $this->authorize('update', $bot);

        $bot->connections()->create($request->validated());

        return redirect()->route('bots.edit', $bot);
    }

    /** Изменить подключение. */
    public function update(
        UpdateBotConnectionRequest $request,
        Bot $bot,
        BotConnection $connection,
    ): RedirectResponse {
        $this->authorize('update', $connection);
        abort_if($connection->bot_id !== $bot->id, 404);

        $payload = $request->validated();
        $payload['auth_config'] = $this->mergeSecrets($connection, $payload['auth_config'] ?? null);

        $connection->update($payload);

        return back();
    }

    /** Удалить подключение. */
    public function destroy(Bot $bot, BotConnection $connection): RedirectResponse
    {
        $this->authorize('delete', $connection);
        abort_if($connection->bot_id !== $bot->id, 404);

        $connection->delete();

        return redirect()->route('bots.edit', $bot);
    }

    /** Если поле секрета пришло пустым — сохранить старое значение.
     *
     * @param  array<string, mixed>|null  $incoming
     * @return array<string, mixed>|null
     */
    private function mergeSecrets(BotConnection $connection, ?array $incoming): ?array
    {
        if ($incoming === null) {
            return $connection->auth_config;
        }

        $existing = $connection->auth_config ?? [];

        foreach (['token', 'password', 'value'] as $secretKey) {
            if (array_key_exists($secretKey, $incoming) && ($incoming[$secretKey] === '' || $incoming[$secretKey] === null)) {
                $incoming[$secretKey] = $existing[$secretKey] ?? null;
            }
        }

        return $incoming;
    }
}
