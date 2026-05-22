<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use App\Models\BotRoute;
use App\Enums\EntityStatus;
use Illuminate\Support\Str;

/** Генератор файла маршрутов мессенджера. */
class RouteGenerator
{
    /** Сгенерировать routes/messenger.php.
     * @param  array<int, string>  $flowClassNames
     */
    public function generate(Bot $bot, array $flowClassNames = []): string
    {
        $importsFqcn = ['Govorun\Routing\Route'];

        $topRoutes = $bot->routes()
            ->whereNull('parent_id')
            ->where('status', EntityStatus::Active->value)
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->where('status', EntityStatus::Active->value)])
            ->get();

        $entries = [];
        foreach ($topRoutes as $route) {
            $entries[] = $this->buildEntry($route, $flowClassNames, $importsFqcn);
        }

        $importsFqcn = array_values(array_unique($importsFqcn));
        $aliasMap = $this->resolveAliasMap($importsFqcn);

        $routes = $this->applyAliases($entries, $aliasMap);
        $imports = $this->formatImports($importsFqcn, $aliasMap);
        usort($imports, fn ($a, $b) => strlen($a) <=> strlen($b));

        $code = "<?php\n\n".view('stubs.routes_messenger', compact('routes', 'imports'))->render();

        return CodeHelper::wrapLongLines($code);
    }

    /** Построить массив данных маршрута для шаблона.
     * @param  BotRoute  $route
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
            $fqcn = CodeHelper::controllerNamespace($route->type->value).'\\'.$className;
            $entry['controller_class'] = $className;
            $entry['controller_fqcn'] = $fqcn;
            $imports[] = $fqcn;

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

        if ($route->type->value === 'fallback') {
            $className = 'FallbackController';
        } elseif ($route->controller_name) {
            $className = $route->controller_name.'Controller';
        } elseif ($route->handler_type->value === 'controller') {
            $className = Str::studly($route->type->value.'_'.Str::slug($route->match ?? 'handler', '_')).'Controller';
        } else {
            $flowName = $flowClassNames[$route->flow_id] ?? 'UnknownFlow';
            $className = $flowName.'Controller';
        }

        $fqcn = CodeHelper::controllerNamespace($route->type->value).'\\'.$className;
        $entry['controller_class'] = $className;
        $entry['controller_fqcn'] = $fqcn;
        $imports[] = $fqcn;

        return $entry;
    }

    /** Построить карту FQCN → алиас для тех контроллеров, чьи короткие имена коллизируют.
     * Алиас формируется как «предпоследний сегмент namespace + короткое имя».
     *
     * @param  array<int, string>  $fqcns
     * @return array<string, string>
     */
    private function resolveAliasMap(array $fqcns): array
    {
        $byShort = [];
        foreach ($fqcns as $fqcn) {
            $byShort[$this->shortName($fqcn)][] = $fqcn;
        }

        $aliases = [];
        foreach ($byShort as $short => $candidates) {
            if (count($candidates) <= 1) {
                continue;
            }
            foreach ($candidates as $fqcn) {
                $parts = explode('\\', $fqcn);
                $parent = $parts[count($parts) - 2] ?? '';
                $aliases[$fqcn] = $parent.$short;
            }
        }

        return $aliases;
    }

    /** Подставить алиасы в записи маршрутов, удалить служебное поле controller_fqcn.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @param  array<string, string>  $aliasMap
     * @return array<int, array<string, mixed>>
     */
    private function applyAliases(array $entries, array $aliasMap): array
    {
        return array_map(function ($entry) use ($aliasMap) {
            $fqcn = $entry['controller_fqcn'] ?? null;
            if ($fqcn !== null && isset($aliasMap[$fqcn])) {
                $entry['controller_class'] = $aliasMap[$fqcn];
            }
            unset($entry['controller_fqcn']);

            return $entry;
        }, $entries);
    }

    /** Сформировать строки use-импортов с алиасами для коллизирующих имён.
     *
     * @param  array<int, string>  $fqcns
     * @param  array<string, string>  $aliasMap
     * @return array<int, string>
     */
    private function formatImports(array $fqcns, array $aliasMap): array
    {
        return array_map(
            fn (string $fqcn) => isset($aliasMap[$fqcn])
                ? $fqcn.' as '.$aliasMap[$fqcn]
                : $fqcn,
            $fqcns,
        );
    }

    /** Извлечь короткое имя класса из FQCN.
     */
    private function shortName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }
}
