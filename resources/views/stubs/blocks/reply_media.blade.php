@php
    $type = $params['media_type'] ?? 'photo';
    $url = addslashes($params['url'] ?? '');
    $caption = trim((string) ($params['caption'] ?? ''));
@endphp
@if ($type !== 'photo')
// Unsupported media type: {!! $type !!}
@elseif ($caption === '')
$this->send(Media::photo('{!! $url !!}'));
@else
$this->send(
    Media::photo('{!! $url !!}')
        ->caption({!! \App\Services\CodeGenerator\CodeHelper::renderText($caption) !!})
);
@endif
