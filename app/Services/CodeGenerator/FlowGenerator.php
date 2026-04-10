<?php

namespace App\Services\CodeGenerator;

use Illuminate\Support\Collection;

/** Генератор классов Flow из графа диалога. */
class FlowGenerator
{
    private ControllerGenerator $blockRenderer;

    public function __construct()
    {
        $this->blockRenderer = new ControllerGenerator;
    }

    /** Сгенерировать класс Flow.
     * @param  array<string, mixed>  $graph
     * @param  array<string>  $interruptCommands
     */
    public function generate(string $className, array $graph, array $interruptCommands, bool $interruptOnEvent): string
    {
        $nodes = collect($graph['nodes'] ?? []);
        $edges = collect($graph['edges'] ?? []);

        $stepsCode = $this->buildSteps($nodes, $edges);

        return "<?php\n\n".view('stubs.flow', [
            'className' => $className,
            'interruptCommands' => $interruptCommands,
            'interruptOnEvent' => $interruptOnEvent,
            'stepsCode' => $stepsCode,
        ])->render();
    }

    /** Построить код шагов из графа.
     * @param  Collection  $nodes
     * @param  Collection  $edges
     */
    private function buildSteps($nodes, $edges): string
    {
        $adjacency = [];
        $edgeLabels = [];
        foreach ($edges as $edge) {
            $adjacency[$edge['source']][] = $edge['target'];
            if (! empty($edge['label'])) {
                $edgeLabels[$edge['source'].'->'.$edge['target']] = $edge['label'];
            }
        }

        $targetIds = $edges->pluck('target')->unique();
        $startNodes = $nodes->pluck('id')->diff($targetIds);
        $startId = $startNodes->first();

        if (! $startId) {
            return '';
        }

        $visited = [];

        return $this->walkGraph($startId, $nodes, $adjacency, $edgeLabels, $visited);
    }

    /** Рекурсивный обход графа для генерации кода.
     * @param  Collection  $nodes
     * @param  array<string, array<string>>  $adjacency
     * @param  array<string, string>  $edgeLabels
     * @param  array<string>  $visited
     */
    private function walkGraph(string $nodeId, $nodes, array $adjacency, array $edgeLabels, array &$visited, int $indent = 2): string
    {
        if (in_array($nodeId, $visited)) {
            return '';
        }
        $visited[] = $nodeId;

        $node = $nodes->firstWhere('id', $nodeId);
        if (! $node) {
            return '';
        }

        $code = '';
        $type = $node['type'];
        $data = $node['data'] ?? [];
        $targets = $adjacency[$nodeId] ?? [];

        if (in_array($type, ['start', 'on_complete', 'on_cancel'])) {
            foreach ($targets as $targetId) {
                $code .= $this->walkGraph($targetId, $nodes, $adjacency, $edgeLabels, $visited, $indent);
            }

            return $code;
        }

        if ($type === 'condition') {
            $branches = [];
            $defaultBranch = null;

            foreach ($targets as $targetId) {
                $label = $edgeLabels[$nodeId.'->'.$targetId] ?? null;
                $branchCode = $this->walkGraph($targetId, $nodes, $adjacency, $edgeLabels, $visited, $indent + 2);

                if ($label) {
                    $branches[$label] = $branchCode;
                } else {
                    $defaultBranch = $branchCode;
                }
            }

            $code .= view('stubs.blocks.condition', [
                'params' => $data,
                'branches' => $branches,
                'defaultBranch' => $defaultBranch,
            ])->render()."\n";
        } else {
            $code .= $this->indentBlock($this->blockRenderer->renderBlock($type, $data), $indent);

            foreach ($targets as $targetId) {
                $code .= $this->walkGraph($targetId, $nodes, $adjacency, $edgeLabels, $visited, $indent);
            }
        }

        return $code;
    }

    /** Добавить отступ к блоку кода.
     */
    private function indentBlock(string $code, int $level): string
    {
        $padding = str_repeat('    ', $level);
        $lines = explode("\n", rtrim($code));

        return implode("\n", array_map(
            fn ($line) => $line === '' ? '' : $padding.$line,
            $lines,
        ))."\n";
    }
}
