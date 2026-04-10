APP_NAME="{{ $botName }}"
APP_URL=https://example.com
APP_ENV=production
APP_DEBUG=false

MESSENGER_DRIVER={{ array_key_first($drivers) }}

@foreach($drivers as $driverName => $fields)
@foreach($fields as $configKey => $envVar)
{{ $envVar }}=
@endforeach

@endforeach
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_DATABASE=govorun
DB_USERNAME=root
DB_PASSWORD=
