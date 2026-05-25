<?php

namespace Tests\Feature;

use App\Models\Bot;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты миграции формата messenger_config из плоского массива в объектную структуру. */
class MessengerConfigMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_flat_format_is_recognized_after_refactor(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'telegram' => ['enabled' => true],
                'vk' => ['enabled' => true],
            ],
        ]);

        $bot->refresh();
        $this->assertSame(true, $bot->messenger_config['telegram']['enabled']);
        $this->assertSame(true, $bot->messenger_config['vk']['enabled']);
    }

    public function test_migration_up_converts_flat_to_nested(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('bots')->insert([
            'name' => 'Legacy',
            'description' => null,
            'config' => null,
            'messenger_config' => json_encode(['telegram', 'vk']),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_04_26_000000_refactor_messenger_config_shape.php');
        $migration->up();

        $row = DB::table('bots')->where('name', 'Legacy')->first();
        $cfg = json_decode($row->messenger_config, true);

        $this->assertSame(['enabled' => true], $cfg['telegram']);
        $this->assertSame(['enabled' => true], $cfg['vk']);
    }

    public function test_migration_down_converts_nested_to_flat(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('bots')->insert([
            'name' => 'Modern',
            'description' => null,
            'config' => null,
            'messenger_config' => json_encode([
                'telegram' => ['enabled' => true, 'profile' => ['name' => 'X']],
                'vk' => ['enabled' => true],
            ]),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_04_26_000000_refactor_messenger_config_shape.php');
        $migration->down();

        $row = DB::table('bots')->where('name', 'Modern')->first();
        $flat = json_decode($row->messenger_config, true);

        sort($flat);
        $this->assertSame(['telegram', 'vk'], $flat);
    }
}
