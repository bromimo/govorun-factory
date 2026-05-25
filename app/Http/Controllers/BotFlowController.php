<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\BotFlow;
use App\Enums\EntityStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\SchemaValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
            'status' => EntityStatus::Draft->value,
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
        $currentStatus = $flow->status;
        $data = array_diff_key($request->validated(), ['status' => true]);
        $flow->update($data);

        if (in_array($currentStatus, [EntityStatus::Active, EntityStatus::Inactive], true)) {
            $result = (new SchemaValidator($bot))->validateSingleFlow($flow->fresh());
            if (! empty($result->errors)) {
                $flow->update(['status' => EntityStatus::Draft]);
                session()->flash('auto_drafted_reasons', $result->errors);
            }
        }

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

    /** Список маршрутов, которые будут затронуты при смене статуса flow на не-active.
     */
    public function statusImpact(Bot $bot, BotFlow $flow): JsonResponse
    {
        $this->authorize('view', $bot);

        $routes = $bot->routes()
            ->where('flow_id', $flow->id)
            ->whereIn('status', [EntityStatus::Active->value, EntityStatus::Inactive->value])
            ->get(['id', 'type', 'match']);

        return response()->json([
            'affected_routes' => $routes->map(fn ($r) => [
                'id' => $r->id,
                'label' => $r->match ?: $r->type->value,
            ])->values(),
        ]);
    }

    /** Сменить статус flow. Каскадно сбрасывает ссылающиеся маршруты в draft при переходе flow в не-active.
     */
    public function changeStatus(Request $request, Bot $bot, BotFlow $flow): JsonResponse
    {
        $this->authorize('update', $bot);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_column(EntityStatus::cases(), 'value'))],
        ]);

        $target = EntityStatus::from($data['status']);

        if ($target === EntityStatus::Active) {
            $result = (new SchemaValidator($bot))->validateSingleFlow($flow);
            if (! empty($result->errors)) {
                return response()->json(['errors' => $result->errors], 422);
            }
        }

        $affectedCount = 0;

        DB::transaction(function () use ($bot, $flow, $target, &$affectedCount) {
            $flow->update(['status' => $target]);

            if (in_array($target, [EntityStatus::Inactive, EntityStatus::Draft], true)) {
                $affected = $bot->routes()
                    ->where('flow_id', $flow->id)
                    ->whereIn('status', [EntityStatus::Active->value, EntityStatus::Inactive->value])
                    ->get();

                foreach ($affected as $route) {
                    $route->update([
                        'status' => EntityStatus::Draft,
                        'flow_id' => null,
                    ]);
                    $affectedCount++;
                }
            }
        });

        return response()->json([
            'status' => $target->value,
            'affected_routes_count' => $affectedCount,
        ]);
    }
}
