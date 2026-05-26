# =============================================================================
# Приложение
# =============================================================================
APP_NAME="{{ $botName }}"
APP_URL=https://example.com
APP_ENV=production
APP_DEBUG=false

# =============================================================================
# Мессенджер
# =============================================================================
@foreach($drivers as $driverName => $fields)
# -----------------------------------------------------------------------------
# {{ $descriptions[$driverName]['title'] ?? strtoupper($driverName) }}
@if(!empty($descriptions[$driverName]['description']))
@foreach($descriptions[$driverName]['description'] as $line)
# {{ $line }}
@endforeach
@endif
# -----------------------------------------------------------------------------
@foreach($fields as $configKey => $envVar)
{{ $envVar }}=
@endforeach

@endforeach
# =============================================================================
# База данных
# =============================================================================
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_DATABASE=govorun
DB_USERNAME=root
DB_PASSWORD=
