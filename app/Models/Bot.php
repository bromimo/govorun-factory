<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'config',
        'messenger_config',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'messenger_config' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(BotRoute::class)->orderBy('sort_order');
    }

    public function flows(): HasMany
    {
        return $this->hasMany(BotFlow::class);
    }
}
