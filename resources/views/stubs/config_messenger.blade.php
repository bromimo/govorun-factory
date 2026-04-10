return [
    'default' => env('MESSENGER_DRIVER', '{!! array_key_first($drivers) !!}'),

    'drivers' => [
        env('MESSENGER_DRIVER', '{!! array_key_first($drivers) !!}'),
    ],

@foreach($drivers as $driverName => $fields)
    '{{ $driverName }}' => [
@foreach($fields as $configKey => $envVar)
        '{{ $configKey }}' => env('{{ $envVar }}', {!! $configKey === 'secret' ? 'null' : "''" !!}),
@endforeach
    ],
@endforeach
];
