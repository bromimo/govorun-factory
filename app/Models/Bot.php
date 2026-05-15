<?php

namespace App\Models;

use App\Models\BotMedia;
use App\Observers\BotObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Медиафайлы бота.
     */
    public function media(): HasMany
    {
        return $this->hasMany(BotMedia::class);
    }
}
