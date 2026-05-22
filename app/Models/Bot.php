<?php

namespace App\Models;

use App\Observers\BotObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/** Модель бота — проект с маршрутами и flow-диалогами. */
#[ObservedBy(BotObserver::class)]
class Bot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'config',
        'messenger_config',
        'text_format_version',
        'created_by',
        'updated_by',
    ];

    /** Значения атрибутов по умолчанию. */
    protected $attributes = [
        'text_format_version' => 2,
    ];

    /** Приведение атрибутов к типам.
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'messenger_config' => 'array',
            'text_format_version' => 'integer',
        ];
    }

    /** Создатель бота.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Пользователь, последним изменивший бота или его дочерние сущности.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Маршруты бота, отсортированные по порядку.
     */
    public function routes(): HasMany
    {
        return $this->hasMany(BotRoute::class)->orderBy('sort_order');
    }

    /** Flow-диалоги бота.
     */
    public function flows(): HasMany
    {
        return $this->hasMany(BotFlow::class);
    }

    /** Медиафайлы бота. */
    public function media(): HasMany
    {
        return $this->hasMany(BotMedia::class);
    }

    /** Подключения бота к внешним API. */
    public function connections(): HasMany
    {
        return $this->hasMany(BotConnection::class);
    }

    /** Пересчитать и сохранить кешированную статистику медиа бота.
     * Загружает свежие данные из БД, поэтому безопасен для вызова из обсерверов.
     */
    public static function recomputeMediaStats(int $botId): void
    {
        $bot = static::with([
            'flows:id,bot_id,graph',
            'routes:id,bot_id,handler_schema',
        ])->find($botId);

        if ($bot === null) {
            return;
        }

        $ids = $bot->extractUsedMediaIds();
        $size = empty($ids) ? 0 : BotMedia::whereIn('id', $ids)->sum('size');

        DB::table('bots')->where('id', $botId)->update([
            'used_media_count' => count($ids),
            'used_media_size'  => $size,
        ]);
    }

    /** Извлечь уникальные media_id, реально используемые в флоу и маршрутах.
     * Требует загруженных relations: flows, routes.
     *
     * @return array<int>
     */
    public function extractUsedMediaIds(): array
    {
        $ids = [];

        foreach ($this->flows ?? [] as $flow) {
            foreach ($flow->graph['nodes'] ?? [] as $node) {
                $id = data_get($node, 'data.media.media_id');
                if ($id) {
                    $ids[] = (int) $id;
                }
            }
        }

        foreach ($this->routes ?? [] as $route) {
            foreach ($route->handler_schema['blocks'] ?? [] as $block) {
                $id = data_get($block, 'media.media_id');
                if ($id) {
                    $ids[] = (int) $id;
                }
            }
        }

        return array_unique($ids);
    }
}
