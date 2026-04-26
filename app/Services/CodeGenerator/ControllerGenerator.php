<?php

namespace App\Services\CodeGenerator;

use App\Models\Plugin;
use Illuminate\Support\Facades\Blade;

/** Генератор классов контроллеров из схемы обработчика. */
class ControllerGenerator
{
    /** Сгенерировать класс контроллера с одним методом handle().
     * @param  array<string, mixed>  $handlerSchema
     */
    public function generate(string $className, array $handlerSchema, string $namespace): string
    {
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
     * @param  array<int, array{name: string, schema: array}> $methods
     */
    public function generateWithMethods(string $className, array $methods, string $namespace): string
    {
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

            if (in_array($type, ['ask_keyboard', 'reply_keyboard'], true)) {
                $useMessage = true;
                $useKeyboard = true;
                $useButton = true;
            }

            if ($type === 'reply_media' && ($params['media_type'] ?? 'photo') === 'photo') {
                $useMedia = true;
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
