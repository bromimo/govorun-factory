<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const FALLBACK_MIGRATION_NAME = '2026_04_26_060237_null_controller_name_for_fallback_routes';
const FALLBACK_MIGRATION_PATH = 'database/migrations/2026_04_26_060237_null_controller_name_for_fallback_routes.php';

test('migration nullifies controller_name on fallback routes', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();

    $fallback = BotRoute::factory()->for($bot)->create([
        'type' => 'fallback',
        'controller_name' => 'LegacyName',
    ]);
    $command = BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'controller_name' => 'Start',
    ]);

    DB::table('migrations')->where('migration', FALLBACK_MIGRATION_NAME)->delete();
    Artisan::call('migrate', ['--path' => FALLBACK_MIGRATION_PATH]);

    expect($fallback->fresh()->controller_name)->toBeNull();
    expect($command->fresh()->controller_name)->toBe('Start');
});
