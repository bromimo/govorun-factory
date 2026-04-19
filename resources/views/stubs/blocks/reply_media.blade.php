@php
    $type = $params['media_type'] ?? 'photo';
    $url = addslashes($params['url'] ?? '');
    $caption = trim((string) ($params['caption'] ?? ''));
    $captionChain = $caption !== ''
        ? '->caption('.\App\Services\CodeGenerator\CodeHelper::renderText($caption).')'
        : '';
@endphp
@if ($type === 'photo')
        $this->send(\Govorun\Messaging\Media::photo('{!! $url !!}'){!! $captionChain !!});
@else
        // Unsupported media type: {!! $type !!}
@endif
