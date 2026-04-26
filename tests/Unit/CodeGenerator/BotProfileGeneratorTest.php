<?php

namespace Tests\Unit\CodeGenerator;

use Tests\TestCase;
use App\Models\Bot;
use App\Services\CodeGenerator\BotProfileGenerator;

/** Тесты под-генератора Telegram-профиля бота: config/bot_profile.php и путь к фото. */
class BotProfileGeneratorTest extends TestCase
{
    public function test_renders_bot_profile_config_with_all_fields(): void
    {
        $bot = new Bot([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => [
                        'name' => 'Govorun',
                        'short_description' => 'About text',
                        'description' => 'Long description',
                        'commands' => [
                            ['command' => 'start', 'description' => 'Старт'],
                            ['command' => 'help',  'description' => 'Справка'],
                        ],
                    ],
                ],
            ],
        ]);

        $php = (new BotProfileGenerator())->renderConfig($bot);

        $this->assertStringStartsWith('<?php', $php);
        $this->assertStringContainsString("'name' => 'Govorun'", $php);
        $this->assertStringContainsString("'short_description' => 'About text'", $php);
        $this->assertStringContainsString("'description' => 'Long description'", $php);
        $this->assertStringContainsString("'command' => 'start'", $php);
        $this->assertStringContainsString("'description' => 'Старт'", $php);
    }

    public function test_renders_empty_strings_for_missing_fields(): void
    {
        $bot = new Bot([
            'messenger_config' => [
                'telegram' => ['enabled' => true, 'profile' => []],
            ],
        ]);

        $php = (new BotProfileGenerator())->renderConfig($bot);

        $this->assertStringContainsString("'name' => ''", $php);
        $this->assertStringContainsString("'short_description' => ''", $php);
        $this->assertStringContainsString("'description' => ''", $php);
        $this->assertStringContainsString("'commands' => []", $php);
    }

    public function test_returns_null_when_telegram_disabled(): void
    {
        $bot = new Bot([
            'messenger_config' => [
                'telegram' => ['enabled' => false],
            ],
        ]);

        $this->assertNull((new BotProfileGenerator())->renderConfig($bot));
    }

    public function test_resolves_photo_source_path_when_present(): void
    {
        $bot = new Bot([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => ['photo_path' => 'bot-profiles/42/profile.jpg'],
                ],
            ],
        ]);

        // Создаём файл на диске для проверки.
        $absolute = storage_path('app/bot-profiles/42/profile.jpg');
        @mkdir(dirname($absolute), 0755, true);
        file_put_contents($absolute, 'fake');

        try {
            $info = (new BotProfileGenerator())->resolvePhoto($bot);

            $this->assertNotNull($info);
            $this->assertSame('storage/app/bot-profile.jpg', $info['zip_relative_path']);
            $this->assertSame($absolute, $info['source_absolute_path']);
        } finally {
            @unlink($absolute);
            @rmdir(dirname($absolute));
        }
    }

    public function test_resolves_photo_returns_null_when_path_missing(): void
    {
        $bot = new Bot([
            'messenger_config' => [
                'telegram' => ['enabled' => true, 'profile' => []],
            ],
        ]);

        $this->assertNull((new BotProfileGenerator())->resolvePhoto($bot));
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

        $this->assertNull((new BotProfileGenerator())->resolvePhoto($bot));
    }
}
