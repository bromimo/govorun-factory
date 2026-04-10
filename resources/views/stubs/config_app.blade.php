return [
    'name' => '{{ $botName }}',
    'environment' => '{{ $environment }}',
    'debug' => {{ $debug ? 'true' : 'false' }},
    'state_storage' => '{{ $stateStorage }}',
];
