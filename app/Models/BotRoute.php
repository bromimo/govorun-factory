<?php

namespace App\Models;

use App\Enums\RouteType;
use App\Enums\HandlerType;
use App\Enums\EntityStatus;
use App\Observers\BotRouteObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Модель маршрута бота. */
#[ObservedBy(BotRouteObserver::class)]
class BotRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id', 'parent_id', 'type', 'match', 'description', 'aliases', 'controller_name',
        'handler_type', 'flow_id', 'handler_schema', 'middleware', 'sort_order', 'status',
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
            'status' => EntityStatus::class,
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

    /** Родительский маршрут (для вложенных phrase).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Дочерние маршруты.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** Flow-диалог, к которому привязан маршрут.
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class);
    }
}
