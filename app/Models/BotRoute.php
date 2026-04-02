<?php

namespace App\Models;

use App\Enums\RouteType;
use App\Enums\HandlerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id', 'type', 'match', 'handler_type',
        'flow_id', 'handler_schema', 'middleware', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => RouteType::class,
            'handler_type' => HandlerType::class,
            'handler_schema' => 'array',
            'middleware' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class);
    }
}
