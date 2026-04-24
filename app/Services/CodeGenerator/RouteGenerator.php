<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use Illuminate\Support\Str;

/** Генератор файла маршрутов мессенджера. */
class RouteGenerator
{
    /** Сгенерировать routes/messenger.php.
     * @param  array<int, string>  $flowClassNames
     */
    public function generate(Bot $bot, array $flowClassNames = []): string
    {
        $imports = ['Govorun\Routing\Route'];

        $topRoutes = $bot->routes()->whereNull('parent_id')->orderBy('sort_order')->with('children')->get();

        $routes = [];
        foreach ($topRoutes as $route) {
            $routes[] = $this->buildEntry($route, $flowClassNames, $imports);
        }

        $imports = array_unique($imports);
        usort($imports, fn ($a, $b) => strlen($a) <=> strlen($b));

        $code = "<?php\n\n".view('stubs.routes_messenger', compact('routes', 'imports'))->render();

        return CodeHelper::wrapLongLines($code);
    }

    /** Построить массив данных маршрута для шаблона.
     * @param  \App\Models\BotRoute  $route
     * @param  array<int, string>  $flowClassNames
     * @param  array<int, string>  $imports
     * @return array<string, mixed>
     */
    private function buildEntry($route, array $flowClassNames, array &$imports): array
    {
        $entry = [
            'type' => $route->type->value,
            'match' => $route->match,
            'handler_type' => $route->handler_type->value,
            'middleware' => $route->middleware ?? [],
            'aliases' => $route->aliases ?? [],
            'children' => [],
        ];

        if ($route->children->isNotEmpty()) {
            $className = ($route->controller_name ?? Str::studly(Str::slug($route->match ?? 'handler', '_'))).'Controller';
            $entry['controller_class'] = $className;
            $imports[] = "App\\Controllers\\{$className}";

            foreach ($route->children as $child) {
                $method = $child->controller_name ?: Str::camel(Str::slug($child->match ?: 'handle', '_'));
                $entry['children'][] = [
                    'match' => $child->match,
                    'method' => $method === 'handle' ? null : $method,
                    'aliases' => $child->aliases ?? [],
                ];
            }

            return $entry;
        }

        if ($route->controller_name) {
            $className = $route->controller_name.'Controller';
        } elseif ($route->handler_type->value === 'controller') {
            $className = Str::studly($route->type->value.'_'.Str::slug($route->match ?? 'handler', '_')).'Controller';
        } else {
            $flowName = $flowClassNames[$route->flow_id] ?? 'UnknownFlow';
            $className = $flowName.'Controller';
        }

        $entry['controller_class'] = $className;
        $imports[] = "App\\Controllers\\{$className}";

        return $entry;
    }
}
