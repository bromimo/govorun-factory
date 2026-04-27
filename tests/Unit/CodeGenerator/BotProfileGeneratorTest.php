<?php

namespace Tests\Unit\CodeGenerator;

use Tests\TestCase;
use App\Models\Bot;
use App\Models\BotRoute;
use App\Enums\RouteType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CodeGenerator\BotProfileGenerator;

/** Тесты под-генератора Telegram-профиля бота: config/bot_profile.php и путь к фото. */
class BotProfileGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_bot_profile_config_with_all_fields(): void
    {
        $bot = Bot::factory()->create([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => [
                        'name' => 'Govorun',
                        'short_description' => 'About text',
                        'description' => 'Long description',
                    ],
                ],
            ],
        ]);

        BotRoute::factory()->for($bot)->create([
            'type' => RouteType::Command->value,
            'match' => '/start',
            'description' => 'Старт',
            'sort_order' => 0,
        ]);
        BotRoute::factory()->for($bot)->create([
            'type' => RouteType::Command->value,
            'match' => '/help',
            'description' => 'Справка',
            'sort_order' => 1,
        ]);

        $php = (new BotProfileGenerator)->renderConfig($bot);

        $this->assertStringStartsWith('<?php', $php);
        $this->assertStringContainsString("'name' => 'Govorun'", $php);
        $this->assertStringContainsString("'short_description' => 'About text'", $php);
        $this->assertStringContainsString("'description' => 'Long description'", $php);
        $this->assertStringContainsString("'command' => 'start'", $php);
        $this->assertStringContainsString("'description' => 'Старт'", $php);
        $this->assertStringContainsString("'command' => 'help'", $php);
        $this->assertStringContainsString("'description' => 'Справка'", $php);
    }

    public function test_skips_command_routes_without_description(): void
    {
        $bot = Bot::factory()->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        BotRoute::factory()->for($bot)->create([
            'type' => RouteType::Command->value,
            'match' => '/start',
            'description' => null,
        ]);

        $php = (new BotProfileGenerator)->renderConfig($bot);

        $this->assertStringContainsString("'commands' => []", $php);
    }

    public function test_skips_non_command_routes(): void
    {
        $bot = Bot::factory()->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        BotRoute::factory()->for($bot)->create([
            'type' => RouteType::Phrase->value,
            'match' => 'привет',
            'description' => 'Должен быть проигнорирован',
        ]);

        $php = (new BotProfileGenerator)->renderConfig($bot);

        $this->assertStringContainsString("'commands' => []", $php);
    }

    public function test_renders_empty_strings_for_missing_fields(): void
    {
        $bot = Bot::factory()->create([
            'messenger_config' => [
                'telegram' => ['enabled' => true, 'profile' => []],
            ],
        ]);

        $php = (new BotProfileGenerator)->renderConfig($bot);

        $this->assertStringContainsString("'name' => ''", $php);
        $this->assertStringContainsString("'short_description' => ''", $php);
        $this->assertStringContainsString("'description' => ''", $php);
        $this->assertStringContainsString("'commands' => []", $php);
    }

    public function test_returns_null_when_telegram_disabled(): void
    {
        $bot = Bot::factory()->create([
            'messenger_config' => ['telegram' => ['enabled' => false]],
        ]);

        $this->assertNull((new BotProfileGenerator)->renderConfig($bot));
    }

    public function test_resolves_photo_source_path_when_present(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $bot = new Bot([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => ['photo_path' => 'bot-profiles/42/profile.jpg'],
                ],
            ],
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put('bot-profiles/42/profile.jpg', 'fake');

        $info = (new BotProfileGenerator)->resolvePhoto($bot);

        $this->assertNotNull($info);
        $this->assertSame('storage/app/bot-profile.jpg', $info['zip_relative_path']);
        $this->assertStringEndsWith('bot-profiles/42/profile.jpg', str_replace('\\', '/', $info['source_absolute_path']));
        $this->assertFileExists($info['source_absolute_path']);
    }

    public function test_resolves_photo_returns_null_when_path_missing(): void
    {
        $bot = new Bot([
            'messenger_config' => [
                'telegram' => ['enabled' => true, 'profile' => []],
            ],
        ]);

        $this->assertNull((new BotProfileGenerator)->resolvePhoto($bot));
    }

    public function test_resolves_photo_returns_null_when_file_missing(): void
    {
        $bot = new Bot([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => ['photo_path' => 'bot-profiles/999/profile.jpg'],
                ],
            ],
        ]);

        $this->assertNull((new BotProfileGenerator)->resolvePhoto($bot));
    }
}
