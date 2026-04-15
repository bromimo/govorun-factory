<?php

use App\Models\Bot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

const MIGRATION_NAME = '2026_04_15_000000_migrate_validation_placeholders';
const MIGRATION_PATH = 'database/migrations/2026_04_15_000000_migrate_validation_placeholders.php';

test('migration converts positional placeholders to named in validation_messages', function () {
    $user = User::factory()->admin()->create();
    $bot = Bot::factory()->for($user, 'creator')->create([
        'config' => [
            'validation_messages' => [
                'required' => 'Пусто!',
                'min' => 'Надо от {0} символов',
                'max' => 'Максимум {0}',
                'between' => 'Между {0} и {1}',
                'minNumeric' => 'Минимум {0}',
                'maxNumeric' => 'Максимум {0}',
                'email' => 'Плохой email',
            ],
        ],
    ]);

    DB::table('migrations')->where('migration', MIGRATION_NAME)->delete();
    Artisan::call('migrate', ['--path' => MIGRATION_PATH]);

    $bot->refresh();
    $messages = $bot->config['validation_messages'];

    expect($messages['required'])->toBe('Пусто!');
    expect($messages['min'])->toBe('Надо от {value} символов');
    expect($messages['max'])->toBe('Максимум {value}');
    expect($messages['between'])->toBe('Между {min} и {max}');
    expect($messages['minNumeric'])->toBe('Минимум {value}');
    expect($messages['maxNumeric'])->toBe('Максимум {value}');
    expect($messages['email'])->toBe('Плохой email');
});

test('migration handles bots without validation_messages', function () {
    $user = User::factory()->admin()->create();
    $bot = Bot::factory()->for($user, 'creator')->create([
        'config' => ['foo' => 'bar'],
    ]);

    DB::table('migrations')->where('migration', MIGRATION_NAME)->delete();
    Artisan::call('migrate', ['--path' => MIGRATION_PATH]);

    $bot->refresh();
    expect($bot->config)->toBe(['foo' => 'bar']);
});
