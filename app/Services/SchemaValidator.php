<?php

namespace App\Services;

use App\Models\Bot;

/** Валидатор схемы бота перед экспортом. */
class SchemaValidator
{
    public function __construct(
        private Bot $bot,
    ) {}

    /** Валидировать схему бота.
     */
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
        }

        return new ValidationResult($errors);
    }
}
