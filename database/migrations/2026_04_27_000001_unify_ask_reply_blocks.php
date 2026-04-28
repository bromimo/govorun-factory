<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Миграция данных: legacy-блоки ask_text/ask_keyboard/reply_text/reply_keyboard/reply_media → ask/reply. */
return new class extends Migration
{
    /** Применить миграцию.
     * @return void
     */
    public function up(): void
    {
        $this->migrateFlows();
        $this->migrateRoutes();
    }

    /** Откатить миграцию (no-op — данные не восстанавливаются).
     * @return void
     */
    public function down(): void
    {
        //
    }

    /** Преобразовать графы flow с legacy-нодами в новый формат.
     * @return void
     */
    private function migrateFlows(): void
    {
        DB::table('bot_flows')->orderBy('id')->lazy()->each(function (object $flow): void {
            $graph = json_decode((string) $flow->graph, true);
            if (! is_array($graph) || empty($graph['nodes'] ?? [])) {
                return;
            }

            $changed = false;
            foreach ($graph['nodes'] as &$node) {
                $migrated = $this->migrateNode($node);
                if ($migrated !== null) {
                    $node = $migrated;
                    $changed = true;
                }
            }
            unset($node);

            if ($changed) {
                DB::table('bot_flows')->where('id', $flow->id)->update([
                    'graph' => json_encode($graph, JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }

    /** Преобразовать handler_schema всех маршрутов с legacy-блоками в новый формат.
     * @return void
     */
    private function migrateRoutes(): void
    {
        DB::table('bot_routes')->orderBy('id')->lazy()->each(function (object $route): void {
            $schema = json_decode((string) $route->handler_schema, true);
            if (! is_array($schema) || empty($schema['blocks'] ?? [])) {
                return;
            }

            $changed = false;
            foreach ($schema['blocks'] as &$block) {
                $migrated = $this->migrateBlock($block);
                if ($migrated !== null) {
                    $block = $migrated;
                    $changed = true;
                }
            }
            unset($block);

            if ($changed) {
                DB::table('bot_routes')->where('id', $route->id)->update([
                    'handler_schema' => json_encode($schema, JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }

    /** Преобразовать одну ноду графа.
     * @param array<string, mixed> $node Узел.
     * @return ?array<string, mixed> Новый узел или null если изменений не требуется.
     */
    private function migrateNode(array $node): ?array
    {
        $type = $node['type'] ?? '';
        $data = $node['data'] ?? [];

        $newData = $this->mapData($type, $data);

        if ($newData === null) {
            return null;
        }

        $node['type'] = $this->mapType($type);
        $node['data'] = $newData;

        return $node;
    }

    /** Преобразовать один блок маршрута.
     * @param array<string, mixed> $block Блок.
     * @return ?array<string, mixed> Новый блок или null если изменений не требуется.
     */
    private function migrateBlock(array $block): ?array
    {
        $type = $block['type'] ?? '';
        $params = $block['params'] ?? [];

        $newParams = $this->mapData($type, $params);

        if ($newParams === null) {
            return null;
        }

        $block['type'] = $this->mapType($type);
        $block['params'] = $newParams;

        return $block;
    }

    /** Сопоставить старый тип с новым.
     * @param string $type Старое имя типа.
     * @return string Новое имя.
     */
    private function mapType(string $type): string
    {
        return match ($type) {
            'ask_text', 'ask_keyboard' => 'ask',
            'reply_text', 'reply_keyboard', 'reply_media' => 'reply',
            default => $type,
        };
    }

    /** Преобразовать data/params legacy-блока в новый формат.
     * @param string $type Старый тип.
     * @param array<string, mixed> $data Данные блока.
     * @return ?array<string, mixed> Новые данные или null если тип не legacy.
     */
    private function mapData(string $type, array $data): ?array
    {
        return match ($type) {
            'ask_text' => [
                'mode' => 'text',
                'stepName' => $data['stepName'] ?? '',
                'text' => $data['text'] ?? '',
                'media' => $this->imageToMedia($data['image'] ?? ''),
                'validation' => $data['validation'] ?? [],
                'keyboard' => null,
            ],
            'ask_keyboard' => [
                'mode' => 'callback',
                'stepName' => $data['stepName'] ?? '',
                'text' => $data['text'] ?? '',
                'media' => $this->imageToMedia($data['image'] ?? ''),
                'validation' => $data['validation'] ?? [],
                'keyboard' => [
                    'type' => 'inline',
                    'buttons' => $data['buttons'] ?? [],
                ],
            ],
            'reply_text' => [
                'text' => $data['text'] ?? '',
                'media' => null,
                'keyboard' => null,
            ],
            'reply_keyboard' => [
                'text' => $data['text'] ?? '',
                'media' => null,
                'keyboard' => [
                    'type' => 'inline',
                    'buttons' => $data['buttons'] ?? [],
                ],
            ],
            'reply_media' => [
                'text' => trim((string) ($data['caption'] ?? '')) !== '' ? $data['caption'] : null,
                'media' => [
                    'type' => $data['media_type'] ?? 'photo',
                    'url' => $data['url'] ?? '',
                ],
                'keyboard' => null,
            ],
            default => null,
        };
    }

    /** Преобразовать поле image (URL или пусто) в media-объект или null.
     * @param string $url URL картинки.
     * @return ?array<string, string>
     */
    private function imageToMedia(string $url): ?array
    {
        $url = trim($url);

        return $url === '' ? null : ['type' => 'photo', 'url' => $url];
    }
};