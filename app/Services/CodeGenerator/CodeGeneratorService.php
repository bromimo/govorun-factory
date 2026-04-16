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

    /** @var array<int, string> Маппинг flow_id → имя класса. */
    private array $flowClassNames = [];

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

        $this->buildFlowClassNames($bot);
        $this->copySkeletonTo($outputPath);
        $this->generateConfigs($bot, $outputPath);
        $this->generateRoutes($bot, $outputPath);
        $this->generateControllers($bot, $outputPath);
        $this->generateFlows($bot, $outputPath);
        $this->generateComposer($bot, $outputPath);
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

        File::put("{$outputPath}/config/app.php", $this->config->generateAppConfig($bot->name, $config));
        File::put("{$outputPath}/config/messenger.php", $this->config->generateMessengerConfig($drivers));
        File::put("{$outputPath}/config/database.php", $this->config->generateDatabaseConfig());
        File::put("{$outputPath}/.env.example", $this->config->generateEnvExample(
            $bot->name,
            $drivers,
        ));
    }

    /** Сгенерировать файл маршрутов.
     */
    private function generateRoutes(Bot $bot, string $outputPath): void
    {
        File::ensureDirectoryExists("{$outputPath}/routes");
        File::put("{$outputPath}/routes/messenger.php", $this->routes->generate($bot, $this->flowClassNames));
    }

    /** Сгенерировать контроллеры.
     */
    private function generateControllers(Bot $bot, string $outputPath): void
    {
        File::ensureDirectoryExists("{$outputPath}/app/Controllers");

        $topRoutes = $bot->routes()->whereNull('parent_id')->orderBy('sort_order')->with('children')->get();

        foreach ($topRoutes as $route) {
            if ($route->children->isNotEmpty()) {
                $this->generateGroupController($route, $outputPath);
            } elseif ($route->handler_type->value === 'controller') {
                $className = $route->controller_name
                    ? $route->controller_name.'Controller'
                    : Str::studly($route->type->value.'_'.Str::slug($route->match ?? 'handler', '_')).'Controller';
                $code = $this->controller->generate($className, $route->handler_schema ?? ['blocks' => []]);
                File::put("{$outputPath}/app/Controllers/{$className}.php", $code);
            } elseif ($route->handler_type->value === 'flow' && $route->flow_id) {
                $flowClass = $this->flowClassNames[$route->flow_id] ?? null;
                if ($flowClass) {
                    $className = $flowClass.'Controller';
                    $code = "<?php\n\n".view('stubs.flow_controller', compact('className', 'flowClass'))->render();
                    File::put("{$outputPath}/app/Controllers/{$className}.php", $code);
                }
            }
        }
    }

    /** Сгенерировать контроллер группы (родительская phrase с дочерними методами).
     */
    private function generateGroupController($parentRoute, string $outputPath): void
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

        $code = $this->controller->generateWithMethods($className, $methods);
        File::put("{$outputPath}/app/Controllers/{$className}.php", $code);
    }

    /** Сгенерировать Flow-классы.
     */
    private function generateFlows(Bot $bot, string $outputPath): void
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
            );
            File::put("{$outputPath}/app/Flows/{$className}.php", $code);
        }
    }

    /** Сгенерировать composer.json.
     */
    private function generateComposer(Bot $bot, string $outputPath): void
    {
        File::put("{$outputPath}/composer.json", $this->composer->generate($bot->name));
    }
}
