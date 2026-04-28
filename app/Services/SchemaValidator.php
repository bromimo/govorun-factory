<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotRoute;
use App\Services\CodeGenerator\FlowGenerator;

/** Валидатор схемы бота перед экспортом. */
class SchemaValidator
{
    private const ALLOWED_MEDIA_TYPES = ['photo', 'video', 'audio', 'document', 'animation'];

    private const LEGACY_BLOCK_TYPES = [
        'ask_text', 'ask_keyboard', 'reply_text', 'reply_keyboard', 'reply_media',
    ];

    public function __construct(
        private Bot $bot,
    ) {}

    /** Валидировать схему бота.
     * @return ValidationResult
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
            $this->validateFlow($flow, $errors);
        }

        foreach ($this->bot->routes as $route) {
            $this->validateRoute($route, $errors);
        }

        return new ValidationResult($errors);
    }

    /** Проверить flow.
     * @param BotFlow $flow Flow для проверки.
     * @param array<int, string> $errors Список ошибок (по ссылке).
     * @return void
     */
    private function validateFlow(BotFlow $flow, array &$errors): void
    {
        $nodes = $flow->graph['nodes'] ?? [];

        if (empty($nodes)) {
            $errors[] = "Flow «{$flow->name}» не содержит ни одного узла";

            return;
        }

        $hasOnComplete = collect($nodes)->contains(fn ($n) => ($n['type'] ?? '') === 'on_complete');
        if (! $hasOnComplete) {
            $errors[] = "Flow «{$flow->name}» должен содержать узел on_complete";
        }

        foreach ($this->findDuplicateStepNames($nodes) as $name) {
            $errors[] = "Flow «{$flow->name}»: имя шага «{$name}» используется более одного раза";
        }

        foreach ($nodes as $node) {
            $type = $node['type'] ?? '';

            if (in_array($type, self::LEGACY_BLOCK_TYPES, true)) {
                $errors[] = "Тип блока «{$type}» больше не поддерживается. Откройте flow в редакторе для миграции.";

                continue;
            }

            $where = "Flow «{$flow->name}», узел «{$node['id']}»";

            if ($type === 'ask') {
                $this->validateAsk($node['data'] ?? [], $where, $errors);
            } elseif ($type === 'reply') {
                $this->validateReply($node['data'] ?? [], $where, $errors);
            }
        }
    }

    /** Проверить route.
     * @param BotRoute $route Маршрут для проверки.
     * @param array<int, string> $errors Список ошибок (по ссылке).
     * @return void
     */
    private function validateRoute(BotRoute $route, array &$errors): void
    {
        $blocks = $route->handler_schema['blocks'] ?? [];

        foreach ($blocks as $index => $block) {
            $type = $block['type'] ?? '';
            $blockNum = $index + 1;
            $where = "Маршрут «{$route->match}», блок #{$blockNum}";

            if (in_array($type, self::LEGACY_BLOCK_TYPES, true)) {
                $errors[] = "Тип блока «{$type}» больше не поддерживается. Откройте маршрут в редакторе для миграции.";

                continue;
            }

            if ($type === 'reply') {
                $this->validateReply($block['params'] ?? [], $where, $errors);
            }
        }
    }

    /** Валидировать данные ask-блока.
     * @param array<string, mixed> $data Данные узла.
     * @param string $where Контекст для сообщений об ошибках.
     * @param array<int, string> $errors Список ошибок (по ссылке).
     * @return void
     */
    private function validateAsk(array $data, string $where, array &$errors): void
    {
        $mode = $data['mode'] ?? null;

        if (! in_array($mode, ['text', 'callback'], true)) {
            $errors[] = "{$where}: ask должен иметь mode = 'text' или 'callback'";

            return;
        }

        if (empty(trim((string) ($data['stepName'] ?? '')))) {
            $errors[] = "{$where}: ask должен иметь непустой stepName";
        }

        $hasText = trim((string) ($data['text'] ?? '')) !== '';
        $hasMedia = ! empty($data['media']);

        if (! $hasText && ! $hasMedia) {
            $errors[] = "{$where}: ask должен содержать text или media";
        }

        if ($hasMedia) {
            $this->validateMedia($data['media'], $where, $errors);
        }

        if ($mode === 'callback') {
            $kb = $data['keyboard'] ?? null;
            if (empty($kb) || empty($kb['buttons'] ?? [])) {
                $errors[] = "{$where}: ask в режиме callback должен иметь непустую клавиатуру";
            }
        } elseif ($mode === 'text') {
            if (! empty($data['keyboard'])) {
                $errors[] = "{$where}: ask в режиме text не должен иметь клавиатуру";
            }
        }
    }

    /** Валидировать данные reply-блока.
     * @param array<string, mixed> $data Параметры блока.
     * @param string $where Контекст для сообщений об ошибках.
     * @param array<int, string> $errors Список ошибок (по ссылке).
     * @return void
     */
    private function validateReply(array $data, string $where, array &$errors): void
    {
        $hasText = trim((string) ($data['text'] ?? '')) !== '';
        $hasMedia = ! empty($data['media']);

        if (! $hasText && ! $hasMedia) {
            $errors[] = "{$where}: reply должен содержать text или media";
        }

        if ($hasMedia) {
            $this->validateMedia($data['media'], $where, $errors);
        }
    }

    /** Валидировать структуру media.
     * @param mixed $media Объект media (ожидается массив с type и url).
     * @param string $where Контекст для сообщений об ошибках.
     * @param array<int, string> $errors Список ошибок (по ссылке).
     * @return void
     */
    private function validateMedia(mixed $media, string $where, array &$errors): void
    {
        if (! is_array($media)) {
            $errors[] = "{$where}: media должен быть объектом";

            return;
        }

        $type = $media['type'] ?? null;
        $url = $media['url'] ?? null;

        if (! in_array($type, self::ALLOWED_MEDIA_TYPES, true)) {
            $errors[] = "{$where}: тип медиа «{$type}» не поддерживается";
        }

        if (empty(trim((string) $url))) {
            $errors[] = "{$where}: media должен иметь непустой url";
        }
    }

    /** Найти дублирующиеся имена ask-шагов.
     * @param array<int, array<string, mixed>> $nodes Список узлов flow.
     * @return array<int, string>
     */
    private function findDuplicateStepNames(array $nodes): array
    {
        $names = FlowGenerator::resolveAskStepNames($nodes);
        $counts = array_count_values($names);

        return array_keys(array_filter($counts, fn (int $c) => $c > 1));
    }
}
