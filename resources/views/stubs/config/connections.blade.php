return [
@foreach ($connections as $conn)
    '{!! $conn->slug !!}' => [
        'base_url' => '{!! addslashes($conn->base_url) !!}',
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
        'default_headers' => [
@foreach ($conn->default_headers ?? [] as $h)
            '{!! addslashes($h["key"]) !!}' => '{!! addslashes($h["value"] ?? "") !!}',
@endforeach
        ],
    ],
@endforeach
];