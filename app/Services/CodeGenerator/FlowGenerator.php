<?php

namespace App\Services\CodeGenerator;

use Illuminate\Support\Collection;

/** Генератор классов Flow из графа диалога (step-based архитектура).
 * Один step-метод — одна ask-нода. Ветки condition генерируют match с явным
 * $this->nextStep('name') / $this->completeFlow(). Схождения веток выделяются
 * в приватные tail-методы.
 */
class FlowGenerator
{
    /** @var array<string, string> Дефолтные сообщения валидации на уровне бота. */
    private array $validationMessages = [];

    /** @var Collection<int, array<string, mixed>> Коллекция всех нод для поиска по id. */
    private Collection $nodes;

    /** @var array<string, array<int, string>> Карта adjacency: nodeId → список targetId. */
    private array $adjacency = [];

    /** @var array<string, string> Метки ребёр: "source->target" → label. */
    private array $edgeLabels = [];

    /** @var array<string, string> Имена ask-шагов: nodeId → stepName. */
    private array $askStepNames = [];

    /** @var array<string, string> Имена tail-методов: nodeId → tailName. */
    private array $tailNames = [];

    /** Сгенерировать класс Flow.
     * @param  array<string, mixed>  $graph
     * @param  array<string>  $interruptCommands
     * @param  array<string, string>  $validationMessages
     */
    public function generate(string $className, array $graph, array $interruptCommands, bool $interruptOnEvent, array $validationMessages = []): string
    {
        $this->validationMessages = $validationMessages;
        $this->nodes = collect($graph['nodes'] ?? []);
        $edges = collect($graph['edges'] ?? []);

        $this->buildAdjacency($edges);
        $this->askStepNames = self::resolveAskStepNames($this->nodes->all());
        $this->detectTails($edges);

        [$orderedAskIds, $onCompleteStart, $onCancelStart] = $this->analyzeStructure();

        $stepsList = collect($orderedAskIds)
            ->map(fn (string $id) => "'{$this->askStepNames[$id]}'")
            ->implode(', ');

        $stepMethods = '';
        foreach ($orderedAskIds as $askId) {
            $stepMethods .= $this->renderStepMethod($askId);
        }

        $tailMethods = '';
        foreach ($this->tailNames as $nodeId => $tailName) {
            $tailMethods .= $this->renderTailMethod($nodeId, $tailName);
        }

        $onCompleteMethod = $onCompleteStart
            ? $this->renderLifecycleMethod('onComplete', $this->buildLifecycleBody($onCompleteStart))
            : '';

        $onCancelMethod = $onCancelStart
            ? $this->renderLifecycleMethod('onCancel', $this->buildLifecycleBody($onCancelStart))
            : '';

        return "<?php\n\n".view('stubs.flow', [
            'className' => $className,
            'interruptCommands' => $interruptCommands,
            'interruptOnEvent' => $interruptOnEvent,
            'stepsList' => $stepsList,
            'stepMethods' => $stepMethods.$tailMethods,
            'onCompleteMethod' => $onCompleteMethod,
            'onCancelMethod' => $onCancelMethod,
        ])->render();
    }

    /** Разрешить имена ask-steps для всех ask-нод flow.
     * Источник имени: data.stepName (если задано и не пустое) либо автоген «ask + PascalCase(транслит(text))».
     * Возвращает map nodeId → stepName.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<string, string>
     */
    public static function resolveAskStepNames(array $nodes): array
    {
        $result = [];
        $fallbackCounter = 1;

        foreach ($nodes as $node) {
            if (! in_array($node['type'] ?? null, ['ask_text', 'ask_keyboard'], true)) {
                continue;
            }

            $explicit = trim((string) ($node['data']['stepName'] ?? ''));
            if ($explicit !== '') {
                $result[$node['id']] = $explicit;

                continue;
            }

            $text = (string) ($node['data']['text'] ?? '');
            $auto = self::autoStepName($text);
            if ($auto === '') {
                $auto = 'askStep'.$fallbackCounter;
                $fallbackCounter++;
            }
            $result[$node['id']] = $auto;
        }

        return $result;
    }

    /** Автоген имени шага: «ask» + PascalCase(транслит(text)). Пустую строку возвращает при пустом text. */
    private static function autoStepName(string $text): string
    {
        $ascii = self::transliterate($text);
        $words = preg_split('/[^a-zA-Z0-9]+/', $ascii, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($words)) {
            return '';
        }

        $pascal = implode('', array_map(
            fn (string $w) => ucfirst(strtolower($w)),
            $words,
        ));

        return 'ask'.$pascal;
    }

    /** Транслитерация кириллицы в латиницу (ISO-9 упрощённая). */
    private static function transliterate(string $str): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];

        return strtr($str, $map);
    }

    /** Построить adjacency и edgeLabels. */
    private function buildAdjacency(Collection $edges): void
    {
        $this->adjacency = [];
        $this->edgeLabels = [];
        foreach ($edges as $edge) {
            $this->adjacency[$edge['source']][] = $edge['target'];
            if (! empty($edge['label'])) {
                $this->edgeLabels[$edge['source'].'->'.$edge['target']] = $edge['label'];
            }
        }
    }

    /** Обнаружить ноды-схождения (in-degree > 1) и назначить им tail-имена.
     * Исключаем типы, которые сами по себе являются точками останова (ask_*, on_*, start, condition).
     */
    private function detectTails(Collection $edges): void
    {
        $this->tailNames = [];
        $inDegree = [];
        foreach ($edges as $edge) {
            $inDegree[$edge['target']] = ($inDegree[$edge['target']] ?? 0) + 1;
        }

        $excluded = ['ask_text', 'ask_keyboard', 'on_complete', 'on_cancel', 'start', 'condition'];
        $counter = 1;

        foreach ($this->nodes as $node) {
            $id = $node['id'];
            $type = $node['type'] ?? '';
            if (in_array($type, $excluded, true)) {
                continue;
            }
            if (($inDegree[$id] ?? 0) > 1) {
                $this->tailNames[$id] = 'tail'.$counter;
                $counter++;
            }
        }
    }

    /** Анализ структуры: упорядоченные ask-ноды, стартовые ноды onComplete / onCancel.
     * @return array{0: list<string>, 1: ?string, 2: ?string}
     */
    private function analyzeStructure(): array
    {
        $targetIds = collect($this->adjacency)->flatten()->unique()->values();
        $startId = $this->nodes->pluck('id')->diff($targetIds)->first();

        if (! $startId) {
            return [[], null, null];
        }

        $startNode = $this->nodes->firstWhere('id', $startId);
        if (! $startNode || ($startNode['type'] ?? '') !== 'start') {
            return [[], null, null];
        }

        $orderedAsks = [];
        $visited = [];
        $stack = [$startId];

        while ($stack) {
            $nodeId = array_pop($stack);
            if (in_array($nodeId, $visited, true)) {
                continue;
            }
            $visited[] = $nodeId;

            $node = $this->nodes->firstWhere('id', $nodeId);
            if (! $node) {
                continue;
            }

            if (in_array($node['type'], ['ask_text', 'ask_keyboard'], true)
                && ! in_array($nodeId, $orderedAsks, true)
            ) {
                $orderedAsks[] = $nodeId;
            }

            $targets = $this->adjacency[$nodeId] ?? [];
            foreach (array_reverse($targets) as $t) {
                if (! in_array($t, $visited, true)) {
                    $stack[] = $t;
                }
            }
        }

        $onCompleteNode = $this->nodes->firstWhere('type', 'on_complete');
        $onCancelNode = $this->nodes->firstWhere('type', 'on_cancel');

        return [
            $orderedAsks,
            $onCompleteNode['id'] ?? null,
            $onCancelNode['id'] ?? null,
        ];
    }

    /** Отрендерить step-метод для ask-ноды. */
    private function renderStepMethod(string $askId): string
    {
        $node = $this->nodes->firstWhere('id', $askId);
        $name = $this->askStepNames[$askId];
        $askCode = $this->renderAskCode($node);
        $validation = $node['data']['validation'] ?? [];

        $isKeyboard = $node['type'] === 'ask_keyboard';
        $validationCode = ! empty($validation)
            ? $this->renderValidationChain($validation, 3, $isKeyboard)
            : '';

        $firstDownstream = $this->adjacency[$askId][0] ?? null;
        $receiveBody = $firstDownstream !== null
            ? $this->buildSequence($firstDownstream, 3)
            : $this->indent('$this->completeFlow(); return;', 3)."\n";

        $code = "\n    public function {$name}Step(Step \$step): void\n";
        $code .= "    {\n";
        $code .= $askCode;
        $code .= "        \$step->receive(function (IncomingMessage \$message) {\n";
        $code .= $validationCode;
        $code .= $receiveBody;
        $code .= "        });\n";
        $code .= "    }\n";

        return $code;
    }

    /** Отрендерить tail-метод — action самой tail-ноды + продолжение. */
    private function renderTailMethod(string $nodeId, string $tailName): string
    {
        $node = $this->nodes->firstWhere('id', $nodeId);
        $actionCode = $this->renderActionBlock($node['type'], $node['data'] ?? [], 2);
        $next = $this->adjacency[$nodeId][0] ?? null;
        $continuation = $next !== null
            ? $this->buildSequence($next, 2)
            : $this->indent('$this->completeFlow(); return;', 2)."\n";

        $code = "\n    private function {$tailName}(): void\n";
        $code .= "    {\n";
        $code .= $actionCode;
        $code .= $continuation;
        $code .= "    }\n";

        return $code;
    }

    /** Построить код для lifecycle-метода (onComplete/onCancel).
     * Начинаем с первой downstream-ноды узла on_* — она сама по себе пустая.
     */
    private function buildLifecycleBody(string $lifecycleNodeId): string
    {
        $first = $this->adjacency[$lifecycleNodeId][0] ?? null;
        if ($first === null) {
            return '';
        }

        $code = '';
        $cursor = $first;
        $visited = [];
        while ($cursor !== null && ! in_array($cursor, $visited, true)) {
            $visited[] = $cursor;
            $node = $this->nodes->firstWhere('id', $cursor);
            if (! $node) {
                break;
            }
            $type = $node['type'];
            if (in_array($type, ['ask_text', 'ask_keyboard', 'condition', 'on_complete', 'on_cancel'], true)) {
                break;
            }
            $code .= $this->renderActionBlock($type, $node['data'] ?? [], 2);
            $cursor = $this->adjacency[$cursor][0] ?? null;
        }

        return $code;
    }

    /** Построить код последовательности шагов графа начиная с $nodeId.
     * Возвращает готовый блок с отступами, уже завершающийся соответствующим `return;`.
     */
    private function buildSequence(string $nodeId, int $indent): string
    {
        $code = '';
        $cursor = $nodeId;

        while ($cursor !== null) {
            $node = $this->nodes->firstWhere('id', $cursor);
            if (! $node) {
                $code .= $this->indent('$this->completeFlow(); return;', $indent)."\n";

                return $code;
            }

            $type = $node['type'];

            if (isset($this->tailNames[$cursor])) {
                $tailName = $this->tailNames[$cursor];
                $code .= $this->indent("\$this->{$tailName}(); return;", $indent)."\n";

                return $code;
            }

            if (in_array($type, ['ask_text', 'ask_keyboard'], true)) {
                $stepName = $this->askStepNames[$cursor];
                $code .= $this->indent("\$this->nextStep('{$stepName}'); return;", $indent)."\n";

                return $code;
            }

            if ($type === 'on_complete') {
                $code .= $this->indent('$this->completeFlow(); return;', $indent)."\n";

                return $code;
            }

            if ($type === 'on_cancel') {
                $code .= $this->indent('$this->onCancel(); return;', $indent)."\n";

                return $code;
            }

            if ($type === 'condition') {
                $code .= $this->renderConditionBlock($cursor, $node['data'] ?? [], $indent);
                $code .= $this->indent('return;', $indent)."\n";

                return $code;
            }

            $code .= $this->renderActionBlock($type, $node['data'] ?? [], $indent);
            $cursor = $this->adjacency[$cursor][0] ?? null;
        }

        $code .= $this->indent('$this->completeFlow(); return;', $indent)."\n";

        return $code;
    }

    /** Отрендерить condition внутри sequence. */
    private function renderConditionBlock(string $conditionId, array $data, int $indent): string
    {
        $pad = str_repeat('    ', $indent);
        $fieldExpr = $this->renderSourceAccessor($data['field'] ?? 'message.action');

        $code = "{$pad}match ({$fieldExpr}) {\n";

        $targets = $this->adjacency[$conditionId] ?? [];
        foreach ($targets as $targetId) {
            $label = $this->edgeLabels[$conditionId.'->'.$targetId] ?? null;
            if ($label === null) {
                continue;
            }
            $branchCode = $this->buildBranchExpr($targetId);
            $code .= "{$pad}    '{$label}' => (function () { {$branchCode} })(),\n";
        }

        $code .= "{$pad}    default => null,\n";
        $code .= "{$pad}};\n";

        return $code;
    }

    /** Построить однострочный branch для match: либо вызов tail, либо nextStep, либо completeFlow,
     * либо inline-последовательность из нескольких инструкций через сцепку.
     */
    private function buildBranchExpr(string $targetId): string
    {
        $node = $this->nodes->firstWhere('id', $targetId);
        if (! $node) {
            return '$this->completeFlow(); return;';
        }
        $type = $node['type'];

        if (isset($this->tailNames[$targetId])) {
            $tailName = $this->tailNames[$targetId];

            return "\$this->{$tailName}(); return;";
        }

        if (in_array($type, ['ask_text', 'ask_keyboard'], true)) {
            $stepName = $this->askStepNames[$targetId];

            return "\$this->nextStep('{$stepName}'); return;";
        }

        if ($type === 'on_complete') {
            return '$this->completeFlow(); return;';
        }

        $raw = $this->buildSequence($targetId, 0);

        return trim(preg_replace('/\s+/', ' ', $raw));
    }

    /** Отрендерить ask-часть шага. */
    private function renderAskCode(array $node): string
    {
        $data = $node['data'] ?? [];
        $type = $node['type'];

        if ($type === 'ask_text') {
            $text = $this->renderText($data['text'] ?? '');
            $image = trim((string) ($data['image'] ?? ''));
            if ($image !== '') {
                $url = "'".addslashes($image)."'";

                return "        \$step->ask(Media::photo({$url})->caption({$text}));\n";
            }

            return "        \$step->ask({$text});\n";
        }

        if ($type === 'ask_keyboard') {
            $text = $this->renderText($data['text'] ?? '');
            $image = trim((string) ($data['image'] ?? ''));
            $buttons = $data['buttons'] ?? [];

            $messageExpr = $image !== ''
                ? "Media::photo('".addslashes($image)."')->caption({$text})"
                : $text;

            $code = "        \$step->ask({$messageExpr}, fn () => Keyboard::make()\n";
            foreach ($buttons as $button) {
                $label = addslashes($button['label'] ?? '');
                $action = addslashes($button['action'] ?? '');
                $code .= "            ->button('{$label}', '{$action}')\n";
            }
            $code .= "        );\n";

            return $code;
        }

        return '';
    }

    /** Отрендерить action-блок с указанным уровнем отступа. */
    private function renderActionBlock(string $type, array $data, int $indent): string
    {
        $pad = str_repeat('    ', $indent);

        return match ($type) {
            'save_state' => $this->renderSaveState($data, $pad),
            'reply_text' => "{$pad}\$this->reply(".$this->renderText($data['text'] ?? '').");\n",
            'reply_keyboard' => $this->renderReplyKeyboard($data, $pad),
            'reply_media' => $this->renderReplyMedia($data, $pad),
            'api_call' => "{$pad}\$response = \$this->apiCall('".($data['method'] ?? 'GET')."', '".addslashes($data['url'] ?? '')."');\n",
            default => "{$pad}// Unknown block: {$type}\n",
        };
    }

    /** Отрендерить reply_media — поддерживается только photo. */
    private function renderReplyMedia(array $data, string $pad): string
    {
        $type = $data['media_type'] ?? 'photo';
        if ($type !== 'photo') {
            return "{$pad}// Unsupported media type: {$type}\n";
        }

        $url = "'".addslashes($data['url'] ?? '')."'";
        $caption = trim((string) ($data['caption'] ?? ''));
        $captionChain = $caption !== ''
            ? '->caption('.$this->renderText($caption).')'
            : '';

        return "{$pad}\$this->send(Media::photo({$url}){$captionChain});\n";
    }

    /** Отрендерить reply_keyboard. */
    private function renderReplyKeyboard(array $data, string $pad): string
    {
        $text = $this->renderText($data['text'] ?? '');
        $buttons = $data['buttons'] ?? [];

        $code = "{$pad}\$this->send(\n";
        $code .= "{$pad}    Message::make({$text})\n";
        $code .= "{$pad}        ->keyboard(Keyboard::make()\n";
        foreach ($buttons as $button) {
            $label = addslashes($button['label'] ?? '');
            $action = addslashes($button['action'] ?? '');
            $code .= "{$pad}            ->button('{$label}', '{$action}')\n";
        }
        $code .= "{$pad}        )\n";
        $code .= "{$pad});\n";

        return $code;
    }

    /** Отрендерить save_state — одну или несколько переменных. */
    private function renderSaveState(array $data, string $pad): string
    {
        $variables = $data['variables'] ?? [];

        if (empty($variables) && isset($data['key'])) {
            $variables = [['key' => $data['key'], 'source' => $data['source'] ?? 'message.text']];
        }

        $code = '';
        foreach ($variables as $var) {
            $key = $var['key'] ?? '';
            if ($key === '') {
                continue;
            }

            $accessor = $this->renderSourceAccessor($var['source'] ?? 'message.text');

            $code .= "{$pad}\$this->state->set('{$key}', {$accessor});\n";
        }

        return $code;
    }

    /** Отрендерить lifecycle-метод (onComplete / onCancel). */
    private function renderLifecycleMethod(string $name, string $bodyCode): string
    {
        $code = "\n    public function {$name}(): void\n";
        $code .= "    {\n";
        $code .= $bodyCode;
        $code .= "    }\n";

        return $code;
    }

    /** Преобразовать source/field в PHP-выражение для receive-контекста.
     * message.text → $message->text, user.id → $message->user->id, name → $this->state->get('name').
     */
    private function renderSourceAccessor(string $source): string
    {
        $messageSources = ['message.', 'user.'];

        foreach ($messageSources as $prefix) {
            if (str_starts_with($source, $prefix)) {
                $path = $prefix === 'message.' ? substr($source, 8) : $source;

                return '$message->'.str_replace('.', '->', $path);
            }
        }

        return "\$this->state->get('{$source}')";
    }

    /** Отрендерить текст с интерполяцией переменных {{var}} → $this->state->get('var'). */
    private function renderText(string $text): string
    {
        return CodeHelper::renderText($text);
    }

    /** Сгенерировать fluent-цепочку валидации внутри receive-callback. */
    private function renderValidationChain(array $rules, int $indent, bool $isKeyboard = false): string
    {
        $pad = str_repeat('    ', $indent);
        $subject = $isKeyboard ? '$message->action' : '$message->text';

        $code = "{$pad}if (\$this->validator({$subject})\n";
        foreach ($rules as $rule) {
            $code .= "{$pad}    ".$this->renderValidationRule($rule)."\n";
        }
        $code .= "{$pad}    ->fails()\n";
        $code .= "{$pad}) {\n";
        $code .= "{$pad}    return;\n";
        $code .= "{$pad}}\n\n";

        return $code;
    }

    /** Отрендерить один вызов метода валидации. */
    private function renderValidationRule(array $rule): string
    {
        $name = $rule['name'];
        $params = $rule['params'] ?? [];
        $customMessage = $rule['message'] ?? $this->validationMessages[$name] ?? null;
        $message = $customMessage !== null ? "'".addslashes($customMessage)."'" : null;

        return match ($name) {
            'required', 'string', 'email', 'numeric', 'integer', 'url', 'phone', 'date' => '->'.$name.'('.($message ?? '').')',
            'regex' => "->regex('".addslashes($params[0] ?? '/^.*$/')."'".($message ? ", {$message}" : '').')',
            'min' => '->min('.(int) ($params[0] ?? 0).($message ? ", {$message}" : '').')',
            'max' => '->max('.(int) ($params[0] ?? 255).($message ? ", {$message}" : '').')',
            'between' => '->between('.(int) ($params[0] ?? 0).', '.(int) ($params[1] ?? 255).($message ? ", {$message}" : '').')',
            'in' => '->in(['.implode(', ', array_map(fn ($v) => "'".addslashes($v)."'", $params)).']'.($message ? ", {$message}" : '').')',
            default => "// Unknown rule: {$name}",
        };
    }

    /** Увеличить отступ однострочного выражения. */
    private function indent(string $line, int $indent): string
    {
        return str_repeat('    ', $indent).$line;
    }
}
