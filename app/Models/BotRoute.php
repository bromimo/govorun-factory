<?php

namespace App\Models;

use App\Enums\HandlerType;
use App\Enums\RouteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Модель маршрута бота. */
class BotRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id', 'type', 'match', 'aliases', 'handler_type',
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
            'aliases' => 'array',
            'middleware' => 'array',
        ];
    }

    /** Бот, которому принадлежит маршрут.
     *
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /** Flow-диалог, к которому привязан маршрут.
     *
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class);
    }
}
