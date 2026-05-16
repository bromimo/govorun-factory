<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Models\BotConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreBotConnectionRequest;
use App\Http\Requests\UpdateBotConnectionRequest;

/** CRUD подключений бота к внешним API. */
class BotConnectionController extends Controller
{
    /** Список подключений бота. */
    public function index(Request $request, Bot $bot): Response|JsonResponse
    {
        $this->authorize('view', $bot);

        $connections = $bot->connections()->with('bot')->latest()->get()
            ->map(fn (BotConnection $c) => $this->serialize($c));

        if ($request->wantsJson()) {
            return response()->json(['connections' => $connections]);
        }

        return Inertia::render('Bots/Connections/Index', [
            'bot' => $bot,
            'connections' => $connections,
        ]);
    }

    /** Создать подключение. */
    public function store(StoreBotConnectionRequest $request, Bot $bot): RedirectResponse
    {
        $this->authorize('update', $bot);

        $bot->connections()->create($request->validated());

        return redirect()->route('bot-connections.index', $bot);
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

        return redirect()->route('bot-connections.index', $bot);
    }

    /** Удалить подключение. */
    public function destroy(Bot $bot, BotConnection $connection): RedirectResponse
    {
        $this->authorize('delete', $connection);
        abort_if($connection->bot_id !== $bot->id, 404);

        $connection->delete();

        return redirect()->route('bot-connections.index', $bot);
    }

    /** Сериализация подключения с маской секретов.
     *
     * @return array<string, mixed>
     */
    private function serialize(BotConnection $connection): array
    {
        $auth = $connection->auth_config ?? [];

        $secret = $auth['token'] ?? $auth['value'] ?? $auth['password'] ?? null;
        $preview = $secret ? '••••'.mb_substr($secret, -4) : null;

        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'slug' => $connection->slug,
            'base_url' => $connection->base_url,
            'auth_type' => $connection->auth_type->value,
            'auth_config' => $this->maskSecrets($auth),
            'auth_config_preview' => $preview,
            'default_headers' => $connection->default_headers ?? [],
            'created_at' => $connection->created_at,
            'updated_at' => $connection->updated_at,
        ];
    }

    /** Заменить значения секретов на маски (для отдачи в UI).
     *
     * @param  array<string, mixed>  $auth
     * @return array<string, mixed>
     */
    private function maskSecrets(array $auth): array
    {
        foreach (['token', 'password', 'value'] as $secretKey) {
            if (! empty($auth[$secretKey])) {
                $auth[$secretKey] = '••••'.mb_substr($auth[$secretKey], -4);
            }
        }

        return $auth;
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
