<?php

use App\Models\Bot;
use App\Enums\ConnectionAuthType;
use App\Models\BotConnection;
use App\Services\CodeGenerator\ConnectionGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('генерирует config/connections.php и env.example', function () {
    $bot = Bot::factory()->create();
    BotConnection::factory()->for($bot)->create([
        'slug' => 'bitrix_prod',
        'base_url' => 'https://acme.bitrix24.ru/rest/1',
        'auth_type' => ConnectionAuthType::Bearer->value,
        'auth_config' => ['token' => 'secret'],
    ]);

    $tmp = sys_get_temp_dir().'/conn-gen-'.uniqid();
    mkdir($tmp.'/config', recursive: true);
    file_put_contents($tmp.'/.env.example', '');

    (new ConnectionGenerator())->generate($bot, $tmp);

    $config = file_get_contents($tmp.'/config/connections.php');
    $env = file_get_contents($tmp.'/.env.example');

    expect($config)->toContain("'bitrix_prod'");
    expect($config)->toContain("'base_url' => 'https://acme.bitrix24.ru/rest/1'");
    expect($config)->toContain("env('CONN_BITRIX_PROD_TOKEN')");
    expect($config)->not->toContain('secret');
    expect($config)->not->toContain('CONN_BITRIX_PROD_BASE_URL');

    expect($env)->not->toContain('CONN_BITRIX_PROD_BASE_URL');
    expect($env)->toContain('CONN_BITRIX_PROD_TOKEN=');

    File::deleteDirectory($tmp);
});