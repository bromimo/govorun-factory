<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use Illuminate\Support\Str;

/** Генератор файла маршрутов мессенджера. */
class RouteGenerator
{
    /** Сгенерировать routes/messenger.php.
     */
    public function generate(Bot $bot): string
    {
        $routes = [];

        foreach ($bot->routes as $route) {
            $entry = [
                'type' => $route->type->value,
                'match' => $route->match,
                'handler_type' => $route->handler_type->value,
                'middleware' => $route->middleware ?? [],
            ];

            if ($route->handler_type->value === 'controller') {
                $entry['controller_class'] = Str::studly($route->type->value.'_'.Str::slug($route->match ?? 'handler', '_')).'Controller';
            } else {
                $entry['flow_class'] = $route->flow?->name ?? 'UnknownFlow';
            }

            $routes[] = $entry;
        }

        return "<?php\n\n".view('stubs.routes_messenger', compact('routes'))->render();
    }
}
