<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** Оркестратор генерации полного проекта из схемы бота. */
class CodeGeneratorService
{
    private ConfigGenerator $config;

    private RouteGenerator $routes;

    private ControllerGenerator $controller;

    private FlowGenerator $flow;

    private ComposerGenerator $composer;

    public function __construct()
    {
        $this->config = new ConfigGenerator;
        $this->routes = new RouteGenerator;
        $this->controller = new ControllerGenerator;
        $this->flow = new FlowGenerator;
        $this->composer = new ComposerGenerator;
    }

    /** Сгенерировать полный проект в указанную директорию.
     */
    public function generate(Bot $bot, string $outputPath): void
    {
        $bot->load(['routes.flow', 'flows']);

        $this->copySkeletonTo($outputPath);
        $this->generateConfigs($bot, $outputPath);
        $this->generateRoutes($bot, $outputPath);
        $this->generateControllers($bot, $outputPath);
        $this->generateFlows($bot, $outputPath);
        $this->generateComposer($bot, $outputPath);
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
        $drivers = $bot->messenger_config ?? [];

        File::ensureDirectoryExists("{$outputPath}/config");

        File::put("{$outputPath}/config/app.php", $this->config->generateAppConfig($bot->name, $config));
        File::put("{$outputPath}/config/messenger.php", $this->config->generateMessengerConfig($drivers));
        File::put("{$outputPath}/config/database.php", $this->config->generateDatabaseConfig());
        File::put("{$outputPath}/.env", $this->config->generateEnv(
            $bot->name,
            $config['environment'] ?? 'production',
            $config['debug'] ?? false,
            $drivers,
        ));
    }

    /** Сгенерировать файл маршрутов.
     */
    private function generateRoutes(Bot $bot, string $outputPath): void
    {
        File::ensureDirectoryExists("{$outputPath}/routes");
        File::put("{$outputPath}/routes/messenger.php", $this->routes->generate($bot));
    }

    /** Сгенерировать контроллеры.
     */
    private function generateControllers(Bot $bot, string $outputPath): void
    {
        File::ensureDirectoryExists("{$outputPath}/app/Controllers");

        foreach ($bot->routes as $route) {
            if ($route->handler_type->value !== 'controller') {
                continue;
            }

            $className = Str::studly($route->type->value.'_'.Str::slug($route->match ?? 'handler', '_')).'Controller';
            $code = $this->controller->generate($className, $route->handler_schema ?? ['blocks' => []]);
            File::put("{$outputPath}/app/Controllers/{$className}.php", $code);
        }
    }

    /** Сгенерировать Flow-классы.
     */
    private function generateFlows(Bot $bot, string $outputPath): void
    {
        File::ensureDirectoryExists("{$outputPath}/app/Flows");

        foreach ($bot->flows as $flow) {
            $code = $this->flow->generate(
                $flow->name,
                $flow->graph ?? ['nodes' => [], 'edges' => []],
                $flow->interrupt_commands ?? [],
                $flow->interrupt_on_event ?? false,
            );
            File::put("{$outputPath}/app/Flows/{$flow->name}.php", $code);
        }
    }

    /** Сгенерировать composer.json.
     */
    private function generateComposer(Bot $bot, string $outputPath): void
    {
        File::put("{$outputPath}/composer.json", $this->composer->generate($bot->name));
    }
}
