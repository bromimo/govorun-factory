<?php

namespace App\Services\Http;

/** Рендерит `{{state.*}}` плейсхолдеры из словаря значений. */
class TemplatePlaceholderRenderer
{
    /**
     * @param  array<string, mixed>  $state
     */
    public function render(string $template, array $state): string
    {
        return preg_replace_callback(
            '/\{\{\s*state\.([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/u',
            fn ($m) => (string) ($state[$m[1]] ?? ''),
            $template,
        );
    }
}
