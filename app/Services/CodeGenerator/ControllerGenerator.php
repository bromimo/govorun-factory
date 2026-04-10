<?php

namespace App\Services\CodeGenerator;

use App\Models\Plugin;
use Illuminate\Support\Facades\Blade;

/** Генератор классов контроллеров из схемы обработчика. */
class ControllerGenerator
{
    /** Сгенерировать класс контроллера.
     * @param  array<string, mixed>  $handlerSchema
     */
    public function generate(string $className, array $handlerSchema): string
    {
        $blocks = $handlerSchema['blocks'] ?? [];
        $blockCode = '';

        foreach ($blocks as $block) {
            $blockCode .= $this->renderBlock($block['type'], $block['params'] ?? []);
        }

        return "<?php\n\n".view('stubs.controller', [
            'className' => $className,
            'blockCode' => $blockCode,
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
}
