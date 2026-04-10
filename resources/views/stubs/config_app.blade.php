return [
    'name' => '{{ $botName }}',
    'url' => env('APP_URL', 'http://localhost'),
    'environment' => '{{ $environment }}',
    'debug' => {{ $debug ? 'true' : 'false' }},
    'state_storage' => '{{ $stateStorage }}',
];
