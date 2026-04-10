<?php

namespace App\Services\CodeGenerator;

/** Генератор composer.json для экспортируемого проекта. */
class ComposerGenerator
{
    /** Сгенерировать composer.json.
     */
    public function generate(string $botName): string
    {
        return view('stubs.composer_json', compact('botName'))->render();
    }
}
