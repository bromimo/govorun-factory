<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotConnection;
use Illuminate\Http\JsonResponse;
use App\Services\Http\RequestDraft;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\TestBotConnectionRequest;
use App\Services\Http\ConnectionRequestService;

/** Endpoint выполнения тестового запроса по подключению из UI редактора. */
class BotConnectionTestController extends Controller
{
    public function __construct(private readonly ConnectionRequestService $service) {}

    public function __invoke(
        TestBotConnectionRequest $request,
        Bot $bot,
        BotConnection $connection,
    ): JsonResponse {
        $this->authorize('update', $connection);
        abort_if($connection->bot_id !== $bot->id, 404);

        $draft = new RequestDraft(
            method: $request->string('method')->upper()->toString(),
            path: $request->string('path')->toString(),
            headers: $request->input('headers', []),
            query: $request->input('query', []),
            bodyMode: $request->string('body_mode')->toString(),
            body: $request->input('body'),
            stateSample: $request->input('state_sample', []),
        );

        $result = $this->service->execute($connection, $draft);

        Log::info('connection.test', [
            'user_id' => $request->user()->id,
            'bot_id' => $bot->id,
            'connection_id' => $connection->id,
            'method' => $draft->method,
            'path' => $draft->path,
            'status' => $result->status,
            'duration_ms' => $result->durationMs,
        ]);

        return response()->json($result->toArray());
    }
}
