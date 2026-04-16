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
    public function generate(string $className, array $handlerSchema): string
    {
        $blocks = $handlerSchema['blocks'] ?? [];
        $blockCode = '';

        foreach ($blocks as $block) {
            $blockCode .= $this->indentBlock($this->renderBlock($block['type'], $block['params'] ?? []));
        }

        return "<?php\n\n".view('stubs.controller', [
            'className' => $className,
            'blockCode' => $blockCode,
        ])->render();
    }

    /** Сгенерировать класс контроллера с несколькими методами.
     * @param  array<int, array{name: string, schema: array}> $methods
     */
    public function generateWithMethods(string $className, array $methods): string
    {
        $methodsCode = '';

        foreach ($methods as $method) {
            $blocks = $method['schema']['blocks'] ?? [];
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

        return "<?php\n\n".view('stubs.controller_multi', [
            'className' => $className,
            'methodsCode' => $methodsCode,
        ])->render();
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
