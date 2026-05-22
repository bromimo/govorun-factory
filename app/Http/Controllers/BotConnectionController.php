<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotConnection;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreBotConnectionRequest;
use App\Http\Requests\UpdateBotConnectionRequest;

/** CRUD подключений бота к внешним API. */
class BotConnectionController extends Controller
{
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

        return redirect()->route('bots.edit', $bot);
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
