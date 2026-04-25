return [

    /*
    |--------------------------------------------------------------------------
    | Драйвер мессенджера по умолчанию
    |--------------------------------------------------------------------------
    |
    | Драйвер, который используется по умолчанию, если иной не указан явно.
    | Берётся из переменной окружения MESSENGER_DRIVER.
    |
    */

    'default' => env('MESSENGER_DRIVER', '{!! array_key_first($drivers) !!}'),

    /*
    |--------------------------------------------------------------------------
    | Активные драйверы
    |--------------------------------------------------------------------------
    |
    | Список включённых драйверов мессенджеров. Фреймворк регистрирует
    | webhook и обрабатывает входящие сообщения для каждого из них.
    |
    */

    'drivers' => [
        env('MESSENGER_DRIVER', '{!! array_key_first($drivers) !!}'),
    ],

@foreach($drivers as $driverName => $fields)
    /*
    |--------------------------------------------------------------------------
    | {{ $descriptions[$driverName]['title'] ?? strtoupper($driverName) }}
    |--------------------------------------------------------------------------
@if(!empty($descriptions[$driverName]['description']))
    |
@foreach($descriptions[$driverName]['description'] as $line)
    | {{ $line }}
@endforeach
    |
@endif
    */

    '{{ $driverName }}' => [
@foreach($fields as $configKey => $envVar)
        '{{ $configKey }}' => env('{{ $envVar }}', {!! $configKey === 'secret' ? 'null' : "''" !!}),
@endforeach
    ],

@endforeach
];
