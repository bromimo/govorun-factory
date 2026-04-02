<?php

namespace App\Models;

use App\Enums\RouteType;
use App\Enums\HandlerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Модель маршрута бота. */
class BotRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id', 'type', 'match', 'handler_type',
        'flow_id', 'handler_schema', 'middleware', 'sort_order',
    ];

    /** Приведение атрибутов модели.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RouteType::class,
            'handler_type' => HandlerType::class,
            'handler_schema' => 'array',
            'middleware' => 'array',
        ];
    }

    /** Бот, которому принадлежит маршрут.
     *
     * @return BelongsTo
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /** Flow-диалог, к которому привязан маршрут.
     *
     * @return BelongsTo
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class);
    }
}
