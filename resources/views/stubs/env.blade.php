APP_NAME="{{ $botName }}"
APP_ENV={{ $environment }}
APP_DEBUG={{ $debug ? 'true' : 'false' }}

@foreach($drivers as $driverName => $driverConfig)
@foreach($driverConfig as $key => $value)
{{ strtoupper($driverName) }}_{{ strtoupper($key) }}={{ $value }}
@endforeach

@endforeach
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_DATABASE=govorun
DB_USERNAME=root
DB_PASSWORD=
