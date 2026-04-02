<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/** Модель бота — проект с маршрутами и flow-диалогами. */
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

    /** Приведение атрибутов к типам.
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'messenger_config' => 'array',
        ];
    }

    /** Создатель бота.
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Маршруты бота, отсортированные по порядку.
     * @return HasMany
     */
    public function routes(): HasMany
    {
        return $this->hasMany(BotRoute::class)->orderBy('sort_order');
    }

    /** Flow-диалоги бота.
     * @return HasMany
     */
    public function flows(): HasMany
    {
        return $this->hasMany(BotFlow::class);
    }
}
