<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use Illuminate\Support\Str;
use App\Models\BotConnection;
use App\Enums\ConnectionAuthType;
use Illuminate\Support\Facades\View;

/** Генерирует config/connections.php и дополняет .env.example для экспортируемого бота. */
class ConnectionGenerator
{
    /** Сгенерировать конфиг подключений и дополнить .env.example.
     *
     * @param  Bot  $bot  Бот с загруженными connections.
     * @param  string  $outputDir  Директория вывода.
     */
    public function generate(Bot $bot, string $outputDir): void
    {
        $connections = $bot->connections()->get()->map(function (BotConnection $c) {
            $c->envPrefix = 'CONN_'.Str::upper($c->slug);

            return $c;
        });

        $php = "<?php\n\n".View::make('stubs.config.connections', ['connections' => $connections])->render();
        file_put_contents($outputDir.'/config/connections.php', $php);

        $envLines = [];

        foreach ($connections as $c) {
            $envLines[] = "{$c->envPrefix}_BASE_URL={$c->base_url}";

            match ($c->auth_type) {
                ConnectionAuthType::Bearer => $envLines[] = "{$c->envPrefix}_TOKEN=",
                ConnectionAuthType::ApiKey => $envLines[] = "{$c->envPrefix}_VALUE=",
                ConnectionAuthType::Basic => array_push($envLines, "{$c->envPrefix}_LOGIN=", "{$c->envPrefix}_PASSWORD="),
                ConnectionAuthType::None => null,
            };
        }

        file_put_contents(
            $outputDir.'/.env.example',
            file_get_contents($outputDir.'/.env.example')."\n".implode("\n", $envLines)."\n",
            LOCK_EX,
        );
    }
}
