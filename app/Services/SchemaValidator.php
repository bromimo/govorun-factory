<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotFlow;
use App\Enums\RouteType;
use App\Models\BotRoute;
use App\Enums\HandlerType;
use App\Enums\EntityStatus;
use App\Models\BotConnection;
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

    /** Валидировать схему бота. Проверяются только активные сущности (status=active).
     */
    public function validate(): ValidationResult
    {
        $this->bot->load(['routes' => fn ($q) => $q->with('children'), 'flows']);
        $errors = [];

        $activeRoutes = $this->bot->routes->filter(
            fn ($r) => $r->status === EntityStatus::Active
        );
        if ($activeRoutes->isEmpty()) {
            $errors[] = 'Бот должен иметь хотя бы один активный маршрут';
        }

        $messengerConfig = $this->bot->messenger_config ?? [];
        if (empty($messengerConfig)) {
            $errors[] = 'Необходимо настроить хотя бы один мессенджер';
        }

        foreach ($this->bot->flows as $flow) {
            if ($flow->status !== EntityStatus::Active) {
                continue;
            }
            $this->validateFlow($flow, $errors);
        }

        foreach ($this->bot->routes as $route) {
            if ($route->status !== EntityStatus::Active) {
                continue;
            }
            $this->validateRoute($route, $errors);
        }

        return new ValidationResult($errors);
    }

    /** Валидировать один маршрут (для endpoint'а смены статуса и авто-drop в draft).
     * @param  BotRoute  $route  Маршрут для проверки.
     */
    public function validateSingleRoute(BotRoute $route): ValidationResult
    {
        $errors = [];
        $route->loadMissing('children', 'flow');
        $this->validateRoute($route, $errors);

        return new ValidationResult($errors);
    }

    /** Валидировать один диалог (для endpoint'а смены статуса и авто-drop в draft).
     * @param  BotFlow  $flow  Диалог для проверки.
     */
    public function validateSingleFlow(BotFlow $flow): ValidationResult
    {
        $errors = [];
        $this->validateFlow($flow, $errors);

        return new ValidationResult($errors);
    }

    /** Проверить flow.
     * @param  BotFlow  $flow  Flow для проверки.
     * @param  array<int, string>  $errors  Список ошибок (по ссылке).
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
            } elseif ($type === 'api_call') {
                $this->validateApiCall($node, $flow->graph['edges'] ?? [], $where, $errors);
            }
        }
    }

    /** Проверить route.
     * @param  BotRoute  $route  Маршрут для проверки.
     * @param  array<int, string>  $errors  Список ошибок (по ссылке).
     */
    private function validateRoute(BotRoute $route, array &$errors): void
    {
        $label = $route->match ? "«{$route->match}»" : "#{$route->id}";

        $isParentPhrase = is_null($route->parent_id)
            && $route->type === RouteType::Phrase
            && $route->children->isNotEmpty();

        if (! $isParentPhrase) {
            if ($route->handler_type === HandlerType::Flow) {
                if (empty($route->flow_id)) {
                    $errors[] = "Маршрут {$label}: не выбран диалог (handler_type = flow)";
                }
            } else {
                if (empty($route->handler_schema['blocks'] ?? [])) {
                    $errors[] = "Маршрут {$label}: нет ни одного блока в контроллере";
                }
            }
        }

        $blocks = $route->handler_schema['blocks'] ?? [];

        foreach ($blocks as $index => $block) {
            $type = $block['type'] ?? '';
            $blockNum = $index + 1;
            $where = "Маршрут {$label}, блок #{$blockNum}";

            if (in_array($type, self::LEGACY_BLOCK_TYPES, true)) {
                $errors[] = "Тип блока «{$type}» больше не поддерживается. Откройте маршрут в редакторе для миграции.";

                continue;
            }

            if ($type === 'reply') {
                $this->validateReply($block['params'] ?? [], $where, $errors);
            }
        }

        if ($route->handler_type === HandlerType::Flow && $route->flow_id) {
            $flow = $route->flow ?? BotFlow::find($route->flow_id);
            if ($flow && $flow->status !== EntityStatus::Active) {
                $errors[] = "Маршрут {$label}: ссылается на неактивный диалог «{$flow->name}»";
            }
        }
    }

    /** Валидировать данные ask-блока.
     * @param  array<string, mixed>  $data  Данные узла.
     * @param  string  $where  Контекст для сообщений об ошибках.
     * @param  array<int, string>  $errors  Список ошибок (по ссылке).
     */
    private function validateAsk(array $data, string $where, array &$errors): void
    {
        $mode = $data['mode'] ?? null;

        if (! in_array($mode, ['text', 'callback'], true)) {
            $errors[] = "{$where}: ask должен иметь mode = 'text' или 'callback'";

            return;
        }

        $hasText = trim((string) ($data['text'] ?? '')) !== '';
        $hasMedia = ! empty($data['media']);

        if (! $hasText && ! $hasMedia) {
            $errors[] = "{$where}: ask должен содержать text или media";
        }

        if ($hasMedia) {
            $this->validateMedia($data['media'], $where, $errors);
        }

        $this->validateTextHtml($data['text'] ?? null, $where, $errors);

        if ($mode === 'callback') {
            $kb = $data['keyboard'] ?? null;
            if (empty($kb) || empty($kb['buttons'] ?? [])) {
                $errors[] = "{$where}: ask в режиме callback должен иметь непустую клавиатуру";
            }
            $this->validateKeyboard($data['keyboard'] ?? [], $where, $errors);
        } elseif ($mode === 'text') {
            if (! empty($data['keyboard'])) {
                $errors[] = "{$where}: ask в режиме text не должен иметь клавиатуру";
            }
        }
    }

    /** Валидировать данные reply-блока.
     * @param  array<string, mixed>  $data  Параметры блока.
     * @param  string  $where  Контекст для сообщений об ошибках.
     * @param  array<int, string>  $errors  Список ошибок (по ссылке).
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

        $this->validateTextHtml($data['text'] ?? null, $where, $errors);
        $this->validateKeyboard($data['keyboard'] ?? [], $where, $errors);
    }

    /** Валидировать HTML в поле text блока.
     * @param  mixed  $text  Значение поля text.
     * @param  string  $where  Контекст для сообщений.
     * @param  array<int, string>  $errors  Список ошибок (по ссылке).
     */
    private function validateTextHtml(mixed $text, string $where, array &$errors): void
    {
        if ($text === null || trim((string) $text) === '') {
            return;
        }

        $str = (string) $text;
        if (TelegramHtml::sanitize($str) !== $str) {
            $errors[] = "{$where}: text содержит неподдерживаемый Telegram HTML тег/атрибут";
        }
    }

    /** Валидировать структуру media.
     * @param  mixed  $media  Объект media (ожидается массив с type и url).
     * @param  string  $where  Контекст для сообщений об ошибках.
     * @param  array<int, string>  $errors  Список ошибок (по ссылке).
     */
    private function validateMedia(mixed $media, string $where, array &$errors): void
    {
        if (! is_array($media)) {
            $errors[] = "{$where}: media должен быть объектом";

            return;
        }

        $type = $media['type'] ?? null;
        $url = $media['url'] ?? null;
        $mediaId = $media['media_id'] ?? null;

        if (! in_array($type, self::ALLOWED_MEDIA_TYPES, true)) {
            $errors[] = "{$where}: тип медиа «{$type}» не поддерживается";
        }

        if (empty($mediaId) && empty(trim((string) $url))) {
            $errors[] = "{$where}: media должен иметь непустой url или media_id";
        }
    }

    /** Найти дублирующиеся имена ask-шагов.
     * @param  array<int, array<string, mixed>>  $nodes  Список узлов flow.
     * @return array<int, string>
     */
    private function findDuplicateStepNames(array $nodes): array
    {
        $names = FlowGenerator::resolveAskStepNames($nodes);
        $counts = array_count_values($names);

        return array_keys(array_filter($counts, fn (int $c) => $c > 1));
    }

    /** Проверить api_call-узел.
     * @param  array<string, mixed>  $node  Узел графа.
     * @param  array<int, array<string, mixed>>  $edges  Все рёбра flow.
     * @param  string  $where  Описание места ошибки.
     * @param  array<int, string>  $errors  Список ошибок (по ссылке).
     */
    private function validateApiCall(array $node, array $edges, string $where, array &$errors): void
    {
        $data = $node['data'] ?? [];

        if (empty($data['connection_id'])) {
            $errors[] = "{$where}: не выбрано подключение";

            return;
        }

        if (! BotConnection::find($data['connection_id'])) {
            $errors[] = "{$where}: подключение удалено";
        }

        if (trim((string) ($data['path'] ?? '')) === '') {
            $errors[] = "{$where}: не задан путь запроса";
        }

        $allowed = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        if (! in_array($data['method'] ?? '', $allowed, true)) {
            $errors[] = "{$where}: неподдерживаемый HTTP-метод";
        }

        $stateKeys = [];

        foreach ($data['response_mapping'] ?? [] as $idx => $m) {
            if (empty($m['json_path'])) {
                $errors[] = "{$where}: маппинг {$idx} без json_path";
            }

            $key = $m['state_key'] ?? '';

            if (! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
                $errors[] = "{$where}: state_key «{$key}» должен быть в snake_case";
            }

            if (in_array($key, $stateKeys, true)) {
                $errors[] = "{$where}: state_key «{$key}» дублируется";
            }

            $stateKeys[] = $key;
        }

        if (($data['on_error'] ?? '') === 'branch') {
            $hasOnError = collect($edges)->contains(
                fn ($e) => ($e['source'] ?? '') === ($node['id'] ?? '') && ($e['sourceHandle'] ?? null) === 'on_error',
            );

            if (! $hasOnError) {
                $errors[] = "{$where}: режим on_error=branch требует исходящего ребра с handle on_error";
            }
        }
    }

    /** Проверить клавиатуру под лимиты WhatsApp (если он включён).
     * @param array $keyboard Структура клавиатуры из ноды/блока
     * @param string $where Человекочитаемое место (для текста ошибки)
     * @param array<int, string> $errors Накопитель ошибок (по ссылке)
     * @return void
     */
    private function validateKeyboard(array $keyboard, string $where, array &$errors): void
    {
        if (! $this->isWhatsAppEnabled()) {
            return;
        }

        $buttons = [];
        foreach ($keyboard['buttons'] ?? [] as $row) {
            foreach ((array) $row as $btn) {
                $buttons[] = $btn;
            }
        }

        $count = count($buttons);
        if ($count === 0) {
            return;
        }

        if ($count > 10) {
            $errors[] = "{$where}: WhatsApp поддерживает не более 10 кнопок в клавиатуре (сейчас {$count}).";
        }

        $maxTitle = $count <= 3 ? 20 : 24;
        foreach ($buttons as $btn) {
            $type = $btn['type'] ?? 'action';
            $label = (string) ($btn['label'] ?? '');

            if ($type === 'url') {
                $errors[] = "{$where}: WhatsApp не поддерживает URL-кнопки в интерактивных сообщениях (кнопка «{$label}»).";
            }

            if ($type === 'contact' || $type === 'location') {
                $errors[] = "{$where}: WhatsApp не поддерживает кнопки запроса контакта/локации (кнопка «{$label}»).";
            }

            if (mb_strlen($label) > $maxTitle) {
                $errors[] = "{$where}: подпись кнопки «{$label}» превышает {$maxTitle} символов (лимит WhatsApp).";
            }
        }
    }

    /** Включён ли WhatsApp у бота.
     * @return bool Включён ли
     */
    private function isWhatsAppEnabled(): bool
    {
        return ($this->bot->messenger_config['whatsapp']['enabled'] ?? false) === true;
    }

    /** Тестовая обёртка для изолированной проверки правил клавиатуры WhatsApp.
     * @param array $keyboard Структура клавиатуры
     * @param string $where Место
     * @return ValidationResult Результат
     */
    public function validateKeyboardForTest(array $keyboard, string $where): ValidationResult
    {
        $errors = [];
        $this->validateKeyboard($keyboard, $where, $errors);

        return new ValidationResult($errors);
    }
}
