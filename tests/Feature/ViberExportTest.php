<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CodeGenerator\CodeGeneratorService;

class ViberExportTest extends TestCase
{
    use RefreshDatabase;

    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputDir = sys_get_temp_dir() . '/govorun-test-' . uniqid();
        File::makeDirectory($this->outputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->outputDir);
        parent::tearDown();
    }

    public function test_export_generates_viber_profile_config(): void
    {
        $bot = Bot::factory()->for(User::factory(), 'creator')->create([
            'messenger_config' => [
                'viber' => [
                    'enabled' => true,
                    'profile' => [
                        'sender_name' => 'MyBot',
                        'public_account_uri' => 'mybot',
                        'event_types' => ['message', 'subscribed'],
                        'avatar_path' => null,
                    ],
                ],
            ],
        ]);
        $bot->routes()->create([
            'type' => 'command', 'match' => '/start', 'description' => 'Start',
            'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
            'sort_order' => 0, 'status' => 'active',
        ]);

        (new CodeGeneratorService)->generate($bot, $this->outputDir);

        $configPath = "{$this->outputDir}/config/viber_profile.php";
        $this->assertFileExists($configPath);
        $contents = file_get_contents($configPath);
        $this->assertStringContainsString("'sender_name' => 'MyBot'", $contents);
        $this->assertStringContainsString("'message'", $contents);
    }

    public function test_export_skips_viber_config_when_disabled(): void
    {
        $bot = Bot::factory()->for(User::factory(), 'creator')->create([
            'messenger_config' => [
                'telegram' => ['enabled' => true, 'username' => 'tg', 'profile' => []],
            ],
        ]);
        $bot->routes()->create([
            'type' => 'command', 'match' => '/s', 'description' => 'S',
            'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
            'sort_order' => 0, 'status' => 'active',
        ]);

        (new CodeGeneratorService)->generate($bot, $this->outputDir);

        $this->assertFileDoesNotExist("{$this->outputDir}/config/viber_profile.php");
    }

    public function test_export_copies_avatar_to_storage_app_public(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bots/1/viber/avatar.jpg', 'fakeimage');

        $bot = Bot::factory()->for(User::factory(), 'creator')->create([
            'messenger_config' => [
                'viber' => [
                    'enabled' => true,
                    'profile' => [
                        'sender_name' => 'B',
                        'public_account_uri' => null,
                        'event_types' => ['message'],
                        'avatar_path' => 'bots/1/viber/avatar.jpg',
                    ],
                ],
            ],
        ]);
        $bot->routes()->create([
            'type' => 'command', 'match' => '/s', 'description' => 'S',
            'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
            'sort_order' => 0, 'status' => 'active',
        ]);

        (new CodeGeneratorService)->generate($bot, $this->outputDir);

        $this->assertFileExists("{$this->outputDir}/storage/app/public/viber-avatar.jpg");
    }

    public function test_export_includes_viber_in_env_example(): void
    {
        $bot = Bot::factory()->for(User::factory(), 'creator')->create([
            'messenger_config' => [
                'viber' => [
                    'enabled' => true,
                    'profile' => ['sender_name' => 'B', 'event_types' => ['message']],
                ],
            ],
        ]);
        $bot->routes()->create([
            'type' => 'command', 'match' => '/s', 'description' => 'S',
            'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
            'sort_order' => 0, 'status' => 'active',
        ]);

        (new CodeGeneratorService)->generate($bot, $this->outputDir);

        $env = file_get_contents("{$this->outputDir}/.env.example");
        $this->assertStringContainsString('VIBER_AUTH_TOKEN=', $env);
    }
}