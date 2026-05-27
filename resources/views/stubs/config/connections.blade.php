return [

@foreach ($connections as $conn)
    /*
    |--------------------------------------------------------------------------
    | Подключение: {{ $conn->slug }}
    |--------------------------------------------------------------------------
    |
    | Переиспользуемое HTTP-подключение. Используется в нодах api_call
    | через $this->http()->connection('{{ $conn->slug }}')->get(...).
    |
    */

    '{!! $conn->slug !!}' => [

        /*
        | Базовый URL — все запросы через это подключение формируются
        | относительно него.
        */

        'base_url' => '{!! addslashes($conn->base_url) !!}',

        /*
        | Аутентификация: тип и реквизиты доступа к API.
        */

        'auth' => [
            'type' => '{!! $conn->auth_type->value !!}',
@if($conn->auth_type->value === 'bearer')
            'token' => env('{!! $conn->envPrefix !!}_TOKEN'),
@elseif($conn->auth_type->value === 'api_key')
            'key' => '{!! addslashes($conn->auth_config["key"] ?? "") !!}',
            'value' => env('{!! $conn->envPrefix !!}_VALUE'),
            'in' => '{!! $conn->auth_config["in"] ?? "header" !!}',
@elseif($conn->auth_type->value === 'basic')
            'login' => env('{!! $conn->envPrefix !!}_LOGIN'),
            'password' => env('{!! $conn->envPrefix !!}_PASSWORD'),
@endif
        ],

        /*
        | Заголовки, добавляемые к каждому запросу через это подключение.
        */

        'default_headers' => [
@foreach ($conn->default_headers ?? [] as $h)
            '{!! addslashes($h["key"]) !!}' => '{!! addslashes($h["value"] ?? "") !!}',
@endforeach
        ],
    ],

@endforeach
];