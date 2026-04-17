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
}
