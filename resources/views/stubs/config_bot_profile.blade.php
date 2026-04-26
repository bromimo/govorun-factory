return [

    'name' => '{{ $name }}',

    'short_description' => '{{ $shortDescription }}',

    'description' => '{{ $description }}',

@if(count($commands) === 0)
    'commands' => [],
@else
    'commands' => [
@foreach($commands as $cmd)
        ['command' => '{{ $cmd['command'] }}', 'description' => '{{ $cmd['description'] }}'],
@endforeach
    ],
@endif

];
