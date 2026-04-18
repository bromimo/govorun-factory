<?php

namespace App\Models;

use App\Observers\BotFlowObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Модель flow-диалога бота. */
#[ObservedBy(BotFlowObserver::class)]
class BotFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id', 'name', 'description', 'graph',
        'interrupt_commands', 'interrupt_on_event',
    ];

    /** Приведение атрибутов модели.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'graph' => 'array',
            'interrupt_commands' => 'array',
            'interrupt_on_event' => 'boolean',
        ];
    }

    /** Бот, которому принадлежит flow.
     *
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /** Маршруты, привязанные к этому flow.
     *
     */
    public function routes(): HasMany
    {
        return $this->hasMany(BotRoute::class, 'flow_id');
    }
}
