<?php

namespace App\Services\CodeGenerator;

use App\Models\Plugin;
use App\Models\BotConnection;
use Illuminate\Support\Facades\Blade;

/** Генератор классов контроллеров из схемы обработчика. */
class ControllerGenerator
{
    /** @var array<int, string> Маппинг media_id → filename. */
    private array $mediaMap = [];

    /** Сгенерировать класс контроллера с одним методом handle().
     * @param  array<string, mixed>  $handlerSchema
     * @param  array<int, string>  $mediaMap
     */
    public function generate(string $className, array $handlerSchema, string $namespace, array $mediaMap = []): string
    {
        $this->mediaMap = $mediaMap;
        $blocks = $handlerSchema['blocks'] ?? [];
        $blockCode = '';

        foreach ($blocks as $block) {
            $blockCode .= $this->indentBlock($this->renderBlock($block['type'], $block['params'] ?? []));
        }

        $imports = $this->detectRequiredImports($blocks);

        $code = "<?php\n\n".view('stubs.controller', [
            'className' => $className,
            'controllerNamespace' => $namespace,
            'blockCode' => $blockCode,
            'useMedia' => $imports['media'],
            'useMessage' => $imports['message'],
            'useKeyboard' => $imports['keyboard'],
            'useButton' => $imports['button'],
        ])->render();

        return CodeHelper::wrapLongLines($code);
    }

    /** Сгенерировать класс контроллера с несколькими методами.
     * @param  array<int, array{name: string, schema: array}>  $methods
     * @param  array<int, string>  $mediaMap
     */
    public function generateWithMethods(string $className, array $methods, string $namespace, array $mediaMap = []): string
    {
        $this->mediaMap = $mediaMap;
        $methodsCode = '';
        $allBlocks = [];

        foreach ($methods as $method) {
            $blocks = $method['schema']['blocks'] ?? [];
            $allBlocks = array_merge($allBlocks, $blocks);
            $blockCode = '';
            foreach ($blocks as $block) {
                $blockCode .= $this->indentBlock($this->renderBlock($block['type'], $block['params'] ?? []));
            }

            $rendered = view('stubs.controller_method', [
                'methodName' => $method['name'],
                'blockCode' => $blockCode,
            ])->render();
            $methodsCode .= '    '.$rendered;
        }

        $imports = $this->detectRequiredImports($allBlocks);

        $code = "<?php\n\n".view('stubs.controller_multi', [
            'className' => $className,
            'controllerNamespace' => $namespace,
            'methodsCode' => $methodsCode,
            'useMedia' => $imports['media'],
            'useMessage' => $imports['message'],
            'useKeyboard' => $imports['keyboard'],
            'useButton' => $imports['button'],
        ])->render();

        return CodeHelper::wrapLongLines($code);
    }

    /** Определить, какие use-импорты нужны в сгенерированном контроллере.
     * Routing — всегда (базовый Controller). Media/Message/Keyboard/Button — опционально.
     *
     * @param  array<int, array{type: string, params?: array}>  $blocks
     * @return array{media: bool, message: bool, keyboard: bool, button: bool}
     */
    private function detectRequiredImports(array $blocks): array
    {
        $useMedia = false;
        $useMessage = false;
        $useKeyboard = false;
        $useButton = false;

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            $params = $block['params'] ?? [];

            if ($type !== 'reply') {
                continue;
            }

            $hasText = trim((string) ($params['text'] ?? '')) !== '';
            $hasMedia = ! empty($params['media']);
            $hasKeyboard = ! empty($params['keyboard']) && ! empty($params['keyboard']['buttons'] ?? []);

            if ($hasMedia) {
                $useMedia = true;
            }

            if ($hasText && ! $hasMedia) {
                $useMessage = true;
            }

            if ($hasKeyboard) {
                $useKeyboard = true;
                $useButton = true;
            }
        }

        return [
            'media' => $useMedia,
            'message' => $useMessage,
            'keyboard' => $useKeyboard,
            'button' => $useButton,
        ];
    }

    /** Отрендерить один блок в PHP-код.
     * @param  array<string, mixed>  $params
     */
    public function renderBlock(string $type, array $params): string
    {
        if ($type === 'api_call') {
            return $this->renderApiCallBlock($params);
        }

        if (isset($params['media']['media_id'])) {
            $params['media']['filename'] = $this->mediaMap[$params['media']['media_id']] ?? 'unknown';
        }

        $stubView = "stubs.blocks.{$type}";

        if (view()->exists($stubView)) {
            return view($stubView, compact('params'))->render()."\n";
        }

        $plugin = Plugin::where('name', $type)->where('active', true)->first();
        if ($plugin && $plugin->php_stub) {
            return Blade::render($plugin->php_stub, compact('params'))."\n";
        }

        return "        // Unknown block type: {$type}\n";
    }

    /** Рендер api_call-блока маршрута. */
    private function renderApiCallBlock(array $params): string
    {
        $connection = BotConnection::find($params['connection_id'] ?? null);

        if (! $connection) {
            return "        // api_call: подключение не найдено\n";
        }

        $onError = $params['on_error'] ?? 'stop_flow';

        $tpl = view('stubs.flow_api_call', [
            'slug' => $connection->slug,
            'method' => strtolower($params['method'] ?? 'get'),
            'pathExpr' => $this->renderStringExpr(explode('?', (string) ($params['path'] ?? ''), 2)[0]),
            'query' => $this->renderPairsExpr($params['query'] ?? []),
            'headers' => $this->renderPairsExpr($params['headers'] ?? []),
            'bodyMode' => $params['body_mode'] ?? 'none',
            'bodyExpr' => $this->renderBodyExpr($params['body_mode'] ?? 'none', $params['body'] ?? null),
            'mapping' => $params['response_mapping'] ?? [],
            'onError' => $onError === 'branch' ? 'stop_flow' : $onError,
            'onErrorTarget' => null,
            'isController' => true,
        ])->render();

        return $tpl."\n";
    }

    private function renderStringExpr(string $template): string
    {
        return "'".addslashes($template)."'";
    }

    /** @param array<int, array{key: string, value: string}> $pairs */
    private function renderPairsExpr(array $pairs): string
    {
        if (empty($pairs)) {
            return '[]';
        }

        $parts = [];

        foreach ($pairs as $p) {
            $k = "'".addslashes($p['key'] ?? '')."'";
            $v = $this->renderStringExpr($p['value'] ?? '');
            $parts[] = "        {$k} => {$v},";
        }

        return "[\n".implode("\n", $parts)."\n    ]";
    }

    private function renderBodyExpr(string $mode, mixed $body): string
    {
        if ($mode === 'json' && is_string($body)) {
            $decoded = json_decode($body, true);

            if ($decoded !== null) {
                return var_export($decoded, true);
            }
        }

        if ($mode === 'form' && is_array($body)) {
            return $this->renderPairsExpr($body);
        }

        return 'null';
    }

    /** Добавить отступ к блоку кода (2 уровня — класс + метод).
     */
    private function indentBlock(string $code): string
    {
        $padding = str_repeat('    ', 2);
        $lines = explode("\n", rtrim($code));

        return implode("\n", array_map(
            fn ($line) => $line === '' ? '' : $padding.$line,
            $lines,
        ))."\n";
    }
}
