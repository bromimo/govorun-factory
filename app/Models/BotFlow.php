<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id', 'name', 'graph',
        'interrupt_commands', 'interrupt_on_event',
    ];

    protected function casts(): array
    {
        return [
            'graph' => 'array',
            'interrupt_commands' => 'array',
            'interrupt_on_event' => 'boolean',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(BotRoute::class, 'flow_id');
    }
}
