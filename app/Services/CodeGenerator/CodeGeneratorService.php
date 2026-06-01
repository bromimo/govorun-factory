<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use App\Enums\EntityStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

/** Оркестратор генерации полного проекта из схемы бота. */
class CodeGeneratorService
{
    private ConfigGenerator $config;

    private RouteGenerator $routes;

    private ControllerGenerator $controller;

    private FlowGenerator $flow;

    private ComposerGenerator $composer;

    private BotProfileGenerator $botProfile;

    private ViberProfileGenerator $viberProfile;

    private WhatsAppProfileGenerator $whatsAppProfile;

    private ConnectionGenerator $connection;

    /** @var array<int, string> Маппинг flow_id → имя класса. */
    private array $flowClassNames = [];

    public function __construct()
    {
        $this->config = new ConfigGenerator;
        $this->routes = new RouteGenerator;
        $this->controller = new ControllerGenerator;
        $this->flow = new FlowGenerator;
        $this->composer = new ComposerGenerator;
        $this->botProfile = new BotProfileGenerator;
        $this->viberProfile = new ViberProfileGenerator;
        $this->whatsAppProfile = new WhatsAppProfileGenerator;
        $this->connection = new ConnectionGenerator;
    }

    /** Сгенерировать полный проект в указанную директорию.
     */
    public function generate(Bot $bot, string $outputPath): void
    {
        $bot->load([
            'routes' => fn ($q) => $q->where('status', EntityStatus::Active->value)->with('flow'),
            'flows' => fn ($q) => $q->where('status', EntityStatus::Active->value),
            'media',
        ]);

        $this->buildFlowClassNames($bot);
        $mediaMap = $this->buildMediaMap($bot);
        $this->copySkeletonTo($outputPath);
        $this->generateConfigs($bot, $outputPath);
        $this->generateBotProfile($bot, $outputPath);
        $this->generateViberProfile($bot, $outputPath);
        $this->generateWhatsAppProfile($bot, $outputPath);
        $this->generateRoutes($bot, $outputPath);
        $this->generateControllers($bot, $outputPath, $mediaMap);
        $this->generateFlows($bot, $outputPath, $mediaMap);
        $this->generateConnections($bot, $outputPath);
        $this->generateComposer($bot, $outputPath);
    }

    /** Записать PHP-файл, применив пост-процессор переноса длинных строк. */
    private function putPhp(string $path, string $code): void
    {
        File::put($path, CodeHelper::wrapLongLines($code));
    }

    /** Построить уникальные имена классов для всех flow.
     */
    private function buildFlowClassNames(Bot $bot): void
    {
        $usedNames = [];

        foreach ($bot->flows as $flow) {
            $baseName = Str::studly(Str::ascii($flow->name, 'ru'));

            if (empty($baseName)) {
                $baseName = 'Flow';
            }

            $className = $baseName;
            $counter = 2;
            while (in_array($className, $usedNames)) {
                $className = $baseName.$counter;
                $counter++;
            }

            $usedNames[] = $className;
            $this->flowClassNames[$flow->id] = $className;
        }
    }

    /** Скопировать скелетон в целевую директорию.
     */
    private function copySkeletonTo(string $outputPath): void
    {
        File::copyDirectory(resource_path('stubs/skeleton'), $outputPath);
    }

    /** Сгенерировать конфигурационные файлы.
     */
    private function generateConfigs(Bot $bot, string $outputPath): void
    {
        $config = $bot->config ?? [];
        $drivers = $this->config->resolveDriverFields($bot->messenger_config ?? []);

        File::ensureDirectoryExists("{$outputPath}/config");

        $botUsername = $bot->messenger_config['telegram']['username'] ?? '';
        $this->putPhp("{$outputPath}/config/app.php", $this->config->generateAppConfig($bot->name, $botUsername, $config));
        $this->putPhp("{$outputPath}/config/messenger.php", $this->config->generateMessengerConfig($drivers));
        $this->putPhp("{$outputPath}/config/database.php", $this->config->generateDatabaseConfig());
        File::put("{$outputPath}/.env.example", $this->config->generateEnvExample(
            $bot->name,
            $drivers,
        ));
    }

    /** Сгенерировать config/viber_profile.php и (если есть) скопировать аватар-бинарь. */
    private function generateViberProfile(Bot $bot, string $outputPath): void
    {
        $configPhp = $this->viberProfile->renderConfig($bot);
        if ($configPhp === null) {
            return;
        }

        $configPath = "{$outputPath}/config/viber_profile.php";
        File::ensureDirectoryExists(dirname($configPath));
        $this->putPhp($configPath, $configPhp);

        $avatar = $this->viberProfile->resolveAvatar($bot);
        if ($avatar === null) {
            return;
        }

        $avatarTargetPath = "{$outputPath}/{$avatar['zip_relative_path']}";
        File::ensureDirectoryExists(dirname($avatarTargetPath));
        File::copy($avatar['source_absolute_path'], $avatarTargetPath);
    }

    /** Сгенерировать config/whatsapp_profile.php и скопировать фото профиля.
     * @param Bot $bot Бот
     * @param string $outputPath Каталог сборки
     * @return void
     */
    private function generateWhatsAppProfile(Bot $bot, string $outputPath): void
    {
        $configPhp = $this->whatsAppProfile->renderConfig($bot);
        if ($configPhp === null) {
            return;
        }

        $configPath = "{$outputPath}/config/whatsapp_profile.php";
        File::ensureDirectoryExists(dirname($configPath));
        $this->putPhp($configPath, $configPhp);

        $photo = $this->whatsAppProfile->resolvePhoto($bot);
        if ($photo === null) {
            return;
        }

        $photoTargetPath = "{$outputPath}/{$photo['zip_relative_path']}";
        File::ensureDirectoryExists(dirname($photoTargetPath));
        File::copy($photo['source_absolute_path'], $photoTargetPath);
    }

    /** Сгенерировать файл маршрутов.
     */
    private function generateRoutes(Bot $bot, string $outputPath): void
    {
        File::ensureDirectoryExists("{$outputPath}/routes");
        $this->putPhp("{$outputPath}/routes/messenger.php", $this->routes->generate($bot, $this->flowClassNames));
    }

    /** Сгенерировать контроллеры.
     */
    private function generateControllers(Bot $bot, string $outputPath, array $mediaMap = []): void
    {
        File::ensureDirectoryExists("{$outputPath}/app/Controllers");

        $topRoutes = $bot->routes()
            ->whereNull('parent_id')
            ->where('status', EntityStatus::Active->value)
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->where('status', EntityStatus::Active->value)])
            ->get();

        foreach ($topRoutes as $route) {
            if ($route->children->isNotEmpty()) {
                $this->generateGroupController($route, $outputPath, $mediaMap);
            } elseif ($route->handler_type->value === 'controller') {
                $className = $this->resolveControllerClassName($route);
                $namespace = CodeHelper::controllerNamespace($route->type->value);
                $code = $this->controller->generate($className, $route->handler_schema ?? ['blocks' => []], $namespace, $mediaMap);
                $this->putControllerFile($outputPath, $route->type->value, $className, $code);
            } elseif ($route->handler_type->value === 'flow' && $route->flow_id) {
                $flowClass = $this->flowClassNames[$route->flow_id] ?? null;
                if ($flowClass) {
                    $className = $route->type->value === 'fallback' ? 'FallbackController' : $flowClass.'Controller';
                    $controllerNamespace = CodeHelper::controllerNamespace($route->type->value);
                    $code = "<?php\n\n".view('stubs.flow_controller', compact('className', 'flowClass', 'controllerNamespace'))->render();
                    $this->putControllerFile($outputPath, $route->type->value, $className, $code);
                }
            }
        }
    }

    /** Определить имя класса controller-обработчика для маршрута без детей.
     * Для fallback — всегда FallbackController, controller_name игнорируется.
     */
    private function resolveControllerClassName($route): string
    {
        if ($route->type->value === 'fallback') {
            return 'FallbackController';
        }

        return $route->controller_name
            ? $route->controller_name.'Controller'
            : Str::studly($route->type->value.'_'.Str::slug($route->match ?? 'handler', '_')).'Controller';
    }

    /** Записать файл контроллера в подпапку, соответствующую типу маршрута.
     */
    private function putControllerFile(string $outputPath, string $routeType, string $className, string $code): void
    {
        $subdir = CodeHelper::controllerSubdir($routeType);
        $dir = $subdir === null
            ? "{$outputPath}/app/Controllers"
            : "{$outputPath}/app/Controllers/{$subdir}";

        File::ensureDirectoryExists($dir);
        $this->putPhp("{$dir}/{$className}.php", $code);
    }

    /** Сгенерировать контроллер группы (родительская phrase с дочерними методами).
     * @param  array<int, string>  $mediaMap
     */
    private function generateGroupController($parentRoute, string $outputPath, array $mediaMap = []): void
    {
        $className = ($parentRoute->controller_name ?? Str::studly(Str::slug($parentRoute->match ?? 'handler', '_'))).'Controller';

        $methods = [];
        foreach ($parentRoute->children as $child) {
            $methodName = $child->controller_name ?: Str::camel(Str::slug($child->match ?: 'handle', '_'));
            $methods[] = [
                'name' => $methodName,
                'schema' => $child->handler_schema ?? ['blocks' => []],
            ];
        }

        $namespace = CodeHelper::controllerNamespace($parentRoute->type->value);
        $code = $this->controller->generateWithMethods($className, $methods, $namespace, $mediaMap);
        $this->putControllerFile($outputPath, $parentRoute->type->value, $className, $code);
    }

    /** Сгенерировать Flow-классы.
     */
    private function generateFlows(Bot $bot, string $outputPath, array $mediaMap = []): void
    {
        File::ensureDirectoryExists("{$outputPath}/app/Flows");

        $validationMessages = $bot->config['validation_messages'] ?? [];

        foreach ($bot->flows as $flow) {
            $className = $this->flowClassNames[$flow->id];
            $code = $this->flow->generate(
                $className,
                $flow->graph ?? ['nodes' => [], 'edges' => []],
                $flow->interrupt_commands ?? [],
                $flow->interrupt_on_event ?? false,
                $validationMessages,
                $mediaMap,
            );
            $this->putPhp("{$outputPath}/app/Flows/{$className}.php", $code);
        }
    }

    /** Построить маппинг media_id → filename для всех медиафайлов бота.
     *
     * @return array<int, string>
     */
    private function buildMediaMap(Bot $bot): array
    {
        return $bot->media()->pluck('filename', 'id')->all();
    }

    /** Сгенерировать config/connections.php и дополнить .env.example.
     */
    private function generateConnections(Bot $bot, string $outputPath): void
    {
        $this->connection->generate($bot, $outputPath);
    }

    /** Сгенерировать composer.json.
     */
    private function generateComposer(Bot $bot, string $outputPath): void
    {
        File::put("{$outputPath}/composer.json", $this->composer->generate($bot->name));
    }

    /** Сгенерировать config/bot_profile.php и (если есть) скопировать фото-бинарь.
     */
    private function generateBotProfile(Bot $bot, string $outputPath): void
    {
        $configPhp = $this->botProfile->renderConfig($bot);
        if ($configPhp === null) {
            return;
        }

        $configPath = "{$outputPath}/config/bot_profile.php";
        File::ensureDirectoryExists(dirname($configPath));
        $this->putPhp($configPath, $configPhp);

        $photo = $this->botProfile->resolvePhoto($bot);
        if ($photo === null) {
            return;
        }

        $photoTargetPath = "{$outputPath}/{$photo['zip_relative_path']}";
        File::ensureDirectoryExists(dirname($photoTargetPath));
        File::copy($photo['source_absolute_path'], $photoTargetPath);
    }
}
