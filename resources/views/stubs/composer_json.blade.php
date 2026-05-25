{
    "name": "app/{{ Str::slug($botName) }}",
    "description": "{{ $botName }}",
    "version": "1.0.0",
    "require": {
        "php": ">=8.3",
        "govorun/framework": "^3.4"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
