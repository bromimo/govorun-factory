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
        $routes = [];
        $imports = ['Govorun\Routing\Route'];

        foreach ($bot->routes as $route) {
            $match = $route->match;

            $entry = [
                'type' => $route->type->value,
                'match' => $match,
                'handler_type' => $route->handler_type->value,
                'middleware' => $route->middleware ?? [],
                'aliases' => $route->aliases ?? [],
            ];

            if ($route->handler_type->value === 'controller') {
                $className = Str::studly($route->type->value.'_'.Str::slug($route->match ?? 'handler', '_')).'Controller';
            } else {
                $flowName = $flowClassNames[$route->flow_id] ?? 'UnknownFlow';
                $className = $flowName.'Controller';
            }

            $entry['controller_class'] = $className;
            $imports[] = "App\\Controllers\\{$className}";

            $routes[] = $entry;
        }

        $imports = array_unique($imports);
        usort($imports, fn ($a, $b) => strlen($a) <=> strlen($b));

        return "<?php\n\n".view('stubs.routes_messenger', compact('routes', 'imports'))->render();
    }
}
