return [
@foreach($drivers as $driverName => $driverConfig)
    '{{ $driverName }}' => [
@foreach($driverConfig as $key => $value)
        '{{ $key }}' => env('{{ strtoupper($driverName) }}_{{ strtoupper($key) }}', ''),
@endforeach
    ],
@endforeach
];
