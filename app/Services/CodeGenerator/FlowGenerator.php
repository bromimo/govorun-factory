<?php

namespace App\Services\CodeGenerator;

use Illuminate\Support\Collection;

/** Генератор классов Flow из графа диалога (step-based архитектура). */
class FlowGenerator
{
    /** @var array<string, string> Дефолтные сообщения валидации на уровне бота. */
    private array $validationMessages = [];

    /** Сгенерировать класс Flow.
     * @param  array<string, mixed>  $graph
     * @param  array<string>  $interruptCommands
     * @param  array<string, string>  $validationMessages
     */
    public function generate(string $className, array $graph, array $interruptCommands, bool $interruptOnEvent, array $validationMessages = []): string
    {
        $this->validationMessages = $validationMessages;
        $nodes = collect($graph['nodes'] ?? []);
        $edges = collect($graph['edges'] ?? []);

        $analysis = $this->analyzeGraph($nodes, $edges);

        $stepsList = collect($analysis['steps'])
            ->pluck('name')
            ->map(fn ($name) => "'{$name}'")
            ->implode(', ');

        $stepMethods = '';
        foreach ($analysis['steps'] as $step) {
            $stepMethods .= $this->renderStepMethod($step);
        }

        $onCompleteMethod = $analysis['onCompleteCode']
            ? $this->renderLifecycleMethod('onComplete', $analysis['onCompleteCode'])
            : '';

        $onCancelMethod = $analysis['onCancelCode']
            ? $this->renderLifecycleMethod('onCancel', $analysis['onCancelCode'])
            : '';

        return "<?php\n\n".view('stubs.flow', [
            'className' => $className,
            'interruptCommands' => $interruptCommands,
            'interruptOnEvent' => $interruptOnEvent,
            'stepsList' => $stepsList,
            'stepMethods' => $stepMethods,
            'onCompleteMethod' => $onCompleteMethod,
            'onCancelMethod' => $onCancelMethod,
        ])->render();
    }

    /** Анализ графа: выделение шагов и lifecycle-методов.
     * @return array{steps: list<array>, onCompleteCode: string, onCancelCode: string}
     */
    private function analyzeGraph(Collection $nodes, Collection $edges): array
    {
        $adjacency = [];
        $edgeLabels = [];
        foreach ($edges as $edge) {
            $adjacency[$edge['source']][] = $edge['target'];
            if (! empty($edge['label'])) {
                $edgeLabels[$edge['source'].'->'.$edge['target']] = $edge['label'];
            }
        }

        $targetIds = $edges->pluck('target')->unique();
        $startId = $nodes->pluck('id')->diff($targetIds)->first();

        if (! $startId) {
            return ['steps' => [], 'onCompleteCode' => '', 'onCancelCode' => ''];
        }

        $steps = [];
        $onCompleteCode = '';
        $onCancelCode = '';
        $visited = [];

        $this->collectSteps($startId, $nodes, $adjacency, $edgeLabels, $visited, $steps, $onCompleteCode, $onCancelCode);

        $counter = 1;
        foreach ($steps as &$step) {
            $step['name'] = 'step'.$counter;
            $counter++;
        }
        unset($step);

        return [
            'steps' => $steps,
            'onCompleteCode' => $onCompleteCode,
            'onCancelCode' => $onCancelCode,
        ];
    }

    /** Рекурсивный сбор шагов из графа. */
    private function collectSteps(
        string $nodeId,
        Collection $nodes,
        array $adjacency,
        array $edgeLabels,
        array &$visited,
        array &$steps,
        string &$onCompleteCode,
        string &$onCancelCode,
    ): void {
        if (in_array($nodeId, $visited)) {
            return;
        }

        $node = $nodes->firstWhere('id', $nodeId);
        if (! $node) {
            return;
        }

        $type = $node['type'];
        $targets = $adjacency[$nodeId] ?? [];

        if ($type === 'start') {
            $visited[] = $nodeId;
            foreach ($targets as $targetId) {
                $this->collectSteps($targetId, $nodes, $adjacency, $edgeLabels, $visited, $steps, $onCompleteCode, $onCancelCode);
            }

            return;
        }

        if (in_array($type, ['ask_text', 'ask_keyboard'])) {
            $visited[] = $nodeId;

            $stepIndex = count($steps);
            $steps[] = null;

            $receiveCode = '';
            foreach ($targets as $targetId) {
                $receiveCode .= $this->buildReceiveCode($targetId, $nodes, $adjacency, $edgeLabels, $visited, $steps, $onCompleteCode, $onCancelCode, 3);
            }

            $steps[$stepIndex] = [
                'askNode' => $node,
                'receiveCode' => $receiveCode,
            ];

            return;
        }

        if ($type === 'on_complete') {
            $visited[] = $nodeId;
            foreach ($targets as $targetId) {
                $onCompleteCode .= $this->buildLifecycleCode($targetId, $nodes, $adjacency, $visited, 2);
            }

            return;
        }

        if ($type === 'on_cancel') {
            $visited[] = $nodeId;
            foreach ($targets as $targetId) {
                $onCancelCode .= $this->buildLifecycleCode($targetId, $nodes, $adjacency, $visited, 2);
            }

            return;
        }

        $visited[] = $nodeId;
        foreach ($targets as $targetId) {
            $this->collectSteps($targetId, $nodes, $adjacency, $edgeLabels, $visited, $steps, $onCompleteCode, $onCancelCode);
        }
    }

    /** Построить код тела receive-callback. */
    private function buildReceiveCode(
        string $nodeId,
        Collection $nodes,
        array $adjacency,
        array $edgeLabels,
        array &$visited,
        array &$steps,
        string &$onCompleteCode,
        string &$onCancelCode,
        int $indent,
    ): string {
        if (in_array($nodeId, $visited)) {
            return '';
        }

        $node = $nodes->firstWhere('id', $nodeId);
        if (! $node) {
            return '';
        }

        $type = $node['type'];
        $data = $node['data'] ?? [];
        $targets = $adjacency[$nodeId] ?? [];

        if (in_array($type, ['ask_text', 'ask_keyboard', 'on_complete', 'on_cancel'])) {
            $this->collectSteps($nodeId, $nodes, $adjacency, $edgeLabels, $visited, $steps, $onCompleteCode, $onCancelCode);

            return '';
        }

        $visited[] = $nodeId;
        $code = '';

        if ($type === 'condition') {
            $branches = [];
            $defaultBranch = null;

            foreach ($targets as $targetId) {
                $label = $edgeLabels[$nodeId.'->'.$targetId] ?? null;
                $branchCode = $this->buildReceiveCode($targetId, $nodes, $adjacency, $edgeLabels, $visited, $steps, $onCompleteCode, $onCancelCode, $indent + 2);

                if ($label) {
                    $branches[$label] = $branchCode;
                } else {
                    $defaultBranch = $branchCode;
                }
            }

            return $this->renderConditionBlock($data, $branches, $defaultBranch, $indent);
        }

        $code .= $this->renderActionBlock($type, $data, $indent);

        foreach ($targets as $targetId) {
            $code .= $this->buildReceiveCode($targetId, $nodes, $adjacency, $edgeLabels, $visited, $steps, $onCompleteCode, $onCancelCode, $indent);
        }

        return $code;
    }

    /** Построить код для lifecycle-метода (onComplete / onCancel). */
    private function buildLifecycleCode(string $nodeId, Collection $nodes, array $adjacency, array &$visited, int $indent): string
    {
        if (in_array($nodeId, $visited)) {
            return '';
        }
        $visited[] = $nodeId;

        $node = $nodes->firstWhere('id', $nodeId);
        if (! $node) {
            return '';
        }

        $code = $this->renderActionBlock($node['type'], $node['data'] ?? [], $indent);

        foreach ($adjacency[$nodeId] ?? [] as $targetId) {
            $code .= $this->buildLifecycleCode($targetId, $nodes, $adjacency, $visited, $indent);
        }

        return $code;
    }

    /** Отрендерить ask-часть шага. */
    private function renderAskCode(array $node): string
    {
        $data = $node['data'] ?? [];
        $type = $node['type'];

        if ($type === 'ask_text') {
            $text = $this->renderText($data['text'] ?? '');

            return "        \$step->ask({$text});\n";
        }

        if ($type === 'ask_keyboard') {
            $text = $this->renderText($data['text'] ?? '');
            $buttons = $data['buttons'] ?? [];

            $code = "        \$step->ask({$text}, fn () => Keyboard::make()\n";
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
            'api_call' => "{$pad}\$response = \$this->apiCall('".($data['method'] ?? 'GET')."', '".addslashes($data['url'] ?? '')."');\n",
            default => "{$pad}// Unknown block: {$type}\n",
        };
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

    /** Отрендерить condition внутри receive. */
    private function renderConditionBlock(array $data, array $branches, ?string $defaultBranch, int $indent): string
    {
        $pad = str_repeat('    ', $indent);
        $fieldExpr = $this->renderSourceAccessor($data['field'] ?? 'message.action');

        $code = "{$pad}match ({$fieldExpr}) {\n";

        foreach ($branches as $label => $branchCode) {
            $code .= "{$pad}    '{$label}' => (function () {\n";
            $code .= $branchCode;
            $code .= "{$pad}    })(),\n";
        }

        if ($defaultBranch) {
            $code .= "{$pad}    default => (function () {\n";
            $code .= $defaultBranch;
            $code .= "{$pad}    })(),\n";
        }

        $code .= "{$pad}};\n";

        return $code;
    }

    /** Отрендерить метод шага. */
    private function renderStepMethod(array $step): string
    {
        $name = $step['name'];
        $askCode = $this->renderAskCode($step['askNode']);
        $receiveCode = $step['receiveCode'];
        $validation = $step['askNode']['data']['validation'] ?? [];

        $isKeyboard = $step['askNode']['type'] === 'ask_keyboard';
        $validationCode = ! empty($validation)
            ? $this->renderValidationChain($validation, 3, $isKeyboard)
            : '';

        $code = "\n    public function {$name}Step(Step \$step): void\n";
        $code .= "    {\n";
        $code .= $askCode;
        $code .= "        \$step->receive(function (IncomingMessage \$message) {\n";
        $code .= $validationCode;
        $code .= $receiveCode;
        $code .= "            \$this->nextStep();\n";
        $code .= "        });\n";
        $code .= "    }\n";

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
        if (! str_contains($text, '{{')) {
            return "'".addslashes($text)."'";
        }

        $parts = preg_split('/(\{\{\w+\}\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $segments = [];
        foreach ($parts as $part) {
            if (preg_match('/^\{\{(\w+)\}\}$/', $part, $m)) {
                $segments[] = "\$this->state->get('{$m[1]}')";
            } else {
                $segments[] = "'".addslashes($part)."'";
            }
        }

        return implode(' . ', $segments);
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
}
