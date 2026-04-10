{
    "name": "app/{{ Str::slug($botName) }}",
    "require": {
        "php": ">=8.3",
        "govorun/framework": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
