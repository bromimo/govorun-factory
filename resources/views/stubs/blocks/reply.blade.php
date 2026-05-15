@php
    use App\Services\CodeGenerator\CodeHelper;
    use App\Services\CodeGenerator\KeyboardCodeBuilder;

    $text = trim((string) ($params['text'] ?? ''));
    $media = $params['media'] ?? null;
    $keyboard = $params['keyboard'] ?? null;
    $hasText = $text !== '';
    $hasMedia = ! empty($media) && ! empty($media['type']);
    $hasKeyboard = ! empty($keyboard) && ! empty($keyboard['buttons'] ?? []);
@endphp
@if (! $hasText && ! $hasMedia)
        // Empty reply block
@else
        $this->send(
@if ($hasMedia)
            Media::{{ $media['type'] }}('{{ addslashes($media['url'] ?? '') }}')@if ($hasText)

                ->caption({!! CodeHelper::renderText($text) !!})
                ->parseMode('HTML')@endif

@else
            Message::make({!! CodeHelper::renderText($text) !!})
                ->parseMode('HTML')@endif
@if ($hasKeyboard)

                ->keyboard(
                    {!! KeyboardCodeBuilder::renderKeyboard($keyboard, '                    ') !!}
                )
@endif

        );
@endif