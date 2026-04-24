<?php

namespace App\Services;

use App\Models\Bot;
use App\Services\CodeGenerator\FlowGenerator;

/** Валидатор схемы бота перед экспортом. */
class SchemaValidator
{
    public function __construct(
        private Bot $bot,
    ) {}

    /** Валидировать схему бота. */
    public function validate(): ValidationResult
    {
        $this->bot->load(['routes', 'flows']);
        $errors = [];

        if ($this->bot->routes->isEmpty()) {
            $errors[] = 'Бот должен иметь хотя бы один маршрут';
        }

        $messengerConfig = $this->bot->messenger_config ?? [];
        if (empty($messengerConfig)) {
            $errors[] = 'Необходимо настроить хотя бы один мессенджер';
        }

        foreach ($this->bot->flows as $flow) {
            $nodes = $flow->graph['nodes'] ?? [];

            if (empty($nodes)) {
                $errors[] = "Flow «{$flow->name}» не содержит ни одного узла";

                continue;
            }

            $hasOnComplete = collect($nodes)->contains(fn ($n) => $n['type'] === 'on_complete');
            if (! $hasOnComplete) {
                $errors[] = "Flow «{$flow->name}» должен содержать узел on_complete";
            }

            $duplicates = $this->findDuplicateStepNames($nodes);
            foreach ($duplicates as $name) {
                $errors[] = "Flow «{$flow->name}»: имя шага «{$name}» используется более одного раза";
            }

            foreach ($nodes as $node) {
                if (! in_array($node['type'] ?? '', ['ask_keyboard', 'reply_keyboard'], true)) {
                    continue;
                }

                if ($this->isLegacyKeyboardFormat($node['data']['buttons'] ?? [])) {
                    $nodeLabel = $node['id'] ?? '—';
                    $errors[] = "Клавиатура в ноде «{$nodeLabel}» flow «{$flow->name}» в устаревшем формате. Откройте и сохраните flow в редакторе, чтобы обновить.";
                }
            }
        }

        foreach ($this->bot->routes as $route) {
            $blocks = $route->handler_schema['blocks'] ?? [];

            foreach ($blocks as $index => $block) {
                if (! in_array($block['type'] ?? '', ['ask_keyboard', 'reply_keyboard'], true)) {
                    continue;
                }

                if ($this->isLegacyKeyboardFormat($block['params']['buttons'] ?? [])) {
                    $blockNum = $index + 1;
                    $errors[] = "Клавиатура в блоке #{$blockNum} маршрута «{$route->match}» в устаревшем формате. Откройте и сохраните маршрут в редакторе, чтобы обновить.";
                }
            }
        }

        return new ValidationResult($errors);
    }

    /** Найти дублирующиеся имена ask-шагов (с учётом автогена).
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, string>
     */
    private function findDuplicateStepNames(array $nodes): array
    {
        $names = FlowGenerator::resolveAskStepNames($nodes);
        $counts = array_count_values($names);

        return array_keys(array_filter($counts, fn (int $c) => $c > 1));
    }

    /** Проверить, что buttons в старом плоском формате (массив объектов вместо массива рядов).
     * Новый формат: Row[], где Row = Button[]; Button — ассоциативный массив с полем label.
     * Старый формат: Button[] — ассоциативный массив как элемент первого уровня.
     *
     * @param  array<int, mixed>  $buttons
     */
    private function isLegacyKeyboardFormat(array $buttons): bool
    {
        if (empty($buttons)) {
            return false;
        }

        $first = $buttons[0] ?? null;

        if (! is_array($first)) {
            return false;
        }

        return array_is_list($first) === false;
    }
}
