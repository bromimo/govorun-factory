<?php

use App\Models\Bot;
use App\Models\BotRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can export valid bot as ZIP', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram'],
    ]);
    BotRoute::factory()->for($bot)->create();

    $response = $this->actingAs($admin)->get("/bots/{$bot->id}/export");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/zip');
});

test('archive is deleted from storage/exports after download', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram'],
    ]);
    BotRoute::factory()->for($bot)->create();

    $before = glob(storage_path('exports/*.zip')) ?: [];

    $response = $this->actingAs($admin)->get("/bots/{$bot->id}/export");
    $response->assertOk();
    $response->streamedContent();

    $after = glob(storage_path('exports/*.zip')) ?: [];
    expect(array_diff($after, $before))->toBeEmpty();
});

test('export fails with validation errors for invalid bot', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => [],
    ]);

    $response = $this->actingAs($admin)->get("/bots/{$bot->id}/export");

    $response->assertStatus(422);
    $response->assertJsonStructure(['errors']);
});

test('viewer cannot export', function () {
    $viewer = User::factory()->create();
    $bot = Bot::factory()->create();

    $this->actingAs($viewer)->get("/bots/{$bot->id}/export")->assertForbidden();
});

test('stale archives older than threshold are cleaned up before export', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram'],
    ]);
    BotRoute::factory()->for($bot)->create();

    $exportsDir = storage_path('exports');
    if (! is_dir($exportsDir)) {
        mkdir($exportsDir, 0777, true);
    }

    $stalePath = $exportsDir.'/stale-leftover-bot-20200101-000000.zip';
    file_put_contents($stalePath, 'fake zip');
    touch($stalePath, time() - 3600);

    $freshPath = $exportsDir.'/fresh-leftover-bot-99999999-999999.zip';
    file_put_contents($freshPath, 'fake zip');
    touch($freshPath, time() - 60);

    try {
        $response = $this->actingAs($admin)->get("/bots/{$bot->id}/export");
        $response->assertOk();
        $response->streamedContent();

        expect(file_exists($stalePath))->toBeFalse();
        expect(file_exists($freshPath))->toBeTrue();
    } finally {
        @unlink($stalePath);
        @unlink($freshPath);
    }
});

test('export includes bot_profile when telegram enabled', function () {
    $admin = \App\Models\User::factory()->admin()->create();
    $bot = \App\Models\Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => [
            'telegram' => [
                'enabled' => true,
                'profile' => [
                    'name' => 'Z',
                    'short_description' => 'A',
                    'description' => 'D',
                    'commands' => [['command' => 'start', 'description' => 'X']],
                ],
            ],
        ],
    ]);
    \App\Models\BotRoute::factory()->for($bot)->create();

    $response = $this->actingAs($admin)->get(route('bots.export', $bot));

    $response->assertOk();

    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    file_put_contents($tmp, $response->streamedContent());

    $zip = new \ZipArchive();
    $zip->open($tmp);
    $found = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (str_ends_with($name, '/config/bot_profile.php')) {
            $content = $zip->getFromIndex($i);
            expect($content)->toContain("'name' => 'Z'");
            $found = true;
        }
    }
    $zip->close();
    unlink($tmp);

    expect($found)->toBeTrue('config/bot_profile.php not found in ZIP');
});
